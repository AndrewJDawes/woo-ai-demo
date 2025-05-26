<?php

/**
 * Plugin Name: WPSOLR Free Import Data
 */

use wpsolr\core\classes\extensions\import_export\WPSOLR_Option_Import_Export;


if (defined('WP_CLI') && WP_CLI) {

    // Maybe require and invoke any other filters you need here as theme/plugins may not be loaded at this point.

    /**
     * Class WP_CLI_WPSOLR_Import_Settings_JSON
     */
    class WP_CLI_WPSOLR_Import_Settings_JSON
    {


        /**
         * Import a JSON file
         *
         * ## OPTIONS
         *
         * <file>
         * : The path to the JSON file
         *
         * ## EXAMPLES
         *
         *     wp wpsolr import-settings-json /path/to/file.json
         *
         * @when after_wp_load
         */
        public function __invoke($args, $assoc_args)
        {
            if (!defined('WPSOLR_PLUGIN_VERSION')) {
                WP_CLI::error('WPSOLR plugin is not active.');
                return;
            }

            $filename = realpath($args[0]);

            if (! file_exists($filename)) {
                WP_CLI::error('File not found : ' . $filename);

                return;
            }

            $wpsolr_data_to_import_string = file_get_contents($filename);


            $wpsolr_data_to_import = json_decode($wpsolr_data_to_import_string, true);

            WPSOLR_Option_Import_Export::import_data($wpsolr_data_to_import);

            WP_CLI::success('Imported data from ' . $filename);
        }
    }

    /**
     * Register the WP CLI command
     */
    $instance = new WP_CLI_WPSOLR_Import_Settings_JSON();
    WP_CLI::add_command('wpsolr import-settings-json', $instance, [
        'shortdesc' => 'Import settings using JSON import/export format',
        'synopsis'  => [
            [
                'type'      => 'positional',
                'name'      => 'file',
                'optional'  => false,
                'repeating' => false,
            ],
        ]
    ]);
}
