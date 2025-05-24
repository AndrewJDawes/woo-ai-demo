<?php

use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax;
use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\metabox\WPSOLR_Metabox;
use wpsolr\core\classes\models\taxonomy\WPSOLR_Model_Meta_Type_Taxonomy;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\models\WPSOLR_Model_Meta_Type_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\shortcode\WPSOLR_Shortcode;
use wpsolr\core\classes\ui\widget\WPSOLR_Widget;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;

// Definitions
define( 'WPSOLR_PLUGIN_FILE', __FILE__ );
define( 'WPSOLR_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'WPSOLR_DEFINE_PLUGIN_DIR_URL', substr_replace( plugin_dir_url( __FILE__ ), '', - 1 ), false );
define( 'WPSOLR_PLUGIN_ANY_DIR', defined( 'WPSOLR_PLUGIN_DIR' ) ? WPSOLR_PLUGIN_DIR : WPSOLR_PLUGIN_DIR );

// Constants
const WPSOLR_AJAX_ADMIN_SETUP_WIZARD            = 'wpsolr_setup_wizard';
const WPSOLR_AJAX_AUTO_COMPLETE_ACTION          = 'wdm_return_solr_rows';
const WPSOLR_AUTO_COMPLETE_NONCE_SELECTOR       = 'wpsolr_autocomplete_nonce';

const WPSOLR_AJAX_RECOMMENDATION_ACTION         = 'wpsolr_recommendations';
const WPSOLR_AJAX_RECOMMENDATION_NONCE_SELECTOR = 'wpsolr_recommendations_nonce';

// WPSOLR autoloader
require_once( WPSOLR_PLUGIN_ANY_DIR . '/wpsolr/core/class-wpsolr-autoloader.php' );

// WPSOLR Filters (compatibility)
require_once( WPSOLR_PLUGIN_ANY_DIR . '/wpsolr/core/classes/class-wpsolrfilters-old.php' );

global $license_manager;
$license_manager                                = new OptionLicenses();

// Composer autoloader
if ( ! isset( $_REQUEST['page'] ) || ( false === strpos( WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ 'page' ] ), 'wpcf-' ) ) ) {
	// No autoloader for toolset admin, else conflict with its twig libraries
	if ( ! defined( 'WPSOLR_TEST_PLUGIN' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );
	}
}

require_once 'ajax_solr_services.php';
require_once 'dashboard/dashboard.php';
require_once 'autocomplete.php';
require_once 'classes/admin/setup_wizard/class-wpsolr-admin-setup-wizard-factory.php';

/* Register index settings from dashboard
 * Add menu page in dashboard - index settings
 * Add solr settings- solr host, post and path
 *
 */
add_action( 'wp_head', 'check_default_options_and_function' );
add_action( 'admin_menu', 'fun_add_solr_settings' );
add_action( 'admin_init', 'wpsolr_admin_init' );
add_action( 'admin_enqueue_scripts', 'wpsolr_enqueue_script', 99 ); // admin
add_action( 'wp_enqueue_scripts', 'wpsolr_enqueue_script' ); // front


// Register WpSolr widgets and shortcodes when current theme's search is used.
if ( WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_search_template() ) {
	WPSOLR_Widget::Autoload();
	WPSOLR_Shortcode::Autoload();
}

if ( is_admin() ) {

	// Init Ajax admin methods
	WPSOLR_Admin_UI_Ajax::init();

	/*
	 * Register metabox
	 */
	WPSOLR_Metabox::register();
}

/*
 * Display index errors in admin when a save on a post can't index to index
 */
function solr_post_save_admin_notice() {

	delete_transient( 'wpsolr_cache_indexed_posts' );

	if ( $out = get_transient( get_current_user_id() . 'error_solr_post_save_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'error_solr_post_save_admin_notice' );
		WPSOLR_Escape::echo_escaped( sprintf( "<div class=\"error wpsolr_admin_notice_error\"><p>(WPSOLR) Error while indexing this post type:<br><br>%s</p></div>", WPSOLR_Escape::esc_html( $out ) ) );
	}

	if ( $out = get_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' );
		WPSOLR_Escape::echo_escaped( sprintf( "<div class=\"updated wpsolr_admin_notice_updated\"><p>(WPSOLR) %s</p></div>", WPSOLR_Escape::esc_html( $out ) ) );
	}

	if ( $out = get_transient( get_current_user_id() . 'wpsolr_some_languages_have_no_solr_index_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'wpsolr_some_languages_have_no_solr_index_admin_notice' );
		WPSOLR_Escape::echo_escaped( sprintf( "<div class=\"error wpsolr_admin_notice_error\"><p>(WPSOLR) %s</p></div>", WPSOLR_Escape::esc_html( $out ) ) );
	}

	if ( $out = get_transient( get_current_user_id() . 'wpsolr_error_during_search' ) ) {
		delete_transient( get_current_user_id() . 'wpsolr_error_during_search' );
		WPSOLR_Escape::echo_escaped( sprintf( "<div class=\"error wpsolr_admin_notice_error\"><p>(WPSOLR) Error while searching. WPSOLR search is not used, standard Wordpress search results are displayed instead.<br><br>%s</p></div>", WPSOLR_Escape::esc_html( $out ) ) );
	}

	if ( $out = get_transient( get_current_user_id() . 'search_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'search_admin_notice' );
		WPSOLR_Escape::echo_escaped( sprintf( "<div class=\"updated wpsolr_admin_notice_updated\"><p>(WPSOLR) %s</p></div>", WPSOLR_Escape::esc_html_but_href( $out ) ) );
	}

}

add_action( 'admin_notices', "solr_post_save_admin_notice" );

if ( defined( 'WPSOLR_PLUGIN_VERSION' ) ) {
	// Index as soon as a save is performed.
	add_action( 'save_post', 'add_remove_document_to_solr_index', 999, 3 ); // Must be after everybody to ensure real-time indexing happens on already published posts. Especially WPML.
	do_action( 'after_delete_post', 'add_remove_document_to_solr_index', 10, 2 ); // wp_delete_post()


	add_action( 'add_attachment', 'add_attachment_to_solr_index', 10, 3 );
	add_action( 'edit_attachment', 'add_attachment_to_solr_index', 10, 3 );
	add_action( 'delete_attachment', 'delete_attachment_to_solr_index', 10, 3 );
	add_action( 'create_term', 'add_edit_term_to_solr_index', 10, 3 );
	add_action( 'edit_term', 'add_edit_term_to_solr_index', 10, 3 );
	add_action( 'pre_delete_term', 'delete_term_from_solr_index', 10, 2 ); // just before term is deleted


	if ( WPSOLR_Service_Container::getOption()->get_index_are_comments_indexed() ) {
		// new comment
		add_action( 'comment_post', 'add_remove_comment_to_solr_index', 11, 1 );

		// approved, unaproved, trashed, untrashed, spammed, unspammed
		add_action( 'wp_set_comment_status', 'add_remove_comment_to_solr_index', 11, 1 );
	}
}

/**
 * Reindex a post when one of it's comment is updated.
 *
 * @param $comment_id
 */
function add_remove_comment_to_solr_index( $comment_id ) {

	$comment = get_comment( $comment_id );

	if ( ! empty( $comment ) ) {

		add_remove_document_to_solr_index( $comment->comment_post_ID, get_post( $comment->comment_post_ID ) );
	}
}



/**
 * Add/remove document to/from index when status changes to/from published
 * We have to use action 'save_post', as it is used by other plugins to trigger meta boxes save
 *
 * @param $post_id
 * @param $post
 */
function add_remove_document_to_solr_index( $post_id, $post ) {

	if ( empty( $model_type = WPSOLR_Model_Builder::get_model_type_object( $post->post_type ) ) ) {
		return;
	}

	$messages[] = _add_remove_document_to_solr_index( $model_type, $post, $model_type );


	$transients = [];
	foreach ( $messages as $message ) {
		foreach ( $message as $message_id => $messages_texts ) {
			$transients[ $message_id ][] = implode( ' ', $messages_texts );
		}
	}
	foreach ( $transients as $message_id => $messages_texts ) {
		set_transient( $message_id, implode( ' ', $messages_texts ) );
	}

}

/**
 * @param $post_id
 * @param $post
 * @param WPSOLR_Model_Meta_Type_Abstract $model_type
 *
 * @return array
 */
function _add_remove_document_to_solr_index( $post_id, $post, $model_type ) {

	// If this is just a revision, don't go on.
	if ( wp_is_post_revision( $post_id ) ) {
		return [];
	}

	if ( ( 'shop_order' === $post->post_type ) &&
	     // phpcs:ignore WordPress.Security.NonceVerification.Missing
	     ( 'edit' !== ( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'action' ], '' ) ) )
	) {
		// legacy order in compatibility mode
		// Prevent indexing on non post actions, like heartbeat or locking order on loading
		//return;
	}

	$messages    = [];
	$index_uuids = [];
	WPSOLR_Option_View::backup_current_view_uuid();
	$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
	foreach ( $views as $view_uuid => $view ) {
		try {
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

			if ( in_array( $index_uuid = WPSOLR_Service_Container::getOption()->get_view_index_uuid(), $index_uuids ) ||
			     empty( $index_uuid )
			) {
				// Index already processed in another view, or no index in current view
				continue;
			}
			WPSOLR_Option_View::set_current_index_uuid( $index_uuid );
			$index_uuids[] = $index_uuid;

			// If real-time is deactivated.
			if ( ! WPSOLR_Service_Container::getOption()->get_index_is_real_time() ) {
				continue;
			}

			// If this model type is not indexable in setup, don't go on.
			if ( ! WPSOLR_Model_Meta_Type_Abstract::get_is_model_type_can_be_indexed( $model_type->get_type() ) ) {
				continue;
			}

			$index_post_statuses = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_STATUSES_TO_INDEX, array( 'publish' ), $post->post_type );
			if ( ( WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_post_type_admin() &&
			       WPSOLR_Service_Container::getOption()->get_option_index_post_type_is_admin( $post->post_type ) ) ||
			     ( WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wc_hpos_order_admin() &&
			       ( 'wc_order' === $post->post_type ) ) ||
			     ( in_array( $post->post_status, $index_post_statuses, true ) && ! WPSOLR_Metabox::get_metabox_is_do_not_index( $post->ID ) ) ) {
				// post unpublised with admin search, or post published, add/update it from index

				$solr = WPSOLR_AbstractIndexClient::create_from_post( $post );

				$results = $solr->index_data( false, 'default', [ $model_type ], 1, $post );// Display confirmation in admin, if one doc at least has been indexed
				if ( ! empty( $results ) && ! empty( $results['nb_results'] ) ) {

					$messages[ get_current_user_id() . 'updated_solr_post_save_admin_notice' ][] =
						sprintf( '%s updated in index \'%s\'. %s',
							ucfirst( $model_type->get_singular_label() ), $solr->index['index_name'], $solr->get_notice_message() );
				}

			} else {

				if ( 'auto-draft' === $post->post_status ) {
					// Need indexing for admin to appear in post status filter
					// but no removing message and delete else
					continue;
				}


				// post unpublished without admin search, or modified with 'do not index', remove it from index
				$solr = WPSOLR_AbstractIndexClient::create_from_post( $post );

				$solr->delete_document( WPSOLR_Model_Builder::get_model( WPSOLR_Model_Builder::get_model_type_object( $post->post_type ), $post->ID ) );

				// Display confirmation in admin
				$messages[ get_current_user_id() . 'updated_solr_post_save_admin_notice' ][] = sprintf( '%s removed from index \'%s\'', ucfirst( $post->post_type ), $solr->index['index_name'] );
			}

		} catch ( Exception $e ) {
			$messages[ get_current_user_id() . 'error_solr_post_save_admin_notice' ][] =
				sprintf( '%s error with index \'%s\'. %s',
					ucfirst( $post->post_type ), $solr->index['index_name'], htmlentities( $e->getMessage() ) );
		}

	}
	WPSOLR_Option_View::restore_current_view_uuid();

	return $messages;
}

/*
 * Add an attachment to index
 */
function add_attachment_to_solr_index( $attachment_id ) {

	$filetype = wp_check_filetype( wp_get_attachment_url( $attachment_id ) );

	$messages    = [];
	$index_uuids = [];
	WPSOLR_Option_View::backup_current_view_uuid();
	$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
	foreach ( $views as $view_uuid => $view ) {
		try {
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

			if ( in_array( $index_uuid = WPSOLR_Service_Container::getOption()->get_view_index_uuid(), $index_uuids ) ||
			     empty( $index_uuid ) ) {
				// Index already processed in another view
				continue;
			}
			WPSOLR_Option_View::set_current_index_uuid( $index_uuid );
			$index_uuids[] = $index_uuid;

			if ( ! empty( $filetype['type'] ) && ! in_array( $filetype['type'], WPSOLR_Service_Container::getOption()->get_option_index_attachment_types(), true ) ) {
				// Prevent indexing attachment files not declared in screen 2.2
				return;
			}

			// If real-time is deactivated.
			if ( ! WPSOLR_Service_Container::getOption()->get_index_is_real_time() ) {
				continue;
			}

			// Index the new attachment
			$solr = WPSOLR_AbstractIndexClient::create();

			$post    = get_post( $attachment_id );
			$results = $solr->index_data( false, 'default', WPSOLR_Model_Builder::get_model_type_objects( [ $post->post_type ] ), 1, $post );

			// Display confirmation in admin, if one doc at least has been indexed
			if ( ! empty( $results ) && ! empty( $results['nb_results'] ) ) {

				$messages[ get_current_user_id() . 'updated_solr_post_save_admin_notice' ][] = sprintf( 'Media file uploaded to index "%s"', $solr->index['index_name'] );
			}

		} catch ( Exception $e ) {

			$messages[ get_current_user_id() . 'error_solr_post_save_admin_notice' ][] =
				sprintf( '%s error with index \'%s\'. %s',
					ucfirst( $post->post_type ), $solr->index['index_name'], htmlentities( $e->getMessage() ) );
		}
	}
	WPSOLR_Option_View::restore_current_view_uuid();

	foreach ( $messages as $message_id => $messages_texts ) {
		set_transient( $message_id, implode( '<br>', $messages_texts ) );
	}

}

/*
 * Delete an attachment from index
 */
function delete_attachment_to_solr_index( $attachment_id ) {

	$messages    = [];
	$index_uuids = [];
	WPSOLR_Option_View::backup_current_view_uuid();
	$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
	foreach ( $views as $view_uuid => $view ) {
		WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

		if ( in_array( $index_uuid = WPSOLR_Service_Container::getOption()->get_view_index_uuid(), $index_uuids ) ||
		     empty( $index_uuid ) ) {
			// Index already processed in another view
			continue;
		}
		WPSOLR_Option_View::set_current_index_uuid( $index_uuid );
		$index_uuids[] = $index_uuid;

		// If real-time is deactivated.
		if ( ! WPSOLR_Service_Container::getOption()->get_index_is_real_time() ) {
			continue;
		}

		// Remove the attachment from index
		try {
			$solr = WPSOLR_AbstractIndexClient::create();

			$post = get_post( $attachment_id );
			$solr->delete_document( WPSOLR_Model_Builder::get_model( WPSOLR_Model_Builder::get_model_type_object( $post->post_type ), $post->ID ) );

			$messages[ get_current_user_id() . 'updated_solr_post_save_admin_notice' ][] = sprintf( 'Attachment deleted from index "%s"', $solr->index['index_name'] );

		} catch ( Exception $e ) {

			$messages[ get_current_user_id() . 'error_solr_post_save_admin_notice' ][] =
				sprintf( '%s error with index \'%s\'. %s',
					ucfirst( $post->post_type ), $solr->index['index_name'], htmlentities( $e->getMessage() ) );
		}
	}
	WPSOLR_Option_View::restore_current_view_uuid();

}

/**
 * Add a term to index
 *
 * @param int $term_id
 * @param int $tt_id
 * @param string $taxonomy
 */
function add_edit_term_to_solr_index( $term_id, $tt_id, $taxonomy ) {

	$messages    = [];
	$index_uuids = [];
	WPSOLR_Option_View::backup_current_view_uuid();
	$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
	foreach ( $views as $view_uuid => $view ) {
		WPSOLR_Option_View::set_current_view_uuid( $view_uuid );
		if ( in_array( $index_uuid = WPSOLR_Service_Container::getOption()->get_view_index_uuid(), $index_uuids ) ||
		     empty( $index_uuid ) ) {
			// Index already processed in another view
			continue;
		}
		WPSOLR_Option_View::set_current_index_uuid( $index_uuid );
		$index_uuids[] = $index_uuid;

		try {
			// Update index history
			if ( in_array( $taxonomy, WPSOLR_Service_Container::getOption()->get_option_index_post_types(), true ) ) {

				WPSOLR_Model_Meta_Type_Taxonomy::index_history_add( $term_id );

				$solr = WPSOLR_AbstractIndexClient::create();

				$term    = get_term( $term_id, $taxonomy );
				$results = $solr->index_data( false, 'default', WPSOLR_Model_Builder::get_model_type_objects( [ $taxonomy ] ), 1, $term );

				// Display confirmation in admin, if one doc at least has been indexed
				if ( ! empty( $results ) && ! empty( $results['nb_results'] ) ) {

					$taxonomy_obj                                                                = get_taxonomy( $taxonomy );
					$messages[ get_current_user_id() . 'updated_solr_post_save_admin_notice' ][] = sprintf( '%s updated in index "%s', $taxonomy_obj->labels->singular_name, $solr->index['index_name'] );
				}
			}

		} catch ( Exception $e ) {

			$messages[ get_current_user_id() . 'error_solr_post_save_admin_notice' ][] =
				sprintf( '%s error with index \'%s\'. %s',
					ucfirst( $taxonomy_obj->labels->singular_name ), $solr->index['index_name'], htmlentities( $e->getMessage() ) );
		}
	}
	WPSOLR_Option_View::restore_current_view_uuid();

	foreach ( $messages as $message_id => $messages_texts ) {
		set_transient( $message_id, implode( '<br>', $messages_texts ) );
	}

}

/**
 * Remove a term from index
 *
 * @param int $term_id
 * @param int $tt_id
 * @param string $taxonomy
 */
function delete_term_from_solr_index( $term_id, $taxonomy ) {

	$messages    = [];
	$index_uuids = [];
	WPSOLR_Option_View::backup_current_view_uuid();
	$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
	foreach ( $views as $view_uuid => $view ) {
		WPSOLR_Option_View::set_current_view_uuid( $view_uuid );
		if ( in_array( $index_uuid = WPSOLR_Service_Container::getOption()->get_view_index_uuid(), $index_uuids ) ||
		     empty( $index_uuid ) ) {
			// Index already processed in another view
			continue;
		}
		WPSOLR_Option_View::set_current_index_uuid( $index_uuid );
		$index_uuids[] = $index_uuid;

		try {
			// Update index history
			if ( in_array( $taxonomy, WPSOLR_Service_Container::getOption()->get_option_index_post_types(), true ) ) {
				WPSOLR_Model_Meta_Type_Taxonomy::index_history_delete( $term_id );
			}

			$solr = WPSOLR_AbstractIndexClient::create();

			$solr->delete_document( WPSOLR_Model_Builder::get_model( WPSOLR_Model_Builder::get_model_type_object( $taxonomy ), $term_id ) );

			$taxonomy_obj                                                                = get_taxonomy( $taxonomy );
			$messages[ get_current_user_id() . 'updated_solr_post_save_admin_notice' ][] = sprintf( '%s removed from index "%s', $taxonomy_obj->labels->singular_name, $solr->index['index_name'] );

		} catch ( Exception $e ) {

			$messages[ get_current_user_id() . 'error_solr_post_save_admin_notice' ][] =
				sprintf( '%s error with index \'%s\'. %s',
					ucfirst( $taxonomy_obj->labels->singular_name ), $solr->index['index_name'], htmlentities( $e->getMessage() ) );
		}
	}
	WPSOLR_Option_View::restore_current_view_uuid();

	foreach ( $messages as $message_id => $messages_texts ) {
		set_transient( $message_id, implode( '<br>', $messages_texts ) );
	}

}

/**
 * Replace WordPress search
 * Default WordPress will be replaced with index search
 */
function check_default_options_and_function() {

	if ( WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_search() && ! WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_search_template() ) {

		add_filter( 'get_search_form', 'solr_search_form', 99 );

	}
}

add_filter( 'template_include', 'wpsolr_ajax_template_include', 99 );
function wpsolr_ajax_template_include( $template ) {

	if ( is_page( WPSOLR_Service_Container::getOption()->get_search_ajax_search_page_slug() ) ) {
		$new_template = locate_template( WPSOLR_SearchSolariumClient::_SEARCH_PAGE_TEMPLATE );
		if ( '' != $new_template ) {
			return $new_template;
		}
	}

	return $template;
}

/* Create default page template for search results
*/
add_shortcode( 'solr_search_shortcode', 'fun_search_indexed_data' );
add_shortcode( 'solr_form', 'fun_dis_search' );
function fun_dis_search() {
	WPSOLR_Escape::echo_escaped( solr_search_form() );
}

add_action( 'admin_notices', 'curl_dependency_check' );
function curl_dependency_check() {
	if ( ! in_array( 'curl', get_loaded_extensions() ) ) {

		WPSOLR_Escape::echo_escaped( "<div class='updated'><p><b>cURL</b> is not installed on your server. In order to make <b>'WPSOLR'</b> plugin work, you need to install <b>cURL</b> on your server </p></div>" );
	}


}


function solr_search_form() {

	ob_start();

	// Load current theme's wpsolr search form if it exists
	$search_form_template = locate_template( 'wpsolr-search-engine/searchform.php' );
	if ( '' !== $search_form_template ) {

		require( $search_form_template );
		$form = ob_get_clean();

	} else {

		$ad_url = admin_url();

		if ( isset( $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q ] ) ) {
			$search_que = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q ] );
		} else if ( isset( $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH ] ) ) {
			$search_que = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH ] );
		} else {
			$search_que = '';
		}

		// Get localization options
		$localization_options = OptionLocalization::get_options();

		$wdm_typehead_request_handler = WPSOLR_AJAX_AUTO_COMPLETE_ACTION;

		$get_page_info = WPSOLR_SearchSolariumClient::get_search_page();
		$ajax_nonce    = wp_create_nonce( "nonce_for_autocomplete" );


		$url = get_permalink( $get_page_info->ID );
		// Filter the search page url. Used for multi-language search forms.
		$url = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_PAGE_URL, $url, $get_page_info->ID );

		$form = "<div class='cls_search' style='width:100%'><form action='$url' method='get'  class='search-frm2' >";
		$form .= '<input type="hidden" value="' . $wdm_typehead_request_handler . '" id="path_to_fold">';
		$form .= '<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">';

		$form .= '<input type="hidden" value="' . $ad_url . '" id="path_to_admin">';
		$form .= '<input type="hidden" value="' . WPSOLR_Service_Container::get_query()->get_wpsolr_query( '', true ) . '" id="search_opt">';

		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $url_params );
		if ( ! empty( $url_params ) && isset( $url_params['lang'] ) ) {
			$form .= '<input type="hidden" value="' . esc_attr( $url_params['lang'] ) . '" name="lang">';
		}

		$form .= '
       <div class="ui-widget search-box">
 	<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">
        <input type="text" placeholder="' . OptionLocalization::get_term( $localization_options, 'search_form_edit_placeholder' ) . '" value="' . WPSOLR_Service_Container::get_query()->get_wpsolr_query( '', true ) . '" name="' . WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q . '" id="search_que" class="' . WPSOLR_Option::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT . ' sfl1" autocomplete="off"/>
	<input type="submit" value="' . OptionLocalization::get_term( $localization_options, 'search_form_button_label' ) . '" id="searchsubmit" style="position:relative;width:auto">
		         <input type="hidden" value="' . WPSOLR_Service_Container::getOption()->get_search_after_autocomplete_block_submit() . '" id="is_after_autocomplete_block_submit">'
		         . apply_filters( WPSOLR_Events::WPSOLR_FILTER_APPEND_FIELDS_TO_AJAX_SEARCH_FORM, '' )
		         . '<div style="clear:both"></div></div></form></div>';

	}

	return $form;
}

add_action( 'after_setup_theme', 'wpsolr_after_setup_theme' ); // Some plugins are loaded with the theme, like ACF. We need to wait till then.
function wpsolr_after_setup_theme() {

	// Load active extensions
	WPSOLR_Extension::load();

	/*
	 * Load WPSOLR text domain to the Wordpress languages plugin directory (WP_LANG_DIR/plugins)
	 * Copy your .mo files there
	 * Example: /htdocs/wp-includes/languages/plugins/wpsolr-fr_FR.mo or /htdocs/wp-content/languages/plugins/wpsolr-fr_FR.mo
	 * You can find our .pot files in this plugin's /wpsolr-pro/wpsolr/core/languages/wpsolr.pot file
	 */
	load_plugin_textdomain( 'wpsolr', false, false );
}

function wpsolr_enqueue_script() {

	// Load scripts and css only in WPSOLR admin pages
	if ( false !== strpos( WPSOLR_Sanitize::sanitize_request_uri(), 'solr_settings' ) ) {

		/**
		 * select2 dropdown list
		 *
		 * Here because it must be called in last to unregister other versions more recent and incompatible (like MyLising theme's)
		 *
		 */
		wp_dequeue_script( 'select2' );
		wp_deregister_script( 'select2' );
		wp_deregister_style( 'select2' );
		wp_register_script( 'select2', plugins_url( './js/select2/select2.js', __FILE__ ), [ 'jquery' ], false, false );
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'select2', plugins_url( './css/select2.css', __FILE__ ) );

		// Enhanced select
		wp_enqueue_script( 'wpsolr-enhanced-select', plugins_url( './js/wpsolrc-enhanced-select.js', __FILE__ ), [
			'jquery',
			'select2'
		], WPSOLR_PLUGIN_VERSION );
		wp_localize_script( 'wpsolr-enhanced-select', 'wpsolrc_enhanced_select_params', [
			'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'wpsolr-free' ),
			'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'wpsolr-free' ),
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'wpsolr-free' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'wpsolr-free' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'wpsolr-free' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'wpsolr-free' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'wpsolr-free' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'wpsolr-free' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'wpsolr-free' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'wpsolr-free' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'wpsolr-free' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'wpsolr-free' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'security'                  => wp_create_nonce( 'security' ),
		] );

	}

	/**
	 * Autocomplete in front and backend
	 */
	wpsolr_enqueue_search();

	wpsolr_enqueue_infinitescroll();
}

/**
 * Autocomplete and search
 */
function wpsolr_enqueue_search() {
	global $pagenow, $wp_query;

	if ( ! $wp_query instanceof WPSOLR_Query ) {
		// suggestions! return;
	}

	$active_view_uuid = $wp_query->wpsolr_get_view_uuid();

	WPSOLR_Option_View::backup_current_view_uuid();
	$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
	foreach ( $views as $view_uuid => $view ) {
		if ( $active_view_uuid !== $view_uuid ) {
			// Not the active view
			// suggestions! continue;
		}

		WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

		if ( is_admin() &&
		     ! ( ! empty( $pagenow ) && ( 'edit.php' === $pagenow ) && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_post_type_admin() ) &&
		     ! ( ( 'wc-orders' === WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ 'page' ], '' ) ) && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wc_hpos_order_admin() ) &&
		     ! ( ! empty( $pagenow ) && ( 'upload.php' === $pagenow ) && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_media_admin() )
		) {
			// No need in admin, unless it is an archive/media and admin/media archives search is active
			continue;
		}

		if ( ! WPSOLR_Service_Container::getOption()->get_search_is_prevent_loading_front_end_css() ) {
			wp_enqueue_style( 'solr_frontend', plugins_url( 'css/style.css', __FILE__ ), array(), WPSOLR_PLUGIN_VERSION );
		}

		$is_autocomplete = true;
		if ( $is_autocomplete ) {
			// In this mode, suggestions do not work, as suggestions cannot be filtered by site.

			wp_enqueue_script( 'solr_auto_js1', plugins_url( 'js/devbridge/jquery.autocomplete.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );
		}

		// Url utilities to manipulate the url parameters
		wp_enqueue_script( 'urljs', plugins_url( 'bower_components/jsurl/url.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );
		wp_enqueue_script( 'autocomplete', plugins_url( 'js/autocomplete_solr.js', __FILE__ ),
			$is_autocomplete ? [ 'solr_auto_js1', 'urljs' ] : [ 'urljs' ],
			WPSOLR_PLUGIN_VERSION, true );

		wp_enqueue_script( 'loadingoverlay', plugins_url( 'js/loadingoverlay/loadingoverlay.min.js', __FILE__ ),
			$is_autocomplete ? [ 'solr_auto_js1', 'urljs' ] : [ 'urljs' ],
			WPSOLR_PLUGIN_VERSION, true );

		$is_ajax = WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_with_ajax();

		$is_event_tracking_cached = false;

		wp_localize_script( 'autocomplete', 'wp_localize_script_autocomplete',
			apply_filters( WPSOLR_Events::WPSOLR_FILTER_JAVASCRIPT_FRONT_LOCALIZED_PARAMETERS,
				[
					'data' =>
						[
							WPSOLR_Query_Parameters::SEARCH_PARAMETER_VIEW_UUID => $view_uuid,
							'ajax_url'                                          => admin_url( 'admin-ajax.php' ),
							'lang'                                              => '',
							'is_show_url_parameters'                            => WPSOLR_Service_Container::getOption()->get_search_is_show_url_parameters(),
							'is_ajax'                                           => (bool) $is_ajax,
							'SEARCH_PARAMETER_VIEW_ID'                          => WPSOLR_Query_Parameters::SEARCH_PARAMETER_VIEW_UUID,
							'SEARCH_PARAMETER_S'                                => WPSOLR_Query_Parameters::SEARCH_PARAMETER_S,
							'SEARCH_PARAMETER_SEARCH'                           => WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH,
							'SEARCH_PARAMETER_Q'                                => WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q,
							'SEARCH_PARAMETER_FQ'                               => WPSOLR_Query_Parameters::SEARCH_PARAMETER_FQ,
							'SEARCH_PARAMETER_SORT'                             => WPSOLR_Query_Parameters::SEARCH_PARAMETER_SORT,
							'SEARCH_PARAMETER_PAGE'                             => WPSOLR_Query_Parameters::SEARCH_PARAMETER_PAGE,
							'SORT_CODE_BY_RELEVANCY_DESC'                       => WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC,
							'css_ajax_container_page_title'                     => empty( $container_page_title ) ? WPSOLR_Option::OPTION_THEME_AJAX_PAGE_TITLE_JQUERY_SELECTOR_DEFAULT : $container_page_title,
							'css_ajax_container_page_sort'                      => empty( $container_page_sort ) ? WPSOLR_Option::OPTION_THEME_AJAX_SORT_JQUERY_SELECTOR_DEFAULT : $container_page_sort,
							'css_ajax_container_results'                        => empty( $container_results ) ? WPSOLR_Option::OPTION_THEME_AJAX_RESULTS_JQUERY_SELECTOR_DEFAULT : $container_results,
							'css_ajax_container_overlay'                        => empty( $container_overlay ) ? WPSOLR_Option::OPTION_THEME_AJAX_RESULTS_JQUERY_SELECTOR_DEFAULT : $container_overlay,
							'css_ajax_container_pagination'                     => empty( $container_pagination ) ? WPSOLR_Option::OPTION_THEME_AJAX_PAGINATION_JQUERY_SELECTOR_DEFAULT : $container_pagination,
							'css_ajax_container_pagination_page'                => empty( $container_pagination_page ) ? WPSOLR_Option::OPTION_THEME_AJAX_PAGINATION_PAGE_JQUERY_SELECTOR_DEFAULT : $container_pagination_page,
							'css_ajax_container_results_count'                  => empty( $container_results_count ) ? WPSOLR_Option::OPTION_THEME_AJAX_RESULTS_COUNT_JQUERY_SELECTOR_DEFAULT : $container_results_count,
							'ajax_delay_ms'                                     => empty( $ajax_delay_ms ) ? 500 : $ajax_delay_ms,
							'redirect_search_home'                              => apply_filters( WPSOLR_Events::WPSOLR_FILTER_REDIRECT_SEARCH_HOME, '' ),
							'suggestions_icon'                                  => WPSOLR_PLUGIN_DIR_IMAGE_URL . 'wpsolr-ajax-loader.gif',
							'is_event_tracking_cached'                          => $is_event_tracking_cached,
						],
				]
			),
			WPSOLR_PLUGIN_VERSION
		);

		// Only one view. Too many modifications on 'wp_localize_script_autocomplete' to do for this first release of Views.
		break;
	}
	WPSOLR_Option_View::restore_current_view_uuid();

}

/*
 * Infinite scroll: load javascript if option is set.
 */
function wpsolr_enqueue_infinitescroll() {

	if ( is_admin() ) {
		// No need in admin
		return;
	}

	if ( WPSOLR_Service_Container::getOption()->get_search_is_infinitescroll() && ! WPSOLR_Service_Container::getOption()->get_search_is_infinitescroll_replace_js() ) {
		// Get localization options
		$localization_options = OptionLocalization::get_options();

		wp_register_script( 'infinitescroll', plugins_url( '/js/jquery.infinitescroll.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );

		wp_enqueue_script( 'infinitescroll' );

		// loadingtext for translation
		// loadimage custom loading image url
		wp_localize_script( 'infinitescroll', 'wp_localize_script_infinitescroll',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'loadimage'          => plugins_url( '/images/infinitescroll.gif', __FILE__ ),
				'loadingtext'        => OptionLocalization::get_term( $localization_options, 'infinitescroll_loading' ),
				'SEARCH_PARAMETER_Q' => WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q,
			),
			WPSOLR_PLUGIN_VERSION
		);
	}
}

/*
 *  Add hidden fields in footer containing the nonce for auto suggestions on non-wpsolr search boxes
 */
if ( WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_NONE !== WPSOLR_Service_Container::getOption()->get_search_suggest_content_type_before_version_21_5() ) {
	function wpsolr_footer() {
		?>

        <!-- wpsolr - ajax auto completion nonce -->
        <input type="hidden" id="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AUTO_COMPLETE_NONCE_SELECTOR ); ?>"
               value="<?php WPSOLR_Escape::echo_esc_attr( wp_create_nonce( 'nonce_for_autocomplete' ) ); ?>">

		<?php
	}

	add_action( 'wp_footer', 'wpsolr_footer' );
	add_action( 'admin_footer', 'wpsolr_footer' ); // Nonce for autocomplete in admin archives
}

function wpsolr_activate() {

	if ( ! is_multisite() ) {
		/**
		 * Mark licenses
		 */
		WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_LICENSES, true );
		OptionLicenses::upgrade_licenses();
	}
}

register_activation_hook( __FILE__, 'wpsolr_activate' );
