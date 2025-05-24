<?php

namespace wpsolr\core\classes\ui;

use WP_Query;
use wpsolr\core\classes\engines\WPSOLR_AbstractResultsClient;
use wpsolr\core\classes\exceptions\WPSOLR_Exception_Security;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Manage Solr query parameters.
 *
 */
class WPSOLR_Query extends \WP_Query {

	protected string $wpsolr_replica_index_label;
	protected $solr_client;

	/** @var  WP_Query $wp_query */
	protected $wp_query;

	//protected $query;
	protected $wpsolr_query;

	/** @var array */
	protected $wpsolr_filter_query, $wpsolr_archive_filter_query;

	/* @var int $wpsolr_paged */
	protected $wpsolr_paged;
	protected $wpsolr_sort;
	protected $wpsolr_sort_secondary;
	protected $wpsolr_latitude;
	protected $wpsolr_longitude;
	protected $wpsolr_is_geo;

	/** @var WPSOLR_AbstractResultsClient */
	protected $results_set;

	/** @var  int $wpsolr_nb_results_by_page */
	protected $wpsolr_nb_results_by_page;

	/** @var array */
	protected $wpsolr_suggestion;

	/** @var bool */
	protected $wpsolr_is_admin = false;

	/** @var string */
	protected $wpsolr_did_you_mean = '';

	/**
	 * @var string[]
	 */
	protected $wpsolr_post_types;

	/**
	 * @var string[]
	 */
	protected $wpsolr_fields;

	/** @var int */
	protected $wpsolr_start;

	/** @var string */
	protected $wpsolr_cursor_mark;

	/** @var string */
	protected $wpsolr_view_uuid;

	protected array $wpsolr_recommendation;


	/** @var string $wpsolr_query_cache_key Cache key for current query */
	protected $wpsolr_query_cache_key;
	protected array $wpsolr_ajax_context;
	protected string $wpsolr_image_size;

	/**
	 * Constructor used by factory WPSOLR_Service_Container
	 *
	 * @return WPSOLR_Query
	 */
	static function global_object( WPSOLR_Query $wpsolr_query = null ) {

		// Create/Update query from parameters
		return WPSOLR_Query_Parameters::CreateQuery( $wpsolr_query );
	}


	/**
	 * @param WP_Query $wp_query
	 *
	 * @return WPSOLR_Query
	 */
	public static function Create() {

		$wpsolr_query = new WPSOLR_Query();

		$wpsolr_query->set_defaults();

		return $wpsolr_query;
	}

	/**
	 * @return $this
	 */
	public function set_defaults() {

		$this->set_wpsolr_query( '' );
		$this->set_filter_query_fields( [] );
		$this->set_wpsolr_paged( '0' );
		$this->set_wpsolr_sort( '' );
		$this->set_wpsolr_sort_secondary( '' );
		$this->wpsolr_set_nb_results_by_page( '0' );

		return $this;
	}

	/**
	 * @param string $default
	 * @param bool $is_escape Prevent xss attacks when outputing the value in html
	 *
	 * @return string
	 */
	public function get_wpsolr_query( $default = '', $is_escape = false ) {

		// Prevent Solr error by replacing empty query by default value
		return empty( $this->wpsolr_query ) ? $default : ( $is_escape ? esc_attr( $this->wpsolr_query ) : $this->wpsolr_query ); // Prevent xss
	}

	/**
	 * @param string $query
	 *
	 * @return $this
	 */
	public function set_wpsolr_query( $query ) {
		$this->wpsolr_query = $query;

		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function wpsolr_get_suggestion() {
		return $this->wpsolr_suggestion;
	}

	/**
	 *
	 * @return array
	 */
	public function wpsolr_get_recommendation() {
		return $this->wpsolr_recommendation ?? [];
	}

	/**
	 * @param array $recommendation
	 *
	 * @return $this
	 */
	public function wpsolr_set_recommendation( $recommendation ) {
		$this->wpsolr_recommendation = $recommendation;

		return $this;
	}

	/**
	 * @param array $suggestion
	 *
	 * @return $this
	 */
	public function wpsolr_set_suggestion( $suggestion ) {
		$this->wpsolr_suggestion = $suggestion;

		return $this;
	}

	/**
	 * return bool
	 */
	public function wpsolr_get_is_suggestion() {
		return ! empty( $this->wpsolr_suggestion );
	}

	/**
	 * return bool
	 */
	public function wpsolr_get_is_recommendation() {
		return ! empty( $this->wpsolr_recommendation );
	}

	/**
	 * @param bool $is_admin
	 *
	 * @return $this
	 */
	public function wpsolr_set_is_admin( $is_admin ) {
		$this->wpsolr_is_admin = $is_admin;

		return $this;
	}

	/**
	 * return bool
	 */
	public function wpsolr_get_is_admin() {
		return ! empty( $this->wpsolr_is_admin );
	}

	/**
	 * return bool
	 */
	public function wpsolr_get_is_suggestion_type_question_answer() {
		return ! empty( $this->wpsolr_suggestion ) &&
		       ( WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_QUESTIONS_ANSWERS === $this->wpsolr_suggestion[ WPSOLR_Option::OPTION_SUGGESTION_TYPE ] );
	}

	/**
	 * return bool
	 */
	public function wpsolr_get_is_suggestion_type_content_grouped() {
		return ! empty( $this->wpsolr_suggestion ) &&
		       ( WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED === $this->wpsolr_suggestion[ WPSOLR_Option::OPTION_SUGGESTION_TYPE ] );
	}

	/**
	 * return bool
	 */
	public function wpsolr_get_is_suggestion_type_content_grouped_sorted_by_position() {
		return $this->wpsolr_get_is_suggestion_type_content_grouped() &&
		       ( WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY_GROUP_POSITION === $this->wpsolr_suggestion[ WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY ] );
	}

	/**
	 * @return array
	 */
	public function get_filter_query_fields() {
		return ! empty( $this->wpsolr_filter_query ) ? $this->wpsolr_filter_query : [];
	}

	/**
	 * @param array $fq
	 *
	 * @return $this
	 */
	public function set_filter_query_fields( $fq ) {
		// Ensure fq is always an array
		$this->wpsolr_filter_query = empty( $fq ) ? [] : ( is_array( $fq ) ? array_unique( $fq ) : [ $fq ] );

		return $this;
	}


	/**
	 * @return array
	 */
	public function get_archive_filter_query_fields() {
		return ! empty( $this->wpsolr_archive_filter_query ) ? $this->wpsolr_archive_filter_query : [];
	}

	/**
	 * @param array $fq
	 *
	 * @return $this
	 */
	public function set_archive_filter_query_fields( $fq ) {
		// Ensure fq is always an array
		$this->wpsolr_archive_filter_query = empty( $fq ) ? [] : ( is_array( $fq ) ? array_unique( $fq ) : [ $fq ] );

		return $this;
	}


	/**
	 * @return int
	 */
	public function get_wpsolr_paged() {
		return $this->wpsolr_paged;
	}

	/**
	 * Calculate the start of pagination
	 *
	 * @return int
	 */
	public function wpsolr_get_start() {
		return $this->wpsolr_start ??
		       ( ( $this->get_wpsolr_paged() === 0 || $this->get_wpsolr_paged() === 1 ) ?
			       0 :
			       ( ( $this->get_wpsolr_paged() - 1 ) * $this->get_nb_results_by_page() )
		       );
	}

	/**
	 * Set the start of pagination
	 *
	 * @param int $wpsolr_start
	 *
	 * @return $this
	 */
	public function wpsolr_set_start( $wpsolr_start ) {
		$this->wpsolr_start = $wpsolr_start;

		return $this;
	}

	/**
	 * Set the nb of results by page
	 *
	 * @param string $nb_results_by_page
	 *
	 * @return $this
	 */
	public function wpsolr_set_nb_results_by_page( $nb_results_by_page ) {
		$this->wpsolr_nb_results_by_page = intval( $nb_results_by_page );

		return $this;
	}

	/**
	 * Get the nb of results by page
	 * @return integer
	 */
	public function get_nb_results_by_page() {
		return ( $this->wpsolr_nb_results_by_page > 0 ) ? $this->wpsolr_nb_results_by_page : WPSOLR_Service_Container::getOption()->get_search_max_nb_results_by_page();
	}

	/**
	 * @param string $wpsolr_paged
	 *
	 * @return $this
	 */
	public function set_wpsolr_paged( $wpsolr_paged ) {
		$this->wpsolr_paged = intval( $wpsolr_paged );

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_sort() {

		if ( empty( $this->wpsolr_sort ) ) {
			$this->wpsolr_sort = apply_filters( WPSOLR_Events::WPSOLR_FILTER_DEFAULT_SORT, WPSOLR_Service_Container::getOption()->get_first_sort_by_default(), $this );
		}

		return $this->wpsolr_sort;
	}

	/**
	 * @param string $wpsolr_sort
	 *
	 * @return $this
	 */
	public function set_wpsolr_sort_secondary( $wpsolr_sort ) {
		$this->wpsolr_sort_secondary = $wpsolr_sort;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_sort_secondary() {

		if ( empty( $this->wpsolr_sort_secondary ) ) {
			$this->wpsolr_sort_secondary = apply_filters( WPSOLR_Events::WPSOLR_FILTER_DEFAULT_SORT_SECONDARY, WPSOLR_Service_Container::getOption()->get_second_sort_by_default(), $this );
		}

		return $this->wpsolr_sort_secondary;
	}

	/**
	 * @param string $wpsolr_sort
	 *
	 * @return $this
	 */
	public function set_wpsolr_sort( $wpsolr_sort ) {
		$this->wpsolr_sort = $wpsolr_sort;

		return $this;
	}

	/**
	 * @param string $wpsolr_sort
	 *
	 * @return $this
	 */
	public function set_wpsolr_latitude( $wpsolr_latitude ) {
		$this->wpsolr_latitude = $wpsolr_latitude;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_latitude() {
		return $this->wpsolr_latitude;
	}

	/**
	 * @param string $wpsolr_sort
	 *
	 * @return $this
	 */
	public function set_wpsolr_longitude( $wpsolr_longitude ) {
		$this->wpsolr_longitude = $wpsolr_longitude;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_longitude() {
		return $this->wpsolr_longitude;
	}


	/**
	 * @param boolean $is_geo
	 *
	 * @return $this
	 */
	public function set_wpsolr_is_geo( $is_geo ) {
		$this->wpsolr_is_geo = $is_geo;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function get_wpsolr_is_geo() {
		return $this->wpsolr_is_geo;
	}

	/**************************************************************************
	 *
	 * Override WP_Query methods
	 *
	 *************************************************************************/

	function get_posts() {

		try {//return parent::get_posts();

			// Let WP extract parameters
			if ( apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_PARSE_QUERY, true ) ) {
				$this->parse_query();
			}

			/**
			 * Prevent empty pagination on hierarchical post types in admin archives
			 */
			if ( ( isset( $this->query['orderby'] ) ) && ( 'menu_order title' === $this->query['orderby'] ) ) {
				$this->query['orderby'] = '';
			}

			/**
			 * Prevent error on WooCommerce home page setup as static shop page
			 */
			do_action_ref_array( 'pre_get_posts', array( &$this ) );

			$q     = &$this->query_vars;
			$query = isset( $this->query[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] ) ? $this->query[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] : '';

			if ( empty( $query ) &&
			     ! empty( $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] ) &&
			     $this->wpsolr_get_is_admin()
			) {
				// Admin 's' is sent in $_GET only
				$query = WPSOLR_Sanitize::sanitize_text_field( $_GET, [ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] );
			}

			if ( ! empty( $this->query_vars['posts_per_page'] ) &&
			     $this->wpsolr_get_is_admin()
			) {
				// Use admin posts per page
				$this->wpsolr_set_nb_results_by_page( $this->query_vars['posts_per_page'] > 0 ? $this->query_vars['posts_per_page'] : 20 );
			}

			if ( empty( $q[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] ) ) {
				$q[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] = $query;
			}
			$this->parse_search( $q );

			// Copy WP standard query to WPSOLR query
			$this->set_wpsolr_query( $query );

			// Copy WP standard paged to WPSOLR paged, if not already set
			if ( empty( $this->get_wpsolr_paged() ) ) {
				$this->set_wpsolr_paged( isset( $this->query_vars['paged'] ) ? $this->query_vars['paged'] : 1 );
			}

			// $_GET['s'] is used internally by some themes
			//$_GET['s'] = $query;

			// Set variable 's', so that get_search_query() and other standard WP_Query methods still work with our own search parameter
			//$this->set( 's', $query );

			$this->solr_client = WPSOLR_Service_Container::get_solr_client( false );
			$this->results_set = $this->solr_client->execute_wpsolr_query( $this, false );

			// Create posts from PIDs
			$posts_in_results = $this->solr_client->get_posts_from_pids( $this->query_vars );

			foreach ( $posts_in_results['posts'] as $position => $post ) {
				if ( $post instanceof \WP_Post ) {
					$this->set_the_title( $post, $posts_in_results['documents'][ $position ] );
					$this->set_the_excerpt( $post, $posts_in_results['documents'][ $position ] );
				}
			}

			$this->posts = $posts_in_results['posts'];
			if ( $this->posts ) {
				// Prevent error on admin post lists
				$this->post = reset( $this->posts );
			}
			$this->post_count  = count( $this->posts );
			$this->found_posts = $this->results_set->get_nb_results();

			$this->posts_per_page = $this->get_nb_results_by_page();
			$this->set( "posts_per_page", $this->posts_per_page );
			$this->max_num_pages = ceil( $this->found_posts / $this->posts_per_page );

			if ( ! isset( $this->query_vars['name'] ) ) {
				// Prevent error later in WP code
				$this->query_vars['name'] = '';
			}

			// Action for updating post before getting back to the theme's search page.
			do_action( WPSOLR_Events::WPSOLR_ACTION_POSTS_RESULTS, $this, $this->results_set );

			return $this->posts;

		} catch ( WPSOLR_Exception_Security $e ) {

			// Show nothing
			$this->posts = [];

			return $this->posts;

		} catch ( \Exception $e ) {

			// Write error in debug.log
			WPSOLR_Escape::error_log( 'WPSOLR message: ' . $e->getMessage() );
			WPSOLR_Escape::error_log( 'WPSOLR trace: ' . $e->getTraceAsString() );

			if ( is_admin() ) {
				set_transient( get_current_user_id() . 'wpsolr_error_during_search', htmlentities( $e->getMessage() ) );
			}

			?>

            <script>
                console.error("<?php WPSOLR_Escape::echo_esc_html( WPSOLR_PLUGIN_SHORT_NAME ); ?> : an error prevented the search engine query to be executed. To prevent empty results, the default WordPress query is used instead. " +
                    "Please check the error details in your debug.log file. See https://codex.wordpress.org/Debugging_in_WordPress.");
            </script>

			<?php
			// Error: revert to standard WP search.
			return parent::get_posts();
		}

	}

	/**
	 * @param $field_name
	 * @param $document
	 *
	 * @return string
	 */
	protected function get_highlighting_of_field( $field_name, $document ) {
		$result = '';

		$highlighting = $this->results_set->get_highlighting( $document );

		$highlighted_field = isset( $highlighting[ $field_name ] ) ? $highlighting[ $field_name ] : null;
		if ( $highlighted_field ) {

			$result = empty( $highlighted_field ) ? '' : ( is_array( $highlighted_field ) ? implode( ' (...) ', $highlighted_field ) : $highlighted_field );

			// Replace highlighting tags for Vespa
			$result = str_replace( '<hi>', '<b>', $result );
			$result = str_replace( '</hi>', '</b>', $result );
		}


		return $result;
	}

	/**
	 * @param \WP_Post $post
	 * @param $document
	 *
	 * @return $this
	 */
	protected function set_the_title( \WP_Post $post, $document ) {

		if ( isset( $document ) ) {

			$title = $this->get_highlighting_of_field( WpSolrSchema::_FIELD_NAME_TITLE, $document );

			if ( ! empty( $title ) ) {

				$post->post_title = $title;
			}
		}

		return $this;
	}


	/**
	 * @param \WP_Post $post
	 * @param $document
	 *
	 * @return $this
	 */
	protected function set_the_excerpt( \WP_Post $post, $document ) {

		if ( isset( $document ) ) {

			$content = $this->get_highlighting_of_field( WpSolrSchema::_FIELD_NAME_CONTENT, $document );

			if ( ! empty( $content ) ) {

				$post->post_excerpt = $content;
			}
		}

		return $this;
	}

	/**
	 * Regroup filter query fields by field
	 * ['type:post', 'type:page', 'category:cat1'] => ['type' => ['post', 'page'], 'category' => ['cat1']]
	 * @return array
	 */
	public function get_filter_query_fields_group_by_name( $facet_hierarchy_separator = WpSolrSchema::FACET_HIERARCHY_SEPARATOR ) {

		$results = [];

		/**
		 * Google Retail requires separator ' > ', but current filters are '->'. Replace '->' with ' > '
		 */
		$is_separator_to_convert = ( WpSolrSchema::FACET_HIERARCHY_SEPARATOR !== $facet_hierarchy_separator );

		foreach ( $this->get_filter_query_fields() as $field_encoded ) {

			// Convert 'field:year:2003' in ['field', 'year:2003']
			$field = explode( ':', $field_encoded, 2 );

			if ( ( count( $field ) === 2 ) && ( '' !== $field[1] ) ) {

				$value = $is_separator_to_convert ? str_replace( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $facet_hierarchy_separator, $field[1] ) : $field[1];

				if ( ! isset( $results[ $field[0] ] ) ) {

					$results[ $field[0] ] = [ $value ];

				} else {

					$results[ $field[0] ][] .= $value;
				}
			}
		}

		return $results;
	}

	/**
	 * @return WP_Query
	 */
	public function wpsolr_get_wp_query() {
		return $this->wp_query;
	}

	/**
	 * @param WP_Query $wp_query
	 *
	 * @return $this
	 */
	public function wpsolr_set_wp_query( $wp_query ) {
		$this->wp_query = $wp_query;

		return $this;
	}

	/**
	 * @return string
	 */
	public function wpsolr_get_did_you_mean() {
		return $this->wpsolr_did_you_mean;
	}

	/**
	 * @param string $wpsolr_did_you_mean
	 *
	 * @return $this
	 */
	public function wpsolr_set_did_you_mean( $wpsolr_did_you_mean ) {
		$this->wpsolr_did_you_mean = $wpsolr_did_you_mean;

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function wpsolr_get_post_types() {
		return $this->wpsolr_post_types;
	}

	/**
	 * @param array $wpsolr_post_types
	 *
	 * @return $this
	 */
	public function wpsolr_set_post_types( $post_types ) {
		$this->wpsolr_post_types = $post_types;

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function wpsolr_get_fields() {
		return $this->wpsolr_fields;
	}

	/**
	 * @param string[] $fields
	 *
	 * @return $this
	 */
	public function wpsolr_set_fields( $fields ) {
		$this->wpsolr_fields = array_merge(
		/* Minimum fields */
			[
				WpSolrSchema::_FIELD_NAME_ID,
				WpSolrSchema::_FIELD_NAME_PID,
				WpSolrSchema::_FIELD_NAME_TYPE,
			],
			$fields );

		return $this;
	}


	/**
	 * @return string
	 */
	public function wpsolr_get_cursor_mark() {
		return $this->wpsolr_cursor_mark;
	}

	/**
	 * @param string $wpsolr_cursor_mark
	 *
	 * @return $this
	 */
	public function wpsolr_set_cursor_mark( $wpsolr_cursor_mark = '' ) {
		$this->wpsolr_cursor_mark = $wpsolr_cursor_mark;

		return $this;
	}

	/**
	 * @param string $view_uuid
	 *
	 * @return $this
	 */
	public function wpsolr_set_view_uuid( $view_uuid ) {
		$this->wpsolr_view_uuid = $view_uuid;

		return $this;
	}

	/**
	 * return string
	 */
	public function wpsolr_get_view_uuid() {
		return $this->wpsolr_view_uuid;
	}

	/**
	 * @return WPSOLR_AbstractResultsClient
	 */
	public function wpsolr_get_results() {
		return $this->results_set;
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_query_cache_key() {
		return $this->wpsolr_query_cache_key ?? '';
	}

	public function get_wpsolr_image_size(): string {
		return empty( $this->wpsolr_image_size ) ? 'thumbnail' : $this->wpsolr_image_size;
	}

	public function set_wpsolr_image_size( string $image_size ) {
		$this->wpsolr_image_size = $image_size;
	}

	/**
	 * @param string $cache_key
	 *
	 * @return void
	 */
	public function set_wpsolr_query_cache_key( $cache_key = '' ) {
		$this->wpsolr_query_cache_key = $cache_key;
	}

	public function get_wpsolr_replica_index_label(): string {
		return $this->wpsolr_replica_index_label ?? '';
	}

	public function set_wpsolr_replica_index_label( string $wpsolr_replica_index_label ): void {
		$this->wpsolr_replica_index_label = $wpsolr_replica_index_label;
	}

	public function set_wpsolr_ajax_context( array $context ) {
		$this->wpsolr_ajax_context = $context;
	}

	public function get_wpsolr_ajax_context(): array {
		return $this->wpsolr_ajax_context ?? [];
	}


}