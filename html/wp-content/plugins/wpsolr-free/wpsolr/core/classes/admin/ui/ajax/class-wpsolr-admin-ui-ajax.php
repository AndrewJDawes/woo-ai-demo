<?php

namespace wpsolr\core\classes\admin\ui\ajax;

use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Ajax.
 *
 * AJAX Event Handler.
 *
 * Class WPSOLR_Admin_UI_Ajax
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax {

	const ADMIN_AJAX = 'ADMIN_AJAX';

	const AJAX_TERMS_SEARCH = 'wpsolr_ajax_terms_search';
	const AJAX_TAXONOMIES_SEARCH = 'wpsolr_ajax_taxonomies_search';
	const AJAX_RELATIONS_SEARCH = 'wpsolr_ajax_relations_search';
	const AJAX_POSTS_SEARCH = 'wpsolr_ajax_posts_search';
	const AJAX_POST_TYPES_SEARCH = 'wpsolr_ajax_post_types_search';
	const AJAX_ENVIRONMENTS_SEARCH = 'wpsolr_ajax_environments_search';
	const AJAX_INDEX_CONFIGURATIONS_SEARCH = 'wpsolr_ajax_index_configurations_search';
	const AJAX_INDEX_CONFIGURATIONS_TOKENIZERS_SEARCH = 'wpsolr_ajax_index_configurations_tokenizers_search';
	const AJAX_INDEX_CONFIGURATIONS_TOKENIZER_FILTERS_SEARCH = 'wpsolr_ajax_index_configurations_tokenizer_filters_search';
	const AJAX_INDEX_CONFIGURATIONS_CHAR_FILTERS_SEARCH = 'wpsolr_ajax_index_configurations_char_filters_search';
	const AJAX_INDEX_CONFIGURATION_DEFAULT_BUILDER = 'wpsolr_ajax_index_configuration_default_builder';
	const AJAX_MEDIA_POST_ID_CONTENT_GET = 'wpsolr_ajax_media_post_id_content_get';
	const AJAX_MEDIA_CONTENT_UPLOAD = 'wpsolr_ajax_media_content_upload';
	const AJAX_INDEX_SERVER_CONFIGURATION_FILE_GET = 'wpsolr_ajax_index_server_configuration_file_get';
	const AJAX_INDEX_SERVER_CONFIGURATION_FILE_DEPLOY = 'wpsolr_ajax_index_server_configuration_file_deploy';

	const IS_SORT_ASC = true;
	const ID_ERROR = 'error';

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = [
			self::AJAX_RELATIONS_SEARCH                              => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Relations::class,
				'nopriv' => false,
			],
			self::AJAX_TAXONOMIES_SEARCH                             => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Taxonomies::class,
				'nopriv' => false,
			],
			self::AJAX_TERMS_SEARCH                                  => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Terms::class,
				'nopriv' => false,
			],
			self::AJAX_POSTS_SEARCH                                  => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Posts::class,
				'nopriv' => false,
			],
			self::AJAX_POST_TYPES_SEARCH                             => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Post_Types::class,
				'nopriv' => false,
			],
			self::AJAX_ENVIRONMENTS_SEARCH                           => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Environments::class,
				'nopriv' => false,
			],
			self::AJAX_INDEX_CONFIGURATIONS_SEARCH                   => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Index_Configurations::class,
				'nopriv' => false,
			],
			self::AJAX_INDEX_CONFIGURATIONS_TOKENIZERS_SEARCH        => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Index_Configurations_Tokenizers::class,
				'nopriv' => false,
			],
			self::AJAX_INDEX_CONFIGURATIONS_CHAR_FILTERS_SEARCH      => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Index_Configurations_Char_Filters::class,
				'nopriv' => false,
			],
			self::AJAX_INDEX_CONFIGURATIONS_TOKENIZER_FILTERS_SEARCH => [
				'class'  => WPSOLR_Admin_UI_Ajax_Search_Index_Configurations_Tokenizer_Filters::class,
				'nopriv' => false,
			],
			self::AJAX_INDEX_CONFIGURATION_DEFAULT_BUILDER           => [
				'class'  => WPSOLR_Admin_UI_Ajax_Index_Configuration_Default_builder::class,
				'nopriv' => false,
			],
			self::AJAX_MEDIA_POST_ID_CONTENT_GET                     => [
				'class'  => WPSOLR_Admin_UI_Ajax_Media_PostId_Content_Get::class,
				'nopriv' => false,
			],
			self::AJAX_MEDIA_CONTENT_UPLOAD                          => [
				'class'  => WPSOLR_Admin_UI_Ajax_Media_Content_Upload::class,
				'nopriv' => false,
			],
			self::AJAX_INDEX_SERVER_CONFIGURATION_FILE_GET           => [
				'class'  => WPSOLR_Admin_UI_Ajax_Index_Server_File_Get::class,
				'nopriv' => false,
			],
			self::AJAX_INDEX_SERVER_CONFIGURATION_FILE_DEPLOY        => [
				'class'  => WPSOLR_Admin_UI_Ajax_Index_Server_File_Deploy::class,
				'nopriv' => false,
			],
		];

		foreach ( $ajax_events as $ajax_event_name => $ajax_event ) {

			add_action( 'wp_ajax_' . $ajax_event_name, [ $ajax_event['class'], 'do_ajax_method' ] );

			if ( $ajax_event['nopriv'] ) {
				add_action( 'wp_ajax_nopriv_wpsolr_' . $ajax_event_name, [
					$ajax_event['class'],
					'do_ajax_method',
				] );

				// WC AJAX can be used for frontend ajax requests
				add_action( 'wc_ajax_' . $ajax_event_name, [ $ajax_event['class'], 'do_ajax_method' ] );
			}
		}
	}

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_wc_ajax' ), 0 );
		self::add_ajax_events();
	}

	/**
	 * Set WC AJAX constant and headers.
	 */
	public static function define_ajax() {
		if ( ! empty( $_GET['wc-ajax'] ) ) {
			if ( ! defined( 'DOING_AJAX' ) ) {
				define( 'DOING_AJAX', true );
			}
			if ( ! defined( 'WC_DOING_AJAX' ) ) {
				define( 'WC_DOING_AJAX', true );
			}
			// Turn off display_errors during AJAX events to prevent malformed JSON
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 );
			}
			$GLOBALS['wpdb']->hide_errors();
		}
	}

	/**
	 * Send headers for WC Ajax Requests
	 * @since 2.5.0
	 */
	private static function wc_ajax_headers() {
		send_origin_headers();
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		nocache_headers();
		status_header( 200 );
	}

	/**
	 * Check for WC Ajax request and fire action.
	 */
	public static function do_wc_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['wc-ajax'] ) ) {
			$wp_query->set( 'wc-ajax', WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'wc-ajax' ] ) );
		}

		if ( $action = $wp_query->get( 'wc-ajax' ) ) {
			self::wc_ajax_headers();
			do_action( 'wc_ajax_' . WPSOLR_Sanitize::sanitize_text_field( $action ) );
			die();
		}
	}

	/**
	 * Ajax super action.
	 */
	public static function do_ajax_method() {
		global $wp_query;

		try {

			$results = [];

			define( self::ADMIN_AJAX, true );

			ob_start();

			check_ajax_referer( 'security', 'security' );// Extract parameters

			$parameters = static::extract_parameters();// Generate array of results

			$results = static::execute_parameters( $parameters );// Format array of results

			$results = static::sort_results( $results );// Sort array of results

			$results = static::format_results( $parameters, $results );

		} catch ( \Exception $e ) {

			$error_msg = $e->getMessage();

			if ( WP_DEBUG === true ) {
				WPSOLR_Escape::error_log( $error_msg );
			}

			$results[] = [
				'id'    => self::ID_ERROR,
				'label' => $error_msg,
			];

		} finally {

			// Return json results
			wp_send_json( $results );
		}

	}

	/**
	 * Ajax parameters extraction.
	 * To be implemented in children
	 *
	 * @return array
	 */
	public static function extract_parameters() {
		check_ajax_referer( 'security', 'security' ); // Redundant to pass plugin-check

		return [];
	}

	/**
	 * Ajax parameters execution.
	 * To be implemented in children
	 *
	 * @param $parameters
	 * @param $not_formatted_results
	 *
	 * @return array
	 */
	public static function format_results( $parameters, $not_formatted_results ) {
		return $not_formatted_results;
	}

	/**
	 * Ajax child action.
	 * To be implemented in children
	 *
	 * @return array
	 */
	public static function execute_parameters( $parameters ) {
		return [];
	}


	/**
	 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $var
	 *
	 * @return string|array
	 */
	public static function wc_clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'wc_clean', $var );
		} else {
			return is_scalar( $var ) ? WPSOLR_Sanitize::sanitize_text_field( $var ) : $var;
		}
	}

	/**
	 * @param array $results
	 *
	 * @return array
	 */
	public static function sort_results( $results ) {

		// Sort values by the label
		uasort( $results, function ( $a1, $b1 ) {
			$a = (array) $a1;
			$b = (array) $b1;

			/*
			if ( empty( $a['id'] ) ) {
				return - 1;
			}*/

			if ( $a['label'] == $b['label'] ) {
				return 0;
			}

			if ( static::IS_SORT_ASC ) {
				return ( $a['label'] < $b['label'] ) ? - 1 : 1;
			} else {
				return ( $a['label'] > $b['label'] ) ? - 1 : 1;
			}

		} );

		$sorted_results = [];
		foreach ( $results as $result ) {
			$sorted_results[] = $result;
		}

		return $sorted_results;
	}

}