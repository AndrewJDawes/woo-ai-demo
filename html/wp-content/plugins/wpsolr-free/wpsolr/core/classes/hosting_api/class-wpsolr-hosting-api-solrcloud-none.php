<?php

namespace wpsolr\core\classes\hosting_api;

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;

class WPSOLR_Hosting_Api_Solrcloud_None extends WPSOLR_Hosting_Api_Abstract {

	const HOSTING_API_ID = 'none_solrcloud';

	/**
	 * @inheritdoc
	 */
	public function get_is_no_hosting() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return sprintf( self::NONE_LABEL, 'SolrCloud' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_search_engine() {
		return WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		$result = [
			static::FIELD_NAME_FIELDS_INDEX_CLIENT_ADAPTER_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_PROTOCOL_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_HOST_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_PORT_SOLR_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_PATH_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_MAX_SHARDS_NODE_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_REPLICATION_FACTOR_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_SHARDS_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_KEY_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_SECRET_DEFAULT,
			static::FIELD_NAME_FIELDS_CERT_1_DEFAULT,
		];

		return $result;
	}
}