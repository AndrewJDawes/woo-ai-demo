<?php

/**
 * Plugin Name: WP CLI Command (import of CSV)
 * Description: Add a WP CLI Command which allows import of CSV files from the command line.
 * Version: 1.0
 * Author: WP Squad
 * Author URI: https://thewpsquad.com
 */

if (defined('WP_CLI') && WP_CLI) {
    // Require WooCommerce if it's not already loaded
    if (! defined('WC_ABSPATH')) {
        define('WC_ABSPATH', WP_PLUGIN_DIR . '/woocommerce/');
    }

    // Require the WooCommerce Product CSV Importer class
    require_once WP_PLUGIN_DIR . '/woocommerce/includes/import/class-wc-product-csv-importer.php';

    // Maybe require and invoke any other filters you need here as theme/plugins may not be loaded at this point.

    /**
     * Class WP_CLI_WC_Import_CSV
     */
    class WP_CLI_WC_Import_CSV
    {

        /**
         * Mappings
         *
         * @var array
         */
        protected $_mappings = [
            'from' => [],
            'to'   => []
        ];

        /**
         * Import a CSV file
         *
         * ## OPTIONS
         *
         * <file>
         * : The path to the CSV file
         *
         * [--mappings=<mappings>]
         * : The path to the CSV file containing mappings
         *
         * [--delimiter=<delimiter>]
         * : The delimiter for the CSV file
         *
         * [--update]
         * : Update existing products matched on ID or SKU, omit this flag to insert new products.  Those which exist will be skipped.
         *
         * ## EXAMPLES
         *
         *     wp wc import-csv /path/to/file.csv --mappings=/path/to/mappings.csv
         *
         * @when after_wp_load
         */
        public function __invoke($args, $assoc_args)
        {
            $filename = realpath($args[0]);

            if (! file_exists($filename)) {
                WP_CLI::error('File not found : ' . $filename);

                return;
            }

            if (! current_user_can('manage_product_terms')) {
                WP_CLI::error('Current user cannot manage categories, ensure you set the --user parameter');

                return;
            }

            $params = [
                'mapping'          => $this->_readMappings(realpath($assoc_args['mappings'])),
                'update_existing'  => array_key_exists('update', $assoc_args) ? true : false,
                'prevent_timeouts' => false,
                'parse'            => true,
                'delimiter'        => $assoc_args['delimiter'],
            ];

            $importer = new WC_Product_CSV_Importer($filename, $params);
            $result   = $importer->import();
            WP_CLI::success(print_r($result, true));
        }

        /**
         * Read mappings from a CSV file
         *
         * @param string $filename Path to the CSV file
         *
         * @return array
         */
        protected function _readMappings($filename)
        {
            $mappings = [];

            $row = 1;

            if (($fh = fopen($filename, 'r')) !== false) {;
                while (($data = fgetcsv($fh)) !== false) {

                    if ($row > 1) {
                        $mappings['from'][] = $data[0];
                        $mappings['to'][]   = $data[1];
                    }
                    $row++;
                }
            }

            fclose($fh);

            return $mappings;
        }
    }

    /**
     * Register the WP CLI command
     */
    $instance = new WP_CLI_WC_Import_CSV();
    WP_CLI::add_command('wc import-csv', $instance, [
        'shortdesc' => 'Import woocommerce products using the standard CSV import',
        'synopsis'  => [
            [
                'type'      => 'positional',
                'name'      => 'file',
                'optional'  => false,
                'repeating' => false,
            ],
            [
                'type'        => 'assoc',
                'name'        => 'mappings',
                'description' => 'Mappings csv file, with "from" and "to" column headers.  The "to" column matches to "Maps to product property" on the import schema.  More details here at https://github.com/woocommerce/woocommerce/wiki/Product-CSV-Import-Schema',
                'optional'    => false,
            ],
            [
                'type'        => 'assoc',
                'name'        => 'delimiter',
                'description' => 'Delimeter for csv file (defaults to comma)',
                'default'     => ',',
                'optional'    => true
            ],
            [
                'type'        => 'flag',
                'name'        => 'update',
                'description' => 'Update existing products matched on ID or SKU, omit this flag to insert new products.  Those which exist will be skipped.',
                'optional'    => true

            ]
        ]
    ]);
}
