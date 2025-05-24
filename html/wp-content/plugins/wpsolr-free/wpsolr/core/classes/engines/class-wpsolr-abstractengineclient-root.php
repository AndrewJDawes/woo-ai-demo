<?php

namespace wpsolr\core\classes\engines;

use Exception;
use wpsolr\core\classes\engines\elasticsearch_php\WPSOLR_IndexElasticsearchClient;
use wpsolr\core\classes\engines\opensearch_php\WPSOLR_IndexOpenSearchClient;
use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\engines\vespa\WPSOLR_Index_Vespa_Client;
use wpsolr\core\classes\engines\weaviate\WPSOLR_Index_Weaviate_Client;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\models\WPSOLR_Model_Abstract;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\models\WPSOLR_Model_Meta_Type_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_AbstractEngineClient
 * @package wpsolr\core\classes\engines
 */
abstract class WPSOLR_AbstractEngineClient_Root {
	use WPSOLR_Client;

	const OPTION_INDEX_MODEL_VERSION = 'wpsolr_index_model_version';

	// For engines which cannot filter on undefined fields: we add a value meaning 'undefined'
	const FIELD_VALUE_UNDEFINED = '_wpsolr_undefined';

	// Anonymous visitor id
	const VISITOR_ID_ANONYMOUS = 'wpsolr_anonymous';

	// Engine types
	const ENGINE = 'index_engine';

	const ENGINE_ELASTICSEARCH = 'engine_elasticsearch';
	const ENGINE_ELASTICSEARCH_NAME = 'Elasticsearch';
	const ENGINE_OPENSEARCH = 'engine_opensearch';
	const ENGINE_OPENSEARCH_NAME = 'OpenSearch';
	const ENGINE_SOLR = 'engine_solr';
	const ENGINE_SOLR_NAME = 'Apache Solr';
	const ENGINE_SOLR_CLOUD = 'engine_solr_cloud';
	const ENGINE_SOLR_CLOUD_NAME = 'Apache SolrCloud';
	const ENGINE_REDISEARCH = 'engine_redisearch';
	const ENGINE_REDISEARCH_NAME = 'RediSearch (coming soon)';
	const ENGINE_WEAVIATE = 'engine_weaviate';
	const ENGINE_WEAVIATE_NAME = 'Weaviate';

	const ENGINE_VESPA = 'engine_vespa';
	const ENGINE_VESPA_NAME = 'Vespa.ai';

	const DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND = 30;

	// Timeout in seconds when calling Solr
	const SEP_NESTED_PARENT_CHILD_FIELD = '.';
	/** @var mixed */
	public $index;
	/** @var mixed */
	protected $search_engine_client;
	protected $search_engine_client_config;

	// Index
	protected $index_indice;


	// Array of active extension objects
	protected $wpsolr_extensions;
	protected $custom_field_properties;
	protected array $taxonomy_properties;

	// Custom fields properties
	/** @var WPSOLR_Model_Meta_Type_Abstract[] $models */
	protected $models;
	/** @var array */
	protected $config;

	/**
	 * @return array[]
	 */
	static function get_engines_definitions(): array {
		return [
			WPSOLR_AbstractEngineClient_Root::ENGINE_SOLR          => [
				'name'                => WPSOLR_AbstractEngineClient_Root::ENGINE_SOLR_NAME,
				'is_active'           => true,
				'has_search'          => true,
				'has_recommendations' => true,
			],
			WPSOLR_AbstractEngineClient_Root::ENGINE_SOLR_CLOUD    => [
				'name'                => WPSOLR_AbstractEngineClient_Root::ENGINE_SOLR_CLOUD_NAME,
				'is_active'           => true,
				'has_search'          => true,
				'has_recommendations' => true,
			],
			WPSOLR_AbstractEngineClient_Root::ENGINE_ELASTICSEARCH => [
				'name'                => WPSOLR_AbstractEngineClient_Root::ENGINE_ELASTICSEARCH_NAME,
				'is_active'           => true,
				'has_search'          => true,
				'has_recommendations' => true,
			],
			WPSOLR_AbstractEngineClient_Root::ENGINE_OPENSEARCH    => [
				'name'                => WPSOLR_AbstractEngineClient_Root::ENGINE_OPENSEARCH_NAME,
				'is_active'           => true,
				'has_search'          => true,
				'has_recommendations' => true,
			],
			WPSOLR_AbstractEngineClient_Root::ENGINE_WEAVIATE      => [
				'name'                => WPSOLR_AbstractEngineClient_Root::ENGINE_WEAVIATE_NAME,
				'is_active'           => true,
				'has_search'          => true,
				'has_vector_search'   => true,
				'has_hybrid_search'   => true,
				'has_recommendations' => true,
			],
			WPSOLR_AbstractEngineClient_Root::ENGINE_VESPA         => [
				'name'                => WPSOLR_AbstractEngineClient_Root::ENGINE_VESPA_NAME,
				'is_active'           => true,
				'has_search'          => true,
				'has_vector_search'   => true,
				'has_hybrid_search'   => true,
				'has_recommendations' => false,
			],
			/*
			WPSOLR_AbstractEngineClient::ENGINE_REDISEARCH         => [
				'name'                    => WPSOLR_AbstractEngineClient::ENGINE_REDISEARCH_NAME,
				'is_active'               => false,
				'has_search'              => true,
			],
			*/
		];
	}

	/**
	 * Get the analysers available for all search engine types
	 *
	 * @return array
	 */
	static public function get_search_engines_type_analysers() {

		$results = [];
		foreach (
			static::_get_analyser_search_engines() as $search_engine
		) {
			$results[ $search_engine ] = static::get_search_engine_type_analysers( $search_engine );
		}

		return $results;
	}

	/**
	 * @return array
	 */
	protected static function _get_analyser_search_engines(): array {
		return [
			static::ENGINE_ELASTICSEARCH,
			static::ENGINE_OPENSEARCH,
			static::ENGINE_SOLR,
			static::ENGINE_WEAVIATE,
		];
	}

	/**
	 * Get the analysers available for a search engine type
	 *
	 * @param string $search_engine
	 *
	 * @return array
	 */
	static public function get_search_engine_type_analysers( $search_engine ) {

		$results = [];
		switch ( $search_engine ) {
			case static::ENGINE_ELASTICSEARCH:
				$results = WPSOLR_IndexElasticsearchClient::get_analysers();
				break;

			case static::ENGINE_OPENSEARCH:
				$results = WPSOLR_IndexOpenSearchClient::get_analysers();
				break;

			case static::ENGINE_SOLR:
			case static::ENGINE_SOLR_CLOUD:
				$results = WPSOLR_IndexSolariumClient::get_analysers();
				break;

			case static::ENGINE_WEAVIATE:
				$results = WPSOLR_Index_Weaviate_Client::get_analysers();
				break;

			case static::ENGINE_VESPA:
				$results = WPSOLR_Index_Vespa_Client::get_analysers();
				break;

		}

		return $results;
	}

	/**
	 * @return WPSOLR_Model_Meta_Type_Abstract[]
	 */
	public function get_models() {
		return is_null( $this->models ) ? $this->set_default_models() : $this->models;
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $models
	 */
	public function set_models( $models ) {
		$this->models = $models;
	}

	/**
	 * Set default models (all post types selected)
	 *
	 * @return WPSOLR_Model_Meta_Type_Abstract[]
	 */
	protected function set_default_models() {

		$models_to_index = WPSOLR_Model_Builder::get_model_type_objects( WPSOLR_Service_Container::getOption()->get_option_index_post_types(), false );
		$this->set_models( $models_to_index );

		return $models_to_index;
	}

	/**
	 * @return mixed
	 */
	public function get_search_engine_client() {
		return $this->search_engine_client;
	}

	/**
	 * Generate a unique post_id for site
	 *
	 * @param WPSOLR_Model_Abstract $model
	 * @param string $id
	 *
	 * @return string
	 */
	public function generate_model_unique_id( $model ) {

		$result = $this->_get_mode_id( $model );

		$prefix = $model->get_id_prefix();

		return empty( $prefix ) ? $result : sprintf( '%s_%s', $prefix, $result ); // Prevent duplicates between same ids from distinct model types
	}

	/**
	 * @param WPSOLR_Model_Abstract $model
	 *
	 * @return string
	 */
	protected function _get_mode_id( WPSOLR_Model_Abstract $model ): string {
		return $model->get_id();
	}

	/**
	 * Is a field sortable ?
	 *
	 * @param string $field_name Field name (like 'price_str')
	 *
	 * @return bool
	 */
	public
	function get_is_field_sortable(
		$field_name
	) {

		return (
			( ! empty( $this->custom_field_properties[ $field_name ] )
			  && ! empty( $this->custom_field_properties[ $field_name ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
			  && WpSolrSchema::get_solr_dynamic_entension_id_is_sortable( $this->custom_field_properties[ $field_name ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
			) ||
			( in_array( $field_name, $this->taxonomy_properties ) )
		);

	}

	/**
	 * Create a field_name with the _t extension. Used by boosts to use analysers.
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function copy_field_name( $field_name ) {

		$option_search_fields_boost_types = WPSOLR_Service_Container::getOption()->get_search_fields_boost_types();

		if ( isset( $option_search_fields_boost_types[ $field_name ] ) ) {

			if ( empty( $option_search_fields_boost_types[ $field_name ] ) ) {

				$field_name = WpSolrSchema::replace_field_name_extension( $field_name );

			} else {

				// Field 'categories' store categories and custom fields
				// Field categories_str stores categories
				switch ( $field_name ) {
					case WpSolrSchema::_FIELD_NAME_CATEGORIES:
					case WpSolrSchema::_FIELD_NAME_TAGS:
					case WpSolrSchema::_FIELD_NAME_AUTHOR:
						$field_name .= WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT;
						break;

					default:
						$field_name = WpSolrSchema::replace_field_name_extension_with( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT );
						break;
				}
			}

		}

		return $field_name;
	}

	/**
	 * @return string
	 */
	public function get_index_uuid() {
		return $this->index_indice;
	}

	/**
	 * @return array
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Transform an array of string in an array of dates.
	 *
	 * @param $date_str String[] date to convert from.
	 *
	 * @return string[]
	 */
	public
	function search_engine_client_format_dates(
		array $dates_str
	) {
		$results = [];
		foreach ( $dates_str as $date_str ) {
			$results[] = $this->search_engine_client_format_date( $date_str );
		}

		return $results;
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return mixed
	 */
	abstract public function search_engine_client_format_date( $date_str );

	/**
	 * Get a facet name if it's hierarchy (or not)
	 *
	 * @param string $hierarchy_field_name
	 * @param string $facet_name
	 *
	 * @return string Facet name with hierarch or not
	 */
	public
	function get_facet_hierarchy_name(
		$hierarchy_field_name, $facet_name, $is_archive_hierarchical_filter = false
	) {

		$facet_name = strtolower( str_replace( ' ', '_', $facet_name ) );

		$is_hierarchy = $this->is_facet_to_show_as_a_hierarchy( WpSolrSchema::_FIELD_NAME_CATEGORIES_STR === $facet_name ? WpSolrSchema::_FIELD_NAME_CATEGORIES : $facet_name );

		if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $facet_name ) {

			// Field 'categories' are now treated as other fields (dynamic string type)
			$facet_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
		}

		$result =
			(
				$is_hierarchy ||
				( $this->_get_is_taxonomy_hierarchical( $facet_name ) &&
				  ( $is_archive_hierarchical_filter || ( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY === $hierarchy_field_name ) )
				)
			) ? sprintf( $hierarchy_field_name, $facet_name ) : $facet_name;

		return $result;
	}

	/**
	 * Does a facet has to be shown as a hierarchy
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	private
	function is_facet_to_show_as_a_hierarchy(
		$facet_name
	) {

		$facets_to_show_as_a_hierarchy = WPSOLR_Service_Container::getOption()->get_facets_to_show_as_hierarchy();

		// Relations: 'parent_obj.field_name' ===> 'field_name'
		$facet_name = WPSOLR_Regexp::extract_last_separator( $facet_name, static::SEP_NESTED_PARENT_CHILD_FIELD );

		return ! empty( $facets_to_show_as_a_hierarchy ) && ! empty( $facets_to_show_as_a_hierarchy[ $facet_name ] );
	}

	/**
	 * @param string $hierarchy_name_str
	 *
	 * @return bool
	 */
	protected function _get_is_taxonomy_hierarchical( string $hierarchy_name_str ): bool {

		$hierarchy_name_without_str = WpSolrSchema::get_field_without_str_ending( $hierarchy_name_str );
		if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $hierarchy_name_without_str ) {
			return true;
		}

		if (
			( $taxonomy = get_taxonomy( $hierarchy_name_without_str ) ) &&
			( $taxonomy->hierarchical )
		) {
			return true;
		}

		return false;
	}

	/**
	 * How many documents are in the index ?
	 *
	 * @param $site_id
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function search_engine_client_get_count_document( $site_id = '' ) {
		throw new \Exception( 'Not implemented.' );
	}

	/**
	 * Create an client
	 *
	 * @param array $config
	 *
	 * @return object
	 */
	abstract protected function create_search_engine_client( $config );

	/**
	 * Execute an update query with the client.
	 *
	 * @param $search_engine_client
	 * @param $update_query
	 *
	 * @return WPSOLR_AbstractResultsClient
	 */
	abstract protected function search_engine_client_execute( $search_engine_client, $update_query );

	/**
	 * Fix an error while querying the engine.
	 *
	 * @param Exception $e
	 * @param $search_engine_client
	 * @param $update_query
	 *
	 * @return
	 * @throws Exception
	 */
	protected function search_engine_client_execute_fix_error( Exception $e, $search_engine_client, $update_query ) {
		// No fix by default.
		throw $e;
	}

	/**
	 * Multivalue sort is not supported on this field. Remove it.
	 *
	 * @param string $field_name_with_extension
	 */
	protected function remove_multivalue_sort( $field_name_with_extension ) {
		WPSOLR_Service_Container::getOption()->set_sortby_is_multivalue( $field_name_with_extension, false );
	}

	/**
	 * Multivalue sort is supported on this field. Add it.
	 *
	 * @param string $field_name_with_extension
	 */
	protected function add_multivalue_sort( $field_name_with_extension ) {
		WPSOLR_Service_Container::getOption()->set_sortby_is_multivalue( $field_name_with_extension, true );
	}

	/**
	 * Init details
	 *
	 * @param $config
	 */
	protected function init( $config = null ) {

		$this->config       = $config;
		$this->index_indice = $config['index_uuid'] ?? null;

		$all_models = [];
		//$all_models[] = new WPSOLR_Model_Abstract_User();
		//$all_models[] = new WPSOLR_Model_Abstract_BP_Profile_Data();

		$this->custom_field_properties = WPSOLR_Service_Container::getOption()->get_option_index_custom_field_properties();
		$this->taxonomy_properties     = WPSOLR_Service_Container::getOption()->get_option_index_taxonomies();
	}

	/**
	 * @return int
	 */
	protected function _start_log_timer(): float {

		if ( $this->is_log_query() ) {
			// Start timer
			$start_time = microtime( true );

			return $start_time;
		}

		return 0;
	}

	/**
	 * Can we log queries?
	 * @return bool
	 */
	protected function is_log_query() {
		return static::IS_LOG_QUERY_TIME_IMPLEMENTED &&
		       ! empty( WPSOLR_Service_Container::getOption()->get_search_log_query_mode() );
	}

	/**
	 * @param WPSOLR_AbstractResultsClient $results
	 * @param int $start_time
	 * @param Exception $e
	 */
	protected function _end_log_timer( $results, $start_time, $e = null ) {

		if ( $this->is_log_query() ) {
			// Start timer
			$end_time = microtime( true );

			$option_indexes = new WPSOLR_Option_Indexes();
			$log            = [
				'level'                => $results ? 'Log' : 'Error',
				'view_label'           => WPSOLR_Option_View::get_view( $this->get_wpsolr_query()->wpsolr_get_view_uuid() )[ WPSOLR_Option::OPTION_VIEW_LABEL ] ?? '',
				'recommendation_label' => $this->get_wpsolr_query()->wpsolr_get_recommendation()[ WPSOLR_Option::OPTION_RECOMMENDATION_LABEL ] ?? '',
				'url'                  => WPSOLR_Sanitize::sanitize_request_uri(),
				'index_label'          => $option_indexes->get_index_name( $option_indexes->get_index( $this->config['index_uuid'] ) ),
				'nb_rows'              => $results ? $results->get_nb_rows() : 'unknown',
				'nb_results'           => $results ? $results->get_nb_results() : 'unknown',
				'time_ms'              => round( 1000 * ( $end_time - $start_time ) ),
				'query_as_string'      => $this->_log_query_as_string(),
				'results_as_string'    => $results ? $this->_log_results_as_string( $results ) : ( $e ? sprintf( "%s\n%s", $e->getMessage(), $e->getTraceAsString() ) : 'unknown' ),
			];

			/**
			 * Chance to update log
			 */
			$log = apply_filters( WPSOLR_Events::WPSOLR_FILTER_LOG, $log, 10, 1 );

			/**
			 * Store log in global variable
			 */
			global /** @var array $wpsolr_query_logs */
			$wpsolr_query_logs;
			if ( ! isset( $wpsolr_query_logs ) ) {
				$wpsolr_query_logs = [];
			}
			$wpsolr_query_logs[] = $log;

			if ( WPSOLR_Service_Container::getOption()->get_is_search_log_query_mode_debug_file() ) {
				/**
				 * Generate the debug.log text
				 */
				$log_text = sprintf( "WPSOLR %s query =>\nView: \"%s\"\n"
				                     . "Url: \"%s\"\nIndex: \"%s\"\nNb results shown: %s\nTotal nb results: %s\nSpeed: %d ms \nQuery: %s \nResults: %s\n",
					$log['level'],
					$log['view_label'],
					$log['url'],
					$log['index_label'],
					$log['nb_rows'],
					$log['nb_results'],
					$log['time_ms'],
					$log['query_as_string'],
					$log['results_as_string'],
				);
				WPSOLR_Escape::error_log( $log_text );
			}

		}
	}

	/**
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function _log_query_as_string() {
		return '_log_query_as_string() is not implemented.';
	}

	/**
	 *
	 * @param WPSOLR_AbstractResultsClient $results
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function _log_results_as_string( $results ) {
		return wp_json_encode( (array) $results->get_raw_results(), JSON_PRETTY_PRINT );
	}

	/**
	 * Get the nested name from a name
	 *
	 * 'price_nobj.price_f' ==> 'price_nobj'
	 * 'price_f' ==> 'price_f'
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function _get_nested_name( string $name ): string {
		return $this->_is_nested( $name ) ?
			explode( sprintf( '%s.', WpSolrSchema::_SOLR_DYNAMIC_TYPE_EMBEDDED_OBJECT ), $name )[0] . WpSolrSchema::_SOLR_DYNAMIC_TYPE_EMBEDDED_OBJECT :
			$name;
	}

	/**
	 * Is a name 'nested' ?
	 *
	 * 'price_nobj.price_f' ==> true
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	protected function _is_nested( string $name ): bool {
		return ( false !== strpos( $name, sprintf( '%s.', WpSolrSchema::_SOLR_DYNAMIC_TYPE_EMBEDDED_OBJECT ) ) );
	}

	/**
	 *
	 * @return array
	 */
	public function get_recommendation() {
		return $this->recommendation ?? [];
	}

	/**
	 * @param array $recommendation
	 *
	 * @return void
	 */
	public function set_recommendation( $recommendation ) {
		$this->recommendation = $recommendation;
	}

	protected function _get_is_more_like_this() {
		$recommendation = $this->get_recommendation();
		if ( ! empty( $recommendation ) &&
		     ( WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_MORE_LIKE_THIS === $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] )
		) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $option
	 * @param string $indice_uuid
	 */
	function update_old_indice_format( &$option, $indice_uuid ) {
		if ( ! isset( $option[ $indice_uuid ] ) || is_scalar( $option[ $indice_uuid ] ) ) {
			$option[ $indice_uuid ] = []; // Old format as a string, replaced by an array
		}
	}

	/**
	 * @param string $model_type
	 * @param string $postfix
	 *
	 * @return string
	 */
	protected function _generate_index_model_version( string $model_type, string $postfix = '' ): string {
		return substr( preg_replace( '/[^a-zA-Z0-9]/', '', wp_generate_uuid4() ), 0, 7 );
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract $model_type
	 * @param $option_name
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public function get_index_indice_option_value( $model_type, $option_name, $option_value ) {

		// Get option value. Replace by default value if undefined.
		//$option = WPSOLR_Service_Container::getOption()->get_option( $option_name, null );
		$option = get_option( $option_name, null );

		// Ensure compatibility
		$this->update_old_indice_format( $option, $this->index_indice );

		$result = $option_value;
		if ( isset( $option ) && isset( $option[ $this->index_indice ] ) && isset( $option[ $this->index_indice ][ $model_type->get_type() ] ) ) {

			$result = $option[ $this->index_indice ][ $model_type->get_type() ];
		}

		return $result;
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract|string $model_type
	 *
	 * @return string
	 */
	public function get_index_model_version( $model_type ): string {

		if ( ! $this->get_has_index_model_version() ) {
			return '';
		}

		if ( is_string( $model_type ) ) {
			$model_type = WPSOLR_Model_Builder::get_model_type_object( $model_type );
		}


		return $this->get_index_indice_option_value( $model_type, static::OPTION_INDEX_MODEL_VERSION, '' );
	}

	/**
	 * Create an index version for each model, each time "Delete all" is used.
	 * For engines that cannot delete index.
	 * They use a filter on version instead.
	 *
	 * @return bool
	 */
	public function get_has_index_model_version(): bool {
		return false;
	}

}
