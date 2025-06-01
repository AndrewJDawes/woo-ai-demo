<?php

/**
 * Plugin Name: WPSOLR Free Import Data
 */

use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;

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
         * [--indexes=<indexes>]
         * : Comma-separated list of indexes to reindex. If not provided, all indexes will be reindexed.
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
                $indexes = isset($assoc_args['indexes']) ? explode(',', $assoc_args['indexes']) : array_keys((new WPSOLR_Option_Indexes())->get_indexes());
                $post_types = isset($assoc_args['post-types']) ? explode(',', $assoc_args['post-types']) : [];
                $batch_size = isset($assoc_args['batch-size']) ? (int) $assoc_args['batch-size'] : 1000;
                if (count($indexes) === 0) {
                    WP_CLI::error('No indexes found.');
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

                foreach ($indexes as $index_uuid) {
                    WP_CLI::line('Rebuilding index with UUID: ' . $index_uuid);
                    $solr = WPSOLR_IndexSolariumClient::create($index_uuid);

                    $model_types = WPSOLR_Model_Builder::get_model_type_objects($post_types);

                    $model_types_string = array_map(function ($model_type) {
                        return $model_type->get_type();
                    }, $model_types);

                    WP_CLI::line('Indexing the following model types: ' . implode(', ', $model_types_string));

                    $process_id = (string) (getmypid() ?? 'unknown');

                    $solr->reset_documents($process_id, $model_types, true);

                    $res_final = $solr->index_data(
                        false,
                        $process_id,
                        $model_types,
                        $batch_size,
                        null,
                        false,
                        false,
                        false
                    );
                    WP_CLI::line('Indexing completed for index UUID: ' . $index_uuid);
                }


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
                'optional'  => true,
                'repeating' => false,
                'description' => 'The index to reindex. If not provided, all indexes will be reindexed.',
                'default'   => null,
            ],
            [
                'type'      => 'assoc',
                'name'      => 'post-types',
                'optional'  => true,
                'repeating' => false,
                'description' => 'Comma-separated list of post types to index.',
                'default'   => null,
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
