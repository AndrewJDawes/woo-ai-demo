<?php

namespace wpsolr\core\classes\engines\vespa;

use Exception;
use stdClass;
use wpsolr\core\classes\engines\vespa\php_client\WPSOLR_Php_Search_Client;
use wpsolr\core\classes\engines\vespa\php_client\WPSOLR_Php_Search_Index;
use wpsolr\core\classes\engines\WPSOLR_Client;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Vespa_None;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Some common methods of the Vespa client.
 *
 */
trait WPSOLR_Vespa_Client {
	use WPSOLR_Client;
	use WPSOLR_Vespa_GraphQL_Utilities;

	static protected string $alias_get = 'search';
	static protected string $alias_aggregate_search_count = 'search_count';
	static protected string $alias_aggregate_type_field_prefix = 'field_';
	static protected string $alias_aggregate_type_stats_prefix = 'stats_';
	static protected array $converted_field_names = [];
	static protected array $unconverted_field_names = [];

	static protected $convert_field_name_if_date = [];

	protected $INDEX_REPLICAT_SORT_NAME_PATTERN = '_replica_sort_';

	protected $wpsolr_type = 'wpsolr_types';

	// Unique id to store attached decoded files.
	protected $WPSOLR_DOC_ID_ATTACHMENT = 'wpsolr_doc_id_attachment';

	/** @var WPSOLR_Php_Search_Client */
	protected $search_engine_client;

	/** @var string */
	protected $index_label;

	/** @var WPSOLR_Php_Search_Index[] */
	protected $search_indexes;

	// Index conf files
	protected $FILE_CONF_INDEX_5 = 'wpsolr_index_5.json';
	protected $FILE_CONF_INDEX_6 = 'wpsolr_index_6.json';
	protected $FILE_CONF_INDEX_7 = 'wpsolr_index_7.json';

	/**
	 * Get the analysers available
	 * @return array
	 */
	static public function get_analysers() {

		return [];
	}

	/**
	 * @inerhitDoc
	 */
	public function get_has_exists_filter(): bool {
		return false;
	}

	/**
	 * @return string
	 */
	public
	function get_index_label() {
		return $this->index_label;
	}

	/**
	 * @param string $index_label
	 */
	public
	function set_index_label(
		$index_label
	) {
		$this->index_label = empty( $index_label ) ? '' : $this->convert_class_name( $index_label );
	}

	/**
	 * Delete the index
	 *
	 * https://www.vespa.com/doc/guides/sending-and-managing-data/manage-your-indices/how-to/deleting-multiple-indices/
	 *
	 * @throws Exception
	 */
	public function admin_delete_index() {
		$this->get_search_index()->delete_index();
	}

	/**
	 * Strict Vespa name syntax
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function convert_class_name( string $class_name ): string {
		return $class_name;
	}

	/**
	 * Try to fix the current index configuration before retrying
	 *
	 * @param $error_msg
	 *
	 * @return bool
	 */
	protected function _try_to_fix_error_doc_type( $error_msg ) {

		if ( false !== strpos( $error_msg, 'the final mapping would have more than 1 type' ) ) {
			// No type required (ES >= 7.x)
			$this->_fix_error_doc_type( 'index_doc_type', '' );

			// Fixed
			return true;

		} else if ( false !== strpos( $error_msg, 'type is missing' ) ) {
			// Type required (ES < 7.x)
			$this->_fix_error_doc_type( 'index_doc_type', $this->wpsolr_type );

			// Fixed
			return true;

		} else if ( false !== strpos( $error_msg, "suggester [autocomplete] doesn't expect any context" ) ) {
			// Index does not support suggester contexts: deactivate contexts in next request
			$this->_fix_error_doc_type( WPSOLR_Option::OPTION_INDEXES_VERSION_SUGGESTER_HAS_CONTEXT, null );

			// Fixed
			return true;

		} else if ( false !== strpos( $error_msg, "Missing mandatory contexts" ) ) {
			// Index does support suggester contexts: activate contexts in next request
			$this->_fix_error_doc_type( WPSOLR_Option::OPTION_INDEXES_VERSION_SUGGESTER_HAS_CONTEXT, '1' );

			// Fixed
			return true;
		}

		// Not fixed
		return false;
	}

	/**
	 * Fix the current index configuration with the guessed doc type
	 *
	 * @param string $index_property
	 * @param string $doc_type
	 *
	 * @return void
	 */
	protected
	function _fix_error_doc_type(
		$index_property, $doc_type
	) {

		// To be able to retry now, save it on current object index
		$this->index[ $index_property ] = $doc_type;

		$option_indexes = WPSOLR_Service_Container::getOption()->get_option_indexes();

		if ( isset( $option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $this->index_indice ] ) ) {
			// To prevent retry later, save it in the index options

			if ( is_null( $doc_type ) ) {
				// null value means "unset"

				unset( $option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $this->index_indice ][ $index_property ] );

			} else {

				$option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $this->index_indice ][ $index_property ] = $doc_type;
			}

			// Save it now
			update_option( WPSOLR_Option::OPTION_INDEXES, $option_indexes );
		}

	}

	/**
	 * This index has the deprecated "type"?
	 *
	 * @return bool
	 */
	protected
	function _get_index_doc_type() {
		return $this->index['index_doc_type'] ?? $this->wpsolr_type;
	}

	/**
	 * @param $config
	 *
	 * @return WPSOLR_Php_Search_Client
	 */
	protected
	function create_search_engine_client(
		$config
	) {

		$client = WPSOLR_Php_Search_Client::create( $config );

		$this->set_index_label( empty( $config ) ? '' : $config['index_label'] );

		return $client;
	}

	/**
	 * Retrieve the live Vespa version
	 *
	 * @return string
	 * @throws Exception
	 */
	protected
	function get_version() {

		$status      = $this->search_engine_client->getStatus();
		$status_data = $status->getResponse()->getData();
		if ( ! empty( $status_data ) && ! empty( $status_data['message'] ) ) {
			throw new \Exception( esc_html( $status_data['message'] ) );
		}

		$version = $this->search_engine_client->getVersion();

		if ( version_compare( $version, '5', '<' ) ) {
			throw new \Exception( sprintf( 'WPSOLR works only with Vespa >= 5. Your version is %s.', esc_html( $version ) ) );
		}

		return $version;
	}

	/**
	 * Create a match_all query
	 *
	 * @return array
	 */
	protected
	function _create_match_all_query() {

		$params         = $this->get_search_index();
		$params['body'] = [ 'query' => [ 'match_all' => new stdClass() ] ];

		return $params;
	}

	/**
	 * @param string $index_label
	 *
	 * @return WPSOLR_Php_Search_Index
	 */
	public
	function get_search_index(
		$index_label = ''
	) {

		$index_label = empty( $index_label ) ? $this->index_label : $index_label;

		if ( ! isset( $this->search_indexes[ $index_label ] ) ) {
			$this->search_indexes[ $index_label ] = $this->search_engine_client->init_index( $index_label );
		}

		return $this->search_indexes[ $index_label ];
	}

	/**
	 * Create a bool query
	 *
	 * @param array $bool_query
	 *
	 * @return array
	 */
	protected
	function _create_bool_query(
		$bool_query
	) {

		$params         = $this->get_search_index();
		$params['body'] = [ 'query' => [ 'bool' => $bool_query ] ];

		return $params;
	}

	/**
	 * Create the index
	 *
	 * @href https://www.semi.technology/developers/vespa/current/restful-api-references/schema.html#create-a-class
	 *
	 * @param array $index_parameters
	 */
	protected function admin_create_index( &$index_parameters ) {

		$settings = $this->get_index_settings();

		$this->get_search_index()->create_index( $settings, $this->_prepare_shema_data() );
	}

	/**
	 * Load the content of a conf file.
	 *
	 * @return array
	 */
	protected
	function get_index_settings() {

		return $this->config;

	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function _prepare_shema_data( array $fields = [] ): array {

		$schema = [
			'label'           => $this->index_label,
			'document_fields' => [],
			'fieldset'        => [],
		];

		if ( empty( $fields ) ) {
			$fields['title']   = [ 'is_array' => false ];
			$fields['content'] = [ 'is_array' => false ];
		}

		/**
		 * Download schema files
		 */
		$schema_custom_file    = $this->get_search_index()->get_schema_custom();
		$schema_generated_file = $this->get_search_index()->get_schema_generated();

		/**
		 * Collect fields already in generated schema
		 */
		$schema_generated_existing_fields = array_keys( $fields );
		$this->_extract_schema_file_document_fields( $schema_generated_file, $schema_generated_existing_fields );

		/**
		 * Add document fields if not moved to custom schema
		 */
		$schema_custom_existing_fields = [];
		$this->_extract_schema_file_document_fields( $schema_custom_file, $schema_custom_existing_fields );

		foreach ( array_unique( $schema_generated_existing_fields ) as $field_name ) {
			if ( ( false !== strpos( $field_name, '%' ) ) ) {
				continue;
			}


			if ( in_array( $field_name, $schema_custom_existing_fields ) ) {
				// Only add elements in generated schema if not present in custom schema
				continue;
			}

			$converted_field_name = $this->convert_field_name( $field_name );
			if ( ! in_array( $converted_field_name, $schema['document_fields'] ) ) {
				$schema['document_fields'][] = $this->_get_field_definition( $field_name );
			}

			/**
			 * Search on all indexed fields with type string or array of string
			 */
			if ( false !== strpos( $this->_get_field_definition( $field_name )['type'], 'string' ) ) {
				$schema['text_fields'][] = $converted_field_name;
			}
		}


		if ( empty( $this->_extract_schema_file_input_fields( $schema_custom_file, [ 'wpsolr_input_text' ] ) ) ) {
			$schema['input_text_fields'] = $schema['text_fields'];
		}

		/**
		 * Add fieldsets if not moved to custom schema
		 */
		$schema['fieldsets'] = [ 'wpsolr_fieldset_input_text' ];
		if ( ! empty( $custom_fieldsets = $this->_extract_schema_file_fieldsets( $schema_custom_file, $schema['fieldsets'] ) ) ) {
			$schema['fieldsets'] = array_diff( $schema['fieldsets'], $custom_fieldsets );
		}

		/**
		 * Add rank profiles if not moved to custom schema
		 */
		$schema['rank_profiles'] = [ 'wpsolr_rank_text', 'wpsolr_rank_text_vector', 'wpsolr_rank_text_hybrid' ];
		if ( ! empty( $custom_rank_profiles = $this->_extract_schema_file_rank_profiles( $schema_custom_file, $schema['rank_profiles'] ) ) ) {
			$schema['rank_profiles'] = array_diff( $schema['rank_profiles'], $custom_rank_profiles );
		}

		/**
		 * Add embedding fields if not moved to custom schema
		 */
		if ( ! empty( $text_vectorizer_id = $this->config['extra_parameters']['text_vectorizer_id'] ?: '' ) &&
		     ! empty( $text_vectorizer_def = WPSOLR_Hosting_Api_Vespa_None::get_components_vectorizer_text()[ $text_vectorizer_id ] ) &&
		     empty( $this->_extract_schema_file_input_fields( $schema_custom_file, [ WPSOLR_Hosting_Api_Vespa_None::WPSOLR_FIELD_VECTOR_TEXT ] ) )
		) {
			$schema['input_text_embedding_fields']                                      = [];
			$text_vectorizer_def['field_label']                                         = WPSOLR_Hosting_Api_Vespa_None::WPSOLR_FIELD_VECTOR_TEXT;
			$schema['input_text_embedding_fields'][ $text_vectorizer_id ]['definition'] = $text_vectorizer_def;
			$schema['input_text_embedding_fields'][ $text_vectorizer_id ]['fields']     = $schema['text_fields'];
		}

		return $schema;
	}

	/**
	 * @param string $schema_file
	 * @param array $schema_existing_fields
	 */
	protected function _extract_schema_file_document_fields( string $schema_file, array &$schema_existing_fields ) {

		if ( preg_match( '/document.*(\{(?:([^\{\}]*)|(?:(?2)(?1)(?2))*)\})/', $schema_file, $document_outer_parenthesis_content ) &&
		     preg_match_all( "/field\s+(wpsolr_\w+)\s+type/", $document_outer_parenthesis_content[1], $matches ) ) {

			foreach ( $matches[1] as $match ) {
				$schema_existing_fields[] = $this->unconvert_field_name( $match );
			}
		}

	}

	/**
	 * Reverse strict Vespa name syntax
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function unconvert_field_name( string $field_name ): string {

		return WPSOLR_Regexp::remove_string_at_the_begining( $field_name, 'wpsolr_' );
	}

	/**
	 * Strict Vespa name syntax
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function convert_field_name( string $field_name ): string {
		return sprintf( 'wpsolr_%s', $field_name );
	}

	/**
	 * @href https://docs.vespa.ai/en/reference/schema-reference.html#field-types
	 *
	 * @param string $field_name
	 * @param null $field_value
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function _get_field_definition( string $field_name, &$field_value = null ): array {

		$result = [
			'label'    => $this->convert_field_name( $field_name ),
			'indexing' => 'summary | attribute',
			#'index'    => '',
		];

		$field_type = WpSolrSchema::DEFAULT_FIELD_TYPES[ $field_name ] ?? WpSolrSchema::get_custom_field_dynamic_type( $field_name );

		// ['int'] => 'int'
		$field_type             = is_array( $field_type ) ? $field_type[0] : $field_type;
		$field_type_is_singular = ( isset( WpSolrSchema::DEFAULT_FIELD_TYPES[ $field_name ] ) && ! is_array( WpSolrSchema::DEFAULT_FIELD_TYPES[ $field_name ] ) );

		switch ( $field_type ) {
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_INTEGER:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_INTEGER_LONG:
				$result['type'] = 'long';
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_DATE:
				$result['type'] = 'long';
				$field_value    = is_array( $field_value ) ? $this->search_engine_client_format_dates( $field_value ) : $this->search_engine_client_format_date( $field_value );
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_FLOAT:
				$result['type'] = 'float';
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_FLOAT_DOUBLE:
				$result['type'] = 'double';
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_S:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING1:
				$result['type']  = 'string';
				$result['index'] = 'enable-bm25';
				//$result['match'] = 'exact'; // https://docs.vespa.ai/en/reference/schema-reference.html#match
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT:
				$result['type']     = 'string';
				$result['indexing'] = 'summary | index';
				$result['summary']  = 'dynamic';
				$result['index']    = 'enable-bm25';
				//$result['match']    = 'text';
				// For partial matching: https://docs.vespa.ai/en/text-matching-ranking.html#n-gram-match
				// match{} must be the same for all fields in a fieldset, so we do not use it here (https://github.com/vespa-engine/vespa/issues/27878#issuecomment-1648249391)
				//$result['match'] = $this->_get_field_definition_match();

				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_BASE64:
				$result['type'] = 'raw';
				//$result['indexing'] = 'summary';
				break;

			default:
				throw new \Exception( sprintf( "%s type not implemented for field %s", esc_html( $field_type ), esc_html( $field_name ) ) );
		}


		if ( ! $field_type_is_singular ) {
			$result['type'] = sprintf( 'array<%s>', $result['type'] );
		}

		return $result;
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return string
	 */
	public
	function search_engine_client_format_date(
		$date_str
	) {
		$result = false;

		if ( is_numeric( $date_str ) ) {

			$result = (int) $date_str;

		} else {

			$timestamp = strtotime( $date_str );

			if ( is_int( $timestamp ) ) {
				$result = $timestamp;
			}

		}

		return $result;
	}

	/**
	 * @param string $schema_file
	 * @param string[] $names
	 * @param string $pattern
	 *
	 * @return string[]
	 */
	protected function _extract_schema_file_pattern( string $schema_file, array $names, string $pattern ): array {

		$results = [];
		foreach ( $names as $name ) {
			if ( preg_match_all( sprintf( $pattern, $name ), $schema_file, $matches ) ) {
				$results[] = $name;
			}
		}

		return $results;
	}

	/**
	 * @param string $schema_file
	 * @param string[] $input_fields
	 *
	 * @return string[]
	 */
	protected function _extract_schema_file_input_fields( string $schema_file, array $input_fields ): array {
		return $this->_extract_schema_file_pattern( $schema_file, $input_fields, "/field\s+(%s)\s+/" );
	}

	/**
	 * @param string $schema_file
	 * @param string[] $rank_profiles
	 *
	 * @return string[]
	 */
	protected function _extract_schema_file_rank_profiles( string $schema_file, array $rank_profiles ): array {
		return $this->_extract_schema_file_pattern( $schema_file, $rank_profiles, "/rank-profile\s+(%s)\s+/" );
	}

	/**
	 * @param string $schema_file
	 * @param string[] $fieldsets
	 *
	 * @return string[]
	 */
	protected function _extract_schema_file_fieldsets( string $schema_file, array $fieldsets ): array {
		return $this->_extract_schema_file_pattern( $schema_file, $fieldsets, "/fieldset\s+(%s)\s+/" );
	}

	/**
	 * Add a configuration to the index if missing.
	 */
	protected function admin_index_update( &$index_parameters ) {
		// No need
	}

	/**
	 * Date fields usable are the unix timestamp version
	 * https://www.vespa.com/doc/guides/managing-results/refine-results/sorting/how-to/sort-an-index-by-date/
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	protected function _convert_field_name_if_date( $field_name ): string {

		if ( ! empty( static::$convert_field_name_if_date[ $field_name ] ) ) {
			return static::$convert_field_name_if_date[ $field_name ];
		}

		$new_field_name = $field_name;

		if ( WpSolrSchema::get_custom_field_is_date_type( $field_name ) ) {
			$new_field_name .= wpsolrschema::_SOLR_DYNAMIC_TYPE_INTEGER;
		}

		// save
		static::$convert_field_name_if_date[ $field_name ] = $new_field_name;

		return $new_field_name;
	}

	/**
	 * Date fields usable are the unix timestamp version
	 *
	 * @param string $value
	 *
	 * @return int|string
	 */
	protected function _convert_to_unix_time_if_date( $value ) {

		if ( ! is_numeric( $value ) ) {

			$converted_value = 1000 * strtotime( $value ); // ms
			$value           = ( false === $converted_value ) ? $value : $converted_value;
		}

		return $value;
	}

	/**
	 * @param string[] $fields
	 * @param string $error_msg
	 *
	 * @throws Exception
	 */
	protected function _add_index_fields_definitions( array $fields ): void {
		$schema = $this->_prepare_shema_data( $fields );

		/**
		 * Update schema with fields
		 */
		$this->get_search_index()->update_schema( $schema );
	}

	/**
	 * Generate new base64 field names: wpsolr_blob_0_b64, wpsolr_blob_1_b64, wpsolr_blob_2_b64, ....
	 *
	 * @param int $i
	 * @param bool $is_convert_field
	 *
	 * @return string
	 */
	protected function _generate_blob_field_name( int $i, $is_convert_field ): string {
		return str_replace( WpSolrSchema::_SOLR_DYNAMIC_TYPE_BASE64,
			sprintf( '_%s%s', $i, WpSolrSchema::_SOLR_DYNAMIC_TYPE_BASE64 ),
			$is_convert_field ? $this->convert_field_name( WpSolrSchema::_FIELD_NAME_BASE64 ) : WpSolrSchema::_FIELD_NAME_BASE64
		);
	}

	/**
	 * @param array $property
	 *
	 * @return array
	 */
	protected function _add_extra_properties_to_field_definition( array $property ): array {
		return $property;


		switch ( $property['dataType'][0] ) {
			case 'string[]':
				// https://vespa.io/developers/vespa/configuration/schema-configuration#property-tokenization
				$property['tokenization'] = 'field'; // default is 'world', which uses an analyser
				break;
		}

		return $property;
	}

	/**
	 * Add ngram to field definition
	 * @return string
	 */
	protected function _get_field_definition_match(): string {
		return '{
	gram
	gram-size: 2
}';
	}

}
