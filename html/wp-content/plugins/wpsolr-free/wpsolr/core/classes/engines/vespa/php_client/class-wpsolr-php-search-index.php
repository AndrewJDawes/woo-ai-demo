<?php

namespace wpsolr\core\classes\engines\vespa\php_client;

use DOMDocument;
use SimpleXMLElement;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Vespa_None;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Admin_Utilities;

class WPSOLR_Php_Search_Index {

	/**
	 * Vespa API parameters
	 */
	const VESPA_DEFAULT_TENANT = 'default';
	const VESPA_DEFAULT_APPLICATION = 'default';
	const VESPA_DEFAULT_DOCUMENT_NAMESPACE = 'wpsolr';

	/**
	 * Vespa API URLs
	 */
	const URL_APPLICATION_V2_TENANT_PREPAREANDACTIVATE = '/application/v2/tenant/%s/prepareandactivate';
	const URL_CONFIG_V2_TENANT_APPLICATION = '/config/v2/tenant/%s/application/%s/';
	const URL_CONFIG_V2_TENANT_APPLICATION_SEARCH_INDEXSCHEMA = '/config/v2/tenant/%s/application/%s/vespa.config.search.indexschema/%s/';

	/**
	 * Application files
	 */
	const ZIP_NEW_APPLICATION_PACKAGE = 'https://www.dropbox.com/s/z9k5iq4t559grqj/application_create.zip?dl=0';
	const SCHEMA_PATTERN = 'schemas/%s.sd';
	const SERVICES_PATTERN = 'services.xml';
	const VALIDATION_OVERRIDES_PATTERN = 'validation-overrides.xml';

	const DEFAULT_SERVICE_CONTENT_ID = 'wpsolr'; // id of the content element embedding WPSOLR's schema declarations
	const DEFAULT_SERVICE_CONTAINER_ID = 'default'; // id of the container
	const TWIG_SCHEMA_TEMPLATE_PATTERN = __DIR__ . '/../application_packages/application_update/schemas/%s';
	const TWIG_SCHEMA_ROOT_PATTERN = __DIR__ . '/../application_packages/application_update/%s';
	const SCHEMA_TEMPLATE_GENERATED_TWIG = 'schema_template_generated.twig';
	const SCHEMA_TEMPLATE_CUSTOM_TWIG = 'schema_template_custom.twig';
	const VALIDATION_OVERRIDES_TWIG = 'validation-overrides.twig';

	# https://github.com/vespa-engine/vespa/blob/master/config-model-api/src/main/java/com/yahoo/config/application/api/ValidationId.java
	const VALIDATION_OVERRIDES_VALIDATION_ID_REMOVE_SCHEMA = 'schema-removal';
	const VALIDATION_OVERRIDES_VALIDATION_ID_INDEXING_CHANGE = 'indexing-change';
	const VALIDATION_OVERRIDES_VALIDATION_ID_FIELD_TYPE_CHANGE = 'field-type-change';
	const COM_YAHOO_LANGUAGE_SIMPLE_SIMPLE_LINGUISTICS = 'com.yahoo.language.simple.SimpleLinguistics';

	protected string $index_label;
	protected WPSOLR_Php_Rest_Api $api;
	protected array $config;

	/**
	 * Constructor.
	 *
	 * @param string $index_label
	 * @param WPSOLR_Php_Rest_Api $api
	 * @param array $config
	 */
	public function __construct( string $index_label, WPSOLR_Php_Rest_Api $api, array $config ) {
		$this->index_label = $index_label;
		$this->api         = $api;
		$this->config      = $config;
	}

	/**
	 * @return string
	 */
	public function get_index_label() {
		return $this->index_label;
	}

	/**************************************************************************************************************
	 *
	 * Vespa REST API calls
	 *
	 *************************************************************************************************************/

	/**
	 * @param array $settings
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function _create_session_id( array $settings ) {
		$result = $this->api->post( '/application/v2/tenant/default/session?from=%s/application/v2/tenant/default/application/default/environment/default/region/default/instance/default', [ $this->api->generate_path() ], $settings );
		if ( empty( $session_id = $result->get_body_session_id() ) ) {
			throw new \Exception( 'A session id could not be created.' );
		}

		return $session_id;
	}

	/**
	 * @param string $session_id
	 * @param array $files
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	protected function _application_upload_files( array $files, string $session_id = '' ) {

		if ( empty( $session_id ) ) {
			$session_id = $this->_create_session_id( [] );
		}

		foreach ( $files as $file => $file_content ) {

			$result = $this->api->put_binary_content( '/application/v2/tenant/default/session/%s/content/%s',
				[ $session_id, $file ],
				$this->_format_content( $file, $file_content )
			);

			if ( ! $result->is_http_code_200() || empty( $result->get_body_prepared() ) ) {
				throw new \Exception( sprintf( 'Problem during upload of index content file "%s"', esc_html( $file ) ) );
			}

		}
	}

	/**
	 * @param string $session_id
	 * @param string[] $files
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _application_download_files( array $files, string $session_id = '' ): array {
		$results = [];

		if ( empty( $session_id ) ) {
			$session_id = $this->_create_session_id( [] );
		}

		foreach ( $files as $file ) {

			try {

				$result = $this->api->get( '/application/v2/tenant/default/session/%s/content/%s', [
					$session_id,
					$file
				], [] );

				$results[ $file ] = $this->_format_content( $file, $result->get_body_raw_content() );

			} catch ( \Exception $e ) {
				// Nothing
			}
		}

		return $results;
	}

	/**
	 * @param string $session_id
	 * @param string[] $files
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _application_delete_files( array $files, string $session_id = '' ): array {
		$results = [];

		if ( empty( $session_id ) ) {
			$session_id = $this->_create_session_id( [] );
		}

		foreach ( $files as $file ) {

			try {

				$result = $this->api->delete( '/application/v2/tenant/default/session/%s/content/%s', [
					$session_id,
					$file
				], [] );

				$results[ $file ] = '';

			} catch ( \Exception $e ) {
				// Nothing
			}
		}

		return $results;
	}

	/**
	 * @param string $session_id
	 * @param array $settings
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	protected function _application_prepare_active( string $session_id, array $settings ) {
		$result = $this->api->put( '/application/v2/tenant/default/session/%s/prepared?applicationName=%s',
			[ $session_id, static::VESPA_DEFAULT_APPLICATION ],
			$settings
		);

		if ( $result && ( false !== strpos( $result->get_body_raw_content(), 'WARNING' ) ) ) {
			set_transient( get_current_user_id() . 'wpsolr_index_settings_warning', $result->get_body_raw_content() );
		}

		$this->api->put( '/application/v2/tenant/default/session/%s/active',
			[ $session_id ],
			$settings
		);

		/**
		 * Check deployement is complete: https://docs.vespa.ai/en/application-packages.html#convergence
		 */
		foreach ( range( 0, 5 ) as $loop ) {
			$result = $this->api->get( '/application/v2/tenant/default/application/default/environment/prod/region/default/instance/default/serviceconverge',
				[],
				$settings
			);

			if ( $result->get_body_converged() ) {
				// Deployement is finished: continue
				break;
			}

			sleep( 10 );
		}


		return $result;
	}

	/**
	 * @param array $settings
	 * @param array $schema
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function create_index( array $settings, array $schema_data ) {
		$session_id = $this->_create_session_id( $settings );

		/**
		 * Download application files
		 */
		$files = $this->_application_download_files( [
			static::SERVICES_PATTERN,
			$this->get_schema_generated_file_name(),
			$this->get_schema_custom_file_name(),
		], $session_id );


		/**
		 * Modify services file to add the schema if necessary
		 */
		$files[ static::SERVICES_PATTERN ] = $this->_add_schema_to_services_file( $files[ static::SERVICES_PATTERN ] );

		/**
		 * Modify services file to add the model if necessary
		 */
		$files[ static::SERVICES_PATTERN ] = $this->_add_component_to_services_file( $files[ static::SERVICES_PATTERN ] );

		/**
		 * Generate schema file if not already present
		 */
		if ( ! isset( $files[ $this->get_schema_generated_file_name() ] ) ) {
			$files[ $this->get_schema_generated_file_name() ] = $this->generate_twig_content( self::SCHEMA_TEMPLATE_GENERATED_TWIG, static::TWIG_SCHEMA_TEMPLATE_PATTERN, $schema_data );
		}
		if ( ! isset( $files[ $this->get_schema_custom_file_name() ] ) ) {
			$files[ $this->get_schema_custom_file_name() ] = $this->generate_twig_content( self::SCHEMA_TEMPLATE_CUSTOM_TWIG, static::TWIG_SCHEMA_TEMPLATE_PATTERN, $schema_data );
		}

		/**
		 * Upload
		 */
		$this->_application_upload_files( $files, $session_id );

		/**
		 * Prepare and activate the application modifications
		 */
		return $this->_application_prepare_active( $session_id, $settings );
	}

	/**
	 * @param array $settings
	 *
	 * @throws \Exception
	 */
	public function update_index( array $settings ) {
		return $this->api->put( '/v1/schema', [ $this->index_label ], $settings );
	}

	public function get_index_fields_definitions() {
		$results = $this->api->get( '/v1/schema/%s', [ $this->index_label ], [] );

		return $results->get_fields();
	}

	/**
	 * @param array $field_definition
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function add_index_field_definition( array $field_definition ) {
		return $this->api->post( '/v1/schema/%s/properties', [ $this->index_label ], $field_definition );
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function has_index(): bool {

		/**
		 * Is application already deployed?
		 */
		try {
			$this->api->get( static::URL_CONFIG_V2_TENANT_APPLICATION, [
				static::VESPA_DEFAULT_TENANT,
				static::VESPA_DEFAULT_APPLICATION
			], [] );
		} catch ( \Exception $e ) {
			if ( str_contains( $e->getMessage(), 'NOT_FOUND' ) ) {
				// Deploy application before creation the index
				$this->api->post_binary_content( static::URL_APPLICATION_V2_TENANT_PREPAREANDACTIVATE, [
					static::VESPA_DEFAULT_TENANT
				], file_get_contents( WPSOLR_Admin_Utilities::convert_dropbox_url_from_html_to_zip( static::ZIP_NEW_APPLICATION_PACKAGE ) ) );

			} else {
				throw $e;
			}
		}


		/**
		 * Is index already deployed?
		 */
		try {
			/**
			 * Download index configuration files from the application session
			 */
			$results = $this->_application_download_files( $files = [
				static::SERVICES_PATTERN,
				$this->get_schema_generated_file_name(),
				$this->get_schema_custom_file_name(),
			] );

			return ( count( $files ) === count( $results ) );

		} catch ( \Exception $e ) {
			if ( str_contains( $e->getMessage(), 'NOT_FOUND' ) ) {
				return false;
			} else {
				throw $e;
			}
		}

	}

	/**
	 * @throws \Exception
	 */
	public function delete_index() {
		$session_id = $this->_create_session_id( [] );

		/**
		 * Download application files
		 */
		$files = $this->_application_download_files( [
			static::SERVICES_PATTERN,
			static::VALIDATION_OVERRIDES_PATTERN,
		], $session_id );


		/**
		 * Remove schema from services.xml
		 */
		$files[ static::SERVICES_PATTERN ] = $this->_remove_schema_from_services_file( $files[ static::SERVICES_PATTERN ] );

		/**
		 * Modify validation overrides to add/remove the schema if necessary
		 */
		$this->_set_validation_overrides( $files );


		/**
		 * Upload
		 */
		$this->_application_upload_files( $files, $session_id );

		/**
		 * Prepare and activate the application modifications
		 */
		$this->_application_prepare_active( $session_id, [] );


		/**
		 * Remove unused application files with a new session
		 */
		$session_id = $this->_create_session_id( [] );
		$this->_application_delete_files( [
			static::VALIDATION_OVERRIDES_PATTERN,
			$this->get_schema_generated_file_name(),
			$this->get_schema_custom_file_name(),
		], $session_id );
		$result = $this->_application_prepare_active( $session_id, [] );

		return $result;
	}

	/**
	 * @param array $schema
	 *
	 * @return void
	 */
	public function update_schema( array $schema ) {
		$session_id = $this->_create_session_id( [] );

		/**
		 * Download schema files
		 */
		$files = $this->_application_download_files( [
			$this->get_schema_generated_file_name(),
			static::VALIDATION_OVERRIDES_PATTERN,
		], $session_id );


		/**
		 * Modify validation overrides to add/remove the schema if necessary
		 */
		/**
		 * Modify validation overrides to add/remove the schema if necessary
		 */
		$this->_set_validation_overrides( $files );

		/**
		 * Update schema
		 */
		$files[ $this->get_schema_generated_file_name() ] = $this->generate_twig_content(
			self::SCHEMA_TEMPLATE_GENERATED_TWIG, static::TWIG_SCHEMA_TEMPLATE_PATTERN, $schema );

		/**
		 * Upload
		 */
		$this->_application_upload_files( $files, $session_id );

		/**
		 * Prepare and activate the application modifications
		 */
		$this->_application_prepare_active( $session_id, [] );

		// Wait
		//sleep( 30 );
	}

	/**
	 *
	 * @return string
	 */
	public function get_schema_generated() {
		$session_id = $this->_create_session_id( [] );

		/**
		 * Download schema files
		 */
		$files = $this->_application_download_files( [
			$this->get_schema_generated_file_name(),
		], $session_id );

		return $files[ $this->get_schema_generated_file_name() ] ?? '';
	}

	/**
	 *
	 * @return string
	 */
	public function get_schema_custom() {
		$session_id = $this->_create_session_id( [] );

		/**
		 * Download schema files
		 */
		$files = $this->_application_download_files( [
			$this->get_schema_custom_file_name(),
		], $session_id );

		return $files[ $this->get_schema_custom_file_name() ] ?? '';
	}

	/**
	 * @https://docs.vespa.ai/en/reference/document-select-language.html
	 *
	 * @param string $id
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function delete_object_id( string $id ) {
		return $this->api->delete( '/document/v1/%s/%s/docid/%s', [
			$this->index_label, // namespace
			$this->index_label, // schema
			$id
		] );
	}

	/**
	 * @param string $selection
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function delete_objects( string $selection ) {
		return $this->api->delete( "/document/v1/%s/%s/docid?cluster=%s&selection=%s", [
			$this->index_label, // namespace
			$this->index_label, // schema
			$this->_get_service_content_id(), // cluster
			$selection
		] );
	}

	/**
	 * @param array $query
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function search( array $query ): WPSOLR_Php_Rest_Api_Response {
		return $this->api->post( '/search/', [], $query );
	}

	/**
	 * @href https://docs.vespa.ai/en/document-v1-api-guide.html#create-if-nonexistent - http://localhost:8080/document/v1/wpsolr1/wpsolr1/docid/47?create=true
	 *
	 * @param array $documents
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function index_objects( array $documents ): WPSOLR_Php_Rest_Api_Response {
		return $this->api->post( '/document/v1/%s/%s/docid/%%s?create=true', [
			$this->index_label, // namespace
			$this->index_label, // schema
		], $documents, true );

	}

	/**
	 * @param string $file
	 * @param array $data
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function generate_twig_content( string $file, string $folder, array $data ): string {
		return WPSOLR_Service_Container::get_template_builder()->load_template(
			[
				'template_file' => sprintf( $folder, $file ),
				'template_args' => 'data',
			],
			$data
		);
	}

	/**
	 * @return mixed|string
	 */
	protected function _get_service_content_id(): mixed {
		return ( $this->config['extra_parameters']['vespa_service_content_id'] ?? '' ) ?: static::DEFAULT_SERVICE_CONTENT_ID;
	}

	/**
	 * @return mixed|string
	 */
	protected function _get_service_container_id(): mixed {
		return ( $this->config['extra_parameters']['vespa_service_container_id'] ?? '' ) ?: static::DEFAULT_SERVICE_CONTAINER_ID;
	}

	/**
	 * @return string
	 */
	protected function _get_index_text_vectorizer_id(): string {
		return $this->config['extra_parameters']['text_vectorizer_id'] ?: '';
	}

	/**
	 * @return string
	 */
	protected function get_schema_generated_file_name(): string {
		return sprintf( static::SCHEMA_PATTERN, sprintf( '%s_generated', $this->index_label ) );
	}

	/**
	 * @return string
	 */
	protected function get_schema_custom_file_name(): string {
		return sprintf( static::SCHEMA_PATTERN, $this->index_label );
	}

	/**
	 * @param string $file_name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_application_file_content( string $file_name ): string {

		if ( $file_name === 'schema_generated.sd' ) {
			$file_name = $this->get_schema_generated_file_name();
		}
		if ( $file_name === 'schema_custom.sd' ) {
			$file_name = $this->get_schema_custom_file_name();
		}

		if ( empty( $result = $this->_application_download_files( [ $file_name ] )[ $file_name ] ) ) {
			throw new \Exception( sprintf( '%s could not be found.', esc_html( $file_name ) ) );
		}

		return str_replace( "\'", "'", $result );
	}

	/**
	 * @param string $file_name
	 * @param string $file_content
	 *
	 * @throws \Exception
	 */
	public function deploy_application_file_content( string $file_name, string $file_content ) {

		if ( $file_name === 'schema_generated.sd' ) {
			$file_name = $this->get_schema_generated_file_name();
		}
		if ( $file_name === 'schema_custom.sd' ) {
			$file_name = $this->get_schema_custom_file_name();
		}

		$files = [ $file_name => $file_content, ];

		/**
		 * Add validation overrides
		 */
		$this->_set_validation_overrides( $files );

		/**
		 * Upload
		 */
		$session_id = $this->_create_session_id( [] );
		$this->_application_upload_files( $files, $session_id );

		/**
		 * Prepare and activate the application modifications
		 */
		$this->_application_prepare_active( $session_id, [] );
	}

	/**
	 * @param array $files
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function _set_validation_overrides( array &$files ): void {

		$today = wp_date( "Y-m-d", time() );

		$files[ static::VALIDATION_OVERRIDES_PATTERN ] = $this->generate_twig_content(
			self::VALIDATION_OVERRIDES_TWIG, static::TWIG_SCHEMA_ROOT_PATTERN,
			[
				[
					'until'         => $today,
					'validation_id' => self::VALIDATION_OVERRIDES_VALIDATION_ID_REMOVE_SCHEMA,
				],
				[
					'until'         => $today,
					'validation_id' => self::VALIDATION_OVERRIDES_VALIDATION_ID_INDEXING_CHANGE,
				],
				[
					'until'         => $today,
					'validation_id' => self::VALIDATION_OVERRIDES_VALIDATION_ID_FIELD_TYPE_CHANGE,
				],
			] );
	}

	/**
	 * Prettify an XML string
	 *
	 * @param string $files
	 *
	 * @return string
	 */
	protected function _format_xml_string( string $files ): string {
		$dom_xml                     = new DOMDocument();
		$dom_xml->preserveWhiteSpace = false;
		$dom_xml->formatOutput       = true;
		$dom_xml->loadXML( $files );

		return $dom_xml->saveXML();
	}

	/**
	 *
	 * Format file content
	 *
	 * @param string $file
	 * @param string $content
	 *
	 * @return string
	 */
	protected function _format_content( string $file, string $content ): string {

		if ( str_ends_with( $file, '.xml' ) ) {
			return $this->_format_xml_string( $content );
		}

		if ( str_ends_with( $file, '.sd' ) ) {
			$content = $this->_replace_caracters( $content, "\t", ' ' ); // Unix
			$content = $this->_replace_caracters( $content, '  ', ' ' );
			$content = $this->_replace_caracters( $content, "\n ", "\n" ); // Unix
			$content = $this->_replace_caracters( $content, "\n\n", "\n" ); // Unix
			$content = $this->_indent_lines_inside_brackets( $content );
			$content = $this->_add_carriage_after_specific_closing_braces( $content );
		}

		return $content;
	}

	function _replace_caracters( $input, $find, $replace ) {
		// Replace double (or more) spaces with a single space
		$output = str_replace( $find, $replace, $input );

		// Recurse if needed
		if ( $output !== $input ) {
			return $this->_replace_caracters( $output, $find, $replace );
		}

		return $output;
	}

	/**
	 * @param string $input
	 * @param string $indentation
	 *
	 * @return string
	 */
	function _indent_lines_inside_brackets( string $input, string $indentation = "  " ): string {
		$output      = "";
		$lines       = explode( "\n", $input );
		$indentLevel = 0;

		foreach ( $lines as $line ) {
			$trimmedLine = trim( $line );

			if ( strpos( $trimmedLine, '{' ) !== false ) {
				$output .= str_repeat( $indentation, $indentLevel ) . $trimmedLine . "\n";
				$indentLevel ++;
			} elseif ( strpos( $trimmedLine, '}' ) !== false ) {
				$indentLevel = max( 0, $indentLevel - 1 );
				$output      .= str_repeat( $indentation, $indentLevel ) . $trimmedLine . "\n";
			} else {
				$output .= str_repeat( $indentation, $indentLevel ) . $line . "\n";
			}
		}

		return $output;
	}

	/**
	 * @param string $input
	 *
	 * @return string
	 */
	function _add_carriage_after_specific_closing_braces( string $input ): string {
		/**
		 * Add a "\n" after "}\n" if directly followed with another "}"
		 *
		 */
		return preg_replace( "/(}\n)([^}]*\w)/", "$1\n$2", $input );
	}

	/**
	 * Modify services file to remove the schema
	 *
	 * @param string $services_file_content
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function _remove_schema_from_services_file( string $services_file_content ): string {
		$file_content = new SimpleXMLElement( $services_file_content );
		$documents    = $file_content->xpath( sprintf( '//content[@id="%s"]', $this->_get_service_content_id() ) )[0]->documents;
		if ( $document = $documents->xpath( sprintf( 'document[@type="%s"]', $this->index_label ) ) ) {
			// https://stackoverflow.com/questions/9643116/deleting-simplexmlelement-node
			unset( $document[0][0] );

			// XML content
			return $file_content->asXML();
		}

		return $services_file_content;
	}

	/**
	 * Modify services file to add the schema if necessary
	 *
	 * @param string $services_file_content
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function _add_schema_to_services_file( string $services_file_content ): string {

		$file_content = new SimpleXMLElement( $services_file_content );

		$content_id = $this->_get_service_content_id();
		$content    = $this->_get_services_element( $file_content, 'content', $content_id );

		$container_id = $this->_get_service_container_id();
		$container    = $this->_get_services_element( $file_content, 'container', $container_id );

		$documents = $content[0]->documents;
		if ( ! $documents->xpath( sprintf( 'document[@type="%s"]', $this->index_label ) ) ) {
			$new_document = $documents->addChild( 'document' );
			$new_document->addAttribute( 'type', $this->index_label );
			$new_document->addAttribute( 'mode', 'index' );

			// Update content
			$services_file_content = $file_content->asXML();
		}

		/**
		 * Add stemmer
		 */
		if ( ! $documents->xpath( sprintf( 'document-processing[@cluster="%s"]', $container_id ) ) ) {
			$new_document = $documents->addChild( 'document-processing' );
			$new_document->addAttribute( 'cluster', $container_id );

			// Update content
			$services_file_content = $file_content->asXML();
		}

		return $services_file_content;
	}

	/**
	 * Modify services file to add/remove the model if necessary
	 * https://docs.vespa.ai/en/reference/embedding-reference.html
	 *
	 * @param array $files
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function _add_component_to_services_file( string $services_file_content ): string {
		$file_content            = new SimpleXMLElement( $services_file_content );
		$services_application_id = $this->_get_service_container_id();
		if ( empty( $services_container = $file_content->xpath( sprintf( '//container[@id="%s"]', $services_application_id ) ) ) ) {
			$current_services_container_ids = [];
			foreach ( $file_content->xpath( '//container' ) as $element ) {
				$current_services_container_ids[] = (string) $element['id'];
			}

			throw new \Exception( sprintf( 'Please choose among your current services.xml container id(s): %s.',
				esc_html( implode( ',', $current_services_container_ids ) ) ) );
		}

		/*
		foreach ( $services_container[0]->xpath( 'component[starts-with(@id, "wpsolr")]' ) as $component ) {
			// https://stackoverflow.com/questions/9643116/deleting-simplexmlelement-node
			unset( $component[0][0] );

			// Update content
			$files[ static::SERVICES_PATTERN ] = $file_content->asXML();
		}
		*/

		/*
		 * Add stemmer component
		*/
		if ( ! $services_container[0]->xpath( 'document-processing' ) ) {
			$services_container[0]->addChild( 'document-processing' );
			$services_file_content = $file_content->asXML();
		}
		if ( ! $services_container[0]->xpath( sprintf( 'component[@id="%s"]', static::COM_YAHOO_LANGUAGE_SIMPLE_SIMPLE_LINGUISTICS ) ) ) {
			$new_component = $services_container[0]->addChild( 'component' );
			$new_component->addAttribute( 'id', static::COM_YAHOO_LANGUAGE_SIMPLE_SIMPLE_LINGUISTICS );
			$services_file_content = $file_content->asXML();
		}

		/**
		 * Add embedder component
		 */
		if ( ! empty( $text_vectorizer_id = $this->_get_index_text_vectorizer_id() ) &&
		     ! empty( $text_vectorizer_def = WPSOLR_Hosting_Api_Vespa_None::get_components_vectorizer_text()[ $text_vectorizer_id ] )
		) {

			if ( ! $services_container[0]->xpath( sprintf( 'component[@id="%s"]', $text_vectorizer_id ) ) ) {
				$new_component = $services_container[0]->addChild( 'component' );
				$new_component->addAttribute( 'id', $text_vectorizer_id );
				$new_component->addAttribute( 'type', $text_vectorizer_def['type'] );

				if ( isset( $text_vectorizer_def['transformer-model-url'] ) ) {
					$new_component->addChild( 'transformer-model' )->addAttribute( 'url', $text_vectorizer_def['transformer-model-url'] );
				}
				if ( isset( $text_vectorizer_def['tokenizer-model-url'] ) ) {
					$new_component->addChild( 'tokenizer-model' )->addAttribute( 'url', $text_vectorizer_def['tokenizer-model-url'] );
				}

				if ( isset( $text_vectorizer_def['pooling-strategy'] ) ) {
					$new_component->addChild( 'pooling-strategy', $text_vectorizer_def['pooling-strategy'] );
				}
				if ( isset( $text_vectorizer_def['normalize'] ) ) {
					$new_component->addChild( 'normalize', $text_vectorizer_def['normalize'] );
				}
			}

			$services_file_content = $file_content->asXML();
		}

		return $services_file_content;
	}

	/**
	 * @param SimpleXMLElement $file_content
	 * @param string $element
	 * @param mixed $element_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _get_services_element( SimpleXMLElement $file_content, string $element, string $element_id ): array {
		if ( empty( $result = $file_content->xpath( sprintf( '//%s[@id="%s"]', $element, $element_id ) ) ) ) {
			$current_element_ids = [];
			foreach ( $file_content->xpath( sprintf( '//%s', $element ) ) as $existing_element ) {
				$current_element_ids[] = (string) $existing_element['id'];
			}

			throw new \Exception(
				sprintf( 'Please choose among your current services.xml %s id(s): %s.',
					esc_html( $element ), esc_html( implode( ',', $current_element_ids ) ) )
			);
		}

		return $result;
	}
}