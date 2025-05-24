<?php

namespace wpsolr\core\classes\engines\elasticsearch_php;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use ReflectionClass;
use wpsolr\core\classes\engines\WPSOLR_Client;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Error_Handling;
use wpsolr\core\classes\utilities\WPSOLR_Option;

/**
 * Some common methods of the Elasticsearch client.
 *
 */
trait WPSOLR_ElasticsearchClient {
	use WPSOLR_Client;

	protected $wpsolr_type = 'wpsolr_types';

	// Unique id to store attached decoded files.
	protected $WPSOLR_DOC_ID_ATTACHMENT = 'wpsolr_doc_id_attachment';

	/** @var Client */
	protected $search_engine_client;

	/** @var string */
	protected $index_label;

	// Index conf files
	protected $FILE_CONF_ES_INDEX_5 = 'wpsolr_index_5.json';
	protected $FILE_CONF_ES_INDEX_6 = 'wpsolr_index_6.json';
	protected $FILE_CONF_ES_INDEX_7 = 'wpsolr_index_7.json';

	/**
	 * Try to fix the current index configuration before retrying
	 *
	 * @param $error_msg
	 * @param array $documents
	 * @param array $formatted_docs
	 *
	 * @return bool
	 */
	protected function _try_to_fix_error_doc_type( $error_msg, $documents = [], $formatted_docs = [] ) {

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
	 * @return array
	 */
	public
	function get_index() {

		$params = [ 'index' => $this->index_label ];

		if ( $this->index && ! empty( $this->_get_index_doc_type() ) ) {
			$params['type'] = $this->_get_index_doc_type();
		}

		return $params;
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
	 * @param string $index_label
	 */
	public
	function set_index_label(
		$index_label
	) {
		$this->index_label = $index_label;
	}

	/**
	 * @return string
	 */
	public
	function get_index_label() {
		return $this->index_label;
	}

	/**
	 * @param $config
	 *
	 * @return Client
	 */
	protected
	function create_search_engine_client(
		$config
	) {

		/**
		 * Prevent elasticsearch_php deprecation notices like in Bulk.php: @trigger_error('Specifying types in urls has been deprecated', E_USER_DEPRECATED);
		 * Only caught when WP_DEBUG is true
		 **/
		WPSOLR_Error_Handling::deactivate_deprecated_warnings();

		$client = $this->_get_client_builder();

		/**
		 * If necessary, generate a certificate authority file for the index
		 */
		if ( ! empty( $self_cacert_options_content = $config["extra_parameters"]["index_cert_1"] ) ) {
			if ( empty( $self_cacert_file_content = file_get_contents( $this->_get_self_cacert_file( $config['index_label'] ) ) ) ||
			     ( $self_cacert_options_content !== $self_cacert_file_content )
			) {
				// Create the ca file, or recreate it (for instance after a clone of the WP filesystem)
				file_put_contents( $this->_get_self_cacert_file( $config['index_label'] ), $self_cacert_options_content );
			}
			$client->setSSLVerification( $this->_get_self_cacert_file( $config['index_label'] ) );
		}


		$this->_before_client_build( $config, $client );

		$this->set_index_label( empty( $config ) ? '' : $config['index_label'] );

		return $client->build();
	}

	/**
	 * Load the content of a conf file.
	 *
	 * @return array
	 */
	protected
	function get_and_decode_configuration_file() {

		/**
		 * Get config file path, even called from subclass's directory
		 */
		$rc   = new ReflectionClass( get_class( $this ) );
		$file = dirname( $rc->getFileName() ) . '/' . $this->_get_configuration_file_from_version();

		$file_json = file_get_contents( $file );

		if ( empty( $file_json ) ) {
			throw new \Exception( sprintf( 'Missing configuration file %s', esc_html( $file ) ) );
		}
		/**
		 * Analyser to apply
		 */
		$index_analyser_id = $this->config['extra_parameters'][ WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ];
		$index_analyser_id = empty( trim( $index_analyser_id ) ) ? 'english' : $index_analyser_id;
		$file_json         = str_replace( '{{wpsolr_default_index_analyser}}', $index_analyser_id, $file_json );
		$file_json         = str_replace( '{{wpsolr_default_search_analyser}}', $index_analyser_id, $file_json );


		return json_decode( $file_json, true );
	}

	/**
	 * Retrieve the live Elasticsearch version
	 *
	 * @return string
	 * @throws \Exception
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
			throw new \Exception( sprintf( 'WPSOLR works only with Elasticsearch >= 5. Your version is %s.', esc_html( $version ) ) );
		}

		return $version;
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

		if ( is_int( $date_str ) ) {

			$timestamp = $date_str;

		} else {

			$timestamp = strtotime( $date_str );
		}

		$string = wp_date( 'Y-m-d\TH:i:s\Z', $timestamp );

		return $string;
	}

	protected
	function get_index_stats() {
		return $this->search_engine_client->indices()->stats( $this->get_index() );
	}

	/**
	 * Create a match_all query
	 *
	 * @return array
	 */
	protected
	function _create_match_all_query() {

		$params         = $this->get_index();
		$params['body'] = [ 'query' => [ 'match_all' => new \stdClass() ] ];

		return $params;
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

		$params         = $this->get_index();
		$params['body'] = [ 'query' => [ 'bool' => $bool_query ] ];

		return $params;
	}

	/**
	 * Create a query
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	protected
	function _create_query(
		$query
	) {

		$params         = $this->get_index();
		$params['body'] = $query;

		return $params;
	}

	/**
	 * Get the analysers available
	 * @return array
	 */
	static public function get_analysers() {
		/*
		 * https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html
		 */

		return [
			'arabic'     => [],
			'armenian'   => [],
			'basque'     => [],
			'bengali'    => [],
			'brazilian'  => [],
			'bulgarian'  => [],
			'catalan'    => [],
			'cjk'        => [],
			'czech'      => [],
			'danish'     => [],
			'dutch'      => [],
			'english'    => [ 'is_default' => true, ],
			'estonian'   => [],
			'finnish'    => [],
			'french'     => [],
			'galician'   => [],
			'german'     => [],
			'greek'      => [],
			'hindi'      => [],
			'hungarian'  => [],
			'indonesian' => [],
			'irish'      => [],
			'italian'    => [],
			'latvian'    => [],
			'lithuanian' => [],
			'norwegian'  => [],
			'persian'    => [],
			'portuguese' => [],
			'romanian'   => [],
			'russian'    => [],
			'sorani'     => [],
			'spanish'    => [],
			'swedish'    => [],
			'turkish'    => [],
			'thai'       => [],
		];
	}

	/**
	 * @return string
	 */
	protected function _get_configuration_file_from_version(): string {
		try {

			$version = $this->search_engine_client->info()['version']['number'];

			$file = $this->FILE_CONF_ES_INDEX_7;
			if ( version_compare( $version, '6', '<' ) ) {

				$file = $this->FILE_CONF_ES_INDEX_5;

			} elseif ( version_compare( $version, '7', '<' ) ) {

				$file = $this->FILE_CONF_ES_INDEX_6;
			}

		} catch ( \Exception $e ) {
			// Elasticpress does not give access to cluster infos

			$file = $this->FILE_CONF_ES_INDEX_6;
		}

		return $file;
	}

	/**
	 * @return ClientBuilder
	 */
	protected function _get_client_builder() {
		return ClientBuilder::create();
	}

	/**
	 * Generate a cacert file for an index.
	 * Stored in WP root to prevent public http access:  /var/www/html/wpsolr_index_my-index-label_cacert.pem
	 *
	 * @param string $index_label
	 *
	 * @return string
	 */
	protected function _get_self_cacert_file( string $index_label ): string {
		// Ensure get_home_path() is declared.
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$file = sprintf( '%swpsolr_index_%s_node_cert.pem', get_home_path(), $index_label );

		return $file;
	}

	/**
	 * @param $config
	 * @param ClientBuilder $client
	 *
	 * @return void
	 */
	protected function _before_client_build( $config, $client ): void {

		$host = sprintf( '%s://%s:%s', $config['scheme'], $config['host'], $config['port'] );
		$client->setHosts( [ $host ] );
		$client->setBasicAuthentication( $config['username'], $config['password'] );
	}

}
