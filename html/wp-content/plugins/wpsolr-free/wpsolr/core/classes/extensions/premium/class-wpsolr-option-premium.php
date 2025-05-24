<?php

namespace wpsolr\core\classes\extensions\premium;

use WP_Query;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\services\WPSOLR_Service_Container_Factory;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_Option_Premium
 * @package wpsolr\core\classes\extensions\premium
 */
class WPSOLR_Option_Premium extends WPSOLR_Extension {
	use WPSOLR_Service_Container_Factory;

	// Hard-coded pagination value in WP
	const WP_ADMIN_NB_RESULTS_BY_PAGE = 20;

	/** @var bool */
	protected $is_replace_admin_post_type_by_wpsolr_query = false, $is_replace_admin_admin_taxonomy_by_wpsolr_query = false;
	/**
	 * @var bool
	 */
	protected $is_ajax_processing = false;
	/**
	 * @var bool
	 */
	protected $before_media_query = false;

	/**
	 * Constructor.
	 */
	function __construct() {

		add_action( WPSOLR_Events::WPSOLR_FILTER_LAYOUT_OBJECT, [
			$this,
			'wpsolr_filter_layout_object',
		], 10, 2 );

		add_filter( WPSOLR_Events::WPSOLR_FILTER_FACET_FEATURE_LAYOUTS, [
			$this,
			'wpsolr_filter_facet_feature_layouts'
		], 10, 2 );

		add_filter( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, [ $this, 'wpsolr_filter_include_file' ], 10, 1 );

		add_action( WPSOLR_Events::WPSOLR_ACTION_OPTION_SET_REALTIME_INDEXING, [
			$this,
			'wpsolr_action_option_set_realtime_indexing'
		], 10, 1 );

		/**
		 * Admin search
		 */
		add_action( WPSOLR_Events::WPSOLR_FILTER_POST_TYPES, [
			$this,
			'wpsolr_filter_post_types',
		], 99, 2 ); // after all others

		add_action( WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY, [
			$this,
			'wpsolr_action_query',
		], 10, 1 );

		if ( is_admin() ) {

			add_action( WPSOLR_Events::WPSOLR_FILTER_IS_REPLACE_ADMIN_POST_TYPE_BY_WPSOLR_QUERY, [
				$this,
				'wpsolr_filter_is_replace_admin_post_type_by_wpsolr_query',
			], 10, 1 );

			add_action( WPSOLR_Events::WPSOLR_FILTER_UPDATE_WPSOLR_QUERY, [
				$this,
				'wpsolr_filter_update_wpsolr_query',
			], 10, 1 );

			// No highlighting tags
			add_filter( WPSOLR_Events::WPSOLR_FILTER_HIGHLIGHTING_PREFIX, [
				$this,
				'wpsolr_filter_highlighting_prefix'
			], 10, 1 );
			add_filter( WPSOLR_Events::WPSOLR_FILTER_HIGHLIGHTING_POSFIX, [
				$this,
				'wpsolr_filter_highlighting_posfix'
			], 10, 1 );

			// Admin media query
			if ( $this->is_wp_ajax_media_search()
				//&& $this->get_container()->get_service_option()->get_search_is_replace_default_wp_media_admin()
			) {
				add_filter( 'posts_pre_query', [ $this, 'posts_pre_query' ], 10, 2 );
				add_filter( 'ajax_query_attachments_args', [ $this, 'ajax_query_attachments_args' ], 10, 1 );
			}

		}

	}

	/**
	 * @return bool
	 */
	protected function is_wp_ajax_media_search(): bool {
		return 'query-attachments' === WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ 'action' ], '' );
	}

	/**
	 *
	 * Update highlightings tag
	 *
	 * @return string
	 */
	protected function _wpsolr_filter_highlighting_tag( $tag ) {

		if ( $this->is_replace_admin_post_type_by_wpsolr_query || $this->is_replace_admin_admin_taxonomy_by_wpsolr_query ) {
			return '';
		}

		return $tag;
	}

	/**
	 *
	 * Update highlightings prefix
	 *
	 * @return string
	 */
	public function wpsolr_filter_highlighting_prefix( $prefix_tag ) {

		return $this->_wpsolr_filter_highlighting_tag( $prefix_tag );
	}

	/**
	 *
	 * Update highlightings postfix
	 *
	 * @return string
	 */
	public function wpsolr_filter_highlighting_posfix( $postfix_tag ) {

		return $this->_wpsolr_filter_highlighting_tag( $postfix_tag );
	}

	/**
	 *
	 * Update the WPSOLR query
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return WPSOLR_Query
	 */
	public function wpsolr_filter_update_wpsolr_query( WPSOLR_Query $wpsolr_query ) {

		if ( $this->is_replace_admin_post_type_by_wpsolr_query || $this->is_replace_admin_admin_taxonomy_by_wpsolr_query ) {
			// Pagination is hard-coded in admin
			//$wpsolr_query->wpsolr_set_nb_results_by_page( self::WP_ADMIN_NB_RESULTS_BY_PAGE );
			//$wpsolr_query->wpsolr_set_highlighting_tags( [] );
		}

		return $wpsolr_query;
	}

	/**
	 * Filter post types according to the current admin search
	 *
	 * @param string[] $post_types
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return array
	 */
	public
	function wpsolr_filter_post_types(
		$post_types, $wpsolr_query
	) {

		$is_search_admin = static::get_is_search_admin();

		if ( ! $is_search_admin && ! $this->is_replace_admin_post_type_by_wpsolr_query ) {
			// Only admin search can show excluded post types.
			return $this->_filter_post_types_excluded( $post_types );
		}

		return $post_types;
	}

	/**
	 * Remove post types that are not searcheable, and their taxonomies too
	 *
	 * @param string[] $model_types
	 *
	 * @return array
	 */
	protected function _filter_post_types_excluded( $model_types ) {

		$result = $model_types;

		// True, unless current post type is excluded from search
		$post_types_not_excluded_from_search = array_values( get_post_types( [
			'exclude_from_search' => false,
		] ) );

		// True, unless current taxonomy is not public
		$taxonomies_not_excluded_from_search = array_values( get_taxonomies( [
			'public' => true,
		] ) );

		if ( ! empty( $post_types_not_excluded_from_search ) ) {
			$result = [];
			foreach ( $model_types as $model_type ) {
				if (
					in_array( $model_type, $post_types_not_excluded_from_search ) ||
					in_array( $model_type, $taxonomies_not_excluded_from_search )
				) {
					$result[] = $model_type;
				}
			}
		}

		return $result;
	}

	/**
	 *
	 * Add a filter on admin queries.
	 *
	 * @param array $parameters
	 *
	 */
	public function wpsolr_action_query( $parameters ) {

		/* @var WPSOLR_AbstractSearchClient $search_engine_client */
		$search_engine_client = $parameters[ WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_CLIENT ];

		/**
		 * This is a post type admin archive
		 */
		if ( $this->is_replace_admin_post_type_by_wpsolr_query ) {

			$is_page_hpos = ( 'wc-orders' === WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'page' ], '' ) );

			/**
			 * Sort
			 */

			// Sort by title
			$orderby = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'orderby' ], '' );
			$order   = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'order' ], '' );

			$mapping = [
				'ID'    => WpSolrSchema::_FIELD_NAME_PID_I,
				'title' => WpSolrSchema::_FIELD_NAME_TITLE_S,
				'date'  => WpSolrSchema::_FIELD_NAME_DATE,
				'sku'   => '_sku_str',
				'price' => '_price_str',
			];
			if ( ! empty( $orderby ) && ! empty( $order ) && ! empty( $mapping[ $orderby ] ) ) {

				$search_engine_client->search_engine_client_set_sort(
					$mapping[ $orderby ],
					( 'asc' === $order ) ? WPSOLR_AbstractSearchClient::SORT_ASC : WPSOLR_AbstractSearchClient::SORT_DESC
				);
			}


			/**
			 * Filters
			 */

			// Filter by post type
			$post_type_edit_url = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'post_type' ], '' );
			if ( ! empty( $post_type_edit_url ) ) {
				$search_engine_client->search_engine_client_add_filter_term( sprintf( 'admin post type:%s', $post_type_edit_url ), WpSolrSchema::_FIELD_NAME_TYPE, false, $post_type_edit_url );
			}

			// Filter by post status
			$post_status_field = $is_page_hpos ? 'status' : 'post_status';
			$post_status       = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ $post_status_field ], '' );
			$post_status       = ( 'all' === $post_status ) ? '' : $post_status;
			$post_status       = ( $is_page_hpos && ( 'trash' === $post_status ) ) ? 'wc-trash' : $post_status;
			if ( ! empty( $post_status ) ) {

				$search_engine_client->search_engine_client_add_filter_term( 'post_status_admin', WpSolrSchema::_FIELD_NAME_STATUS_S, false,
					$post_status );

			} else {
				// Admin without status filter selected do not show 'trash' and 'auto-draft' post types
				$search_engine_client->search_engine_client_add_filter_not_in_terms( 'post_status_admin_not_trash', WpSolrSchema::_FIELD_NAME_STATUS_S,
					[
						'wc-trash',
						'trash',
						'auto-draft',
					] );
			}

			// Filter by author
			$post_author_id = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'author' ], '' );
			if ( ! empty( $post_author_id ) ) {

				$search_engine_client->search_engine_client_add_filter_term( 'post_author_admin', WpSolrSchema::_FIELD_NAME_AUTHOR_ID_S, false, $post_author_id );

			}

			// Filter by stickyness
			$show_sticky = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'show_sticky' ], '' );
			if ( ! empty( $show_sticky ) ) {

				$search_engine_client->search_engine_client_add_filter_in_terms( 'post_sticky_admin',
					WpSolrSchema::_FIELD_NAME_PID,
					get_option( 'sticky_posts' )
				);
			}

			// Filter by taxonomies (all post types but posts)
			$indexed_taxonomies = WPSOLR_Service_Container::getOption()->get_option_index_taxonomies();
			foreach ( $indexed_taxonomies as $indexed_taxonomy_str ) {
				$indexed_taxonomy = WpSolrSchema::get_field_without_str_ending( $indexed_taxonomy_str );

				$taxonomy_slug = WPSOLR_Sanitize::sanitize_title_for_query( $_GET, [ $indexed_taxonomy ], '' );
				if ( ! empty( $taxonomy_slug ) && ( $product_category = get_term_by( 'slug', $taxonomy_slug, $indexed_taxonomy ) ) ) {

					$search_engine_client->search_engine_client_add_filter_term( 'admin_taxonomy_' . $indexed_taxonomy,
						sprintf( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, $indexed_taxonomy . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
						false,
						$product_category->name
					);
				}

			}

			// Filter by post category (parameter is cat id, not cat slug)
			$category_id = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'cat' ], '' );
			if ( ! empty( $category_id ) && ( $category = get_term( $category_id, 'category' ) ) ) {

				$search_engine_client->search_engine_client_add_filter_term( 'post_category_admin',
					sprintf( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ),
					false,
					$category->name
				);
			}

			// Filter by post tag (parameter is tag slug)
			$tag_slug = WPSOLR_Sanitize::sanitize_title_for_query( $_GET, [ 'tag' ], '' );
			if ( ! empty( $tag_slug ) && ( $tag = get_term_by( 'slug', $tag_slug, 'post_tag' ) ) ) {

				$search_engine_client->search_engine_client_add_filter_term( 'post_tag_admin',
					WpSolrSchema::_FIELD_NAME_TAGS,
					false,
					$tag->name
				);
			}

			// Filter by date
			$month_year = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'm' ], '' ); // &m=201906
			if ( ! empty( $month_year ) ) {

				$search_engine_client->search_engine_client_add_filter( 'post_month_year_admin',
					$search_engine_client->search_engine_client_create_and(
						[
							$search_engine_client->search_engine_client_create_filter_in_terms(
								WpSolrSchema::_FIELD_NAME_DISPLAY_DATE . WpSolrSchema::_FIELD_NAME_YEAR_I, [ (int) substr( $month_year, 0, 4 ) ] ),
							$search_engine_client->search_engine_client_create_filter_in_terms(
								WpSolrSchema::_FIELD_NAME_DISPLAY_DATE . WpSolrSchema::_FIELD_NAME_YEAR_MONTH_I, [ (int) substr( $month_year, 4, 2 ) ] ),
						]
					)
				);

			}

			// Filter by product type
			$product_type_id = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'product_type' ], '' );
			if ( ! empty( $product_type_id ) && ( $product_type = get_term_by( 'slug', $product_type_id, 'product_type' ) ) ) {

				$search_engine_client->search_engine_client_add_filter_term( 'product_type_admin',
					sprintf( 'product_type' . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
					false,
					$product_type->name
				);
			}

			// Filter by product stock status
			$product_stock_status = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ 'stock_status' ], '' );
			if ( ! empty( $product_stock_status ) ) {

				$search_engine_client->search_engine_client_add_filter_term( 'product_stock_status_admin',
					sprintf( '_stock_status' . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
					false,
					$product_stock_status
				);
			}

			// Filter by user id _customer_user
			$_customer_user = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ '_customer_user' ], '' );
			if ( ! empty( $_customer_user ) ) {

				if ( $is_page_hpos ) {
					$search_engine_client->search_engine_client_add_filter_term( 'customer_id',
						sprintf( 'customer_id' . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
						false,
						$_customer_user
					);
				} else {
					$search_engine_client->search_engine_client_add_filter_term( '_customer_user',
						sprintf( '_customer_user' . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
						false,
						$_customer_user
					);
				}
			}

		} else {
			// front-end search: filter out excluded

			$search_engine_client->search_engine_client_add_filter_empty_or_in_terms( 'front-end exclusion 1', WpSolrSchema::_FIELD_NAME_IS_EXCLUDED_S, [ 'n' ] );
		}


	}

	/**
	 *
	 * Replace WP query by a WPSOLR query when the current WP Query is an order type query.
	 *
	 * @param bool $is_replace_by_wpsolr_query
	 *
	 * @return bool
	 */
	public function wpsolr_filter_is_replace_admin_post_type_by_wpsolr_query( $is_replace_by_wpsolr_query ) {

		$this->is_replace_admin_post_type_by_wpsolr_query = $is_replace_by_wpsolr_query;

		return $is_replace_by_wpsolr_query;
	}

	/**
	 * Activate/deactivate real-time indexing
	 *
	 * @param boolean $is_active
	 */
	public function wpsolr_action_option_set_realtime_indexing( $is_active ) {

		$service_options = $this->get_container()->get_service_option();

		$is_realtime_indexing = $service_options->get_index_is_real_time();

		// Change the settings only if it's different
		if ( $is_realtime_indexing !== $is_active ) {

			$option = $service_options->get_option_index();

			if ( ! $is_active ) {
				// Set as 'no real-time'

				$option[ $service_options::OPTION_INDEX_IS_REAL_TIME ] = '1';

			} else {
				// Set as 'real-time'

				unset( $option[ $service_options::OPTION_INDEX_IS_REAL_TIME ] );
			}

			update_option( $service_options::OPTION_INDEX, $option );

		}
	}

	/**
	 * Include the file containing the help feature.
	 *
	 * @param int $help_id
	 *
	 * @return string File name & path
	 */
	public function wpsolr_filter_include_file( $help_id ) {

		switch ( $help_id ) {
			case WPSOLR_Help::HELP_SEARCH_TEMPLATE:
				$file_name = 'search-template.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_PAGE_SLUG:
				$file_name = 'search-page-slug.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_INFINITE_SCROLL:
				$file_name = 'search-infinite-scroll.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_SUGGESTIONS:
				$file_name = 'search-suggestions.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_SUGGESTIONS_JQUERY_SELECTOR:
				$file_name = 'search-suggestions-jquery-selectors.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_DID_YOU_MEAN:
				$file_name = 'search-did-you-mean.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_STOP_REAL_TIME:
				$file_name = 'indexing-stop-real-time.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_POST_TYPES:
				$file_name = 'indexing-post-types.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_TAXONOMIES:
				$file_name = 'indexing-taxonomies.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_IMAGES:
				$file_name = 'indexing-images.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_STATUS:
				$file_name = 'indexing-status.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_CUSTOM_FIELDS:
				$file_name = 'indexing-custom-fields.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_ATTACHMENTS:
				$file_name = 'indexing-attachments.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_BOOSTS:
				$file_name = 'search-boosts.inc.php';
				break;

			case WPSOLR_Help::HELP_FACET_LABEL:
				$file_name = 'search-facet-label.inc.php';
				break;

			case WPSOLR_Help::HELP_SORT_LABEL:
				$file_name = 'search-sort-label.inc.php';
				break;

			case WPSOLR_Help::HELP_BATCH_DEBUG:
				$file_name = 'batch-debug.inc.php';
				break;

			case WPSOLR_Help::HELP_BATCH_MODE_REPLACE:
				$file_name = 'batch-mode-replace.inc.php';
				break;

			case WPSOLR_Help::HELP_BATCH_EVENTS_REQUIREMENTS:
				$file_name = 'batch-events_requirements.inc.php';
				break;

			case WPSOLR_Help::HELP_LOCALIZE:
				$file_name = 'localize.inc.php';
				break;

			case WPSOLR_Help::HELP_MULTI_INDEX:
				$file_name = 'multi-index.inc.php';
				break;

			case WPSOLR_Help::HELP_THEME_FACET_LAYOUT:
				$file_name = '/theme/facet-theme-layout.inc.php';
				break;

			case WPSOLR_Help::HELP_CHECKER:
				$file_name = '/utils/checker.inc.php';
				break;

			default:
				$file_name = '';
		}

		return ! empty( $file_name ) ? sprintf( '%s/includes/%s', dirname( __FILE__ ), $file_name ) : $help_id;
	}

	/**
	 * Stop WordPress performing a DB query for its main loop.
	 *
	 *
	 * @param null $retval Current return value for filter.
	 * @param WP_Query $query Current WordPress query object.
	 *
	 * @return null|array
	 * @since 2.7.0
	 *
	 */
	function posts_pre_query( $retval, $query ) {

		if ( $this->is_ajax_processing ) {
			// Recurse call, stop now.
			return $retval;
		}

		if ( ! $this->before_media_query ) {
			// This is not a media query filter.
			return $retval;
		}

		if ( ! WPSOLR_Service_Container::action_wp_loaded() ) {
			// This is not a media query filter.
			return $retval;
		}


		// To prevent recursive infinite calls
		$this->is_ajax_processing = true;
		$this->before_media_query = false;


		$wpsolr_query = new WPSOLR_Query(); // Potential recurse here
		$wpsolr_query->wpsolr_set_view_uuid( WPSOLR_Option_View::get_current_view_uuid() );

		$wpsolr_query->wpsolr_set_wp_query( $query );
		$wpsolr_query->query['post_type'] = $query->query['post_type'];
		$wpsolr_query->query['s']         = $query->query['s'] ?? '';
		$wpsolr_query->wpsolr_set_nb_results_by_page( $query->query['posts_per_page'] );
		$wpsolr_query->query_vars['paged'] = $query->query['paged'];
		//$this->add_sort( $wpsolr_query );
		$products = $wpsolr_query->get_posts();

		// To prevent recursive infinite calls
		$this->is_ajax_processing = false;

		// Return $results, which prevents standard $wp_query to execute it's SQL.
		$post_ids = array_column( $products, 'ID' );

		$query->post_count    = $wpsolr_query->post_count;
		$query->found_posts   = $wpsolr_query->found_posts;
		$query->max_num_pages = $wpsolr_query->max_num_pages;

		return $post_ids;
	}

	/**
	 * @param array $query_args
	 *
	 * @return mixed
	 */
	public function ajax_query_attachments_args( $query_args ) {
		$this->before_media_query = true;

		return $query_args;
	}

	/**
	 * @return bool
	 */
	static function get_is_search_admin() {
		return filter_var( isset( $_REQUEST['is_search_admin'] ) &&
		                   check_ajax_referer( 'nonce_for_autocomplete', 'security' ) /* Redundant to pass plugin-check */ ?
			WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ 'is_search_admin' ] ) : false, FILTER_VALIDATE_BOOLEAN ); // ajax
	}

}
