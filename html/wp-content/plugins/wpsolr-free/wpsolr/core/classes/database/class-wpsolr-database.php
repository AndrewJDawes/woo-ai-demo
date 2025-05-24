<?php

namespace wpsolr\core\classes\database;


use wpsolr\core\classes\services\WPSOLR_Service_Container;

class WPSOLR_Database {

	/**
	 * Check version and run the updater if required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 *
	 * @param string $db_version
	 * @param string $create_table_sql_script
	 */
	static public function check_db_version( $db_version, $create_table_sql_script ) {

		if ( WPSOLR_Service_Container::getOption()->get_db_current_version() !== $db_version ) {
			static::_create_tables( $create_table_sql_script );

			// Update current version
			WPSOLR_Service_Container::getOption()->set_db_current_version( $db_version );
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * @param string $create_table_sql_script
	 */
	static protected function _create_tables( $create_table_sql_script ) {
		global $wpdb;

		$wpdb->show_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $create_table_sql_script );
	}

}