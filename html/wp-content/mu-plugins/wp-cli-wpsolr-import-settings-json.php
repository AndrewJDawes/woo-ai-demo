<?php

/**
 * Plugin Name: WPSOLR Free Import Data
 */

use wpsolr\core\classes\extensions\import_export\WPSOLR_Option_Import_Export;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;


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

            foreach ($wpsolr_data_to_import['wpsolr_solr_indexes']['solr_indexes'] as $index_uuid => $config) {
                WP_CLI::line('Importing index configuration for UUID: ' . $index_uuid);
                $config = [
                    'index_engine'          => $config['index_engine'],
                    'index_uuid'            => $index_uuid,
                    'index_label'           => $config['index_label'],
                    'scheme'                => $config['index_protocol'],
                    'host'                  => $config['index_host'],
                    'port'                  => $config['index_port'],
                    'scheme1'               => $config['index_endpoint_1'] ? parse_url($config['index_endpoint_1'], PHP_URL_SCHEME) : '',
                    'host1'                 => $config['index_endpoint_1'] ? parse_url($config['index_endpoint_1'], PHP_URL_HOST) : '',
                    'port1'                 => $config['index_endpoint_1'] ? parse_url($config['index_endpoint_1'], PHP_URL_PORT) : '',
                    'path'                  => $config['index_path'],
                    'username'              => $config['index_key'] ?? '',
                    'password'              => $config['index_secret'] ?? '',
                    'timeout'               => WPSOLR_AbstractSearchClient::DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND,
                    'aws_access_key_id'     => (!empty($config['index_hosting_api_id']) && strpos($config['index_hosting_api_id'], 'aws') !== false) ? $config['index_key'] : '',
                    'aws_secret_access_key' => (!empty($config['index_hosting_api_id']) && strpos($config['index_hosting_api_id'], 'aws') !== false) ? $config['index_secret'] : '',
                    'aws_region'            => (!empty($config['index_hosting_api_id']) && strpos($config['index_hosting_api_id'], 'aws') !== false) ? $config['index_aws_region'] : '',
                    'extra_parameters'      => [
                        $config['index_engine']  => [
                            'shards' => $config['index_engine'] === 'engine_elasticsearch' ? $config['index_elasticsearch_shards'] : ($config['index_engine'] === 'engine_opensearch' ? $config['index_opensearch_shards'] : ($config['index_engine'] === 'engine_solr' ? $config['index_solr_cloud_shards'] : '')),
                            'replicas' => $config['index_engine'] === 'engine_elasticsearch' ? $config['index_elasticsearch_replicas'] : ($config['index_engine'] === 'engine_opensearch' ? $config['index_opensearch_replicas'] : ($config['index_engine'] === 'engine_solr' ? $config['index_solr_cloud_replication_factor'] : '')),
                            'maxShardsPerNode' => $config['index_engine'] === 'engine_solr' ? $config['index_solr_cloud_max_shards_node'] : '',
                        ],
                        'index_hosting_api_id'                           => $config['index_hosting_api_id'] ?? '',
                        'index_email'                                    => $config['index_email'] ?? '',
                        'index_api_key'                                  => $config['index_api_key'] ?? '',
                        'index_api_key_1'                                => $config['index_api_key_1'] ?? '',
                        'index_region_id'                                => $config['index_aws_region'] ?? '',
                        'index_language_code'                            => $config['index_language_code'] ?? '',
                        'index_analyser_id'                              => $config['index_analyser_id'] ?? '',
                        'index_weaviate_openai_config_type'              => $config['index_weaviate_openai_config_type'] ?? '',
                        'index_weaviate_openai_config_model'             => $config['index_weaviate_openai_config_model'] ?? '',
                        'index_weaviate_openai_config_model_version'     => $config['index_weaviate_openai_config_model_version'] ?? '',
                        'index_weaviate_openai_config_type_qna'          => $config['index_weaviate_openai_config_type_qna'] ?? '',
                        'index_weaviate_openai_config_model_qna'         => $config['index_weaviate_openai_config_model_qna'] ?? '',
                        'index_weaviate_openai_config_model_version_qna' => $config['index_weaviate_openai_config_model_version_qna'] ?? '',
                        'index_weaviate_huggingface_config_model'        => $config['index_weaviate_huggingface_config_model'] ?? '',
                        'index_weaviate_huggingface_config_model_query'  => $config['index_weaviate_huggingface_config_model_query'] ?? '',
                        'index_key_json'                                 => $config['index_key_json'] ?? '',
                        'index_key_json_1'                               => $config['index_key_json_1'] ?? '',
                        'index_catalog_branch'                           => $config['index_catalog_branch'] ?? '',
                        'index_weaviate_cohere_config_model'             => $config['index_weaviate_cohere_config_model'] ?? '',
                        'index_weaviate_palm_config_model'               => $config['index_weaviate_palm_config_model'] ?? '',
                        'dataset_group_arn'                              => $config['dataset_group_arn'] ?? '',
                        'dataset_items_arn'                              => $config['dataset_items_arn'] ?? '',
                        'dataset_item_interaction_events_arn'            => $config['dataset_item_interaction_events_arn'] ?? '',
                        'dataset_users_arn'                              => $config['dataset_users_arn'] ?? '',
                        'dataset_actions_arn'                            => $config['dataset_actions_arn'] ?? '',
                        'dataset_user_action_interaction_events_arn'     => $config['dataset_user_action_interaction_events_arn'] ?? '',
                        'vespa_service_content_id'                       => $config['vespa_service_content_id'] ?? '',
                        'vespa_service_container_id'                     => $config['vespa_service_container_id'] ?? '',
                        'index_client_adapter'                           => $config['index_client_adapter'] ?? '',
                        'text_vectorizer_id'                             => $config['text_vectorizer_id'] ?? '',
                        'index_cert_1'                                   => $config['index_cert_1'] ?? '',
                    ]
                ];
                $client = WPSOLR_AbstractSearchClient::create_from_config(
                    $config
                );
                $client->admin_ping($config);
            }

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
