<?php

namespace wpsolr\core\classes\services;

/**
 * Replace class WP_Query by the child class WPSOLR_query
 * Action called at the end of wp-settings.php, before $wp_query is processed
 */

use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;
use wpsolr_root\WPSOLR_Template_Builder;

add_action( 'wp_loaded', [ WPSOLR_Service_Container::class, 'action_wp_loaded' ] );


/**
 * Manage a list of singleton objects (global objects).
 */
class WPSOLR_Service_Container {

	/** @var string */
	protected static $view_uuid;

	/** @var bool */
	protected static $is_replace_by_wpsolr_query_admin;

	/** @var array */
	private static $objects = [];

	/** @var bool */
	private static $is_replace_by_wpsolr_query;
	private static array $_archive_filters;

	/**
	 * @return bool
	 */
	public static function action_wp_loaded() {

		$is_replace_by_wpsolr_query = self::get_is_replace_by_wpsolr_query();

		if ( $is_replace_by_wpsolr_query ) {
			// Override global $wp_query with wpsolr_query
			$wpsolr_query = WPSOLR_Service_Container::get_query();
			$wpsolr_query->wpsolr_set_is_admin( static::$is_replace_by_wpsolr_query_admin );
			$wpsolr_query->wpsolr_set_view_uuid( static::$view_uuid );
			if ( ! empty( static::$_archive_filters ) ) {
				$wpsolr_query->set_archive_filter_query_fields( static::$_archive_filters );
			}
			WPSOLR_Option_View::set_current_view_uuid( static::$view_uuid );
			$GLOBALS['wp_the_query'] = $wpsolr_query;
			$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];
		}

		return $is_replace_by_wpsolr_query;
	}

	/**
	 * Get/create a singleton object from it's class.
	 *
	 * @param string $class_name
	 * @param mixed $parameter
	 * @param bool $is_shared
	 *
	 * @return mixed
	 */
	public static function getObject( $class_name, $parameter = null, $is_shared = true ) {

		if ( $is_shared && isset( self::$objects[ $class_name ] ) ) {
			return self::$objects[ $class_name ];
		}

		self::$objects[ $class_name ] = method_exists( $class_name, "global_object" )
			? isset( $parameter ) ? $class_name::global_object( $parameter ) : $class_name::global_object()
			: new $class_name();

		return self::$objects[ $class_name ];
	}

	/**
	 * @return WPSOLR_Option
	 */
	public static function getOption() {

		return self::getObject( WPSOLR_Option::class );
	}

	/**
	 * @return WPSOLR_Query_Parameters
	 */
	public static function get_query_parameters() {

		return self::getObject( WPSOLR_Query_Parameters::class );
	}


	/**
	 * @return WPSOLR_Query
	 */
	public static function get_query( WPSOLR_Query $wpsolr_query = null ) {

		return self::getObject( WPSOLR_Query::class, $wpsolr_query );
	}

	/**
	 * @return WPSOLR_Template_Builder
	 */
	public static function get_template_builder() {

		return self::getObject( WPSOLR_Template_Builder::class );
	}

	/**
	 * @param bool $is_shared
	 * @param string $index_uuid
	 *
	 * @return WPSOLR_SearchSolariumClient
	 */
	public static function get_solr_client( $is_shared = true, $index_uuid = null ) {

		// Shared
		return self::getObject( WPSOLR_SearchSolariumClient::class, $index_uuid, $is_shared );
	}

	/**
	 * @return bool
	 */
	public static function get_is_replace_by_wpsolr_query() {

		if (
			( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			isset( $_REQUEST["consumer_key"] )
		) {
			// Not REST API request
			return false;
		}

		if ( wp_doing_ajax() && ! static::is_wp_ajax_media_search() ) {
			// Not Ajax
			return false;
		}

		if ( false !== strpos( $request_uri = WPSOLR_Sanitize::sanitize_request_uri(), '/widget-types/' ) ) {
			// Not a block widget api
			return false;
		}

		// Eliminate certain requests
		if (
			( false === strpos( $request_uri, '?s=' ) ) && // not front search url
			( false === strpos( $request_uri, 'edit.php' ) ) && // not post type admin search url
			( false === strpos( $request_uri, 'upload.php' ) ) && // media library
			preg_match( '/\..{2,3}([\?].*)?$/', $request_uri ) &&
			! static::is_wp_ajax_media_search() &&
			( ( false === strpos( $request_uri, 'admin.php?page=wc-orders' ) ) ||
			  ( false !== strpos( $request_uri, 'admin.php?page=wc-orders&action=edit' ) )
			) // not HPOS order admin search url
		) {
			// static files like image1.jpg?t=1, or script1.js, or style.css?version=1
			// file1.php like Ajax or Cron php files
			// but not a filter with a dot caracter like /shop/?wpsolr_fq%5B0%5D=pa_diametrojante_str%3A34.5%20EU
			return false;
		}

		if ( isset( self::$is_replace_by_wpsolr_query ) ) {
			return self::$is_replace_by_wpsolr_query;
		}

		/**
		 * Initialize wp to get valid is_search(), is_category(), ...
		 */
		if ( ! is_admin() ) {
			global $wp;
			global $wp_the_query;
			$wp->parse_request();
			$wp_the_query->build_query_string();
			$wp_the_query->init();
			$wp_the_query->query      = wp_parse_args( $wp->query_vars );
			$wp_the_query->query_vars = $wp_the_query->query;
			$wp_the_query->parse_query();

			do_action( WPSOLR_Events::WPSOLR_ACTION_IS_REPLACE_BY_WPSOLR_QUERY_AFTER_POST_PRE_QUERY );
		}

		/**
		 * Find the view that is a front-end or admin search
		 */
		$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
		foreach ( $views as $view_uuid => $view ) {
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );
			WPSOLR_Option_View::set_current_index_uuid( WPSOLR_Service_Container::getOption()->get_view_index_uuid() );

			if ( static::$is_replace_by_wpsolr_query_admin = is_admin() ? static::get_is_replace_admin() : false ) {
				// Current view is a search admin: stop now
				static::$is_replace_by_wpsolr_query = true;
				static::$view_uuid                  = $view_uuid;
				break;
			}

			/**
			 * Important to call apply_filters here, to let some plugins initialize (like Yoast)
			 */
			if ( static::$is_replace_by_wpsolr_query = apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_REPLACE_BY_WPSOLR_QUERY, static::get_is_replace_front_end() ) ) {
				// Current view is a search front-end: stop now
				static::$view_uuid = $view_uuid;
				break;
			}

			WPSOLR_Option_View::set_current_view_uuid();
		}

		return static::$is_replace_by_wpsolr_query;
	}

	/**
	 * Replace admin query ?
	 *
	 * @return bool
	 */
	static protected function get_is_replace_admin() {

		$is_replace_by_wpsolr_query = is_main_query();


		if ( $is_replace_by_wpsolr_query ) {

			$is_replace_by_wpsolr_query = false;

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wc_hpos_order_admin() ) {
				// Replace WooCOmmerce HPOS order admin archive
				$is_replace_by_wpsolr_query = static::get_is_admin_archive_wc_hpos_order();
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_post_type_admin() ) {
				// Replace post type admin archive
				$is_replace_by_wpsolr_query = static::get_is_admin_archive_post_type();
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_media_admin() ) {
				// Replace media admin archive
				$is_replace_by_wpsolr_query = static::get_is_admin_archive_media();
			}

			if ( ! $is_replace_by_wpsolr_query &&
			     ( WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_category_admin() ||
			       WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_tag_admin() )
			) {
				// Replace taxonomy (category or tag) admin archive
				$is_replace_by_wpsolr_query = static::get_is_admin_archive_taxonomy();
			}

		}

		if ( $is_replace_by_wpsolr_query ) {
			/**
			 * Add a notice to warn users: we must do it here, because the display notice event is called before the HPOS query
			 */
			set_transient( get_current_user_id() . 'search_admin_notice', 'Query powered by WSOLR' );
		}

		return $is_replace_by_wpsolr_query;
	}

	/**
	 * Is current url the edit url of a post type selected in 2.2
	 *
	 * @return bool
	 */
	static function get_is_admin_archive_post_type() {

		$result = false;

		$is_post_type_edit_url = ( false !== strpos( WPSOLR_Sanitize::sanitize_request_uri(), '/wp-admin/edit.php' ) ); // non ajax
		$is_search_admin       = filter_var( isset( $_POST['is_search_admin'] ) && check_ajax_referer( 'security', 'security' ) ?
			WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'is_search_admin' ] ) : false, FILTER_VALIDATE_BOOLEAN ); // ajax

		if ( $is_post_type_edit_url || $is_search_admin ) {
			$admin_post_type = isset( $_GET['post_type'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'post_type' ] ) : 'post';

			$indexed_post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();
			if ( ! empty( $indexed_post_types ) && in_array( $admin_post_type, $indexed_post_types ) ) {
				// Current url is the edit url of a post type selected in 2.2.
				$result = true;
			}
		}

		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_REPLACE_ADMIN_POST_TYPE_BY_WPSOLR_QUERY, $result );
	}

	/**
	 * Is current url the edit url of a HPOS order selected in 2.2
	 *
	 * @return bool
	 */
	static function get_is_admin_archive_wc_hpos_order() {

		$result = false;

		$is_post_type_edit_url = ( false !== strpos( WPSOLR_Sanitize::sanitize_request_uri(), '/wp-admin/admin.php?page=wc-orders' ) ) &&
		                         ! isset( $_REQUEST['action'] ); // non ajax
		$is_search_admin       = filter_var( isset( $_POST['is_search_admin'] ) && check_ajax_referer( 'security', 'security' ) ?
			WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'is_search_admin' ] ) : false, FILTER_VALIDATE_BOOLEAN ); // ajax
		$is_search_admin       = $is_search_admin || (
			( 'wc-orders' === WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'page' ], '' ) && ( isset( $_GET['s'] ) ) )
			);

		if ( $is_post_type_edit_url || $is_search_admin ) {

			$indexed_post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();
			if ( ! empty( $indexed_post_types ) && in_array( 'wc_order', $indexed_post_types ) ) {
				// Current url is the edit url of a post type selected in 2.2.
				$result                   = true;
				static::$_archive_filters = [ sprintf( '%s:wc_order', WpSolrSchema::_FIELD_NAME_TYPE ), ];
			}
		}

		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_REPLACE_ADMIN_POST_TYPE_BY_WPSOLR_QUERY, $result );
	}

	/**
	 * Is current url the edit url of a media type selected in 2.2
	 *
	 * @return bool
	 */
	static function get_is_admin_archive_media() {

		$result = false;

		$is_post_type_edit_url = ( false !== strpos( WPSOLR_Sanitize::sanitize_request_uri(), '/wp-admin/upload.php' ) ); // non ajax
		$is_search_admin       = filter_var( isset( $_POST['is_search_admin'] ) && check_ajax_referer( 'security', 'security' )
			? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'is_search_admin' ] ) : false, FILTER_VALIDATE_BOOLEAN ); // ajax
		$is_media_ajax_search  = static::is_wp_ajax_media_search();

		if ( $is_post_type_edit_url || $is_search_admin || $is_media_ajax_search ) {
			$admin_post_type = 'attachment';

			$indexed_post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();
			if ( ! empty( $indexed_post_types ) && in_array( $admin_post_type, $indexed_post_types ) ) {
				// Current url is the edit url of a post type selected in 2.2.
				$result = true;
			}
		}

		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_REPLACE_ADMIN_POST_TYPE_BY_WPSOLR_QUERY, $result );
	}

	/**
	 * Is current url the edit url of a taxonomy selected in 2.2
	 *
	 * @return bool
	 */
	static function get_is_admin_archive_taxonomy() {

		$result = false;

		$is_taxonomy_edit_url = ( false !== strpos( WPSOLR_Sanitize::sanitize_request_uri(), '/wp-admin/edit-tags.php' ) );
		if ( $is_taxonomy_edit_url ) {
			$taxonomy_edit_url = isset( $_GET['taxonomy'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'taxonomy' ] ) : '';

			$indexed_taxonomies = WPSOLR_Service_Container::getOption()->get_option_index_taxonomies();
			if ( ! empty( $indexed_taxonomies ) && in_array( sprintf( '%s%s', $taxonomy_edit_url, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ), $indexed_taxonomies ) ) {
				// Current url is the edit url of a taxonomy selected in 2.2.
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Replace front-end query ?
	 *
	 * @return bool
	 */
	static protected function get_is_replace_front_end() {

		$is_replace_by_wpsolr_query = is_main_query() && WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_search_template();


		if ( $is_replace_by_wpsolr_query ) {

			$is_replace_by_wpsolr_query = false;
			/**
			 * Search
			 */
			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_search() ) {
				$is_replace_by_wpsolr_query = is_search();
			}

			/**
			 * Wordpress post archives
			 */
			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_home() ) {
				$is_replace_by_wpsolr_query = is_home();
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_author() ) {
				$is_replace_by_wpsolr_query = is_author();
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_year() ) {
				$is_replace_by_wpsolr_query = is_year();
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_month() ) {
				$is_replace_by_wpsolr_query = is_month();
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_day() ) {
				$is_replace_by_wpsolr_query = is_day();
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_category_front_end() ) {
				$is_replace_by_wpsolr_query = is_category() || static::get_is_custom_taxonomy( true );
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_tag_front_end() ) {
				$is_replace_by_wpsolr_query = is_tag() || static::get_is_custom_taxonomy( false );
			}

			if ( ! $is_replace_by_wpsolr_query && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_post_type_front_end() ) {
				$is_replace_by_wpsolr_query = is_post_type_archive();
			}
		}


		return $is_replace_by_wpsolr_query;
	}

	/**
	 * Are we on an archive custom category or custom tag?
	 *
	 * @param bool $is_category
	 *
	 * @return bool
	 */
	static public function get_is_custom_taxonomy( $is_category ) {
		global $wp_query, $wp_taxonomies;

		$result = is_tax();

		if ( $result ) {
			$queried_object  = $wp_query->get_queried_object();
			$is_hierarchical = $queried_object ? $wp_taxonomies[ $queried_object->taxonomy ]->hierarchical : false;
			$result          = $is_category ? $is_hierarchical : ! $is_hierarchical;
		}

		return $result;
	}

	/**
	 * Curretn query is from the media library?
	 * @return bool
	 */
	protected static function is_query_attachment(): bool {
		return ( isset( $_REQUEST["action"] ) && ( 'query-attachments' === WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ "action" ] ) ) );
	}

	/**
	 * @return bool
	 */
	protected static function is_wp_ajax_media_search(): bool {
		return 'query-attachments' === WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ 'action' ], '' );
	}

	/**
	 * @return WPSOLR_Service_WPSOLR
	 */
	public
	function get_service_wpsolr() {

		return self::getObject( WPSOLR_Service_WPSOLR::class );
	}

	/**
	 * @return WPSOLR_Service_PHP
	 */
	public
	function get_service_php() {

		return self::getObject( WPSOLR_Service_PHP::class );
	}

	/**
	 * @return WPSOLR_Service_WP
	 */
	public
	function get_service_wp() {

		return self::getObject( WPSOLR_Service_WP::class );
	}

	/**
	 * @return WPSOLR_Option
	 */
	public
	function get_service_option() {

		return self::getObject( WPSOLR_Option::class );
	}

	/**
	 * @return WPSOLR_Query
	 */
	public
	function get_service_wpsolr_query() {

		return self::getObject( WPSOLR_Query::class );
	}

}
