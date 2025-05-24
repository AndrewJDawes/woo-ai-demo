<?php

namespace wpsolr\core\classes\engines\opensearch_php;

use wpsolr\core\classes\engines\elasticsearch_php\WPSOLR_SearchElasticsearchClient;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;

class WPSOLR_SearchOpenSearchClient extends WPSOLR_SearchElasticsearchClient {
	use WPSOLR_OpenSearchClient;

	const MORE_LIKE_THIS_FIELDS = [ 'title', 'text' ];

	/**
	 * Create the index
	 *
	 * @param array $index_parameters
	 */
	protected function admin_create_index( &$index_parameters ) {
		$settings = $this->get_and_decode_configuration_file();

		$settings['index']                                  = $this->get_index_label();
		$settings['body']['settings']['number_of_shards']   = $this->config['extra_parameters'][ self::ENGINE_OPENSEARCH ][ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS ];
		$settings['body']['settings']['number_of_replicas'] = $this->config['extra_parameters'][ self::ENGINE_OPENSEARCH ][ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS ];

		$this->search_engine_client->indices()->create( $settings );
	}

}
