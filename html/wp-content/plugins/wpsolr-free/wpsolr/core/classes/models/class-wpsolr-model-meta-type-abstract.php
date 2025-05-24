<?php

namespace wpsolr\core\classes\models;


use wpsolr\core\classes\database\WPSOLR_Database;
use wpsolr\core\classes\models\post\WPSOLR_Model_Meta_Type_Post;
use wpsolr\core\classes\models\taxonomy\WPSOLR_Model_Meta_Type_Taxonomy;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class WPSOLR_Model_Meta_Type_Abstract
 * @package wpsolr\core\classes\models
 */
abstract class WPSOLR_Model_Meta_Type_Abstract {

	// Current DB version. Aligned with a WPSOLR version. Change it to upgrade the DB schema.
	const DB_VERSION = '21.5';

	const META_TYPE = '';

	/**
	 * Actions on index history
	 */
	const ACTION_ADD = 'A';
	const ACTION_DELETE = 'D';
	const WPSOLR_CUSTOM_FIELD_EVENT_INDEX = 'wpsolr_event_index';

	/** @var bool */
	protected static $is_checked_db_version = false;
	protected static array $_all_model_types;

	/* @var string Model label */
	protected $label;

	/* @var string Model singular label */
	protected $singular_label;

	/* @var string Table name storing the model */
	protected $table_name;

	/* @var string Column containing the model id */
	protected $column_id;

	/* @var string Column containing the model timestamp */
	protected $column_last_updated;

	/* @var array SQL statement for the indexing loop */
	protected $indexing_sql;

	/** @var  string $type */
	protected $type;

	/** @var string Table containing the incremental history done on the indexes */
	const INDEX_HISTORY_TABLE_NAME = 'wpsolr_index_history';

	/**
	 * Get meta type
	 *
	 * @return string
	 */
	static function get_meta_type() {
		return static::META_TYPE;
	}

	/**
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * @param string $table_name
	 *
	 * @return $this
	 */
	public function set_table_name( $table_name ) {
		$this->table_name = $table_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_column_id() {
		return $this->column_id;
	}

	/**
	 * @param string $column_id
	 *
	 * @return $this
	 */
	public function set_column_id( $column_id ) {
		$this->column_id = $column_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_column_last_updated() {
		return $this->column_last_updated;
	}

	/**
	 * @param string $column_last_updated
	 *
	 * @return $this
	 */
	public function set_column_last_updated( $column_last_updated ) {
		$this->column_last_updated = $column_last_updated;

		return $this;
	}

	/**
	 * @param string $index_indice
	 * @param string $debug_text
	 * @param int $batch_size
	 * @param null $post
	 * @param bool $is_debug_indexing
	 * @param bool $is_only_exclude_ids
	 * @param string $last_post_date_indexed
	 * @param bool $is_deleted
	 *
	 * @return array
	 */
	public function get_indexing_sql( $index_indice, $debug_text, $batch_size = 100, $post = null, $is_debug_indexing = false, $is_only_exclude_ids = false, $last_post_date_indexed = null, bool $is_deleted = false ) {
		return $this->indexing_sql;
	}

	/**
	 * @param array $column_last_updated
	 *
	 * @return $this
	 */
	public function set_indexing_sql( $indexing_sql ) {
		$this->indexing_sql = $indexing_sql;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function get_singular_label() {
		return $this->singular_label;
	}

	/**
	 * @param string $label
	 *
	 * @return WPSOLR_Model_Meta_Type_Abstract
	 */
	public function set_label( $label ) {
		$this->label = $label;

		return $this;
	}

	/**
	 * @param string $label
	 *
	 * @return WPSOLR_Model_Meta_Type_Abstract
	 */
	public function set_singular_label( $label ) {
		$this->singular_label = $label;

		return $this;
	}

	/**
	 * Set a type
	 *
	 * @param string $type
	 *
	 * @return WPSOLR_Model_Meta_Type_Abstract
	 */
	public function set_type( $type ) {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Should be on search queries.
	 *
	 * @return bool
	 */
	public function get_is_search() {
		return false;
	}


	/**
	 * Should be on suggestions queries.
	 *
	 * @return bool
	 */
	public function get_is_suggestion() {
		return true;
	}

	/**
	 * Create models from types
	 *
	 * @param string[] $model_types
	 *
	 * @return WPSOLR_Model_Meta_Type_Abstract[]
	 * @throws \Exception
	 */
	static public function get_model_type_objects( $model_types ) {

		$results = [];
		foreach ( $model_types as $model_type ) {

			try {

				$results[] = static::_get_model_type_object( $model_type );

			} catch ( \Exception $e ) {
				// Probably a post type or a taxonomy no more active. Ignore it.
				//WPSOLR_Escape::error_log( $e->getMessage() );
			}
		}

		return $results;
	}

	/**
	 * Model types that can be indexed
	 */
	static function get_all_model_types(): array {
		if ( ! isset( static::$_all_model_types ) ) {
			static::$_all_model_types = apply_filters( WPSOLR_Events::WPSOLR_FILTER_GET_ALL_MODEL_TYPES, [
				//WPSOLR_Model_Meta_Type_User::class,
				WPSOLR_Model_Meta_Type_Post::class,
				WPSOLR_Model_Meta_Type_Taxonomy::class,
			] );
		}

		return static::$_all_model_types;
	}

	/**
	 * @param $model_type
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	static function _get_model_type_object( $model_type ) {

		foreach (
			static::get_all_model_types() as $model_type_class
		) {

			if ( ! is_null( $object = $model_type_class::_child_get_model_type_object( $model_type ) ) ) {
				return $object;
			}
		}

		throw new \Exception( esc_html( "WPSOLR: {$model_type} is not recognized." ) );
	}

	/**
	 * Verify that a $type is compatible with the current model type class
	 *
	 * @param string $type
	 *
	 * @return $this
	 * @throws \Exception
	 */
	static protected function _child_get_model_type_object( $model_type ) {
		throw new \Exception( 'Missing implementation.' );
	}

	/**
	 * Retrieve a model from type and an id
	 *
	 * @param WPSOLR_Model_Meta_Type_Abstract $model_type
	 * @param string $model_id
	 *
	 * @return null|WPSOLR_Model_Abstract
	 * @throws \Exception
	 */
	public static function get_model( $model_type, $model_id ) {
		throw new \Exception( sprintf( 'get_model() not implemented for class: %s.', esc_html( static::class ) ) );
	}

	/**
	 * Model type is authorized to be indexed ?
	 *
	 * @param string $post_type
	 *
	 * @return bool
	 */
	static public function get_is_model_type_can_be_indexed( $post_type ) {

		$post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();

		return in_array( $post_type, $post_types, true );
	}

	/**
	 * Get the model type taxonomies
	 *
	 * @return string[]
	 */
	public function get_taxonomies() {
		return get_object_taxonomies( $this->get_type(), $output = 'names' );
	}

	/**
	 * Get the model type fields
	 *
	 * @return string[]
	 */
	abstract public function get_fields();

	/**
	 * Terms do not have a modified date, hence require a new table to store update/delete actions
	 * @inheritdoc
	 */
	public function upgrade_database_if_model_has_no_modified_date() {
		if ( ! static::$is_checked_db_version && ( is_null( $this->column_last_updated ) ) ) {

			WPSOLR_Database::check_db_version( static::DB_VERSION, $this->_get_schema() );

			static::$is_checked_db_version = true;
		}

	}

	/**
	 * Get Table schema: wpsolr_index_update - Table for storing index updates waiting for processing
	 *
	 * @return string
	 */
	protected function _get_schema() {
		global $wpdb;

		$index_history_table_name = static::INDEX_HISTORY_TABLE_NAME;
		$collate                  = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = /** @lang MySQL */
			"CREATE TABLE {$wpdb->prefix}$index_history_table_name 
(
  id              bigint unsigned auto_increment primary key,
  model_id        varchar(100)                           not null,
  model_meta_type varchar(100)                           not null,
  modified        datetime default '0000-00-00 00:00:00' not null,
  action		  varchar(1)                           not null,
index idx_modified (modified),
unique index idx_model_meta_type_modified (model_meta_type, model_id)
) $collate;
		";

		return $tables;
	}

	/**
	 * Save index history
	 *
	 * @param string $model_id
	 * @param string $action
	 *
	 * @throws \Exception
	 */
	protected static function _save_index_history( $model_id, $action ) {
		global $wpdb;

		$index_history_table_name = static::INDEX_HISTORY_TABLE_NAME;

		switch ( $action ) {

			case static::ACTION_ADD:
				$query                 = /** @lang MySQL */
					"insert into {$wpdb->prefix}$index_history_table_name 
  (model_meta_type, model_id, modified, action) values (%s, %s, %s, %s)
ON DUPLICATE KEY UPDATE
    modified = %s, action = %s;";
				$current_sql_timestamp = current_time( 'mysql' );
				$sql                   = $wpdb->prepare( "{$query}", static::get_meta_type(), $model_id, $current_sql_timestamp, $action, $current_sql_timestamp, $action );
				break;

			case static::ACTION_DELETE:
				$query = /** @lang MySQL */
					"delete from {$wpdb->prefix}$index_history_table_name WHERE model_meta_type = %s and model_id = %s;";
				$sql   = $wpdb->prepare( "{$query}", static::get_meta_type(), $model_id );
				break;

		}

		$wpdb->query( "{$sql}" );
		if ( ! empty( $wpdb->last_error ) && ( false !== strpos( $wpdb->last_error, static::INDEX_HISTORY_TABLE_NAME ) ) ) {
			// Missing table. Create it now, and retry.
			( new WPSOLR_Model_Meta_Type_Taxonomy( 'category' ) )->upgrade_database_if_model_has_no_modified_date();
			$wpdb->query( "{$sql}" );

			if ( ! empty( $wpdb->last_error ) ) {
				throw new \Exception( esc_html( $wpdb->last_error ) );
			}
		}

	}

	/**
	 * Add to index history
	 *
	 * @param string $model_id
	 *
	 * @throws \Exception
	 */
	public static function index_history_add( $model_id ) {
		static::_save_index_history( $model_id, static::ACTION_ADD );
	}

	/**
	 * Remove from index history
	 *
	 * @param string $model_id
	 *
	 * @throws \Exception
	 */
	public static function index_history_delete( $model_id ) {
		static::_save_index_history( $model_id, static::ACTION_DELETE );
	}

	/**
	 * After indexing, delete model ids from the incremental historic table
	 *
	 * @param string[] $model_ids
	 *
	 * @throws \Exception
	 */
	public function delete_ids_from_index_history( $model_ids ) {
		global $wpdb;

		if ( ! empty( $model_ids ) ) {
			if ( is_null( $this->column_last_updated ) && ! empty( $model_ids ) ) {

				// Prepare the right amount of placeholders
				$placeholders = array_fill( 0, count( $model_ids ), '%s' );

				// Glue together all the placeholders...
				// $format = %s, %s, %s, ....
				$format = implode( ', ', $placeholders );

				$index_history_table_name = static::INDEX_HISTORY_TABLE_NAME;
				$query                    = /** @lang MySQL */
					"delete from {$wpdb->prefix}$index_history_table_name where model_meta_type = %s AND model_id IN ({$format})";

				array_unshift( $model_ids, static::get_meta_type() );
				$sql = $wpdb->prepare( "{$query}", $model_ids );

				$wpdb->query( "{$sql}" );
				if ( ! empty( $wpdb->last_error ) && ( false !== strpos( $wpdb->last_error, static::INDEX_HISTORY_TABLE_NAME ) ) ) {
					// Missing table. Create it now, and retry.
					( new WPSOLR_Model_Meta_Type_Taxonomy( 'category' ) )->upgrade_database_if_model_has_no_modified_date();
					$wpdb->query( "{$sql}" );

					if ( ! empty( $wpdb->last_error ) ) {
						throw new \Exception( esc_html( $wpdb->last_error ) );
					}
				}

			}
		}
	}

	/**
	 *
	 * @param string $query SQL query.
	 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 *                       With one of the first three, return an array of rows indexed from 0 by SQL result row number.
	 *                       Each row is an associative array (column => value, ...), a numerically indexed array (0 => value, ...), or an object. ( ->column = value ), respectively.
	 *                       With OBJECT_K, return an associative array of row objects keyed by the value of each row's first column's value.
	 *                       Duplicate keys are discarded.
	 *
	 * @return array|object|null Database query results
	 */
	public function get_results( $query = null, $output = OBJECT ) {
		global $wpdb;

		$results = $wpdb->get_results( "{$query}", $output );
		if ( ! empty( $wpdb->last_error ) && ( false !== strpos( $wpdb->last_error, static::INDEX_HISTORY_TABLE_NAME ) ) ) {
			// Missing table. Create it now, and retry.
			( new WPSOLR_Model_Meta_Type_Taxonomy( 'category' ) )->upgrade_database_if_model_has_no_modified_date();
			$results = $wpdb->get_results( "{$query}", $output );

			if ( ! empty( $wpdb->last_error ) ) {
				throw new \Exception( esc_html( $wpdb->last_error ) );
			}
		}

		return $results;
	}

	/**
	 * Extra join on condition for WPML
	 * @return string
	 */
	public function get_sql_join_on_for_wpml() {
		// Define in children
		return '';
	}

	/**
	 * Extra join on condition for WPML
	 * @return string
	 */
	public function get_sql_join_on_for_polylang() {
		// Define in children
		return '';
	}

	/**
	 * Does type has attachments ?
	 *
	 * @return bool
	 */
	public function has_attachments() {
		return false;
	}

	/**
	 * Does type has images ?
	 *
	 * @return bool
	 */
	public function has_images() {
		return false;
	}

	/**
	 * @return WPSOLR_Model_Meta_Type_Abstract
	 */
	public function get_linked_model_event() {
		return null;
	}

	/**
	 * @return array
	 */
	public function get_all_statuses(): array {
		return [];
	}

	/**
	 * @param $model_type
	 *
	 * @return array
	 */
	public function not_used_get_subtypes( $model_type ): array {
		return [];
	}

}
