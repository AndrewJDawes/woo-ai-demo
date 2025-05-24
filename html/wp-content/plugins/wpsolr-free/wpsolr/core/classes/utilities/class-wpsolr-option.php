<?php

namespace wpsolr\core\classes\utilities;

use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient_Root;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\models\post\WPSOLR_Model_Meta_Type_Post;
use wpsolr\core\classes\WPSOLR_Events;

class WPSOLR_Option {

	/**
	 * Definitions of option slufs: description, can belong to a view or an index list.
	 */
	const OPTIONS_DEFINITIONS = [


		self::OPTION_SETUP_WIZARD_IS_SKIPPED => [
			'description' => 'Skipped setup wizard',
		],

		self::OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE => [
			'description' => 'YITH Woo Search (Free)',
		],

		self::OPTION_INDEX_FILTERED_FIELDS => [
			'description' => 'Index filtered fields',
		],

		self::OPTION_SEARCH_FIELDS => [
			'description' => 'Boosts',
			'type'        => [ 'view' ],
		],

		self::OPTION_FACET => [
			'description' => 'Facets',
			'type'        => [ 'view' ],
		],

		self::OPTION_IMPORT_EXPORT => [
			'description' => 'Import / Export',
		],

		self::OPTION_OPERATIONS => [
			'description' => 'Operations',
		],

		self::OPTION_LOCKING => [
			'description' => 'Index lock',
		],

		self::OPTION_INDEX => [
			'description' => 'Index definition',
			'type'        => [ 'index' ],
		],

		self::OPTION_LOCALIZATION => [
			'description' => 'Localization',
		],

		self::OPTION_PREMIUM => [
			'description' => 'Premium',
		],

		self::OPTION_SEARCH => [
			'description' => 'Search',
			'type'        => [ 'view' ],
		],

		self::OPTION_SORTBY => [
			'description' => 'Sorts',
			'type'        => [ 'view' ],
		],

		self::OPTION_SUGGESTIONS => [
			'description' => 'Suggestions',
			'type'        => [ 'view' ],
		],

		self::OPTION_DB                               => [
			'description' => 'Custom tables version',
		],
		self::OPTION_VIEW                             => [
			'description' => 'Views',
		],
		self::OPTION_WEAVIATE_CONVERTED_FIELD_NAMES   => [
			'description' => 'Weaviate converted fields',
		],
		self::OPTION_WEAVIATE_UNCONVERTED_FIELD_NAMES => [
			'description' => 'Weaviate unconverted fields',
		],
		self::OPTION_INDEXES                          => [
			'description' => 'Index connection',
		],
		self::OPTION_LICENSES                         => [
			'is_exported' => false,
		],

		WPSOLR_AbstractIndexClient_Root::MODEL_LAST_POST_DATE_INDEXED => [
			'description' => 'Last post date indexed',
		],
		'solr_docs_added_or_updated_last_operation'                   => [
			'description' => 'Last post id indexed',
		],
		'solr_docs'                                                   => [
			'description' => 'Nb documents in index',
		],
		WPSOLR_AbstractIndexClient_Root::OPTION_INDEX_MODEL_VERSION   => [
			'description' => 'Model version',
		],

	];

	// Cache of options already retrieved from database.
	const AJAX_DELAY_MS_FOR_TESTS = 6000;
	const AJAX_DELAY_SEC_FOR_TESTS = 6;
	private $cached_options;

	/**
	 * WPSOLR_Option constructor.
	 */
	public function __construct() {
		$this->cached_options = [];

		/*
		add_filter( WPSOLR_Events::WPSOLR_FILTER_AFTER_GET_OPTION_VALUE, array(
					$this,
					'debug',
				), 10, 2 );
		*/

	}

	/**
	 * Test filter WPSOLR_Events::WPSOLR_FILTER_AFTER_GET_OPTION_VALUE
	 *
	 * @param $option_value
	 * @param $option
	 *
	 * @return string
	 */
	function test_filter( $option_value, $option ) {

		WPSOLR_Escape::echo_escaped( sprintf( "%s('%s') = '%s'<br/>", WPSOLR_Escape::esc_html( $option['option_name'] ), WPSOLR_Escape::esc_html( $option['$option_key'] ), WPSOLR_Escape::esc_html( $option_value ) ) );

		return $option_value;
	}

	/**
	 * @param string $option_name
	 *
	 * @return string[]
	 */
	protected function _get_option_types( string $option_name ) {
		return static::OPTIONS_DEFINITIONS[ $option_name ]['type'] ?? [];
	}

	/**
	 * @param string $option_name
	 *
	 * @return bool
	 */
	public function get_is_option_type_view( string $option_name ): bool {
		return in_array( 'view', $this->_get_option_types( $option_name ) );
	}

	/**
	 * @param string $option_name
	 *
	 * @return bool
	 */
	public function get_is_option_type_index( string $option_name ): bool {
		return in_array( 'index', $this->_get_option_types( $option_name ) );
	}

	/**
	 * Retrieve and cache an option
	 *
	 * @param $is_cached
	 * @param string $option_name
	 *
	 * @param mixed $option_default_value
	 *
	 * @return array
	 */
	public function get_option( $is_cached, $option_name, $option_default_value = [], $is_custom_name = true ) {

		if ( $is_custom_name ) {
			if ( $this->get_is_option_type_view( $option_name ) ) {
				// $option_name_default = $option_name;
				$option_name = $this->_get_view_uuid_option_name( $option_name );
			} elseif ( $this->get_is_option_type_index( $option_name ) ) {
				$option_name_default = $option_name;
				$option_name         = $this->_get_index_uuid_option_name( $option_name );
			}
		}

		if ( ! $is_cached || ( defined( 'WPSOLR_OPTIONS_NO_CACHE' ) && WPSOLR_OPTIONS_NO_CACHE ) ) {
			// No cache. Used by unit tests.

			$option = get_option( $option_name, $option_default_value );

		} else {

			// Retrieve option in cache, or in database
			if ( isset( $this->cached_options[ $option_name ] ) ) {

				// Retrieve option from cache
				$option = $this->cached_options[ $option_name ];

			} else {

				// Not in cache, retrieve option from database
				$option = get_option( $option_name, $option_default_value );

				// Add option to cached options
				$this->cached_options[ $option_name ] = $option;
			}
		}

		if ( ! empty( $option_name_default ) && $is_custom_name && empty( $option ) ) {
			/**
			 * Compatibility before multi-views and multi-indexes: if empty, get the default view/index value
			 */
			$option = $this->get_option( $is_cached, $option_name_default, $option_default_value, false );
		}

		return $option;
	}


	/**
	 * Remove option cache
	 *
	 * @param $option_name
	 */
	public function remove_cache( $option_name ) {

		if ( isset( $this->cached_options[ $option_name ] ) ) {

			// Retrieve option from cache
			unset( $this->cached_options[ $option_name ] );
		}
	}

	/**
	 * @param $is_cached
	 * @param $caller_function_name
	 * @param $option_name
	 * @param $option_key
	 * @param null $option_default
	 *
	 * @return mixed|null|void
	 */
	private function get_option_value( $is_cached, $caller_function_name, $option_name, $option_key, $option_default = null ) {

		if ( ! empty( $caller_function_name ) ) {
			// Filter before retrieving an option value
			$result = apply_filters( WPSOLR_Events::WPSOLR_FILTER_BEFORE_GET_OPTION_VALUE, null, [
				'option_name'     => $caller_function_name,
				'$option_key'     => $option_key,
				'$option_default' => $option_default,
			] );
			if ( ! empty( $result ) ) {
				return $result;
			}
		}

		// Retrieve option from cache or databse
		$option = $this->get_option( $is_cached, $option_name );

		// Retrieve option value from option
		if ( isset( $option ) ) {

			$result = isset( $option[ $option_key ] ) ? $option[ $option_key ] : $option_default;

		} else {

			// undefined
			$result = null;
		}

		if ( ! empty( $caller_function_name ) ) {
			// Filter after retrieving an option value
			return apply_filters( WPSOLR_Events::WPSOLR_FILTER_AFTER_GET_OPTION_VALUE, $result, array(
				'option_name'     => $caller_function_name,
				'$option_key'     => $option_key,
				'$option_default' => $option_default,
			) );
		}
	}

	/**
	 * Convert a string to integer
	 *
	 * @param $string
	 * @param $object_name
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function to_integer( $string, $object_name ) {
		if ( is_numeric( $string ) ) {

			return intval( $string );

		} else {
			throw new \Exception( sprintf( 'Option "%s" with value "%s" should be an integer.',
				esc_html( $object_name ), esc_html( $string ) ) );
		}

	}

	/**
	 * Convert a string to integer
	 *
	 * @param $string
	 * @param $object_name
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function to_float( $string, $object_name ) {
		if ( is_numeric( $string ) ) {

			return floatval( $string );

		} else {
			throw new \Exception( sprintf( 'Option "%s" with value "%s" should be a float.',
				esc_html( $object_name ), esc_html( $string ) ) );
		}

	}

	/**
	 * Is value empty ?
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	private function is_empty( $value ) {
		return empty( $value );
	}

	/**
	 * Explode a comma delimited string in array.
	 * Returns empty array if string is empty
	 *
	 * @param $string
	 *
	 * @return array
	 */
	private function explode( $string ) {
		return empty( $string ) ? [] : explode( ',', $string );
	}

	/**
	 * Flatten and deduplicate a multi-level array.
	 *
	 * @param array $array_to_flatten
	 *
	 * @return array
	 */
	private function flatten_and_deduplicate( $array_to_flatten ) {
		$results = [];

		if ( ! empty( $array_to_flatten ) ) {
			array_walk_recursive( $array_to_flatten, function ( $a ) use ( &$results ) {
				if ( empty( $results[ $a ] ) ) {
					// No duplicate: add the element.
					$results[] = $a;
				}
			} );
		}

		return $results;
	}

	/***************************************************************************************************************
	 *
	 * Sort by fields which require multi-value sort Solr syntax
	 *
	 **************************************************************************************************************/
	const OPTION_SORTBY_MULTIVALUED_FIELDS = 'wdm_solr_sortby_multi_data';

	/**
	 * Get sortby options array
	 * @return array
	 */
	public function get_option_sortby_fields_multivalue() {
		return self::get_option( true, self::OPTION_SORTBY_MULTIVALUED_FIELDS, [] );
	}

	/**
	 * Does a a sort field require multi-value Solr syntax ?
	 * @return bool
	 */
	public function get_sortby_is_field_multivalue( $field_name_with_extension ) {
		$values = $this->get_option_sortby_fields_multivalue();

		return isset( $values[ $field_name_with_extension ] );
	}

	/***************************************************************************************************************
	 *
	 * Sort by option and items
	 *
	 **************************************************************************************************************/
	const OPTION_SORTBY = 'wdm_solr_sortby_data';
	const OPTION_SORTBY_ITEM_DEFAULT_FIRST = 'sort_default';
	const OPTION_SORTBY_ITEM_DEFAULT_SECOND = 'sort_default_second';
	const OPTION_SORTBY_ITEM_ITEMS = 'sort';
	const OPTION_SORTBY_ITEM_LABELS = 'sort_labels';

	/**
	 * Get sortby options array
	 * @return array
	 */
	public function get_option_sortby() {
		return self::get_option( true, self::OPTION_SORTBY, [] );
	}

	/**
	 * Set Multivalue sort by option
	 *
	 * @param string $field_name_with_extension
	 * @param bool $is_multivalue
	 *
	 * @return void
	 */
	public function set_sortby_is_multivalue( $field_name_with_extension, $is_multivalue ) {
		$option = $this->get_option_sortby_fields_multivalue();

		$is_updated = false;
		if ( ! $is_multivalue && isset( $option[ $field_name_with_extension ] ) ) {

			unset( $option[ $field_name_with_extension ] );
			$is_updated = true;

		} elseif ( $is_multivalue && ! isset( $option[ $field_name_with_extension ] ) ) {

			$option[ $field_name_with_extension ] = '1';
			$is_updated                           = true;
		}

		if ( $is_updated ) {
			update_option( self::OPTION_SORTBY_MULTIVALUED_FIELDS, $option, true );
			$this->remove_cache( self::OPTION_SORTBY_MULTIVALUED_FIELDS );
		}
	}

	/**
	 * Default first sort by option
	 * @return string
	 */
	public function get_first_sort_by_default() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SORTBY, self::OPTION_SORTBY_ITEM_DEFAULT_FIRST, WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC );
	}

	/**
	 * Default second sort by option
	 * @return string
	 */
	public function get_second_sort_by_default() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SORTBY, self::OPTION_SORTBY_ITEM_DEFAULT_SECOND, '' );
	}

	/**
	 * Comma separated string of items selectable in sort by
	 * @return string Items
	 */
	public function get_sortby_items() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SORTBY, self::OPTION_SORTBY_ITEM_ITEMS, WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC );
	}

	/**
	 * Array of items selectable in sort by
	 * @return array Array of items
	 */
	public function get_sortby_items_as_array() {
		return $this->explode( $this->get_sortby_items() );
	}

	/**
	 * Array of sort items labels
	 * @return string[] Sort items labels
	 */
	public function get_sortby_items_labels() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SORTBY, self::OPTION_SORTBY_ITEM_LABELS, [] );
	}

	public function get_option_installation() {

		if ( ! self::get_option( true, self::OPTION_INSTALLATION, false ) ) {

			$search = $this->get_option_search();
			if ( empty( $search ) ) {

				update_option( self::OPTION_INSTALLATION, true );
			}
		}

	}



	/***************************************************************************************************************
	 *
	 * Search results option and items
	 *
	 **************************************************************************************************************/
	const OPTION_SEARCH = 'wdm_solr_res_data';
	const OPTION_SEARCH_ITEM_REPLACE_WP_SEARCH = 'default_search';
	const OPTION_SEARCH_ITEM_REPLACE_WP_HOME = 'default_home';
	const OPTION_SEARCH_ITEM_REPLACE_WP_YEAR = 'default_year';
	const OPTION_SEARCH_ITEM_REPLACE_WP_MONTH = 'default_month';
	const OPTION_SEARCH_ITEM_REPLACE_WP_DAY = 'default_day';
	const OPTION_SEARCH_ITEM_REPLACE_WP_CATEGORY_FRONT_END = 'default_category';
	const OPTION_SEARCH_ITEM_REPLACE_WP_CATEGORY_ADMIN = 'default_category_admin';
	const OPTION_SEARCH_ITEM_REPLACE_WP_POST_TYPE_FRONT_END = 'default_post_type';
	const OPTION_SEARCH_ITEM_REPLACE_WP_POST_TYPE_ADMIN = 'default_post_type_admin';
	const OPTION_SEARCH_ITEM_REPLACE_WC_HPOS_ORDER_ADMIN = 'default_wc_hpos_order_admin';
	const OPTION_SEARCH_ITEM_REPLACE_WP_MEDIA_ADMIN = 'default_media_admin';
	const OPTION_SEARCH_ITEM_REPLACE_WP_TAG_FRONT_END = 'default_tag';
	const OPTION_SEARCH_ITEM_REPLACE_WP_TAG_ADMIN = 'default_tag_admin';
	const OPTION_SEARCH_ITEM_REPLACE_WP_AUTHOR = 'default_author';
	const OPTION_SEARCH_ITEM_SEARCH_METHOD = 'search_method';
	const OPTION_SEARCH_ITEM_IS_INFINITESCROLL = 'infinitescroll';
	const OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS = 'infinitescroll_is_js';
	const OPTION_SEARCH_ITEM_IS_PREVENT_LOADING_FRONT_END_CSS = 'is_prevent_loading_front_end_css';
	const OPTION_SEARCH_ITEM_IS_PREVENT_AJAX_NONCE_SECURITY_FRONT_END = 'is_no_ajax_nonce_front';
	const OPTION_SEARCH_ITEM_is_after_autocomplete_block_submit = 'is_after_autocomplete_block_submit';
	const OPTION_SEARCH_ITEM_is_display_results_info = 'res_info';
	const OPTION_SEARCH_ITEM_max_nb_results_by_page = 'no_res';
	const OPTION_SEARCH_ITEM_CERTAINTY = 'certainty';
	const OPTION_SEARCH_ITEM_ALPHA = 'alpha';
	const OPTION_SEARCH_ITEM_AUTOCUT = 'autocut';
	/* Weaviate */

	const OPTION_SEARCH_ITEM_RERANKER = 'reranker';
	const OPTION_SEARCH_ITEM_WEAVIATE_RERANKER_COHERE = 'reranker_cohere';
	const OPTION_SEARCH_ITEM_WEAVIATE_RERANKER_TOKEN = 'reranker_token';
	const OPTION_SEARCH_ITEM_WEAVIATE_RERANKER_TRANSFORMERS = 'reranker_transformers';

	const OPTION_SEARCH_ITEM_FILTER = 'filter';
	const OPTION_SEARCH_ITEM_FILTER_WHERE = 'filter_where';
	const OPTION_SEARCH_ITEM_FILTER_NEAR_TEXT = 'filter_near_text';
	const OPTION_SEARCH_ITEM_FILTER_HYBRID = 'filter_hybrid';
	const OPTION_SEARCH_ITEM_FILTER_NEAR_IMAGE = 'filter_near_image  ';
	const OPTION_SEARCH_ITEM_FILTER_NEAR_VECTOR = 'filter_near_vector';
	const OPTION_SEARCH_ITEM_FILTER_NEAR_OBJECT = 'filter_near_object';
	const OPTION_SEARCH_ITEM_FILTER_BM25 = 'filter_bm25';
	const OPTION_SEARCH_ITEM_max_nb_items_by_facet = 'no_fac';
	const OPTION_SEARCH_ITEM_highlighting_fragsize = 'highlighting_fragsize';
	const OPTION_SEARCH_ITEM_is_spellchecker = 'spellchecker';
	const OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES = 'is_partial_matches';
	const OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES = 'is_fuzzy_matches';
	const OPTION_SEARCH_ITEM_SERVING_CONFIG_ID = 'serving_config_id';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE = 'suggest_content_type';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS = 'suggest_content_type_keywords';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT = 'suggest_content_type_posts';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE_NONE = 'suggest_content_type_none';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED = 'suggest_content_type_posts_grouped';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE_QUESTIONS_ANSWERS = 'suggest_content_type_questions_answers';
	const OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR = 'suggest_jquery_selector';
	const OPTION_SEARCH_SUGGEST_CLASS_DEFAULT = 'search-field';
	const OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG = 'ajax-search-slug';
	const OPTION_SEARCH_MODE_AJAX = 'ajax';
	const OPTION_SEARCH_MODE_THEME = 'use_current_theme_search_template';
	const OPTION_SEARCH_MODE_THEME_AJAX = 'use_current_theme_search_template_with_ajax';
	const OPTION_SEARCH_MODE_AJAX_WITH_PARAMETERS = 'ajax_with_parameters';
	const OPTION_SEARCH_DEFAULT_SOLR_INDEX_FOR_SEARCH = 'default_solr_index_for_search';
	const OPTION_SEARCH_LOG_QUERY_MODE = 'option_search_log_query_mode';
	const OPTION_SEARCH_LOG_QUERY_MODE_DEBUG_FILE = 'option_search_log_query_mode_debug_file';
	const OPTION_SEARCH_LOG_QUERY_MODE_DEBUG_QUERY_MONITOR = 'option_search_log_query_mode_debug_qm';
	const OPTION_SEARCH_IS_SHOW_ALL_RESULTS = 'is_show_all_results';
	const OPTION_SEARCH_LOG_QUERY_TRACE_LEVEL = 'trace_level';

	/* Vespa */
	const OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE = 'vespa_query_type';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_ALL = '';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_ANY = 'any';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_WEAKAND = 'weakAnd';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_TOKENIZE = 'tokenize';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_WEB = 'web';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_PHRASE = 'phrase';
	const OPTION_SEARCH_ITEM_VESPA_FIELDSET = 'vespa_fieldset';
	const OPTION_SEARCH_ITEM_VESPA_RANK_PROFILE = 'vespa_rank_profile';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_MODE = 'vespa_query_mode';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT = '';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_VECTOR = 'text_vector';
	const OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_HYBRID = 'text_hybrid';

	/**
	 * Get search options array
	 *
	 * @return array
	 */
	public function get_option_search() {
		return self::get_option( true, static::OPTION_SEARCH, [] );
	}

	/**
	 * Replace default WP search form and search results by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_search() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_SEARCH ) );
	}


	/**
	 * Replace default WP authors archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_author() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_AUTHOR ) );
	}

	/**
	 * Replace default WP categories archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_category_front_end() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_CATEGORY_FRONT_END ) );
	}

	/**
	 * Replace default WP categories archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_category_admin() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_CATEGORY_ADMIN ) );
	}

	/**
	 * Replace default WP post types archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_post_type_front_end() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_POST_TYPE_FRONT_END ) );
	}

	/**
	 * Replace default WP post types archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_post_type_admin() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_POST_TYPE_ADMIN ) );
	}

	/**
	 * Replace default WooCommerce HPOS order archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wc_hpos_order_admin() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WC_HPOS_ORDER_ADMIN ) );
	}

	/**
	 * Replace default WP media library archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_media_admin() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_MEDIA_ADMIN ) );
	}

	/**
	 * Replace default WP days archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_day() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_DAY ) );
	}

	/**
	 * Replace default WP home posts archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_home() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_HOME ) );
	}

	/**
	 * Replace default WP months archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_month() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_MONTH ) );
	}

	/**
	 * Replace default WP tags archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_tag_front_end() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_TAG_FRONT_END ) );
	}

	/**
	 * Replace default WP tags archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_tag_admin() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_TAG_ADMIN ) );
	}

	/**
	 * Replace default WP years archive by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_year() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_YEAR ) );
	}


	/**
	 * Search method
	 * @return boolean
	 */
	public function get_search_method() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_SEARCH_METHOD, self::OPTION_SEARCH_MODE_THEME_AJAX );
	}

	/**
	 * Show search parameters in url ?
	 * @return boolean
	 */
	public function get_search_is_show_url_parameters() {
		$search_mode = $this->get_search_method();

		return ( self::OPTION_SEARCH_MODE_AJAX !== $search_mode );
	}

	/**
	 * Redirect url on facets click ?
	 * @return boolean
	 */
	public function get_search_is_use_current_theme_search_template() {
		$search_mode = $this->get_search_method();

		return ( ( self::OPTION_SEARCH_MODE_THEME === $search_mode ) || ( self::OPTION_SEARCH_MODE_THEME_AJAX === $search_mode ) );
	}

	/**
	 * Use current search with ajax ?
	 * @return boolean
	 */
	public function get_search_is_use_current_theme_with_ajax() {
		$search_mode = $this->get_search_method();

		return ( self::OPTION_SEARCH_MODE_THEME !== $search_mode );
	}

	/**
	 * Show results with Infinitescroll pagination ?
	 * @return boolean
	 */
	public function get_search_is_infinitescroll() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_INFINITESCROLL ) );
	}

	/**
	 * Load Infinitescroll js file ?
	 * @return boolean
	 */
	public function get_search_is_infinitescroll_replace_js() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS ) );
	}

	/**
	 * Prevent loading WPSOLR default front-end css files. It's then easier to use current theme css.
	 * @return boolean
	 */
	public function get_search_is_prevent_loading_front_end_css() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_PREVENT_LOADING_FRONT_END_CSS ) );
	}

	/**
	 * Prevent Ajax nonce security verification
	 * @return boolean
	 */
	public function get_search_is_no_ajax_nonce_verification_front_end() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_PREVENT_AJAX_NONCE_SECURITY_FRONT_END ) );
	}

	/**
	 * Do not trigger a search after selecting an item in the autocomplete list.
	 * @return string '1 for yes
	 */
	public function get_search_after_autocomplete_block_submit() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_is_after_autocomplete_block_submit, '0' );
	}

	/**
	 * Display results information, or not
	 * @return boolean
	 */
	public function get_search_is_display_results_info() {
		return ( 'res_info' === $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_is_display_results_info, 'res_info' ) );
	}

	/**
	 * Maximum number of results displayed on a page
	 * @return integer
	 */
	public function get_search_max_nb_results_by_page() {
		return $this->to_integer( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_max_nb_results_by_page, 20 ), 'Max results by page' );
	}

	/**
	 * Certainty  (weaviate)
	 * @return string
	 */
	public function get_search_certainty() {
		return $this->to_float( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_CERTAINTY, '0.5' ), 'Certainty' );
	}

	/**
	 * Aplpha  (weaviate)
	 * @return string
	 */
	public function get_search_alpha() {
		return $this->to_float( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_ALPHA, '0.75' ), 'Alpha' );
	}

	/**
	 * Autocut  (weaviate)
	 * @return string
	 */
	public function get_search_autocut() {
		return $this->to_integer( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_AUTOCUT, 0 ), 'Autocut' );
	}

	/**
	 * Filter (weaviate)
	 * @return string
	 */
	public function get_search_filter() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_FILTER, '' );
	}

	/**
	 * Maximum number of facet items displayed in any facet
	 * @return integer
	 */
	public function get_search_max_nb_items_by_facet() {
		return $this->to_integer( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_max_nb_items_by_facet, 10 ), 'Max items by facet' );
	}

	/**
	 * Maximum length of highligthing text
	 * @return integer
	 */
	public function get_search_max_length_highlighting() {
		return $this->to_integer( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_highlighting_fragsize, 100 ), 'Max length of highlighting' );
	}

	/**
	 * Serving config (Google Retail)
	 * @return string
	 */
	public function get_search_serving_config_id() {
		$result = $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_SERVING_CONFIG_ID, '' );

		return empty( $result ) ? 'default_search' : $result;
	}

	/**
	 * Is "Did you mean?" activated ?
	 * @return boolean
	 */
	public function get_search_is_did_you_mean() {
		return ( 'spellchecker' === $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_is_spellchecker, false ) );
	}

	/**
	 * Is "Partial matches?" activated ?
	 * @return boolean
	 */
	public function get_search_is_partial_matches() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES ) );
	}

	/**
	 * Is "Fuzzy matches?" activated ?
	 * @return boolean
	 */
	public function get_search_is_fuzzy_matches() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES ) );
	}

	/**
	 * IRemove max # of results limit ?
	 * @return boolean
	 */
	public function get_search_is_show_all_results() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_IS_SHOW_ALL_RESULTS ) );
	}

	/**
	 * Search suggestions content (deprecated since 21.5)
	 * @return string
	 */
	public function get_search_suggest_content_type_before_version_21_5() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_SUGGEST_CONTENT_TYPE, self::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS );
	}

	/**
	 * Search suggestions jquery selector (deprecated since 21.5)
	 * @return string
	 */
	public function get_search_suggest_jquery_selector_before_version_21_5() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR, '' );
	}

	/**
	 * Ajax search page slug
	 * @return string
	 */
	public function get_search_ajax_search_page_slug() {
		$result = $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG, WPSOLR_SearchSolariumClient::_SEARCH_PAGE_SLUG );

		return ! empty( $result ) ? $result : WPSOLR_SearchSolariumClient::_SEARCH_PAGE_SLUG;
	}

	/**
	 * Log trace level for queries
	 * @return string
	 */
	public function get_search_log_query_trace_level() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_LOG_QUERY_TRACE_LEVEL, '' );
	}

	/**
	 * Log mode for queries
	 * @return string
	 */
	public function get_search_log_query_mode() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_LOG_QUERY_MODE, '' );
	}

	/**
	 * Is Log mode file?
	 * @return bool
	 */
	public function get_is_search_log_query_mode_debug_file() {
		return ( self::OPTION_SEARCH_LOG_QUERY_MODE_DEBUG_FILE === $this->get_search_log_query_mode() );
	}

	/**
	 * Is Log mode Query Monitor?
	 * @return bool
	 */
	public function get_is_search_log_query_mode_debug_query_monitor() {
		return ( self::OPTION_SEARCH_LOG_QUERY_MODE_DEBUG_QUERY_MONITOR === $this->get_search_log_query_mode() );
	}

	/**
	 * Is "Rerank?" activated ?
	 * @return boolean
	 */
	public function get_search_has_reranker() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_RERANKER, '' ) );
	}

	/**
	 * Is "Rerank?" activated ?
	 * @return boolean
	 */
	public function get_search_has_reranker_cohere() {
		return ( self::OPTION_SEARCH_ITEM_WEAVIATE_RERANKER_COHERE === $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_RERANKER, '' ) );
	}

	/**
	 * Reranker API key
	 * @return boolean
	 */
	public function get_search_reranker_api_key() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_WEAVIATE_RERANKER_TOKEN );
	}

	/**
	 * Vespa query type
	 * @return string
	 */
	public function get_search_vespa_query_type() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE, self::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_ALL );
	}

	/**
	 * Vespa query mode
	 * @return string
	 */
	public function get_search_vespa_query_mode() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE, self::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT );
	}

	/**
	 * Is a vector search?
	 * @return bool
	 */
	public function get_search_vespa_query_mode_is_vector() {
		return in_array( $this->get_search_vespa_query_mode(), [
			static::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_VECTOR,
			static::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_HYBRID
		] );
	}

	/**
	 * Is a text search?
	 * @return bool
	 */
	public function get_search_vespa_query_mode_is_text() {
		return in_array( $this->get_search_vespa_query_mode(), [
			static::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT,
			static::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_HYBRID
		] );
	}

	/**
	 * Vespa fieldset
	 * @return string
	 */
	public function get_search_vespa_fieldset() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_VESPA_FIELDSET, 'default' );
	}

	/**
	 * Vespa rank profile
	 * @return string
	 */
	public function get_search_vespa_rank_profile() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_VESPA_RANK_PROFILE, 'default' );
	}


	/***************************************************************************************************************
	 *
	 * Installation
	 *
	 **************************************************************************************************************/
	const OPTION_INSTALLATION = 'wpsolr_install';

	/***************************************************************************************************************
	 *
	 * Facets option and items
	 *
	 **************************************************************************************************************/
	const OPTION_FACET = 'wdm_solr_facet_data';
	const OPTION_FACET_FACETS = 'facets';
	const OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH = 'facets_show_hierarchy';
	const OPTION_FACET_FACETS_LABEL = 'facets_label';
	const OPTION_FACET_FACETS_ITEMS_LABEL = 'facets_item_label';
	const OPTION_FACET_FACETS_SORT = 'facets_sort';
	const OPTION_FACET_FACETS_ITEMS_IS_DEFAULT = 'facets_item_is_default';
	const OPTION_FACET_FACETS_ITEMS_IS_HIDDEN = 'facets_item_is_hidden';
	const OPTION_FACET_FACETS_IS_EXCLUSION = 'facets_is_exclusion';
	const OPTION_FACET_FACETS_LAYOUT = 'facets_layout';
	const OPTION_FACET_FACETS_TYPE = 'facet_type';
	const OPTION_FACET_FACETS_IS_OR = 'facets_is_or';
	const OPTION_FACET_FACETS_GRID = 'facets_grid';
	const OPTION_FACET_FACETS_SEO_PERMALINK_TEMPLATE = 'facets_seo_template';
	const OPTION_FACET_FACETS_SEO_IS_PERMALINK = 'facets_seo_is_permalink';
	const OPTION_FACET_FACETS_SEO_PERMALINK_ITEMS_TEMPLATE = 'facets_seo_items_template';
	const OPTION_FACET_FACETS_SEO_PERMALINK_POSITION = 'facets_seo_position';
	const OPTION_FACET_FACETS_SKIN = 'facets_skin';
	const OPTION_FACET_FACETS_GENERIC_SCRIPT = 'facets_generic_script';
	const OPTION_FACET_FACETS_JS = 'facets_js';
	const OPTION_FACET_FACETS_IS_MULTIPLE = 'facets_is_multiple';
	const OPTION_FACET_FACETS_PLACEHOLDER = 'facets_placeholder';
	const OPTION_FACET_FACETS_SIZE = 'facets_size';
	const OPTION_FACET_FACETS_SIZE_SHOW_ALL_LINK = 'facets_size_show_all';
	const OPTION_FACET_FACETS_IS_HIDE_IF_NO_CHOICE = 'facets_is_hide_if_no_choice';
	const OPTION_FACET_FACETS_IS_CAN_NOT_EXIST = 'facets_is_can_not_exist';

	const OPTION_FACETS_ORIENTATION = 'facets_orientation';
	const OPTION_FACETS_ORIENTATION_HORIZONTAL = 'facets_orientation_horizontal';
	const OPTION_FACETS_ORIENTATION_VERTICAL = 'facets_orientation_vertical';

	const OPTION_FACET_GRID_HORIZONTAL = 'h';
	const OPTION_FACET_GRID_1_COLUMN = 'c1';
	const OPTION_FACET_GRID_2_COLUMNS = 'c2';
	const OPTION_FACET_GRID_3_COLUMNS = 'c3';

	const OPTION_FACET_FACETS_TYPE_FIELD = 'facet_type_field';
	const OPTION_FACET_FACETS_TYPE_RANGE = 'facet_type_range';
	const OPTION_FACET_FACETS_TYPE_MIN_MAX = 'facet_type_min_max';

	const FACET_FIELD_LABEL_MIDDLE = 'facet_label_middle'; // Facet label
	const FACET_FIELD_LABEL_FIRST = 'facet_label_first'; // Label of the first label element
	const FACET_FIELD_LABEL_LAST = 'facet_label_last'; // Label of the last label element
	const FACET_FIELD_RANGE_START = 'facet_range_start'; // Start of the range
	const FACET_FIELD_RANGE_END = 'facet_range_end'; // End of the range
	const FACET_FIELD_RANGE_GAP = 'facet_range_gap'; // Gap of the range
	const FACET_FIELD_CUSTOM_RANGES = 'facet_custom_ranges'; // Custom ranges

	const FACETS_LAYOUT_ID_COLOR_PICKER = 'id_color_picker';
	const FACETS_LAYOUT_ID_DATE_PICKER = 'id_date_picker';
	const FACETS_LAYOUT_ID_RANGE_REGULAR_CHECKBOXES = 'id_range_regular_checkboxes';
	const FACETS_LAYOUT_ID_RANGE_IRREGULAR_CHECKBOXES = 'id_range_irregular_checkboxes';
	const FACETS_LAYOUT_ID_RATING_STARS = 'id_rating_stars';
	const FACETS_LAYOUT_ID_RANGE_REGULAR_RADIOBOXES = 'id_range_regular_radioboxes';
	const FACETS_LAYOUT_ID_RANGE_IRREGULAR_RADIOBOXES = 'id_range_irregular_radioboxes';
	const FACETS_LAYOUT_ID_SELECT = 'id_select';
	const FACETS_LAYOUT_ID_SELECT2 = 'id_select2';

	const FACET_LABEL_TEMPLATE_VAR_VALUE = '{{value}}';
	const FACET_LABEL_TEMPLATE_VAR_START = '{{start}}';
	const FACET_LABEL_TEMPLATE_VAR_END = '{{end}}';
	const FACET_LABEL_TEMPLATE_VAR_COUNT = '{{count}}';

	const FACET_LABEL_TEMPLATE_RANGE = '{{start}} - {{end}} ({{count}})';
	const FACET_LABEL_SEO_TEMPLATE_RANGE = '{{start}} - {{end}}';
	const FACET_LABEL_TEMPLATE = '%1$s (%2$s)';
	const FACET_LABEL_SEO_TEMPLATE = '{{value}}';
	const FACET_LABEL_TEMPLATE_MIN_MAX = 'From %1$s to %2$s (%3$d)';
	const FACET_LABEL_TEMPLATE_RANGES = '0|10|%1$s - %2$s (%3$d)';

	const OPTION_FACET_FACETS_IS_SHOW_VARIATION_IMAGE = 'is_use_variation_image';

	/**
	 * Get facet options array
	 * @return array
	 */
	public function get_option_facet() {
		return self::get_option( true, self::OPTION_FACET, [] );
	}

	/**
	 * Comma separated facets
	 * @return string
	 */
	public function get_facets_to_display_str() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS, '' );
	}

	/**
	 * Facets
	 * @return array ["type","author","categories","tags","acf2_str"]
	 */
	public function get_facets_to_display() {
		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACETS_TO_DISPLAY, $this->explode( $this->get_facets_to_display_str() ) );
	}

	/**
	 * Facets to show as a hierarcy
	 *
	 * @return array Facets names
	 */
	public function get_facets_to_show_as_hierarchy() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH, [] );
	}

	/**
	 * Facets orientation
	 *
	 * @return string
	 */
	public function get_facets_orientation() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACETS_ORIENTATION, self::OPTION_FACETS_ORIENTATION_VERTICAL );
	}

	/**
	 * Facets labels
	 *
	 * @return array Facets names
	 */
	public function get_facets_labels() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_LABEL, [] );
	}

	/**
	 * Facets SEO permalink templates
	 *
	 * @return array Facets SEO permalink templates
	 */
	public function get_facets_seo_permalink_templates() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SEO_PERMALINK_TEMPLATE, [] );
	}

	/**
	 * Facets SEO permalink positions
	 *
	 * @return array Facets SEO permalink positions
	 */
	public function get_facets_seo_permalink_positions() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SEO_PERMALINK_POSITION, [] );
	}

	/**
	 * Facets items SEO permalink templates
	 *
	 * @return array Facets items SEO permalink templates
	 */
	public function get_facets_seo_permalink_items_templates() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SEO_PERMALINK_ITEMS_TEMPLATE, [] );
	}

	/**
	 * Facets SEO is permalink
	 * @return array Are facets permalinks ?
	 */
	public function get_facets_seo_is_permalinks() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SEO_IS_PERMALINK, [] );
	}

	/**
	 * Facets items labels
	 *
	 * @return array Facets items names
	 */
	public function get_facets_items_labels() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_ITEMS_LABEL, [] );
	}

	/**
	 * Facet item label
	 *
	 * @return string Facet item label
	 */
	public function get_facets_item_label( $facet_name, $facet_value ) {
		$facet_items_labels = $this->get_facets_items_labels();

		return ( ! empty( $facet_items_labels ) && ! empty( $facet_items_labels[ $facet_name ] ) && ! empty( $facet_items_labels[ $facet_name ][ $facet_value ] ) )
			? $facet_items_labels[ $facet_name ][ $facet_value ]
			: $facet_value;
	}

	/**
	 * Facets items is default
	 *
	 * @return array Facets items names
	 */
	public function get_facets_items_is_default() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_ITEMS_IS_DEFAULT, [] );
	}

	/**
	 * Facets items is hidden
	 *
	 * @return array Facets items names
	 */
	public function get_facets_items_is_hidden() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_ITEMS_IS_HIDDEN, [] );
	}

	/**
	 * Facets sort
	 * @return boolean
	 */
	public function get_facets_sort() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SORT, [] );
	}

	/**
	 * Facets is OR
	 * @return boolean
	 */
	public function get_facets_is_or() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_IS_OR, [] );
	}

	/**
	 * Facets is hide if no choice
	 * @return array
	 */
	public function get_facets_is_hide_if_no_choice() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_IS_HIDE_IF_NO_CHOICE, [] );
	}

	/**
	 * Facets is related
	 * @return array
	 */
	public function get_facets_is_can_not_exist() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_IS_CAN_NOT_EXIST, [] );
	}

	/**
	 * Facets is exclusion
	 * @return array
	 */
	public function get_facets_is_exclusion() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_IS_EXCLUSION, [] );
	}

	/**
	 * Facets layout
	 * @return array
	 */
	public function get_facets_layouts_ids() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_LAYOUT, [] );
	}


	/**
	 * Get a facet option value
	 *
	 * @return mixed
	 */
	public function get_facets_value( $facet_option, $facet_name, $facet_default_value ) {
		$facets = $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, $facet_option, '' );

		return ( ! empty( $facets ) && ! empty( $facets[ $facet_name ] ) )
			? $facets[ $facet_name ]
			: $facet_default_value;
	}

	/**
	 * Get first label of a range regular facet
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_range_regular_template( $facet_name, $default_value = null ) {
		return $this->get_facets_value( self::FACET_FIELD_LABEL_FIRST, $facet_name, isset( $default_value ) ? $default_value : self::FACET_LABEL_TEMPLATE_RANGE );
	}

	/**
	 * Get SEO permalink template
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_seo_permalink_template( $facet_name, $default_value = null ) {
		$facet_seo_templates = $this->get_facets_seo_permalink_templates();

		return ( ! empty( $facet_seo_templates ) && ! empty( $facet_seo_templates[ $facet_name ] ) )
			? $facet_seo_templates[ $facet_name ]
			: ( isset( $default_value ) ? $default_value : self::FACET_LABEL_TEMPLATE_VAR_VALUE );
	}

	/**
	 * Get SEO permalink item template
	 *
	 * @param string $facet_name
	 * @param string $item_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_seo_permalink_item_template( $facet_name, $item_name ) {
		$facet_items_seo_templates = $this->get_facets_seo_permalink_items_templates();

		return ( ! empty( $facet_items_seo_templates ) && ! empty( $facet_items_seo_templates[ $facet_name ] ) & ! empty( $facet_items_seo_templates[ $facet_name ][ $item_name ] ) )
			? $facet_items_seo_templates[ $facet_name ][ $item_name ]
			: '';
	}

	/**
	 * Get layout id of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_layout_id( $facet_name ) {
		$facet_layouts_ids = $this->get_facets_layouts_ids();

		return ( ! empty( $facet_layouts_ids ) && ! empty( $facet_layouts_ids[ $facet_name ] ) )
			? $facet_layouts_ids[ $facet_name ]
			: '';
	}

	/**
	 * Get start of a range regular facet
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_range_regular_start( $facet_name, $default_value = null ) {
		return $this->get_facets_value( self::FACET_FIELD_RANGE_START, $facet_name, isset( $default_value ) ? $default_value : '0' );
	}


	/**
	 * Get end of a range regular facet
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_range_regular_end( $facet_name, $default_value = null ) {
		return $this->get_facets_value( self::FACET_FIELD_RANGE_END, $facet_name, isset( $default_value ) ? $default_value : '100' );
	}


	/**
	 * Get gap of a range regular facet
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_range_regular_gap( $facet_name, $default_value = null ) {
		return $this->get_facets_value( self::FACET_FIELD_RANGE_GAP, $facet_name, isset( $default_value ) ? $default_value : '10' );
	}

	/**
	 * Get ranges of a range irregular facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_range_irregular_ranges( $facet_name ) {
		return $this->get_facets_value( self::FACET_FIELD_CUSTOM_RANGES, $facet_name, self::FACET_LABEL_TEMPLATE_RANGES );
	}

	/**
	 * Facets grid
	 *
	 * @return array
	 */
	public function get_facets_grid() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_GRID, [] );
	}

	/**
	 * Get grid of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_grid_value( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_GRID, $facet_name, self::OPTION_FACET_GRID_1_COLUMN );
	}

	/**
	 * Facets skins
	 *
	 * @return array
	 */
	public function get_facets_skin() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SKIN, [] );
	}

	/**
	 * Generic script
	 *
	 * @return array
	 */
	public function get_facets_generic_script() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_GENERIC_SCRIPT, [] );
	}


	/**
	 * Get skin of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_skin_value( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_SKIN, $facet_name, '' );
	}

	/**
	 * Get language of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_generic_script_value( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_GENERIC_SCRIPT, $facet_name, '' );
	}

	/**
	 * Facets javascripts
	 *
	 * @return array
	 */
	public function get_facets_js() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_JS, [] );
	}

	/**
	 * Get javascript of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_js_value( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_JS, $facet_name, '' );
	}

	/**
	 * Facets multiple
	 *
	 * @return array
	 */
	public function get_facets_is_multiple() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_IS_MULTIPLE, [] );
	}

	/**
	 * Is a facet multiple ?
	 *
	 * @param $facet_name
	 *
	 * @return boolean
	 */
	public function get_facets_is_multiple_value( $facet_name ) {
		return ! $this->is_empty( $this->get_facets_value( self::OPTION_FACET_FACETS_IS_MULTIPLE, $facet_name, '' ) );
	}

	/**
	 * Facets placeholders
	 *
	 * @return array
	 */
	public function get_facets_placeholder() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_PLACEHOLDER, [] );
	}

	/**
	 * Get placeholder of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_placeholder_value( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_PLACEHOLDER, $facet_name, '' );
	}

	/**
	 * Facets sizes
	 *
	 * @return array
	 */
	public function get_facets_size() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SIZE, [] );
	}

	/**
	 * Get size of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_size_value( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_SIZE, $facet_name, '' );
	}

	/**
	 * Show variation image instead of the product thumbnail
	 * @return boolean
	 */
	public function get_facets_is_show_variation_image() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_IS_SHOW_VARIATION_IMAGE, [] );
	}


	/**
	 * Facets max nb items shown
	 *
	 * @return array
	 */
	public function get_facets_size_shown() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SIZE_SHOW_ALL_LINK, [] );
	}

	/**
	 * Get max nb items shown of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_size_shown_value( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_SIZE_SHOW_ALL_LINK, $facet_name, '' );
	}

	/***************************************************************************************************************
	 *
	 * Indexing option and items
	 *
	 **************************************************************************************************************/
	const OPTION_INDEX = 'wdm_solr_form_data';
	const OPTION_INDEX_CATALOGS = 'wdm_catalogs'; // Fields sent to the index catalogs (Google Retail)
	const OPTION_INDEX_ARE_COMMENTS_INDEXED = 'comments';
	const OPTION_INDEX_IS_REAL_TIME = 'is_real_time';
	const OPTION_INDEX_POST_TYPES = 'p_types';
	const OPTION_INDEX_POST_EXCLUDES_IDS_FROM_INDEXING = 'exclude_ids';
	const OPTION_INDEX_ATTACHMENT_TYPES = 'attachment_types';
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTIES = 'custom_field_properties'; // array
	const OPTION_INDEX_CUSTOM_FIELDS = 'cust_fields'; // array
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE = 'solr_dynamic_type'; // string
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION = 'conversion_error_action'; // string
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_THROW_ERROR = 'conversion_error_action_throw_error';
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_IGNORE_FIELD = 'conversion_error_action_ignore_field';
	const OPTION_INDEX_TAXONOMIES = 'taxonomies';
	const OPTION_INDEX_POST_EXCLUDES_IDS_FROM_SEARCHING = 'exclude_search_ids';
	const OPTION_INDEX_POST_TYPES_IS_ADMIN = 'p_types_is_admin';
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION = 'relation'; // string
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION_NONE = '';
	const OPTION_INDEX_POST_TYPES_IS_IMAGE_FEATURED = 'is_image_featured';
	const OPTION_INDEX_POST_TYPES_IS_IMAGE_EMBEDDED = 'is_image_embedded';

	const OPTION_INDEX_STATUSES = 'statuses'; // array

	/**
	 * Get indexing options array
	 *
	 * @param bool $is_show_only_custom
	 *
	 * @return array
	 */
	public function get_option_index( $is_show_only_custom = false ) {
		return $this->get_option( true, self::OPTION_INDEX /*$this->_get_index_option_name( $is_show_only_custom )*/, [] );
	}

	/**
	 * Index comments ?
	 * @return boolean
	 */
	public function get_index_are_comments_indexed() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_ARE_COMMENTS_INDEXED ) );
	}

	/**
	 * Index real-time (on save) ?
	 * @return boolean
	 */
	public function get_index_is_real_time() {
		return $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_IS_REAL_TIME ) );
	}

	/**
	 * @return array Post types
	 */
	public function get_option_index_post_types() {
		return $this->explode( $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_POST_TYPES, '' ) );
	}

	/**
	 * @return array Post types
	 */
	public function get_option_index_post_types_is_admin() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_POST_TYPES_IS_ADMIN, [] );
	}

	/**
	 * @param string $post_type
	 *
	 * @return bool
	 */
	public function get_option_index_post_type_is_admin( $post_type ) {
		$post_types_is_admin = $this->get_option_index_post_types_is_admin();

		return ! empty( $post_types_is_admin[ $post_type ] );
	}

	/**
	 * @return array Post ids excluded from indexing
	 */
	public function get_option_index_post_excludes_ids_from_indexing() {
		return $this->explode( $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_POST_EXCLUDES_IDS_FROM_INDEXING, '' ) );
	}

	/**
	 * @return array Post ids excluded from searching
	 */
	public function get_option_index_post_excludes_ids_from_searching() {
		return $this->explode( $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_POST_EXCLUDES_IDS_FROM_SEARCHING, '' ) );
	}


	/**
	 * @return string Post types
	 */
	public function get_option_index_attachment_types_str() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_ATTACHMENT_TYPES, '' );
	}

	/**
	 * @return array Post types
	 */
	public function get_option_index_attachment_types() {
		return $this->explode( $this->get_option_index_attachment_types_str() );
	}

	/**
	 * @return array Taxonomies indexed
	 */
	public function get_option_index_taxonomies() {
		return $this->explode( $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_TAXONOMIES, '' ) );
	}

	/**
	 * @param bool $is_flatened Remove the model type level, and keep on the deduplicated fields level.
	 *
	 * @return array Active custom fields
	 */
	public function get_option_index_custom_fields( $is_flatened = false ) {

		/**
		 * Old format test changed in version 20.9
		 */
		//$results = '_edit_last_str,_edit_last_str,ait-item-extension-custom-field_str,_edit_last_str,_wp_attached_file_str';

		$results = $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_CUSTOM_FIELDS, [] );

		if ( ! empty( $results ) && ( is_scalar( $results ) ) ) {
			// Old format, convert it once for all.
			$results = WPSOLR_Model_Meta_Type_Post::reformat_old_custom_fields( explode( ',', $results ), $this->get_option_index_post_types() );

			// Now save the new format over the old format.
			$options                                     = $this->get_option_index();
			$options[ self::OPTION_INDEX_CUSTOM_FIELDS ] = $results;
			update_option( self::OPTION_INDEX, $options );
		}

		// Transform fields associative array in array
		if ( is_array( $results ) ) {
			foreach ( $results as $model_type => &$fields ) {
				$fields = array_keys( $fields );
			}
		}

		if ( $is_flatened && ! empty( $results ) ) {
			return $this->flatten_and_deduplicate( $results );
		}

		return $results;
	}

	/**
	 * @return array Array of field's properties
	 */
	public function get_option_index_custom_field_properties() {

		$custom_field_properties = $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES, [] );

		return $custom_field_properties;
	}

	/**
	 * Get the relation parent field to the current field
	 *
	 * @param string $child_field_name
	 *
	 * @return string
	 */
	public function get_option_index_custom_field_relation_parent( $child_field_name ) {

		$custom_fields_properties = $this->get_option_index_custom_field_properties();

		$has_relation = ! empty( $custom_fields_properties ) &&
		                ! empty( $custom_fields_properties[ $child_field_name ] ) &&
		                isset( $custom_fields_properties[ $child_field_name ][ static::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION ] ) &&
		                ! empty( $relation = $custom_fields_properties[ $child_field_name ][ static::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION ] );

		return $has_relation ? $relation : '';
	}

	/**
	 * Get the relation children fields to the current field
	 *
	 * @param string $parent_field_name
	 *
	 * @return string[]
	 */
	public function get_option_index_custom_field_relation_children( $parent_field_name ) {

		$results = [];
		foreach ( $this->get_option_index_custom_field_properties() as $child_field_name => $custom_fields_properties ) {
			if ( ! empty( $custom_fields_properties ) &&
			     ! empty( $custom_fields_properties[ static::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION ] ) &&
			     ( $parent_field_name === $custom_fields_properties[ static::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION ] )
			) {
				$results[] = $child_field_name;
			}
		}

		return $results;
	}

	/**
	 * @return array Image featured
	 */
	public function get_option_index_post_types_is_image_featured() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_POST_TYPES_IS_IMAGE_FEATURED, [] );
	}

	/**
	 * @return array Image featured
	 */
	public function get_option_index_post_types_is_embedded_image() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_POST_TYPES_IS_IMAGE_EMBEDDED, [] );
	}

	/**
	 * @param string $model_type
	 *
	 * @return array Model statuses
	 */
	public function get_option_index_statuses( string $model_type = '' ) {
		$all_statuses = $this->get_option_value( true, __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_STATUSES, [] );

		return empty( $model_type ) ? $all_statuses : ( $all_statuses[ $model_type ] ?? [] );
	}

	/***************************************************************************************************************
	 *
	 * Localization option and items
	 *
	 **************************************************************************************************************/
	const OPTION_LOCALIZATION = 'wdm_solr_localization_data';
	const OPTION_LOCALIZATION_LOCALIZATION_METHOD = 'localization_method';

	/**
	 * Get localization options array
	 * @return array
	 */
	public function get_option_localization() {
		return self::get_option( true, self::OPTION_LOCALIZATION, [] );
	}

	/**
	 * @return bool
	 */
	public function get_localization_is_internal() {
		return ( 'localization_by_admin_options' === $this->get_option_value( true, __FUNCTION__, self::OPTION_LOCALIZATION, self::OPTION_LOCALIZATION_LOCALIZATION_METHOD, 'localization_by_admin_options' ) );
	}

	/***************************************************************************************************************
	 *
	 * Search fields option and items
	 *
	 **************************************************************************************************************/
	const OPTION_SEARCH_FIELDS = 'wdm_solr_search_field_data';
	const OPTION_SEARCH_FIELDS_IS_ACTIVE = 'search_fields_is_active';
	const OPTION_SEARCH_FIELDS_FIELDS = 'search_fields';
	const OPTION_SEARCH_FIELDS_BOOST = 'search_field_boost';
	const OPTION_SEARCH_FIELDS_TERMS_BOOST = 'search_field_terms_boosts';
	const OPTION_SEARCH_FIELDS_BOOST_TYPE = 'search_field_boost_type';

	/**
	 */
	public function get_option_boost() {
		return self::get_option( true, self::OPTION_SEARCH_FIELDS, [] );
	}

	/**
	 * @return string Comma separated Fields
	 */
	public function get_option_search_fields_str() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_FIELDS, '' );
	}

	/**
	 * @return array Array of fields
	 */
	public function get_option_search_fields() {
		return $this->explode( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_FIELDS, '' ) );
	}

	/**
	 * Field boosts
	 *
	 * @return array Field boosts
	 */
	public function get_search_fields_boosts() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_BOOST, [] );
	}


	/**
	 * Field terms boosts
	 *
	 * @return array Field term boosts
	 */
	public function get_search_fields_terms_boosts() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_TERMS_BOOST, [] );
	}

	/**
	 * Is search fields options active ?
	 *
	 * @return boolean
	 */
	public function get_search_fields_is_active() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_IS_ACTIVE ) );
	}

	/**
	 * Field boost types
	 *
	 * @return array Field boost types
	 */
	public function get_search_fields_boost_types() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_BOOST_TYPE, [] );
	}


	/*
	 * Domains used in multi-language string plugins to store dynamic wpsolr translations
	 */
	const TRANSLATION_DOMAIN_FACET_LABEL = 'wpsolr facet label'; // Do not change
	const TRANSLATION_DOMAIN_FACET_JS = 'wpsolr facet js'; // Do not change
	const TRANSLATION_DOMAIN_FACET_PLACEHOLDER = 'wpsolr facet placeholder'; // Do not change
	const TRANSLATION_DOMAIN_SORT_LABEL = 'wpsolr sort label'; // Do not change
	const TRANSLATION_DOMAIN_GEOLOCATION_LABEL = 'wpsolr geolocation label'; // Do not change
	const TRANSLATION_DOMAIN_FACET_SEO_TEMPLATE = 'wpsolr facet SEO template'; // Do not change
	const TRANSLATION_DOMAIN_FACET_ITEM_SEO_TEMPLATE = 'wpsolr facet item SEO template'; // Do not change
	const TRANSLATION_DOMAIN_SUGGESTION_LABEL = 'wpsolr suggestion label'; // Do not change








	/***************************************************************************************************************
	 *
	 * Premium options
	 *
	 **************************************************************************************************************/
	const OPTION_PREMIUM = 'wdm_solr_premium';

	/**
	 * Get premium options array
	 * @return array
	 */
	public function get_option_premium() {
		return self::get_option( true, self::OPTION_PREMIUM, [] );
	}


	/***************************************************************************************************************
	 *
	 * Theme options
	 *
	 **************************************************************************************************************/
	const OPTION_THEME_AJAX_RESULTS_JQUERY_SELECTOR_DEFAULT = '.products,.results-by-facets';
	const OPTION_THEME_AJAX_PAGINATION_JQUERY_SELECTOR_DEFAULT = 'nav.woocommerce-pagination,.paginate_div';
	const OPTION_THEME_AJAX_PAGINATION_PAGE_JQUERY_SELECTOR_DEFAULT = 'a.page-numbers,a.paginate';
	const OPTION_THEME_AJAX_RESULTS_COUNT_JQUERY_SELECTOR_DEFAULT = '.woocommerce-result-count,.res_info';
	const OPTION_THEME_AJAX_PAGE_TITLE_JQUERY_SELECTOR_DEFAULT = '.page-title';
	const OPTION_THEME_AJAX_SORT_JQUERY_SELECTOR_DEFAULT = '.woocommerce-ordering select,.select_field';


	/***************************************************************************************************************
	 *
	 * Common seo options
	 *
	 **************************************************************************************************************/
	const OPTION_SEO_IS_GENERATE_FACETS_PERMALINKS = 'is_facet_permalinks';
	const OPTION_SEO_IS_GENERATE_KEYWORDS_PERMALINKS = 'is_keyword_permalinks';
	const OPTION_SEO_IS_REMOVE_TEST_MODE = 'is_stealth_mode'; // wrong name, should be 'is_remove_test_mode'
	const OPTION_SEO_PERMALINKS_HOME = 'permalinks_home';
	const OPTION_SEO_PERMALINKS_USAGE = 'permalinks_usage';
	const OPTION_SEO_PERMALINKS_USAGE_NORMAL = 'usage_basic';
	const OPTION_SEO_PERMALINKS_IS_REDIRECT_FROM_SEARCH = 'usage_redirect_from_search';
	const OPTION_SEO_PERMALINKS_USAGE_REDIRECT_TO_SEARCH = 'usage_redirect_to_search';
	const OPTION_SEO_PERMALINKS_STORAGE = 'permalinks_storage';
	const OPTION_SEO_PERMALINKS_STORAGE_IS_DATABASE = 'permalinks_storage_is_db';
	const OPTION_SEO_PERMALINKS_STORAGE_IS_INDEX = 'permalinks_storage_is_index';
	const OPTION_SEO_IS_GENERATE_SORTS_PERMALINKS = 'is_sort_permalinks';
	const OPTION_SEO_IS_CONTENTS_NOFOLLOW = 'is_contents_nofollow';
	const OPTION_SEO_IS_CONTENTS_NOINDEX = 'is_contents_noindex';
	const OPTION_SEO_IS_PERMALINKS_NOFOLLOW = 'is_permalinks_nofollow';
	const OPTION_SEO_IS_PERMALINKS_NOINDEX = 'is_permalinks_noindex';
	const OPTION_SEO_IS_REDIRECT_FACETS_PERMALINKS_HOME = 'is_facet_redirect_home';
	const OPTION_SEO_TEMPLATE_META_TITLE = 'meta_title';
	const OPTION_SEO_TEMPLATE_META_DESCRIPTION = 'meta_description';
	const OPTION_SEO_META_VAR_VALUE = '{{meta}}';
	const OPTION_SEO_OPEN_GRAPH_IMAGE = 'og:image';
	const OPTION_SEO_IS_SPEED_SITEMAPS = 'is_sitemaps';
	const OPTION_SEO_SITEMAP_NB_ENTRIES_PER_PAGE = 'sitemap_nb_entries_per_page';


	/**
	 * Permalinks home
	 *
	 * @return string
	 */
	public function get_option_seo_common_permalinks_home( $option_name ) {
		return trim( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_PERMALINKS_HOME, '' ), ' /' );
	}

	/**
	 * Meta title template
	 *
	 * @return string
	 */
	public function get_option_seo_template_meta_title( $option_name ) {
		return $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_TEMPLATE_META_TITLE, '' );
	}

	/**
	 * Open graph image id
	 *
	 * @return string
	 */
	public function get_option_seo_open_graph_image_id( $option_name ) {
		return $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_OPEN_GRAPH_IMAGE, '' );
	}

	/**
	 * Open graph image url
	 *
	 * @return string
	 */
	public function get_option_seo_open_graph_image_url( $option_name ) {
		return wp_get_attachment_url( $this->get_option_seo_open_graph_image_id( $option_name ) );
	}

	/**
	 * Meta description template
	 *
	 * @return string
	 */
	public function get_option_seo_template_meta_description( $option_name ) {
		return $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_TEMPLATE_META_DESCRIPTION, '' );
	}


	/**
	 * Redirect facets to permalinks home ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_is_redirect_facet_to_permalink_home( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_REDIRECT_FACETS_PERMALINKS_HOME ) );
	}

	/**
	 * Nofollow tag for permalinks ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_permalinks_is_tag_nofollow( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_PERMALINKS_NOFOLLOW ) );
	}

	/**
	 * Noindex tag for permalinks ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_permalinks_is_tag_noindex( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_PERMALINKS_NOINDEX ) );
	}

	/**
	 * Nofollow tag for search pages ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_contents_is_tag_nofollow( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_CONTENTS_NOFOLLOW ) );
	}

	/**
	 * Noindex tag for search pages ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_contents_is_tag_noindex( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_CONTENTS_NOINDEX ) );
	}

	/**
	 * Permalinks usage
	 *
	 * @return string
	 */
	public function get_option_seo_common_permalinks_usage( $option_name ) {
		return $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_PERMALINKS_USAGE, '' );
	}

	/**
	 * Force redirect all searches to permalinks ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_is_replace_search( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_PERMALINKS_IS_REDIRECT_FROM_SEARCH, '' ) );
	}

	/**
	 * 404 permalinks ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_is_404_permalinks( $option_name ) {
		return $this->is_empty( $this->get_option_seo_common_permalinks_usage( $option_name ) );
	}

	/**
	 * Redirect permalinks to search parameters ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_is_redirect_permalinks_to_search( $option_name ) {
		return ( self::OPTION_SEO_PERMALINKS_USAGE_REDIRECT_TO_SEARCH === $this->get_option_seo_common_permalinks_usage( $option_name ) );
	}

	/**
	 * Use facet permalinks ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_is_generate_facet_permalinks( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_GENERATE_FACETS_PERMALINKS ) );
	}

	/**
	 * Use keyword permalinks ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_is_generate_keyword_permalinks( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_GENERATE_KEYWORDS_PERMALINKS ) );
	}

	/**
	 * Stealth mode activated ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_is_remove_test_mode( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_REMOVE_TEST_MODE ) );
	}

	/**
	 * Replace sitemaps query ?
	 *
	 * @return bool
	 */
	public function get_option_seo_common_is_speedup_sitemaps( $option_name ) {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_IS_SPEED_SITEMAPS, '' ) );
	}

	/**
	 * Nb sitemaps entries per page
	 *
	 * @return string
	 */
	public function get_option_seo_sitemap_nb_entries_per_page( $option_name ) {
		return $this->get_option_value( true, __FUNCTION__, $option_name, self::OPTION_SEO_SITEMAP_NB_ENTRIES_PER_PAGE, '' );
	}



	/***************************************************************************************************************
	 *
	 * DB options
	 *
	 **************************************************************************************************************/
	const OPTION_DB = 'wdm_db';
	const OPTION_DB_CURRENT_VERSION = 'current_version';

	/**
	 * Get SEO options array
	 * @return array
	 */
	public function get_option_db() {
		return self::get_option( true, self::OPTION_DB, [] );
	}

	/**
	 * Get current DB version (version of schema's custom tables already installed)
	 * @return string
	 */
	public function get_db_current_version() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_DB, self::OPTION_DB_CURRENT_VERSION, '' );
	}

	/**
	 * Set current DB version (version of schema's custom tables already installed)
	 */
	public function set_db_current_version( $version ) {

		$option_db = $this->get_option_db();

		$option_db[ self::OPTION_DB_CURRENT_VERSION ] = $version;
		update_option( self::OPTION_DB, $option_db );
	}


	/***************************************************************************************************************
	 *
	 * Import / Export options
	 *
	 **************************************************************************************************************/
	const OPTION_IMPORT_EXPORT = 'wdm_import_export';

	/**
	 * Get Import / Export options array
	 * @return array
	 */
	public function get_option_import_export() {
		return self::get_option( true, self::OPTION_IMPORT_EXPORT, [] );
	}


	/***************************************************************************************************************
	 *
	 * Indexes options
	 *
	 **************************************************************************************************************/
	const OPTION_INDEXES = 'wpsolr_solr_indexes';
	const OPTION_INDEXES_INDEXES = 'solr_indexes';
	const OPTION_INDEXES_CONFIGURATION_ID = 'index_configuration_id';
	const OPTION_INDEXES_USE_CONFIGURATION = 'use_configuration';
	const OPTION_INDEXES_USE_CONFIGURATION_DEFAULT = 'use_configuration_default';
	const OPTION_INDEXES_USE_CONFIGURATION_BUILDER = 'use_configuration_builder';
	const OPTION_INDEXES_CONFIGURATION_BUILDER = 'configuration_builder';
	const OPTION_INDEXES_CONFIGURATION_BUILDER_ID = 'id';
	const OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETERS = 'parameters';
	const OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_ID = 'id';
	const OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE = 'value';
	const OPTION_INDEXES_USE_CONFIGURATION_CODE = 'use_configuration_code';
	const OPTION_INDEXES_CONFIGURATION_CODE = 'configuration_code';
	const OPTION_INDEXES_VERSION_SUGGESTER_HAS_CONTEXT = 'index_suggester_has_context';
	const OPTION_INDEXES_ANALYSER_ID = 'index_analyser_id';

	/**
	 * Get indexes options array
	 *
	 * @param bool $is_cached
	 *
	 * @return array
	 */
	public function get_option_indexes( $is_cached = true ) {
		return self::get_option( $is_cached, self::OPTION_INDEXES, [] );
	}

	/**
	 * Does index support suggestion contexts?
	 *
	 * @param string $index_uuid
	 * @param bool $is_cached
	 *
	 * @return bool
	 */
	public function get_option_indexes_version_suggester_has_context( $index_uuid, $is_cached = true ) {
		$indexes = $this->get_option_indexes( $is_cached );

		return isset( $indexes[ self::OPTION_INDEXES_INDEXES ] ) &&
		       isset( $indexes[ self::OPTION_INDEXES_INDEXES ][ $index_uuid ] ) &&
		       isset( $indexes[ self::OPTION_INDEXES_INDEXES ][ $index_uuid ][ self::OPTION_INDEXES_VERSION_SUGGESTER_HAS_CONTEXT ] );
	}

	/***************************************************************************************************************
	 *
	 * Indexes options
	 *
	 **************************************************************************************************************/
	const OPTION_LICENSES = 'wpsolr_licenses';

	/**
	 * Get licenses options array
	 * @return array
	 */
	public function get_option_licenses( $is_cached = true ) {
		return self::get_option( $is_cached, self::OPTION_LICENSES, [] );
	}





	/***************************************************************************************************************
	 *
	 * Operations options
	 *
	 **************************************************************************************************************/
	const OPTION_OPERATIONS = 'wdm_solr_operations_data';
	const OPTION_OPERATIONS_POST_TYPES = 'post_types';

	/**
	 * Get operations options array
	 * @return array
	 */
	public function get_option_operations() {
		return self::get_option( true, self::OPTION_OPERATIONS, [] );
	}

	/**
	 * Post types selected
	 *
	 * @return array
	 */
	public function get_option_operations_all_post_types() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_OPERATIONS, self::OPTION_OPERATIONS_POST_TYPES, [] );
	}

	/**
	 * Post types selected
	 *
	 * @return array
	 */
	public function get_option_operations_index_post_types( $index_uuid ) {
		$post_types = $this->get_option_operations_all_post_types();

		return ( empty( $post_types[ $index_uuid ] ) ) ? [] : $post_types[ $index_uuid ];
	}





	/***************************************************************************************************************
	 *
	 * Plugin YITH WooCommerce Ajax Search (free)
	 *
	 **************************************************************************************************************/
	const OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE = 'wdm_solr_extension_yith_woocommerce_ajax_search_free';
	const OPTION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE_IS_REPLACE_PRODUCT_SUGGESTIONS = 'is_replace_product_suggestions';

	/**
	 * Get options array
	 * @return array
	 */
	public function get_option_yith_woocommerce_ajax_search_free() {
		return self::get_option( true, self::OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, [] );
	}

	/**
	 * Is replace suggestions ?
	 *
	 * @return boolean
	 */
	public function get_yith_woocommerce_ajax_search_free_is_replace_product_suggestions() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, self::OPTION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE_IS_REPLACE_PRODUCT_SUGGESTIONS ) );
	}




	/***************************************************************************************************************
	 *
	 * Locking options
	 *
	 **************************************************************************************************************/
	const OPTION_LOCKING = 'wdm_locking';

	/**
	 * Get locking options array
	 * @return array
	 */
	public function get_option_locking() {
		return self::get_option( false, self::OPTION_LOCKING, [] ); // No cache!
	}

	/**
	 * Get models locked for the index
	 * @return array
	 */
	public function get_option_locking_index_models( $index_uuid ) {
		wp_cache_delete( self::OPTION_LOCKING, 'options' ); // not working
		wp_cache_flush(); //necessary!

		return $this->get_option_value( false, __FUNCTION__, self::OPTION_LOCKING, $index_uuid, [] );
	}





	/***************************************************************************************************************
	 *
	 * Suggestions option
	 *
	 **************************************************************************************************************/
	const OPTION_SUGGESTIONS = 'wdm_solr_suggestions_data';
	const OPTION_SUGGESTIONS_SUGGESTIONS = 'suggestions';
	const OPTION_SUGGESTION_LABEL = 'label';
	const OPTION_SUGGESTION_JQUERY_SELECTOR = 'jquery';
	const OPTION_SUGGESTION_UUID = 'suggestion_uuid';
	const OPTION_SUGGESTION_TYPE = 'type';
	const OPTION_SUGGESTION_LAYOUT_ID = 'layout_id';
	const OPTION_SUGGESTION_LAYOUT_ID_CUSTOM_FILE = 'layout_id_custom_file';
	const OPTION_SUGGESTION_IMAGE_WIDTH_PCT = 'image_width_pct';
	const OPTION_SUGGESTION_IMAGE_SIZE = 'image_size';
	const OPTION_SUGGESTION_NB = 'nb';
	const OPTION_SUGGESTION_CUSTOM_CSS = 'css';
	const OPTION_SUGGESTION_CUSTOM_TEMPLATE_FILE = 'custom_file';
	const OPTION_SUGGESTION_IS_ACTIVE = 'is_active';
	const OPTION_SUGGESTION_IS_SHOW_TEXT = 'is_show_text';
	const OPTION_SUGGESTION_MODELS = 'models';
	const OPTION_SUGGESTION_MODEL_LABEL = 'model_label';
	const OPTION_SUGGESTION_MODEL_NB = 'model_nb';
	const OPTION_SUGGESTION_MODEL_ID = 'id';
	const OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_RATING = 'model_show_rating';
	const OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_PRICE = 'model_show_price';
	const OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_ADD_TO_CART = 'model_show_add_to_cart';
	const OPTION_SUGGESTION_ORDER_BY = 'order_by';
	const OPTION_SUGGESTION_ORDER_BY_CONTENT = 'content';
	const OPTION_SUGGESTION_ORDER_BY_GROUP_POSITION = 'position';
	const OPTION_SUGGESTION_ORDER_BY_GROUP_CONTENT_AVERAGE_RELEVANCY = 'average';
	const OPTION_SUGGESTION_ORDER_BY_GROUP_CONTENT_MAX_RELEVANCY = 'max';
	const OPTION_SUGGESTION_IS_ARCHIVE = 'is_archive';
	const OPTION_SUGGESTION_REDIRECTION_PATTERN = 'redirection_pattern';
	const OPTION_SUGGESTION_JQUERY_AUTOCOMPLETE_OPTIONS = 'jquery_autocomplete_options';

	/**
	 * Get suggestions options array
	 * @return array
	 */
	public function get_option_suggestions() {
		return self::get_option( true, self::OPTION_SUGGESTIONS, [] );
	}

	/**
	 * Get suggestions suggestions
	 * @return array
	 */
	public function get_option_suggestions_suggestions() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SUGGESTIONS, self::OPTION_SUGGESTIONS_SUGGESTIONS, [] );
	}

	/***************************************************************************************************************
	 *
	 * Plugin WP Google Map Pro
	 *
	 **************************************************************************************************************/
	const OPTION_EXTENSION_WP_GOOGLE_MAP_PRO = 'wdm_solr_extension_wp_google_map_pro';

	/**
	 * Get options array
	 * @return array
	 */
	public function get_option_extension_wp_google_map_pro() {
		return self::get_option( true, self::OPTION_EXTENSION_WP_GOOGLE_MAP_PRO, [] );
	}



	/***************************************************************************************************************
	 *
	 * Fields associated to an index
	 *
	 **************************************************************************************************************/
	const OPTION_INDEX_FILTERED_FIELDS = 'wdm_index_filtered_fields';

	/**
	 * Get all filtered fields by index
	 *
	 * @return array
	 */
	public function get_option_index_filtered_fields() {
		return self::get_option( true, self::OPTION_INDEX_FILTERED_FIELDS, [] );
	}

	/**
	 * Save all filtered fields by index
	 *
	 */
	public function set_option_index_filtered_fields( $values = [] ) {

		if ( ! empty( $values ) ) {

			update_option( self::OPTION_INDEX_FILTERED_FIELDS, $values );

		} else {

			delete_option( self::OPTION_INDEX_FILTERED_FIELDS );
		}

	}








	/***************************************************************************************************************
	 *
	 * Plugin WP Rocket
	 *
	 **************************************************************************************************************/
	const OPTION_EXTENSION_WP_ROCKET = 'wdm_solr_extension_wp_rocket_data';
	const OPTION_WP_ROCKET_IS_DEFER_JS = 'is_defer_js';

	/**
	 * Get WP Rocket options array
	 * @return array
	 */
	public function get_option_wp_rocket() {
		return self::get_option( true, self::OPTION_EXTENSION_WP_ROCKET, [] );
	}

	/**
	 * Defer js ?
	 *
	 * @return boolean
	 */
	public function get_wp_rocket_is_defer_js() {
		return ! $this->is_empty( $this->get_option_value( true, __FUNCTION__, self::OPTION_EXTENSION_WP_ROCKET, self::OPTION_WP_ROCKET_IS_DEFER_JS ) );
	}

	/***************************************************************************************************************
	 *
	 * "View" options
	 *
	 **************************************************************************************************************/
	const OPTION_VIEW = 'wdm_solr_view';
	const OPTION_VIEW_VIEWS = 'views';
	const OPTION_VIEW_LABEL = 'label';
	const OPTION_VIEW_IS_DISABLED = 'is_disabled';

	/**
	 * Get views options array
	 * @return array
	 */
	public function get_option_view() {
		return self::get_option( true, self::OPTION_VIEW, [] );
	}

	/**
	 * Get views views
	 * @return array
	 */
	public function get_option_view_views() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_VIEW, self::OPTION_VIEW_VIEWS, [] );
	}

	/**
	 * Get a view options name
	 *
	 * @param string $options_name
	 *
	 * @return string
	 */
	protected function _get_view_uuid_option_name( $options_name ) {
		return WPSOLR_Option_View::get_view_uuid_options_name( $options_name );
	}

	/**
	 * Get an index options name
	 *
	 * @param string $options_name
	 *
	 * @return string
	 */
	protected function _get_index_uuid_option_name( $options_name ) {
		return WPSOLR_Option_View::get_index_uuid_options_name( $options_name );
	}

	/**
	 * Get search view's index uuid
	 *
	 * @param string $view_uuid
	 *
	 * @return string
	 */
	public function get_view_index_uuid() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_DEFAULT_SOLR_INDEX_FOR_SEARCH, '' );
	}

	/**
	 * Get index options name
	 *
	 * @param bool $is_show_only_custom
	 *
	 * @return string
	 */
	protected function _get_index_option_name( $is_show_only_custom = false ) {
		$result     = $this->_get_index_uuid_option_name( self::OPTION_INDEX );
		$index_uuid = WPSOLR_Option_View::get_current_index_uuid();

		if ( ! $is_show_only_custom && ( WPSOLR_Option_View::DEFAULT_INDEX_UUID !== $index_uuid ) && empty( $this->get_option( true, $result, [] ) ) ) {
			// No custom index settings, use the shared index settings instead
			$result = self::OPTION_INDEX;
		}


		return $result;
	}

	/**
	 * Weaviate index
	 */
	const OPTION_WEAVIATE_UNCONVERTED_FIELD_NAMES = 'wdm_weaviate_unconverted_field_names';
	const OPTION_WEAVIATE_CONVERTED_FIELD_NAMES = 'wdm_weaviate_converted_field_names';


	/***************************************************************************************************************
	 *
	 * Recommendations option
	 *
	 **************************************************************************************************************/
	const OPTION_RECOMMENDATIONS = 'wdm_solr_recommendations_data';
	const OPTION_RECOMMENDATIONS_RECOMMENDATIONS = 'recommendations';
	const OPTION_RECOMMENDATION_LABEL = 'label';
	const OPTION_RECOMMENDATION_JQUERY_SELECTOR = 'jquery';
	const OPTION_RECOMMENDATION_UUID = 'recommendation_uuid';
	const OPTION_RECOMMENDATION_TYPE = 'type';

	/**
	 * Most engines
	 */
	const OPTION_RECOMMENDATION_TYPE_MORE_LIKE_THIS = 'more_like_this';

	/**
	 * Weaviate
	 */
	const OPTION_RECOMMENDATION_TYPE_WEAVIATE_NEAR_OBJECT = 'near_object';
	const OPTION_RECOMMENDATION_TYPE_WEAVIATE_NEAR_IMAGE = 'near_image';



	const OPTION_RECOMMENDATION_LAYOUT_ID = 'layout_id';
	const OPTION_RECOMMENDATION_LAYOUT_ID_CUSTOM_FILE = 'layout_id_custom_file';
	const OPTION_RECOMMENDATION_IMAGE_WIDTH_PCT = 'image_width_pct';
	const OPTION_RECOMMENDATION_IMAGE_SIZE = 'image_size';
	const OPTION_RECOMMENDATION_NB = 'nb';
	const OPTION_RECOMMENDATION_CUSTOM_CSS = 'css';
	const OPTION_RECOMMENDATION_CUSTOM_TEMPLATE_FILE = 'custom_file';
	const OPTION_RECOMMENDATION_IS_ACTIVE = 'is_active';
	const OPTION_RECOMMENDATION_IS_SHOW_TEXT = 'is_show_text';
	const OPTION_RECOMMENDATION_MODELS = 'models';
	const OPTION_RECOMMENDATION_MODEL_LABEL = 'model_label';
	const OPTION_RECOMMENDATION_MODEL_NB = 'model_nb';
	const OPTION_RECOMMENDATION_MODEL_ID = 'id';
	const OPTION_RECOMMENDATION_MODEL_PRODUCT_IS_SHOW_RATING = 'model_show_rating';
	const OPTION_RECOMMENDATION_MODEL_PRODUCT_IS_SHOW_PRICE = 'model_show_price';
	const OPTION_RECOMMENDATION_MODEL_PRODUCT_IS_SHOW_ADD_TO_CART = 'model_show_add_to_cart';
	const OPTION_RECOMMENDATION_MODEL_SUBTYPES = 'model_subtypes';
	const OPTION_RECOMMENDATION_ORDER_BY = 'order_by';
	const OPTION_RECOMMENDATION_ORDER_BY_CONTENT = 'content';
	const OPTION_RECOMMENDATION_ORDER_BY_GROUP_POSITION = 'position';
	const OPTION_RECOMMENDATION_ORDER_BY_GROUP_CONTENT_AVERAGE_RELEVANCY = 'average';
	const OPTION_RECOMMENDATION_ORDER_BY_GROUP_CONTENT_MAX_RELEVANCY = 'max';
	const OPTION_RECOMMENDATION_IS_ARCHIVE = 'is_archive';
	const OPTION_RECOMMENDATION_IS_NOT_AJAX = 'is_not_ajax';
	const OPTION_RECOMMENDATION_FILTER_IS_SAME_OBJECT_TYPE = 'is_same_object_type';
	const OPTION_RECOMMENDATION_FILTER_IS_NOT_SAME_OBJECT = 'is_not_same_object';

	/**
	 * Get recommendations options array
	 * @return array
	 */
	public function get_option_recommendations() {
		return self::get_option( true, self::OPTION_RECOMMENDATIONS, [] );
	}

	/**
	 * Get recommendations recommendations
	 * @return array
	 */
	public function get_option_recommendations_recommendations() {
		return $this->get_option_value( true, __FUNCTION__, self::OPTION_RECOMMENDATIONS, self::OPTION_RECOMMENDATIONS_RECOMMENDATIONS, [] );
	}

	/***************************************************************************************************************
	 *
	 * Setup Wizard
	 *
	 **************************************************************************************************************/
	const OPTION_SETUP_WIZARD_IS_SKIPPED = 'wpsolr_setup_wizard_is_skipped';



}
