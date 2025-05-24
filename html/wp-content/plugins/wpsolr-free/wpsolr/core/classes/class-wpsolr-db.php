<?php

namespace wpsolr\core\classes;

/**
 * When we need to hack some $wpdb methods (like direct query, without using $wp_query).
 *
 * Class WPSOLR_Db
 * @package wpsolr\core\classes
 */
abstract class WPSOLR_Db extends \wpdb {

	/** @var  \wpdb $old_wpdb */
	protected static $old_wpdb;

	/** @var  bool $wpsolr_is_custom */
	protected $wpsolr_is_custom = false;

	/**
	 * Custom actions after construct
	 */
	protected function _wpsolr_init() {
		// To do in children.
	}

	/**
	 * @param string $query Query
	 * @param array|mixed $args
	 * @param mixed $args,...
	 *
	 * @return string|void
	 */
	abstract protected function wpsolr_custom_prepare( $query, $args );

	/**
	 * @param string $query
	 * @param string $output
	 *
	 * @return array|object|null
	 */
	protected function wpsolr_custom_get_results( $query = null, $output = OBJECT ) {
		// Define in children
		return [];
	}

	/**
	 * @param string|null $query Optional. SQL query. Defaults to previous query.
	 * @param int $x Optional. Column to return. Indexed from 0.
	 *
	 * @return array Database query result. Array indexed from 0 by SQL result row number.
	 */
	protected function wpsolr_custom_get_col( $query = null, $x = 0 ) {
		// Define in children
		return [];
	}

	/**
	 * Retrieve one variable from the database.
	 *
	 * Executes a SQL query and returns the value from the SQL result.
	 * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
	 * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
	 *
	 * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
	 * @param int $x Optional. Column of value to return. Indexed from 0.
	 * @param int $y Optional. Row of value to return. Indexed from 0.
	 *
	 * @return string|null Database query result (as string), or null on failure
	 * @since 0.71
	 *
	 */
	protected function wpsolr_custom_get_var( $query = null, $x = 0, $y = 0 ) {
		// Define in children
		return null;
	}

	/**
	 * @param object $object_initiating
	 */
	static public function wpsolr_replace_wpdb( $object_initiating ) {
		global $wpdb, $current_site, $current_blog, $domain, $path, $site_id, $public, $table_prefix;

		$replacing_object = static::_copy( $wpdb );
		$replacing_object->wpsolr_set_is_custom( true );

		// Custom actions in children
		$replacing_object->_wpsolr_init();

		return $replacing_object;
	}

	protected static function _copy( $object_from ) {

		$object_to = new static (
			$object_from->dbuser,
			$object_from->dbpassword,
			$object_from->dbname,
			$object_from->dbhost
		);

		$reflectedSourceObject           = new \ReflectionClass( $object_from );
		$reflectedSourceObjectProperties = $reflectedSourceObject->getProperties();

		foreach ( $reflectedSourceObjectProperties as $reflectedSourceObjectProperty ) {
			$propertyName = $reflectedSourceObjectProperty->getName();

			$reflectedSourceObjectProperty->setAccessible( true );

			$object_to->$propertyName = $reflectedSourceObjectProperty->getValue( $object_from );
		}

		return $object_to;
	}

	/**
	 * @inheritDoc
	 */
	public function prepare( $query, ...$args ) {

		$args = func_get_args();
		array_shift( $args );
		// If args were passed as an array (as in vsprintf), move them up
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		if ( $this->wpsolr_get_is_custom() && $this->wpsolr_get_is_custom_query( $query ) ) {

			return $this->wpsolr_custom_prepare( $query, $args );

		} else {

			return parent::prepare( $query, $args );
		}

	}


	/**
	 * @inheritDoc
	 */
	public function get_results( $query = null, $output = OBJECT ) {

		if ( $this->wpsolr_get_is_custom() && $this->wpsolr_get_is_custom_query( $query ) ) {

			// Done.
			$this->wpsolr_set_is_custom( false );

			return $this->wpsolr_custom_get_results( $query, $output );

		} else {

			// Done.
			$this->wpsolr_set_is_custom( false );

			return parent::get_results( $query, $output );
		}

	}


	/**
	 * @inheritDoc
	 */
	public function get_col( $query = null, $x = 0 ) {

		if ( $this->wpsolr_get_is_custom() && $this->wpsolr_get_is_custom_query( $query ) ) {

			// Done.
			$this->wpsolr_set_is_custom( false );

			return $this->wpsolr_custom_get_col( $query, $x );

		} else {

			// Done.
			$this->wpsolr_set_is_custom( false );

			return parent::get_col( $query, $x );
		}

	}

	/**
	 * @inheritDoc
	 */
	public function get_var( $query = null, $x = 0, $y = 0 ) {

		if ( $this->wpsolr_get_is_custom() && $this->wpsolr_get_is_custom_query( $query ) ) {

			// Done.
			$this->wpsolr_set_is_custom( false );

			return $this->wpsolr_custom_get_var( $query, $x, $y );

		} else {

			// Done.
			$this->wpsolr_set_is_custom( false );

			return parent::get_var( $query, $x, $y );
		}

	}


	/**
	 * @return bool
	 */
	public function wpsolr_get_is_custom() {
		return $this->wpsolr_is_custom;
	}

	/**
	 * @param string $query
	 *
	 * @return bool
	 */
	public function wpsolr_get_is_custom_query( $query ) {
		return true;
	}

	/**
	 * @param bool $wpsolr_is_custom
	 */
	public function wpsolr_set_is_custom( $wpsolr_is_custom ) {
		global $wpdb;

		if ( $wpsolr_is_custom ) {
			// Backup original wpdb
			self::$old_wpdb = $wpdb;

			// Update original wpdb with ours
			$wpdb = $this;

		} elseif ( ! ( $wpdb instanceof static ) ) {
			// Restore original wpdb
			$wpdb = self::$old_wpdb;
		}

		$this->wpsolr_is_custom = $wpsolr_is_custom;
	}


	/**
	 * Does the current sql text match one of the SQL fragments?
	 *
	 * @param $query
	 *
	 * @return bool
	 */
	public function wpsolr_get_is_custom_query_from_sql_fragments( $query ) {

		foreach ( $this::_wpsolr_get_sql_fragments_per_query_type() as $query_type => $sql_fragments ) {

			$this->wpsolr_query_type = $query_type;
			foreach ( $sql_fragments as $sql_fragment ) {
				if ( false === strpos( $query, $sql_fragment ) ) {
					// Not recognized. Stop this query fragments now.
					$this->wpsolr_query_type = '';
					break;
				}
			}

			if ( ! empty( $this->wpsolr_query_type ) ) {
				// Found. Stop now.
				break;
			}
		}

		// Recognized
		return ! empty( $this->wpsolr_query_type );
	}

	/**
	 * Array of sql fragments grouped by key
	 *
	 * @return array[]
	 */
	protected function _wpsolr_get_sql_fragments_per_query_type() {
		// Define in children
		return [];
	}
}
