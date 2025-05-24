<?php

namespace wpsolr\core\classes\extensions\indexes;

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class WPSOLR_Option_Indexes
 * @package wpsolr\core\classes\extensions\indexes
 */
class WPSOLR_Option_Indexes extends WPSOLR_Extension {

	// Solr index properties
	const INDEX_TYPE = 'index_type';
	const MANAGED_SOLR_SERVICE_ID = 'managed_solr_service_id';


	private $_options;

	// Unmanaged Solr index
	const STORED_INDEX_TYPE_UNMANAGED = 'index_type_unmanaged';
	// Temporary Managed Solr index
	const STORED_INDEX_TYPE_MANAGED_TEMPORARY = 'index_type_managed_temporary';
	// Managed Solr index
	const STORED_INDEX_TYPE_MANAGED = 'index_type_managed';

	/**
	 * Constructor
	 *
	 * Subscribe to actions
	 */
	function __construct( $has_indexing_only = false, $has_search_only = false ) {
		$this->_options = self::get_option_data( self::OPTION_INDEXES, [] );
		if ( empty( $this->_options ) ) {
			// Important, if an empty string instead of an empty array
			$this->_options = [];
		}

		// Remove indices from APIs not available (for instance after a downgrade of WPSOLR PRO to WPSOLR Free)
		$solr_indexes                   = $this->_options['solr_indexes'] ?? [];
		$this->_options['solr_indexes'] = [];
		foreach ( $solr_indexes as $index_uuid => $index_def ) {
			try {
				$hosting_api = WPSOLR_Hosting_Api_Abstract::get_hosting_api_by_id( $index_def['index_hosting_api_id'] ?? '', $index_def['index_engine'] ?? '' );
				if (
					true
				) {
					$this->_options['solr_indexes'][ $index_uuid ] = $index_def;
				}
			} catch ( \Exception $e ) {
				// Nothing
			}
		}

		if ( ! is_array( $this->_options ) ) {
			$this->_options = [];
		}
	}

	/**
	 * Migrate the old index data to the new index data.
	 * Then delete the old index data.
	 */
	function migrate_data_from_v4_9() {

		// Load the old options data
		$old_options_name = 'wdm_solr_conf_data';
		$old_options      = WPSOLR_Service_Container::getOption()->get_option( true, $old_options_name, false );

		/* Clean data for migration tests */
		/*
		$old_options['migrated'] = false;
		update_option( $old_options_name, $old_options );
		delete_option( self::get_option_name( self::OPTION_INDEXES ) );
		*/

		if ( $old_options === false ) {
			// Nothing to migrate
			return;
		}

		$new_options = $this->_options;
		if ( $new_options !== false ) {
			// Migration already done
			return;
		}

		// Move the 2 old style (version <= 4.8) indexes in the new structure
		foreach (
			[
				''      => [
					'indice'    => self::generate_uuid(),
					'name'      => 'Solr index local',
					'host_type' => 'self_hosted',
					'post_fix'  => '_in_self_index',
				],
				'_goto' => [
					'indice'    => self::generate_uuid(),
					'name'      => 'Solr index cloud',
					'host_type' => 'other_hosted',
					'post_fix'  => '_in_cloud_index',
				],
			] as $old_index_postfix => $old_index
		) {
			if ( ! empty( $old_options[ 'solr_host' . $old_index_postfix ] ) ) {

				// Copy the old index structure in the a temporary index structure
				$index_array                   = [];
				$index_array['index_name']     = $old_index['name'];
				$index_array['index_protocol'] = isset( $old_options[ 'solr_protocol' . $old_index_postfix ] ) ? $old_options[ 'solr_protocol' . $old_index_postfix ] : 'http';
				$index_array['index_host']     = isset( $old_options[ 'solr_host' . $old_index_postfix ] ) ? $old_options[ 'solr_host' . $old_index_postfix ] : 'localhost';
				$index_array['index_port']     = isset( $old_options[ 'solr_port' . $old_index_postfix ] ) ? $old_options[ 'solr_port' . $old_index_postfix ] : '8983';
				$index_array['index_path']     = isset( $old_options[ 'solr_path' . $old_index_postfix ] ) ? $old_options[ 'solr_path' . $old_index_postfix ] : '/sol/index_name';
				$index_array['index_key']      = isset( $old_options[ 'solr_key' . $old_index_postfix ] ) ? $old_options[ 'solr_key' . $old_index_postfix ] : '';
				$index_array['index_secret']   = isset( $old_options[ 'solr_secret' . $old_index_postfix ] ) ? $old_options[ 'solr_secret' . $old_index_postfix ] : '';

				// Copy the new index structure
				$new_options['solr_indexes'][ $old_index['indice'] ] = $index_array;

				// Set this index as the default index if it was the default
				if ( ( isset( $old_options['host_type'] ) ? $old_options['host_type'] : '' ) === $old_index['host_type'] ) {

					// Default search Solr index
					$results_options                                  = WPSOLR_Service_Container::getOption()->get_option_search();
					$results_options['default_solr_index_for_search'] = $old_index['indice'];
					update_option( WPSOLR_Option::OPTION_SEARCH, $results_options );

					// Copy the last post date to this index, to prevent re-indexing all its data
					$option_last_post_indexed = WPSOLR_Service_Container::getOption()->get_option( true, 'solr_last_post_date_indexed' . $old_index['post_fix'], null );
					if ( isset( $option_last_post_indexed ) ) {

						update_option( 'solr_last_post_date_indexed', array( $old_index['indice'] => $option_last_post_indexed ) );
					}

				}

			}
		}

		// Save the new option
		self::set_option_data( self::OPTION_INDEXES, $new_options );

		// Do not delete the old options. If the user wants to rollback the version, he can.
		//delete_option( $old_options_name );

	}

	/**
	 * Return all configured Solr indexes
	 */
	function get_indexes() {
		$result = $this->_options;
		$result = isset( $result['solr_indexes'] ) ? $result['solr_indexes'] : [];

		return $result;
	}

	/**
	 * Does a Solr index exist ?
	 *
	 * @param string $solr_index_indice Indice in Solr indexes array
	 *
	 * @return bool
	 */
	public
	function has_index(
		$solr_index_indice
	) {

		$solr_indexes = $this->get_indexes();

		return isset( $solr_indexes[ $solr_index_indice ] );
	}

	/**
	 * Get a Solr index
	 *
	 * @param string $solr_index_indice Indice in Solr indexes array
	 *
	 * @return array
	 */
	public function get_index( $solr_index_indice ) {

		$solr_indexes = $this->get_indexes();

		return isset( $solr_indexes[ $solr_index_indice ] ) ? $solr_indexes[ $solr_index_indice ] : [];
	}


	/**
	 * Get current index
	 *
	 * @return bool
	 */
	public function get_current_index() {

		$solr_indexes = $this->get_indexes();

		return $solr_indexes[ WPSOLR_Option_View::get_current_index_uuid() ] ?? null;
	}


	/**
	 * @param $solr_index
	 * @param $property_name
	 * @param string $default_property_value
	 *
	 * @return string
	 */
	public function get_index_property( $solr_index, $property_name, $default_property_value = '' ) {

		return isset( $solr_index[ $property_name ] ) ? $solr_index[ $property_name ] : $default_property_value;
	}

	/**
	 * @param $solr_index
	 *
	 * @return string
	 */
	public function get_index_name( $solr_index ) {

		return $this->get_index_property( $solr_index, 'index_name', null );
	}

	/**
	 * @param $solr_index
	 *
	 * @return string
	 */
	public function get_index_managed_solr_service_id( $solr_index ) {

		return $this->get_index_property( $solr_index, self::MANAGED_SOLR_SERVICE_ID, '' );
	}

	/**
	 * @param $solr_index
	 *
	 * @return string
	 */
	public function get_index_search_engine( $solr_index ) {

		return $this->get_index_property( $solr_index, WPSOLR_AbstractEngineClient::ENGINE, WPSOLR_AbstractEngineClient::ENGINE_SOLR );
	}

	/**
	 * @param $solr_index
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_index_search_engine_name( $solr_index ) {
		return $this->get_search_engine_name( $this->get_index_search_engine( $solr_index ) );
	}

	/**
	 * @param string $search_engine
	 *
	 * @return string
	 */
	public function get_search_engine_name( $search_engine ) {

		$result = WPSOLR_AbstractEngineClient::get_engines_definitions()[ $search_engine ]['name'] ?? WPSOLR_AbstractEngineClient::ENGINE_SOLR_NAME;

		$features = [];
		if ( WPSOLR_AbstractEngineClient::get_engines_definitions()[ $search_engine ]['has_search'] ?? true ) {
			$features[] = 'Keyword search';
		}
		if ( WPSOLR_AbstractEngineClient::get_engines_definitions()[ $search_engine ]['has_vector_search'] ?? false ) {
			$features[] = 'AI search';
		}
		if ( WPSOLR_AbstractEngineClient::get_engines_definitions()[ $search_engine ]['has_hybrid_search'] ?? false ) {
			$features[] = 'Hybrid search';
		}

		return empty( $features ) ? $result : sprintf( '%s (%s)', $result, implode( ', ', $features ) );
	}

	/**
	 * @param $solr_index
	 *
	 * @return string
	 */
	public function get_index_type( $solr_index ) {

		return $this->get_index_property( $solr_index, self::INDEX_TYPE, '' );
	}

	/**
	 * @param $solr_index
	 *
	 * @return bool
	 */
	public function is_index_type_temporary( $solr_index ) {

		$index_managed_solr_service_id = $this->get_index_managed_solr_service_id( $solr_index );

		return ( ! empty( $index_managed_solr_service_id ) && ( self::STORED_INDEX_TYPE_MANAGED_TEMPORARY === $this->get_index_type( $solr_index ) ) );
	}

	/**
	 * @param $solr_index
	 *
	 * @return bool
	 */
	public function is_index_type_managed( $solr_index ) {

		$index_managed_solr_service_id = $this->get_index_managed_solr_service_id( $solr_index );

		return ( ! empty( $index_managed_solr_service_id ) && ( self::STORED_INDEX_TYPE_MANAGED === $this->get_index_type( $solr_index ) ) );
	}

	/**
	 * @param $solr_index_indice
	 * @param $property_name
	 * @param $property_value
	 */
	public function update_index_property( $solr_index_indice, $property_name, $property_value ) {

		$solr_indexes = $this->get_indexes();

		$solr_indexes[ $solr_index_indice ][ $property_name ] = $property_value;

		$this->_options['solr_indexes'] = $solr_indexes;

		// Save the options containing the new index
		$this->set_option_data( self::OPTION_INDEXES, $this->_options );
	}

	/**
	 * Is there at least one solr index of type temporary ?
	 *
	 * @return bool
	 */
	public function has_index_type_temporary() {

		$solr_indexes = $this->get_indexes();

		foreach ( $solr_indexes as $solr_index ) {

			if ( $this->is_index_type_temporary( $solr_index ) ) {

				// Found one.
				return true;
			}

		}

		// Found none.
		return false;
	}

	/**
	 * @return int
	 */
	public function get_nb_indexes() {

		$solr_indexes = $this->get_indexes();

		return isset( $solr_indexes ) ? count( $solr_indexes ) : 0;
	}

	/**
	 * @param $managed_solr_service_id
	 * @param $index_type
	 * @param $index_uuid
	 * @param $index_name
	 * @param $index_protocol
	 * @param $index_host
	 * @param $index_port
	 * @param $index_path
	 * @param $index_key
	 * @param $index_secret
	 *
	 * @return string Index Uuid
	 */
	public function create_managed_index( $index_engine, $managed_solr_service_id, $index_type, $index_uuid, $index_name, $index_protocol, $index_host, $index_port, $index_path, $index_key, $index_secret ) {

		$solr_indexes = $this->get_indexes();

		// Indice for the solr index
		$solr_index_indice = isset( $index_uuid ) ? $index_uuid : $this->generate_uuid();

		// Fill the solr index
		$solr_indexes[ $solr_index_indice ] = [];

		$solr_indexes[ $solr_index_indice ]['index_engine']                  = $index_engine;
		$solr_indexes[ $solr_index_indice ][ self::MANAGED_SOLR_SERVICE_ID ] = $managed_solr_service_id;
		$solr_indexes[ $solr_index_indice ][ self::INDEX_TYPE ]              = $index_type;
		$solr_indexes[ $solr_index_indice ]['index_name']                    = $index_name;
		$solr_indexes[ $solr_index_indice ]['index_protocol']                = $index_protocol;
		$solr_indexes[ $solr_index_indice ]['index_host']                    = $index_host;
		$solr_indexes[ $solr_index_indice ]['index_port']                    = '443'; // $index_port;
		switch ( $index_engine ) {
			case WPSOLR_AbstractSearchClient::ENGINE_ELASTICSEARCH:
				$solr_indexes[ $solr_index_indice ]['index_label'] = $index_uuid;
				break;

			default:
				$solr_indexes[ $solr_index_indice ]['index_path'] = '/' . $index_path . '/' . $index_uuid;
				break;
		}
		$solr_indexes[ $solr_index_indice ]['index_key']    = $index_key;
		$solr_indexes[ $solr_index_indice ]['index_secret'] = $index_secret;

		$this->_options['solr_indexes'] = $solr_indexes;

		// Save the options containing the new index
		$this->set_option_data( self::OPTION_INDEXES, $this->_options );

		// Update the default search Solr index with the newly created.
		$this->update_default_search_solr_index_indice( $solr_index_indice );

		return $solr_index_indice;
	}

	/**
	 * Update the default solr index indice used by search page.
	 *
	 * @param $solr_index_indice
	 */
	public function update_default_search_solr_index_indice( $solr_index_indice ) {

		// Load results options
		$results_options = WPSOLR_Service_Container::getOption()->get_option_search();

		// Retrieve default search solr index
		$default_search_solr_index = $this->get_default_search_solr_index();

		// If not already set, or set with a non existing solr index (probably removed), update
		if ( ! isset( $default_search_solr_index ) ) {

			// Change the default search Solr index indice
			$results_options['default_solr_index_for_search'] = $solr_index_indice;


			// Save results options
			update_option( WPSOLR_Option::OPTION_SEARCH, $results_options );
		}

	}

	/**
	 * Get the default search Solr index. Must exist in the solr indexes list (not removed for instance).
	 */
	public function get_default_search_solr_index() {

		// Load results options
		$results_options = WPSOLR_Service_Container::getOption()->get_option_search();

		if ( isset( $results_options['default_solr_index_for_search'] ) ) {

			return $this->get_index( $results_options['default_solr_index_for_search'] );
		}

		return null;
	}


	/**
	 * Generate a long random id
	 *
	 * @return string
	 */
	static public function generate_uuid() {

		return strtoupper( md5( uniqid( wp_rand(), true ) ) );
	}

	/**
	 * Generate a long random id
	 *
	 * @return string
	 */
	static public function generate_random_letters( $length = 16 ) {
		return substr( str_shuffle( str_repeat( $x = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil( $length / strlen( $x ) ) ) ), 1, $length );
	}


	/**
	 * @param null $solr_index_indice
	 * @param $language_code
	 * @param $timeout
	 *
	 * @return array Solarium configuration
	 * @throws \Exception
	 */
	public function build_config( &$solr_index_indice, $language_code, $timeout ) {

		if ( ! isset( $solr_index_indice ) ) {

			// Give a chance to set the solr index indice
			$solr_index_indice = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_GET_DEFAULT_SOLR_INDEX_INDICE, null, $language_code );

			if ( ! isset( $solr_index_indice ) ) {
				// Retrieve the default indexing Solr index

				$solr_options = WPSOLR_Service_Container::getOption()->get_option_search();
				if ( false === $this->_options ) {
					throw new \Exception( 'Please complete the setup of your search options. We could not find any.' );
				}

				if ( ! isset( $solr_options['default_solr_index_for_search'] ) ) {
					throw new \Exception( 'Please complete the setup of your index options. There is no index configured for searching.' );
				}
				$solr_index_indice = $solr_options['default_solr_index_for_search'];

			}
		}

		$solr_index = $this->get_index( $solr_index_indice );
		if ( ! isset( $solr_index ) ) {

			throw new \Exception( "The search index is missing.
			Configure one in the <a href='?page=solr_settings&tab=solr_indexes'>Solr indexes</a>, and select it in the <a href='?page=solr_settings&tab=solr_option'>default search Solr index list</a>." );
		}

		// Copy the index parameters in the config

		$label      = isset( $solr_index['index_label'] ) ? $solr_index['index_label'] : '';
		$index_path = isset( $solr_index['index_path'] ) ? $solr_index['index_path'] : '';

		$config = [
			'index_uuid'            => $solr_index_indice,
			'index_engine'          => isset( $solr_index['index_engine'] ) ? $solr_index['index_engine'] : WPSOLR_AbstractEngineClient::ENGINE_SOLR,
			'index_label'           => $label,
			'scheme'                => $solr_index['index_protocol'] ?? '',
			//$hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_SCHEME, $index_region_id, $solr_index['index_protocol'] ),
			'index_endpoint'        => $solr_index['index_endpoint'] ?? '',
			'index_endpoint_1'      => $solr_index['index_endpoint_1'] ?? '',
			'host'                  => $solr_index['index_host'] ?? '',
			//$hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_HOST_BY_REGION_ID, $index_region_id, $solr_index['index_host'] ),
			'username'              => $solr_index['index_key'] ?? '',
			'password'              => $solr_index['index_secret'] ?? '',
			'port'                  => $solr_index['index_port'] ?? '',
			//$hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_PORT, 'donotcare', $solr_index['index_port'] ),
			'path'                  => $index_path,
			//$hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_PATH, $label, $index_path ),
			'timeout'               => $timeout,
			'aws_access_key_id'     => $solr_index['index_key'] ?? '',
			'aws_secret_access_key' => $solr_index['index_secret'] ?? '',
			'aws_region'            => isset( $solr_index['index_aws_region'] ) ? $solr_index['index_aws_region'] : '',
			'extra_parameters'      => [
				'index_email'                                    => $solr_index['index_email'] ?? '',
				'index_api_key'                                  => $solr_index['index_api_key'] ?? '',
				'index_api_key_1'                                => $solr_index['index_api_key_1'] ?? '',
				'index_region_id'                                => $solr_index['index_region_id'] ?? '',
				WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID        => $solr_index[ WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ] ?? '',
				'index_hosting_api_id'                           => isset( $solr_index['index_hosting_api_id'] ) ? $solr_index['index_hosting_api_id'] : '',
				'index_weaviate_openai_config_model'             => $solr_index['index_weaviate_openai_config_model'] ?? '',
				'index_weaviate_openai_config_model_version'     => $solr_index['index_weaviate_openai_config_model_version'] ?? '',
				'index_weaviate_openai_config_type'              => $solr_index['index_weaviate_openai_config_type'] ?? '',
				'index_weaviate_openai_config_model_qna'         => $solr_index['index_weaviate_openai_config_model_qna'] ?? '',
				'index_weaviate_openai_config_model_version_qna' => $solr_index['index_weaviate_openai_config_model_version_qna'] ?? '',
				'index_weaviate_openai_config_type_qna'          => $solr_index['index_weaviate_openai_config_type_qna'] ?? '',
				'index_weaviate_huggingface_config_model'        => $solr_index['index_weaviate_huggingface_config_model'] ?? '',
				'index_weaviate_huggingface_config_model_query'  => $solr_index['index_weaviate_huggingface_config_model_query'] ?? '',
				'index_key_json'                                 => $solr_index['index_key_json'] ?? '',
				'index_key_json_1'                               => $solr_index['index_key_json_1'] ?? '',
				'index_catalog_branch'                           => $solr_index['index_catalog_branch'] ?? '',
				'index_language_code'                            => $solr_index['index_language_code'] ?? '',
				'index_weaviate_cohere_config_model'             => $solr_index['index_weaviate_cohere_config_model'] ?? '',
				'index_weaviate_palm_config_model'               => $solr_index['index_weaviate_palm_config_model'] ?? '',
				'dataset_group_arn'                              => $solr_index['dataset_group_arn'] ?? '',
				'dataset_items_arn'                              => $solr_index['dataset_items_arn'] ?? '',
				'dataset_item_interaction_events_arn'            => $solr_index['dataset_item_interaction_events_arn'] ?? '',
				'dataset_users_arn'                              => $solr_index['dataset_users_arn'] ?? '',
				'dataset_actions_arn'                            => $solr_index['dataset_actions_arn'] ?? '',
				'dataset_user_action_interaction_events_arn'     => $solr_index['dataset_user_action_interaction_events_arn'] ?? '',
				'index_client_adapter'                           => $solr_index['index_client_adapter'] ?? '',
				'text_vectorizer_id'                             => $solr_index['text_vectorizer_id'] ?? '',
				'index_cert_1'                                   => $solr_index['index_cert_1'] ?? '',
			],

		];

		return $config;
	}
}