<?php

namespace wpsolr\core\classes\extensions\view;

use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\utilities\WPSOLR_Admin_Utilities;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;

class WPSOLR_Option_View extends WPSOLR_Extension {


	const VIEW_OPTIONS_NAME_PATTERN = '%s_%s';
	const DEFAULT_VIEW_UUID = '';
	const VIEW_UUID = 'view_uuid';
	const DEFAULT_INDEX_UUID = '';
	const INDEX_UUID = 'index_uuid';

	/** @var string */
	protected static $current_view_uuid, $backup_current_view_uuid, $current_index_uuid, $backup_current_index_uuid;

	/**
	 * Constructor
	 * Subscribe to actions
	 **/
	function __construct() {

		add_filter( WPSOLR_Events::WPSOLR_FILTER_VIEWS, [ WPSOLR_Option_View::class, 'get_list_views' ], 10, 2 );
		add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEXES, [ $this, 'get_list_all_indexes' ], 10, 1 );
		add_filter( WPSOLR_Events::WPSOLR_FILTER_VIEW_ARCHIVE_SEARCH, [ $this, 'get_view_archive_search' ], 10, 1 );
	}

	/**
	 * Get the view for the search archive
	 *
	 * @return string
	 */
	static function get_view_archive_search( $default_view ) {
		return $default_view;
	}

	/**
	 * Get a parameter value in current url
	 *
	 * @param $parameter_name
	 * @param $parameter_value_default
	 * @param string $url
	 *
	 * @return string
	 */
	static function get_url_parameter( $parameter_name, $parameter_value_default, $url = '' ) {
		if ( ! empty( $url ) ) {

			// Extract parameter from url query
			parse_str( wp_parse_url( $url )['query'] ?? [], $params );

			$result = trim( $params[ $parameter_name ] ?? $parameter_value_default );

		} else {
			// $_REQUEST and not $_GET because can be a GET or a POST
			$result = trim( isset( $_REQUEST[ $parameter_name ] ) ? WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ $parameter_name ] ) : $parameter_value_default );
		}

		return $result;
	}

	/**
	 * Get the view_uuid in current url
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	static function get_url_view_uuid( $url = '' ) {
		return static::get_url_parameter( self::VIEW_UUID, static::DEFAULT_VIEW_UUID, $url );
	}

	/**
	 * Get the index_uuid in current url
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	static function get_url_index_uuid( $url = '' ) {
		return static::get_url_parameter( self::INDEX_UUID, static::DEFAULT_INDEX_UUID, $url );
	}

	/**
	 * Get a view
	 *
	 * @return array
	 */
	static function get_view( $view_uuid = null ) {
		$views     = WPSOLR_Service_Container::getOption()->get_option_view_views();
		$view_uuid = is_null( $view_uuid ) ? static::get_url_view_uuid() : $view_uuid;

		return ( empty( $views ) || empty( $view_uuid ) || empty( $views[ $view_uuid ] ) ) ? static::get_default_view() : $views[ $view_uuid ];
	}

	/**
	 * Get a view label
	 *
	 * @return string
	 */
	static function get_view_label( $view_uuid = null ) {
		$view = static::get_view( $view_uuid );

		return $view[ WPSOLR_Option::OPTION_VIEW_LABEL ];
	}

	/**
	 * Get current view uuid
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function get_current_view_uuid( $url = '' ) {
		return empty( $url ) ? ( static::$current_view_uuid ?? static::get_url_view_uuid( $url ) ) : static::get_url_view_uuid( $url );
	}

	/**
	 * Set current view uuid
	 *
	 * @param string $view_uuid
	 *
	 * @return string
	 */
	public static function set_current_view_uuid( $view_uuid = '' ) {
		static::$current_view_uuid = $view_uuid;

		return $view_uuid;
	}

	/**
	 * @return void
	 */
	public static function set_current_view_from_query(): void {
		global $wp_query;

		if ( $wp_query && ( $wp_query instanceof WPSOLR_Query ) ) {
			WPSOLR_Option_View::set_current_view_uuid( $wp_query->wpsolr_get_view_uuid() );
		}

	}

	/**
	 * Get current view uuid
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function get_current_index_uuid( $url = '' ) {
		return empty( $url ) ? ( static::$current_index_uuid ?? static::get_url_index_uuid( $url ) ) : static::get_url_index_uuid( $url );
	}

	/**
	 * Set current view uuid
	 *
	 * @param string $view_uuid
	 *
	 * @return string
	 */
	public static function set_current_index_uuid( $index_uuid ) {
		static::$current_index_uuid = $index_uuid;

		/*
		if ( empty( WPSOLR_Service_Container::getOption()->get_option_index() ) ) {
			static::$current_index_uuid = ''; // Compatibility before views: no settings for current index, use the default settings instead
		}*/

		return static::$current_index_uuid;
	}

	/**
	 * Output the form view hidden fields
	 *
	 * @param string $options_name
	 */
	public static function output_form_view_hidden_fields( $options_name ) {

		$view_uuid = static::get_current_view_uuid();
		settings_fields( static::get_view_uuid_options_name( $options_name ) );

		?>
        <!-- Send parameters in the form POST -->
        <input type='hidden' name='view_uuid' value="<?php WPSOLR_Escape::echo_esc_attr( $view_uuid ); ?>">
		<?php
	}

	/**
	 * Output the form index hidden fields
	 *
	 * @param string $options_name
	 */
	public static function output_form_index_hidden_fields( $options_name ) {

		$index_uuid = static::get_current_index_uuid();
		settings_fields( static::get_index_uuid_options_name( $options_name ) );

		?>
        <!-- Send parameters in the form POST -->
        <input type='hidden' name='index_uuid' value="<?php WPSOLR_Escape::echo_esc_attr( $index_uuid ); ?>">
		<?php
	}

	/**
	 * Retrieve all active views
	 *
	 * @param array $default_views
	 * @param bool $is_all_views
	 *
	 * @return array ['uuid1' => 'view 1', 'uuid2' => 'view 2']
	 */
	static function get_list_views( $default_views, $is_all_views ) {

		$results = [];
		foreach ( WPSOLR_Service_Container::getOption()->get_option_view_views() as $view_uuid => $view ) {
			if ( $is_all_views || ! isset( $view[ WPSOLR_Option::OPTION_VIEW_IS_DISABLED ] ) ) {
				$results[ $view_uuid ] = $view;
			}
		}

		// Default view on last position
		$results = array_merge( $results, $default_views );

		return $results;
	}

	/**
	 * Store the current view uuid before looping on views
	 * @return void
	 */
	public static function backup_current_view_uuid() {
		static::$backup_current_view_uuid = static::$current_view_uuid ?? '';
	}

	/**
	 * Restore the current view uuid after looping on views
	 * @return void
	 */
	public static function restore_current_view_uuid() {
		static::$current_view_uuid = static::$backup_current_view_uuid ?? '';
	}

	/**
	 * Store the current index uuid before looping on views
	 * @return void
	 */
	public static function backup_current_index_uuid() {
		static::$backup_current_index_uuid = static::$current_index_uuid ?? '';
	}

	/**
	 * Restore the current index uuid after looping on views
	 * @return void
	 */
	public static function restore_current_index_uuid() {
		static::$current_index_uuid = static::$backup_current_index_uuid ?? '';
	}

	/**
	 * Retrieve all indexes
	 *
	 * @return array ['uuid1' => 'view 1', 'uuid2' => 'view 2']
	 */
	function get_list_all_indexes() {

		$results = static::get_list_default_index(); // Default value
		$indexes = WPSOLR_Service_Container::getOption()->get_option_indexes();
		if ( ! empty( $indexes ) && ! empty( $indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] ) ) {
			foreach ( $indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] as $index_uuid => $index ) {
				$results[ $index_uuid ] = $index;
			}
		}

		return $results;
	}

	/**
	 * Retrieve default view as a list
	 **
	 * @return array
	 */
	static public function get_list_default_view() {

		return [ '' => static::get_default_view() ];
	}

	/**
	 * Retrieve default index as a list
	 **
	 * @return array
	 */
	static public function get_list_default_index() {

		return [ '' => static::get_default_index() ];
	}

	/**
	 * Retrieve default view
	 **
	 * @return array
	 */
	static public function get_default_view() {

		return [ WPSOLR_Option::OPTION_VIEW_LABEL => 'Default project' ];
	}

	/**
	 * Retrieve default index
	 **
	 * @return array
	 */
	static public function get_default_index() {

		return [ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_NAME => 'All indexes' ];
	}

	/**
	 * Generate a unique options name for a view from an existing options name
	 *
	 * @param string $options_name
	 * @param string $url
	 *
	 * @return string
	 */
	static function get_view_uuid_options_name( $options_name, $url = '' ) {
		$view_uuid = static::get_current_view_uuid( $url );

		return empty( trim( $view_uuid ) ) ? $options_name : trim( sprintf( self::VIEW_OPTIONS_NAME_PATTERN, $options_name, trim( $view_uuid ) ) );
	}

	/**
	 * Generate a unique options name for an index from an existing options name
	 *
	 * @param string $options_name
	 * @param string $url
	 *
	 * @return string
	 */
	static function get_index_uuid_options_name( $options_name, $url = '' ) {
		$index_uuid = static::get_current_index_uuid( $url );

		return empty( trim( $index_uuid ) ) ? $options_name : trim( sprintf( self::VIEW_OPTIONS_NAME_PATTERN, $options_name, trim( $index_uuid ) ) );
	}

	/**
	 * Generate a unique options name for the current index from an existing options name
	 *
	 * @param string $options_name
	 *
	 * @return string
	 */
	static function get_current_index_options_name( $options_name ) {

		return static::get_index_uuid_options_name( $options_name );
	}

	/**
	 * Generate a unique options name for the current view from an existing options name
	 *
	 * @param string $options_name
	 *
	 * @return string
	 */
	static function get_current_view_options_name( $options_name ) {

		return static::get_view_uuid_options_name( $options_name );
	}

	/**
	 * Register options settings with a view
	 *
	 * @param string $setting_group_name
	 * @param string $options_name
	 */
	static function register_setting_view( $setting_group_name, $options_name ) {

		$view_setting_group_name = WPSOLR_Option_View::get_view_uuid_options_name( $setting_group_name );
		$view_options_name       = WPSOLR_Option_View::get_view_uuid_options_name( $options_name );

		register_setting( $view_setting_group_name, $view_options_name, [
			WPSOLR_Sanitize::class,
			'sanitized_register_setting'
		] );
	}

	/**
	 * Register options settings with a view
	 *
	 * @param string $setting_group_name
	 * @param string $options_name
	 */
	static function register_setting_index( $setting_group_name, $options_name ) {

		$view_setting_group_name = WPSOLR_Option_View::get_index_uuid_options_name( $setting_group_name );
		$view_options_name       = WPSOLR_Option_View::get_index_uuid_options_name( $options_name );

		register_setting( $view_setting_group_name, $view_options_name, [
			WPSOLR_Sanitize::class,
			'sanitized_register_setting'
		] );
	}

	/**
	 * Build the list of views as a select box
	 *
	 * @param string $title
	 *
	 * @return false|string
	 */
	static function get_views_html( $title ) {
		$url_view_uuid = self::get_url_view_uuid();

		$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), true, 10, 2 );

		/*
		 * Prevent saving wrong view's options if current url's view does not exist
		 */
		$is_view_exists = false;
		foreach ( $views as $view_uuid => $view ) {
			if ( $view_uuid === $url_view_uuid ) {
				$is_view_exists = true;
				break;
			}
		}
		if ( ! $is_view_exists ) {
			// Redirect to default view by removing the view parameter
			static::clean_deleted_views();
			wp_redirect( remove_query_arg( self::VIEW_UUID ) );
		}


		ob_start();
		?>
        <div>
			<?php WPSOLR_Escape::echo_escaped( sprintf( '%s for project:', WPSOLR_Escape::esc_html( $title ) ) ); ?>
            <select id="wpsolr_views_id">
				<?php
				foreach ( $views as $view_uuid => $view ) {
					$view_label = ( empty( $view_uuid ) || ! isset( $view[ WPSOLR_Option::OPTION_VIEW_IS_DISABLED ] ) ) ?
						$view[ WPSOLR_Option::OPTION_VIEW_LABEL ] :
						sprintf( 'Disabled - %s', $view[ WPSOLR_Option::OPTION_VIEW_LABEL ] );
					?>
                    <option value="<?php WPSOLR_Escape::echo_esc_attr( $view_uuid ); ?>" <?php selected( $view_uuid, $url_view_uuid ); ?>><?php WPSOLR_Escape::echo_esc_html( $view_label ); ?></option>
					<?php
				}
				?>
            </select>
        </div>
		<?php
		$wpsolr_views_html = ob_get_contents();
		ob_end_clean();

		return $wpsolr_views_html;
	}

	/**
	 * Build the list of indexes as a select box
	 *
	 * @param string $title
	 * @param array $parameters
	 *
	 * @return false|string
	 * @throws \Exception
	 */
	static function get_indexes_html( $title, $parameters = [] ) {

		$is_show_default = $parameters['is_show_default'] ?? true;
		$default_label = $parameters['default_label'] ?? '';

		$url_index_uuid = WPSOLR_Option_View::get_url_index_uuid();

		$indexes = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INDEXES, WPSOLR_Option_View::get_list_default_index(), 10, 1 );

		// Sort indexes: enabled show first
		$indexes_options = [ 'enabled' => [], ];
		foreach ( $indexes as $index_uuid => $index ) {
			$index_label = $index[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_NAME ];
			$disabled    = false;
			if ( $is_show_default || ! empty( $index_uuid ) ) {
				$options_group_name = 'enabled';
				$indexes_options[ $options_group_name ][] = [
					'value'    => $index_uuid,
					'label'    => empty( $index_uuid ) && ! empty( $default_label ) ? $default_label : $index_label,
					'selected' => ( $index_uuid === $url_index_uuid ),
				];
			}
		}


		ob_start();
		?>
        <div>
			<?php WPSOLR_Escape::echo_escaped( sprintf( '%s for connector:', WPSOLR_Escape::esc_html( $title ) ) ); ?>
            <select id="wpsolr_indexes_id">
				<?php foreach ( $indexes_options as $index_status => $index_status_options ) { ?>
					<?php foreach ( $index_status_options as $index_option ) { ?>
                        <option value="<?php WPSOLR_Escape::echo_esc_attr( $index_option['value'] ); ?>" <?php selected( $index_option['selected'] ); ?> ><?php WPSOLR_Escape::echo_esc_html( $index_option['label'] ); ?></option>
					<?php } ?>
				<?php } ?>
            </select>
            <a href="<?php WPSOLR_Escape::echo_esc_url( WPSOLR_Admin_Utilities::get_admin_url( '&tab=solr_plugins&subtab=extension_views_opt' ) ); ?>"
               target="_wpsolr_views" style="font-weight: lighter;border-bottom:none;color: #0C4F84;">Activate the
                "Project" extension to manage multi-connector settings</a>
        </div>
		<?php
		$wpsolr_indexes_html = ob_get_contents();
		ob_end_clean();

		return $wpsolr_indexes_html;
	}

	/**
	 * Remove options from deleted views
	 *
	 * @return void
	 */
	static function clean_deleted_views() {
		$views_option = WPSOLR_Service_Container::getOption()->get_option_view_views();
		foreach ( wp_load_alloptions() as $option_name => $option_data ) {
			if ( false !== strpos( $option_name, 'wdm_', 0 ) && get_option( $option_name ) ) {
				$option_name_uuid         = WPSOLR_Regexp::extract_last_separator( $option_name, '_' );
				$option_name_without_uuid = substr( $option_name, 0, strlen( $option_name ) - strlen( '_' . $option_name_uuid ) );

				if ( WPSOLR_Service_Container::getOption()->get_is_option_type_view( $option_name_without_uuid ) ) {
					if ( ! isset( $views_option[ $option_name_uuid ] ) ) {
						// View option data exists, but the view object itself was deleted: remove the view option data
						delete_option( $option_name );
					}
				}
			}
		}
	}


	/**
	 * Add view and index parameters to url
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function add_menus_url_query_view( string $url ) {
		$url_view_uuid  = WPSOLR_Option_View::get_url_view_uuid();
		$url_index_uuid = WPSOLR_Option_View::get_url_index_uuid();

		$url_view_uuid_param  = empty( $url_view_uuid ) ? '' : sprintf( '&view_uuid=%s', $url_view_uuid );
		$url_index_uuid_param = empty( $url_index_uuid ) ? '' : sprintf( '&index_uuid=%s', $url_index_uuid );

		return "{$url}{$url_view_uuid_param}{$url_index_uuid_param}";
	}
}