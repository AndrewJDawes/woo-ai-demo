<?php

namespace wpsolr\core\classes\engines;

use wpsolr\core\classes\engines\elasticsearch_php\WPSOLR_IndexElasticsearchClient;
use wpsolr\core\classes\engines\opensearch_php\WPSOLR_IndexOpenSearchClient;
use wpsolr\core\classes\engines\redisearch_php\WPSOLR_Index_RediSearch_Client;
use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\engines\vespa\WPSOLR_Index_Vespa_Client;
use wpsolr\core\classes\engines\weaviate\WPSOLR_Index_Weaviate_Client;
use wpsolr\core\classes\exceptions\WPSOLR_Exception_Locking;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\models\post\WPSOLR_Model_Meta_Type_Post;
use wpsolr\core\classes\models\WPSOLR_Model_Abstract;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\models\WPSOLR_Model_Meta_Type_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_AbstractIndexClient
 * @package wpsolr\core\classes\engines
 */
abstract class WPSOLR_AbstractIndexClient_Root extends WPSOLR_AbstractEngineClient {

	/**
	 * Events tracking
	 */
	const POST_TYPE_WPSOLR_EVENT = 'wpsolr-event';
	const CUSTOM_FIELD_NAME_EVENT_POST_ID = 'wpsolr_event_post_id'; // Post ID of the event created from current post
	const CUSTOM_FIELD_NAME_EVENT_INDICES = 'wpsolr_indices'; // Uuids of indices already containing current event
	const EVENTS_TRACKING = [
		WPSOLR_Query_Parameters::SEARCH_PARAMETER_EVENT_TRACKING_NAME_CLICK_RESULT   => [ 'label' => 'Click search result' ],
		WPSOLR_Query_Parameters::SEARCH_PARAMETER_EVENT_TRACKING_NAME_CLICK_FILTER   => [ 'label' => 'Click search filter' ],
		WPSOLR_Query_Parameters::SEARCH_PARAMETER_EVENT_TRACKING_NAME_PURCHASE_ORDER => [ 'label' => 'Purchase order' ],
	];

	// Posts table name
	const CONTENT_SEPARATOR = ' ';

	const SQL_DATE_NULL = '1000-01-01 00:00:00';
	const MAIN_SQL_LOOP = /** @lang text */
		'SELECT %s FROM %s %s WHERE %s ORDER BY %s %s';

	protected $solr_indexing_options;

	protected $last_post_infos_to_start = [
		'date' => self::SQL_DATE_NULL,
		'ID'   => '0',
	];
	const MODEL_LAST_POST_DATE_INDEXED = 'solr_last_post_date_indexed';

	const STOP_INDEXING_ID = 'action_stop_indexing';
	/**
	 * @var string
	 */
	protected $notice_text = '';
	protected array $_attached_files = [];

	/**
	 * @param array $config
	 * @param $solr_index_indice
	 * @param $post_language
	 *
	 * @return static
	 * @throws \Exception
	 */
	protected static function _create( array $config, $solr_index_indice, $post_language ) {

		switch ( ! empty( $config['index_engine'] ) ? $config['index_engine'] : static::ENGINE_SOLR ) {

			case static::ENGINE_SOLR:
			case static::ENGINE_SOLR_CLOUD:
				return new WPSOLR_IndexSolariumClient( $config, $solr_index_indice, $post_language );
				break;

			case static::ENGINE_ELASTICSEARCH:
				return new WPSOLR_IndexElasticsearchClient( $config, $solr_index_indice, $post_language );
				break;

			case static::ENGINE_OPENSEARCH:
				return new WPSOLR_IndexOpenSearchClient( $config, $solr_index_indice, $post_language );
				break;

			case static::ENGINE_WEAVIATE:
				return new WPSOLR_Index_Weaviate_Client( $config, $solr_index_indice, $post_language );
				break;

			case static::ENGINE_VESPA:
				return new WPSOLR_Index_Vespa_Client( $config, $solr_index_indice, $post_language );

			case static::ENGINE_REDISEARCH:
				return new WPSOLR_Index_RediSearch_Client( $config, $solr_index_indice, $post_language );
				break;

			default:
				throw new \Exception( sprintf( 'Unknown search engine "%s".', esc_html( $config['index_engine'] ?? '' ) ) );

		}
	}

	/**
	 * Use Tika to extract a file content.
	 *
	 * @param $file
	 *
	 * @return string
	 */
	abstract protected function search_engine_client_extract_document_content( $file );

	/**
	 * @return array
	 */
	public function get_search_engine_indexing_options() {
		return $this->solr_indexing_options;
	}

	/**
	 * Set a geolocation field values
	 *
	 * @param string $field_name
	 * @param string $lat
	 * @param string $long
	 *
	 * @return array
	 */
	public function get_geolocation_field_value( $field_name, $lat, $long ) {

		return [
			'field_name'  => $field_name,
			'field_value' => sprintf( '%s,%s', $lat, $long ),
		];
	}

	/**
	 * Add a text to the notice text
	 *
	 * @param string $notice_text
	 */
	public function add_notice_message( string $notice_text ) {
		$this->notice_text .= $notice_text;
	}

	/**
	 * Get the notice text
	 *
	 * @return string
	 */
	public function get_notice_message() {
		return $this->notice_text;
	}

	/**
	 * @return array
	 */
	public function get_indexing_options() {
		return $this->solr_indexing_options;
	}

	/**
	 * @param array $document_for_update
	 * @param string $field_name
	 * @param string $is_exists 'y' or 'n'
	 */
	public function set_field_is_exist( array &$document_for_update, string $field_name, string $is_exists ) {
		// See children
	}

	/**
	 * Execute a solarium query. Retry 2 times if an error occurs.
	 *
	 * @param $search_engine_client
	 * @param $update_query
	 *
	 * @return mixed
	 */
	protected function execute( $search_engine_client, $update_query ) {


		for ( $i = 0; ; $i ++ ) {

			try {

				$result = $this->search_engine_client_execute( $search_engine_client, $update_query );

				return $result;

			} catch ( \Exception $e ) {

				// Catch error here, to retry in next loop, or throw error after enough retries.
				if ( $i >= 3 ) {
					throw $e;
				}

				// Sleep 1 second before retrying
				sleep( 1 );
			}

		}

	}


	/**
	 * Retrieve the Solr index for a post (usefull for multi languages extensions).
	 *
	 * @param $post
	 *
	 * @return $this
	 */
	static function create_from_post( $post ) {

		// Get the current post language
		$post_language = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_LANGUAGE, null, $post );

		return static::create( null, $post_language );
	}

	/**
	 * @param null $solr_index_indice
	 * @param null $post_language
	 *
	 * @return $this
	 */
	static function create( $solr_index_indice = null, $post_language = null ) {

		// Build Solarium config from the default indexing Solr index
		WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );
		$options_indexes = new WPSOLR_Option_Indexes();
		$config          = $options_indexes->build_config( $solr_index_indice, $post_language, static::DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND );

		WPSOLR_Option_View::set_current_index_uuid( $solr_index_indice );

		return static::_create( $config, $solr_index_indice, $post_language );
	}

	/**
	 * WPSOLR_AbstractIndexClient constructor.
	 *
	 * @param $config
	 * @param null $solr_index_indice
	 * @param null $language_code
	 */
	public function __construct( $config, $solr_index_indice = null, $language_code = null ) {

		$this->init( $config );

		$path = plugin_dir_path( __FILE__ ) . '../../vendor/autoload.php';
		require_once $path;

		// Load options
		$this->solr_indexing_options = WPSOLR_Service_Container::getOption()->get_option_index();

		$this->index_indice = $solr_index_indice;

		$options_indexes = new WPSOLR_Option_Indexes();
		$this->index     = $options_indexes->get_index( $solr_index_indice );

		$this->search_engine_client = $this->create_search_engine_client( $config );
	}


	/**
	 * Delete all documents for some post types
	 *
	 * @param string[] $post_types
	 * @param string $site_id
	 *
	 * @return
	 */
	abstract protected function search_engine_client_delete_all_documents( $post_types = null, $site_id = '' );

	/**
	 * Delete all documents for some models
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $models
	 *
	 * @throws \Exception
	 */
	public
	function delete_documents(
		$process_id, $models = null
	) {

		// Reset docs first (and lock models)
		if ( is_null( $models ) ) {
			$this->reset_documents( $process_id, $this->models, $this->get_has_index_model_version() );
		} else {
			$this->reset_documents( $process_id, $models, $this->get_has_index_model_version() );
		}

		$site_id = $this->get_site_id_for_delete();

		// Delete all content

		if ( is_null( $models ) ) {

			$this->search_engine_client_delete_all_documents( null, $site_id );
			$this->unlock_models( $process_id, $this->models );

		} else {

			$this->search_engine_client_delete_all_documents( $this->get_models_post_types( $models ), $site_id );
			$this->unlock_models( $process_id, $models );
		}

	}

	/**
	 * Get post types for some models
	 *
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $models
	 *
	 * @return string[] post types
	 */
	protected function get_models_post_types( $models ) {


		$results = [];

		if ( ! is_null( $models ) ) {
			foreach ( $models as $model ) {
				$results[] = $model->get_type();
			}
		}

		return $results;
	}

	/**
	 * @param string $process_id
	 * @param null $models
	 * @param bool $is_delete
	 *
	 * @throws \Exception
	 */
	public
	function reset_documents(
		$process_id, $models = null, $is_delete = false
	) {

		if ( is_null( $models ) ) {
			$models = $this->get_models();
		}


		if ( is_null( $models ) ) {
			throw new \Exception( 'WPSOLR: reset on empty models.' );
		}

		if ( empty( $models = $this->_get_models_that_can_be_deleted( $models ) ) ) {
			return;
		}

		// Lock models
		$this->lock_models( $process_id, $models );

		// Store 0 in # of index documents
		static::set_index_indice_option_value( $models, 'solr_docs', 0 );

		// Reset last indexed post date
		static::reset_last_post_date_indexed( $models );

		// Update nb of documents updated/added
		static::set_index_indice_option_value( $models, 'solr_docs_added_or_updated_last_operation', - 1 );

		// Increment version of index for engines that cannot delete documents and instead use filters on version
		if ( $is_delete ) {
			$this->set_index_models_version( $models );
		}
	}

	/**
	 * How many documents were updated/added during last indexing operation
	 *
	 * @return int
	 */
	public
	function get_count_documents() {

		$nb_documents = $this->search_engine_client_get_count_document( $this->get_site_id() );

		// Store 0 in # of index documents
		static::set_index_indice_option_value( null, 'solr_docs', $nb_documents );

		return $nb_documents;

	}

	/**
	 * Delete a document.
	 *
	 * @param string $document_id
	 * @param WPSOLR_Model_Abstract $model
	 *
	 */
	abstract protected function search_engine_client_delete_document( $document_id, $model = null );

	/**
	 * @param WPSOLR_Model_Abstract $model
	 */
	public function delete_document( $model ) {

		if ( $this->get_has_index_model_version() ) {
			$this->index_data( false, 'default', [ WPSOLR_Model_Builder::get_model_type_object( $model->get_type() ) ],
				1, $model->get_data(), false, false
				, true );
		} else {
			$this->search_engine_client_delete_document( $this->generate_model_unique_id( $model ), $model );
		}
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract $model
	 *
	 * @return array
	 */
	public function get_last_post_date_indexed( WPSOLR_Model_Meta_Type_Abstract $model ) {

		$result = $this->get_index_indice_option_value( $model, static::MODEL_LAST_POST_DATE_INDEXED, $this->last_post_infos_to_start );

		return $result;
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $models
	 *
	 * @return mixed
	 */
	public function reset_last_post_date_indexed( $models ) {

		return $this->set_index_indice_option_value( $models, static::MODEL_LAST_POST_DATE_INDEXED, $this->last_post_infos_to_start );
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $models
	 *
	 */
	public function set_index_models_version( array $models ) {
		foreach ( $models as $model ) {
			$this->set_index_model_version( $model );
		}
	}

	public function set_index_model_version( WPSOLR_Model_Meta_Type_Abstract $model ) {
		// Remove non-alphanumeric characters (such as dashes and dots) and reduce to the desired length
		$version = $this->_generate_index_model_version( $model->get_type(), '' );
		$this->set_index_indice_option_value( [ $model ], static::OPTION_INDEX_MODEL_VERSION, $version );
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract $model
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public function set_last_post_date_indexed( WPSOLR_Model_Meta_Type_Abstract $model, $option_value ) {

		return $this->set_index_indice_option_value( [ $model ], static::MODEL_LAST_POST_DATE_INDEXED, $option_value );
	}

	/**
	 * Lock one model with a process
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model_Meta_Type_Abstract $model
	 *
	 * @throws \Exception
	 *
	 */
	public function lock_model( $process_id, WPSOLR_Model_Meta_Type_Abstract $model ) {

		$locked_post_types = WPSOLR_Service_Container::getOption()->get_option_locking_index_models( $this->index_indice );

		if ( ! empty( $locked_post_types[ $model->get_type() ] ) && ( $locked_post_types[ $model->get_type() ] !== $process_id ) ) {
			// This process tries to lock a post type already locked by another process.

			$locking_process_id = $locked_post_types[ $model->get_type() ];
			if ( static::STOP_INDEXING_ID === $locking_process_id ) {
				// Stop now
				$this->unlock_process( static::STOP_INDEXING_ID );
				throw new WPSOLR_Exception_Locking( sprintf( "Indexing stopped as requested, while indexing %s of index %s",
					esc_html( $model->get_type() ), esc_html( $this->config['index_label'] ) ) );
			}

			$crons         = WPSOLR_Service_Container::getOption()->get_option_cron_indexing();
			$process_label = ( isset( $crons[ $locking_process_id ] ) && isset( $crons[ $locking_process_id ]['label'] ) ) ? $crons[ $locking_process_id ]['label'] : $locking_process_id;

			throw new WPSOLR_Exception_Locking( sprintf( "%s is already indexing post type %s of index %s",
				esc_html( $process_label ), esc_html( $model->get_type() ), esc_html( $this->config['index_label'] ) ) );
		}

		$this->set_index_indice_option_value( [ $model ], WPSOLR_Option::OPTION_LOCKING, $process_id );
	}


	/**
	 * Lock models with a process
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $models
	 *
	 * @throws \Exception
	 *
	 */
	public function lock_models( $process_id, $models ) {

		foreach ( $models as $model ) {
			$this->lock_model( $process_id, $model );
		}
	}

	/**
	 * Unlock one model
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model_Meta_Type_Abstract $model
	 *
	 */
	public function unlock_model( $process_id, WPSOLR_Model_Meta_Type_Abstract $model ) {

		// Release the model lock
		$this->set_index_indice_option_value( [ $model ], WPSOLR_Option::OPTION_LOCKING, '' );
	}

	/**
	 * Unlock models
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $models
	 *
	 */
	public function unlock_models( $process_id, $models ) {

		// Release the model lock
		$this->set_index_indice_option_value( $models, WPSOLR_Option::OPTION_LOCKING, '' );
	}

	/**
	 * Is a cron locked ?
	 *
	 * @param $cron_uuid
	 */
	static function is_locked( $process_id ) {

		$lockings = WPSOLR_Service_Container::getOption()->get_option_locking();

		foreach ( $lockings as $index_uuid => $locking ) {
			if ( array_search( $process_id, $locking, true ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Unlock a process
	 *
	 * @param $cron_uuid
	 */
	static function unlock_process( $process_id ) {

		$lockings = WPSOLR_Service_Container::getOption()->get_option_locking();

		foreach ( $lockings as $index_uuid => &$locking ) {
			foreach ( $locking as $post_type => $locking_process_id ) {
				if ( $process_id === $locking_process_id ) {
					$locking[ $post_type ] = ( static::STOP_INDEXING_ID === $process_id ) ? '' : static::STOP_INDEXING_ID;
				}
			}
		}

		update_option( WPSOLR_Option::OPTION_LOCKING, $lockings );
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $models
	 * @param $option_name
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public function set_index_indice_option_value( $models, $option_name, $option_value ) {

		$option = get_option( $option_name, null );

		if ( ! isset( $option ) ) {
			$option                        = [];
			$option[ $this->index_indice ] = [];
		}

		if ( is_null( $models ) ) {

			// Compatibility with post types models stored without the table name
			$option[ $this->index_indice ] = $option_value;

		} else {

			// Ensure compatibility
			$this->update_old_indice_format( $option, $this->index_indice );

			foreach ( $models as $model ) {
				$option[ $this->index_indice ][ $model->get_type() ] = $option_value;
			}
		}

		update_option( $option_name, $option );

		return $option_value;
	}

	/**
	 * Count nb documents remaining to index for a solr index
	 *
	 * @param WPSOLR_Model_Meta_Type_Abstract $model
	 *
	 * @return int Nb documents remaining to index
	 */
	public
	function get_count_nb_documents_to_be_indexed(
		WPSOLR_Model_Meta_Type_Abstract $model
	) {

		return $this->index_data( false, 'default', [ $model ], 0, null );
	}

	/**
	 * @param bool $is_stopping
	 * @param string $process_id Process calling the indexing method
	 * @param WPSOLR_Model_Meta_Type_Abstract[] $model_types
	 * @param int $batch_size
	 * @param null $post
	 *
	 * @param bool $is_debug_indexing
	 * @param bool $is_only_exclude_ids
	 * @param array $test_events_settings
	 * @param string $index_model_version
	 *
	 * @return array|int
	 * @throws WPSOLR_Exception_Locking
	 */
	public
	function index_data(
		$is_stopping, $process_id, $model_types, $batch_size = 100, $post = null,
		$is_debug_indexing = false, $is_only_exclude_ids = false
		, $is_deleted = false
	) {

		global $wpdb;

		//$this->unlock_all_models();

		$model_types = ( is_null( $model_types ) ? $this->set_default_models() : $model_types );


		// Needs locking only on "real" indexing
		$is_needs_locking = is_null( $post ) && ! empty( $batch_size ) && ! $is_only_exclude_ids;

		// Debug variable containing debug text
		$debug_text = '';

		$doc_count         = 0;
		$no_more_posts     = 0;
		$models_nb_results = [];
		foreach ( $model_types as $model_type ) {

			$index_model_version = '';
			if ( $this->get_has_index_model_version() ) {
				$index_model_version = $is_deleted ? 'wpsolr_version_deleted' : $this->get_index_model_version( $model_type );
				if ( empty( $index_model_version ) ) {
					// Generate and store a version
					$this->set_index_models_version( [ $model_type ] );
					$index_model_version = $this->get_index_indice_option_value( $model_type, static::OPTION_INDEX_MODEL_VERSION, '' );
				}
			}
			$model_column_id = $model_type->get_column_id();

			try {
				$is_needs_unlocking = false;

				// Lock the model to prevent concurrent indexing between crons, or between crons and batches
				if ( $is_needs_locking ) {
					$this->lock_model( $process_id, $model_type );
				}

				$model_type_str                       = $model_type->get_type();
				$models_nb_results[ $model_type_str ] = 0;

				// Last post date set in previous call. We begin with posts published after.
				// Reset the last post date is reindexing is required.
				$last_post_date_indexed = $this->get_last_post_date_indexed( $model_type );

				$query_statements = $model_type->get_indexing_sql( $this->index_indice, $debug_text, $batch_size, $post,
					$is_debug_indexing, $is_only_exclude_ids,
					( static::SQL_DATE_NULL === $last_post_date_indexed['date'] ) ? null : $last_post_date_indexed['date'],
					$is_deleted
				);

				// Eventually, log some debug information
				if ( ! empty( $query_statements['debug_info'] ) ) {
					$this->add_debug_line( $debug_text, null, $query_statements['debug_info'] );
				}

				// Filter the query
				$query_statements = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SQL_QUERY_STATEMENT,
					$query_statements,
					[
						'model_type'   => $model_type,
						'index_indice' => $this->index_indice,
					]
				);

				// Generate query string from the query statements
				$query = sprintf(
					static::MAIN_SQL_LOOP,
					$query_statements['SELECT'],
					$query_statements['FROM'],
					$query_statements['JOIN'],
					$query_statements['WHERE'],
					! empty( $query_statements['ORDER'] ) ? $query_statements['ORDER'] : 'NULL',
					0 === $query_statements['LIMIT'] ? '' : 'LIMIT ' . $query_statements['LIMIT']
				);

				$documents = [];
				while ( true ) {

					if ( $is_debug_indexing ) {
						$this->add_debug_line( $debug_text, 'Beginning of new loop (batch size)' );
					}

					// Execute query (retrieve posts IDs, parents and types)
					if ( isset( $post ) ) {

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'Query document with ID', [
								'Query'   => $query,
								'Post ID' => $post->$model_column_id,
							] );
						}

						$ids_array = $model_type->get_results( $wpdb->prepare( "{$query}", $post->$model_column_id ), ARRAY_A );

					} elseif ( $is_only_exclude_ids ) {

						$ids_array = $model_type->get_results( "{$query}", ARRAY_A );

					} else {

						if ( ! is_null( $model_type->get_column_last_updated() ) ) {
							// Model with a modified date

							$query_expanded_text = $wpdb->prepare( "{$query}", $last_post_date_indexed['date'], $last_post_date_indexed['ID'], $last_post_date_indexed['date'] );

						} else {
							// Model without a modified date
							$query_expanded_text = $wpdb->prepare( "{$query}", $last_post_date_indexed['ID'], $last_post_date_indexed['date'] );
						}

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'Query documents from last post date', [
								'Query'          => $query_expanded_text,
								'Last post date' => $last_post_date_indexed['date'],
								'Last post ID'   => $last_post_date_indexed['ID'],
							] );
						}

						$ids_array = $model_type->get_results( "{$query_expanded_text}", ARRAY_A );
					}

					if ( 0 === $batch_size ) {

						$nb_docs = $ids_array[0]['TOTAL'] ?? 0;

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'End of loop', [
								$is_only_exclude_ids ? 'Number of documents in database excluded from indexing' : 'Number of documents in database to be indexed' => $nb_docs,
							] );
						}

						// Just return the count
						return $nb_docs;
					}


					// Aggregate current batch IDs in one Solr update statement
					$post_count = count( $ids_array );

					if ( 0 === $post_count ) {
						// No more documents to index, stop now by exiting the loop

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'No more documents, end of document loop' );
						}

						$no_more_posts ++;
						$is_needs_unlocking = true;
						break;
					}

					$model_ids                             = [];
					$last_model_id                         = null;
					$has_only_models_without_modified_date = true;
					for ( $idx = 0; $idx < $post_count; $idx ++ ) {
						$model_id    = $ids_array[ $idx ]['ID'];
						$model_ids[] = $model_id;

						if ( $has_only_models_without_modified_date && ( is_null( $ids_array[ $idx ]['post_modified'] ) || empty( $ids_array[ $idx ]['post_modified'] ) ) ) {
							$has_only_models_without_modified_date = false;
						}

						// If post is not an attachment
						$this->_attached_files = [];
						if ( 'attachment' !== $ids_array[ $idx ]['post_type'] ) {

							// Count this post
							$doc_count ++;
							$models_nb_results[ $model_type_str ] ++;

							// Customize the attachment body, if attachments are linked to the current post
							$post_attachments = [];
							if ( WPSOLR_Model_Meta_Type_Post::META_TYPE === $model_type::META_TYPE ) {
								$post_attachments = apply_filters( WPSOLR_Events::WPSOLR_FILTER_GET_POST_ATTACHMENTS, [], $model_id );
							}

							// Get the attachments body with a Solr Tika extract query
							$attachment_body = '';
							foreach ( $post_attachments as $post_attachment ) {
								$attachment_body .= ( empty( $attachment_body ) ? '' : '. ' ) . static::extract_attachment_text_by_calling_solr_tika( $model_id, $post_attachment );
							}


							// Get the posts data
							$document = $this->create_solr_document_from_post_or_attachment( $model_type, $model_id, $attachment_body, $index_model_version, $this->_attached_files );

							if ( $is_debug_indexing ) {
								$this->add_debug_line( $debug_text, null, [
									'Post to be sent' => wp_json_encode( $document, JSON_PRETTY_PRINT ),
								] );
							}

							$documents[] = $document;

						} else {
							// Post is of type "attachment"

							if ( $is_debug_indexing ) {
								$this->add_debug_line( $debug_text, null, [
									'Post ID to be indexed (attachment)' => $model_id,
								] );
							}

							// Count this post
							$doc_count ++;
							$models_nb_results[ $model_type_str ] ++;

							// Get the attachments body with a Solr Tika extract query
							$attachment_body = static::extract_attachment_text_by_calling_solr_tika( $model_id, [ 'post_id' => $model_id ] );

							// Get the posts data
							$document = $this->create_solr_document_from_post_or_attachment( $model_type, $model_id, $attachment_body, $index_model_version, $this->_attached_files );

							if ( $is_debug_indexing ) {
								$this->add_debug_line( $debug_text, null, [
									'Attachment to be sent' => wp_json_encode( $document, JSON_PRETTY_PRINT ),
								] );
							}

							$documents[] = $document;

						}
					}

					if ( empty( $documents ) || ! isset( $documents ) ) {
						// No more documents to index, stop now by exiting the loop

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'End of loop, no more documents' );
						}

						break;
					}

					// Send batch documents to index
					try {

						$is_document_type = true;
						if ( $is_document_type ) {
							$res_final = $this->send_posts_or_attachments_to_solr_index( $documents );


						} else {
						}

					} catch ( \Exception $e ) {

						if ( $is_debug_indexing ) {
							// Debug text now, else it will be hidden by the exception
							WPSOLR_Escape::echo_esc_html( $debug_text );
						}

						// Continue
						throw $e;
					}

					// Solr error, or only $post to index: exit loop
					if ( ( null === $res_final ) || isset( $post ) ) {
						break;
					}

					if ( ! isset( $post ) ) {
						// Store last post date sent to Solr (for batch only)
						$last_post                      = end( $ids_array );
						$last_post_date_indexed['date'] = $last_post['post_modified'];

						if ( ! is_null( $model_type->get_column_last_updated() ) ||
						     ! $has_only_models_without_modified_date ) {
							// When last model is incremental: do not update the ID else next time reindexing of all
							$last_post_date_indexed['ID'] = $last_post['ID'];
						}

						if ( true ) {
							// Remove last indexed models from index table
							$model_type->delete_ids_from_index_history( $model_ids );
						}

						// No error, store last indexed reference
						$this->set_last_post_date_indexed( $model_type, $last_post_date_indexed );
					}

					// AJAX: one loop by ajax call
					break;
				}
			} catch ( WPSOLR_Exception_Locking $e ) {
				// Do nothing. Continue
				throw $e;

			} catch ( \Exception $e ) {

				// force unlock the model if error, else would be stuck locked
				if ( $is_needs_locking ) {
					$this->unlock_model( $process_id, $model_type );
				}

				// Continue
				throw $e;
			}

			// unlock the model only if it contains no more data to index, or if the indexing is stopping
			if ( $is_needs_locking && ( $is_stopping || $is_needs_unlocking ) ) {
				$this->unlock_model( $process_id, $model_type );
			}


		}

		$status = ! isset( $res_final ) ? 0 : $res_final;

		// All models have no more data ?
		$indexing_complete = ( $no_more_posts === count( $model_types ) );


		return $res_final = [
			'models_nb_results'    => $models_nb_results,
			'nb_results'           => $doc_count,
			'status'               => $status,
			'indexing_complete'    => $indexing_complete,
			'debug_text'           => $is_debug_indexing ? $debug_text : null,
		];

	}

	/*
	 * Fetch posts and attachments,
	 * Transform them in Solr documents,
	 * Send them in packs to Solr
	 */

	/**
	 * Add a debug line to the current debug text
	 *
	 * @param $is_debug_indexing
	 * @param $debug_text
	 * @param $debug_text_header
	 * @param $debug_text_content
	 */
	public
	function add_debug_line(
		&$debug_text, $debug_line_header, $debug_text_header_content = null
	) {

		if ( isset( $debug_line_header ) ) {
			$debug_text .= '******** DEBUG ACTIVATED - ' . $debug_line_header . ' *******' . '<br><br>';
		}

		if ( isset( $debug_text_header_content ) ) {

			foreach ( $debug_text_header_content as $key => $value ) {
				$debug_text .= $key . ':' . '<br>' . '<b>' . $value . '</b>' . '<br><br>';
			}
		}
	}

	/**
	 * @param WPSOLR_Model_Meta_Type_Abstract $model_type
	 * @param string $model_id
	 * @param string $attachment_body
	 * @param string $index_model_version
	 * @param array $attached_files
	 *
	 * @return mixed
	 * @throws \Exception
	 * @internal param $solr_indexing_options
	 */
	public
	function create_solr_document_from_post_or_attachment(
		$model_type, $model_id, $attachment_body, $index_model_version, $attached_files
	) {

		$model_to_index = WPSOLR_Model_Builder::get_model( $model_type, $model_id );

		$solarium_document_for_update = $model_to_index->create_document_from_model_or_attachment( $this, $attachment_body, $index_model_version );

		$this->update_model( $model_to_index, $solarium_document_for_update, $attached_files );

		return $solarium_document_for_update;
	}

	/**
	 * @param string $postid
	 * @param array $post_attachement
	 *
	 * @return string
	 * @throws \Exception
	 */
	public
	function extract_attachment_text_by_calling_solr_tika(
		$postid, $post_attachement
	) {

		try {
			$post_attachement_file = ! empty( $post_attachement['post_id'] ) ? get_attached_file( $post_attachement['post_id'] ) : download_url( $post_attachement['url'] );

			if ( $post_attachement_file instanceof \WP_Error ) {
				throw new \Exception( sprintf( 'Could not access the attachement content. %s', esc_html( $post_attachement_file->get_error_message() ) ) );
			}
			if ( empty( trim( $post_attachement_file ) ) ) {
				throw new \Exception( 'Attachment without empty file name.' );
			}

			$this->_attached_files[] = $post_attachement_file;

			$response = $this->search_engine_client_extract_document_content( $post_attachement_file );

			$attachment_text_extracted_from_tika = preg_replace( '/^.*?\<body\>(.*)\<\/body\>.*$/i', '\1', $response );
			if ( PREG_NO_ERROR !== preg_last_error() ) {
				throw new \Exception( sprintf( 'Error code (%s) returned by preg_replace() on the extracted file.', esc_html( PREG_NO_ERROR ) ) );
			}

			if ( empty( $attachment_text_extracted_from_tika ) ) {
				// Wrong preg_replace() result,. Use the original text.
				// Wrong preg_replace() result,. Use the original text.
				//throw new \Exception( 'Wrong format returned for the extracted file, cannot extract the <body>.' );
			}

			$attachment_text_extracted_from_tika = str_replace( '\n', ' ', $attachment_text_extracted_from_tika );
		} catch ( \Exception $e ) {
			if ( ! empty( $post_attachement['post_id'] ) ) {

				$post = get_post( $post_attachement['post_id'] );

				throw new \Exception( 'Error on attached file ' . esc_html( $post->post_title ) . ' (ID: ' . esc_html( $post->ID ) . ')' . ': ' . esc_html( $e->getMessage() ), esc_html( $e->getCode() ) );

			} else {

				throw new \Exception( sprintf( 'Error on embedded url "%s" of post_id %s. %s', esc_url( $post_attachement['url'] ), esc_html( $postid ), esc_html( $e->getMessage() ) ), esc_html( $e->getCode() ) );
			}
		}

		// Last chance to customize the tika extracted attachment body
		$attachment_text_extracted_from_tika = apply_filters( WPSOLR_Events::WPSOLR_FILTER_ATTACHMENT_TEXT_EXTRACTED_BY_APACHE_TIKA, $attachment_text_extracted_from_tika, $post_attachement );

		return $attachment_text_extracted_from_tika;
	}

	/**
	 * @param array $documents
	 * @param bool $is_error
	 *
	 * @return mixed
	 */
	abstract public function send_posts_or_attachments_to_solr_index( $documents, $is_error = false );


	/**
	 * Get count of blacklisted post ids
	 *
	 * @param WPSOLR_Model_Meta_Type_Abstract $model_type
	 *
	 * @return int
	 * @throws WPSOLR_Exception_Locking
	 */
	public function get_count_blacklisted_ids( $model_type ) {

		$result = $this->index_data( false, 'default', [ $model_type ], 0, null, false, true );

		return $result;
	}

	/**
	 * Update the model itself
	 *
	 * @param WPSOLR_Model_Abstract $model_to_index
	 * @param mixed $solarium_document_for_update
	 * @param array $attached_files
	 */
	protected function update_model( WPSOLR_Model_Abstract $model_to_index, &$solarium_document_for_update, $attached_files ) {
		// Do in children
	}

	/**
	 * Convert relation values in associative array with keys:
	 * ['cat1 => 10, 'cat2' => 20] ===> [['taxo1' => 'cat1, 'price_f' => 10], ['taxo1' => 'cat2', 'price_f' => 20]]
	 *
	 * @param array $values
	 * @param string $field_name_key
	 * @param string $field_name_value
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function format_relation_value( array $values, string $field_name_key, string $field_name_value ) {
		if ( static::ENGINE_ELASTICSEARCH !== $this->config['index_engine'] ) {
			throw new \Exception( 'Sorry, but currently only Elasticsearch and OpenSearch support relations.' );
		}

		$results = [];
		foreach ( $values as $key => $value ) {
			if ( ! empty( $key ) && ! empty( $value ) ) {
				// Add only not empty values
				$results[] = [ $field_name_key => $key, $field_name_value => $value ];
			}
		}

		return $results;
	}

	/**
	 * @param array $documents
	 *
	 * @return string[]
	 */
	protected function get_all_fields( array $documents ): array {

		/**
		 * Extract all distinct field names from all documents
		 */
		$fields = [];
		foreach ( $documents as $document ) {
			foreach ( $document as $field_name => $field_value ) {
				if ( ! in_array( $field_name, $fields ) ) {
					$fields[ $field_name ] = [ 'is_array' => is_array( $field_value ) ];
				}
			}
		}

		$all_fields = [];
		foreach ( WpSolrSchema::get_all_fields() as $field_name ) {
			$all_fields[ $field_name ] = [ 'is_array' => false ];
		}

		return array_merge( $all_fields, $fields );
	}

	/**
	 * @return string
	 */
	protected function get_site_id(): string {
		return '';
	}

	/**
	 * @return string
	 */
	protected function get_site_id_for_delete(): string {
		return '';
	}

	/**
	 * @param string $file_name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_server_file_content( string $file_name ): string {
		throw new \Exception( 'get_server_file_content() not implemented.' );
	}

	/**
	 * @inerhitDoc
	 *
	 * @param string $file_name
	 * @param string $file_content
	 *
	 * @throws \Exception
	 */
	public function deploy_server_file_content( string $file_name, string $file_content ) {
		throw new \Exception( 'deploy_server_file_content() not implemented.' );
	}

	/**
	 * @param array $models
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _get_models_that_can_be_deleted( array $models ): array {
		$results = $models;

		return $results;
	}

}
