<?php

/**
 * Plugin Name: WPSOLR Free Import Data
 */

use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;


if (defined('WP_CLI') && WP_CLI) {

    // Maybe require and invoke any other filters you need here as theme/plugins may not be loaded at this point.

    /**
     * Class WP_CLI_WPSOLR_Index_Reindex
     */
    class WP_CLI_WPSOLR_Index_Reindex
    {


        /**
         * Rebuild an index
         *
         * ## OPTIONS
         *
         * <index>
         * : The index to rebuild
         *
         * [--post-types=<post-types>]
         * : Comma-separated list of post types to index.
         *
         * [--batch-size=<batch-size>]
         * : The batch-size for indexing.
         *
         * ## EXAMPLES
         *
         *     wp wpsolr index-reindex 0EE76D5EDD73E0E0F72D9E353901FB18
         *
         * @when after_wp_load
         */
        public function __invoke($args, $assoc_args)
        {
            if (!defined('WPSOLR_PLUGIN_VERSION')) {
                WP_CLI::error('WPSOLR plugin is not active.');
                return;
            }
            try {
                $index = $args[0];
                $post_types = isset($assoc_args['post-types']) ? explode(',', $assoc_args['post-types']) : [];
                $batch_size = isset($assoc_args['batch-size']) ? (int) $assoc_args['batch-size'] : 1000;
                if (empty($index)) {
                    WP_CLI::error('Index name is required.');
                    return;
                }
                if ($post_types && !is_array($post_types)) {
                    WP_CLI::error('Post types must be a comma-separated list.');
                    return;
                }
                if ($batch_size <= 0) {
                    WP_CLI::error('Batch size must be a positive integer.');
                    return;
                }

                $solr = WPSOLR_IndexSolariumClient::create($index);

                $process_id = (string) (getmypid() ?? 'unknown');

                $solr->reset_documents($process_id,  WPSOLR_Model_Builder::get_model_type_objects($post_types), true);

                $res_final = $solr->index_data(
                    false,
                    $process_id,
                    WPSOLR_Model_Builder::get_model_type_objects($post_types),
                    $batch_size,
                    null,
                    false,
                    false,
                    false
                );

                WP_CLI::success(print_r($res_final, true));
            } finally {
                WPSOLR_AbstractIndexClient::unlock_process($process_id);
                WPSOLR_AbstractIndexClient::unlock_process(WPSOLR_AbstractIndexClient::STOP_INDEXING_ID);
            }
        }
    }

    /**
     * Register the WP CLI command
     */
    $instance = new WP_CLI_WPSOLR_Index_Reindex();
    WP_CLI::add_command('wpsolr index-reindex', $instance, [
        'shortdesc' => 'Rebuild a WPSOLR index',
        'synopsis'  => [
            [
                'type'      => 'positional',
                'name'      => 'index',
                'optional'  => false,
                'repeating' => false,
            ],
            [
                'type'      => 'assoc',
                'name'      => 'post-types',
                'optional'  => true,
                'repeating' => false,
                'description' => 'Comma-separated list of post types to index.',
                'default'   => '',
            ],
            [
                'type'      => 'assoc',
                'name'      => 'batch-size',
                'optional'  => true,
                'repeating' => false,
                'description' => 'The batch size for indexing.',
                'default'   => 1000,
            ]
        ]
    ]);
}
