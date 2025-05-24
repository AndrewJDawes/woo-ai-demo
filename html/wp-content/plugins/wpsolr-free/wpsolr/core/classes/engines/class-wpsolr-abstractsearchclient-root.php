<?php

namespace wpsolr\core\classes\engines;

use Solarium\QueryType\Select\Query\FilterQuery;
use wpsolr\core\classes\engines\elasticsearch_php\WPSOLR_SearchElasticsearchClient;
use wpsolr\core\classes\engines\opensearch_php\WPSOLR_SearchOpenSearchClient;
use wpsolr\core\classes\engines\redisearch_php\WPSOLR_Search_Redisearch_Client;
use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\engines\vespa\WPSOLR_Search_Vespa_Client;
use wpsolr\core\classes\engines\weaviate\WPSOLR_Search_Weaviate_Client;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\extensions\recommendations\WPSOLR_Option_Recommendations;
use wpsolr\core\classes\extensions\suggestions\WPSOLR_Option_Suggestions;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\metabox\WPSOLR_Metabox;
use wpsolr\core\classes\models\post\WPSOLR_Model_Meta_Type_Post;
use wpsolr\core\classes\models\WPSOLR_Model_Abstract;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\shortcode\WPSOLR_Shortcode_Recommendation;
use wpsolr\core\classes\ui\WPSOLR_Data_Facets;
use wpsolr\core\classes\ui\WPSOLR_Data_Sort;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\utilities\WPSOLR_Translate;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_AbstractSearchClient
 * @package wpsolr\core\classes\engines
 */
abstract class WPSOLR_AbstractSearchClient_Root extends WPSOLR_AbstractEngineClient {

	protected array $_log;

	const _FIELD_NAME_FLAT_HIERARCHY = 'flat_hierarchy_'; // field contains hierarchy as a string with separator (filter)
	const _FIELD_NAME_NON_FLAT_HIERARCHY = 'non_flat_hierarchy_'; // field contains hierarchy as an array (facet)

	const IS_LOG_QUERY_TIME_IMPLEMENTED = false;
	const PATTERN_NESTED_PARENT_CHILD_FIELD = '%s.%s';
	const ERROR_MSG_RANDOM_SORT_IS_NOT_SUPPORTED = 'Random sort is not supported by this search engine.';

	const QUERY_SUGGESTIONS_NOT_SUPPORTED = 'Query suggestions not supported.';

	protected $is_query_wildcard;

	protected $query_select;

	protected $search_engine_client_config;

	// Array of active extension objects
	protected $wpsolr_extensions;

	// Error message when suggester contexts schema is not configured on current index.
	const THIS_INDEX_DOES_NOT_SUPPORT_SUGGESTER_CONTEXTS = 'This index was created before WPSOLR supported suggester contexts, required for admin search and multi-domains configuration. Please create a new index, reindex all the data, and retry.';

	// Error message when suggester contexts schema is not configured on current index.
	const SOLR_DOES_NOT_SUPPORT_SUGGESTER_CONTEXTS = 'Solr does not support suggester contexts, required for admin search and multi-domains configuration.';

	// Filter query exclusion tag used by facets.
	const FILTER_QUERY_TAG_FACET_EXCLUSION = 'fct_excl_%s';

	// Search template
	const _SEARCH_PAGE_TEMPLATE = 'wpsolr-search-engine/search.php';

	// Search page slug
	const _SEARCH_PAGE_SLUG = 'search-wpsolr';

	// Do not change - Sort by most relevant
	const SORT_CODE_BY_RELEVANCY_DESC = 'sort_by_relevancy_desc';

	// Do not change - Sort by newest
	const SORT_CODE_BY_DATE_DESC = 'sort_by_date_desc';

	// Do not change - Sort by oldest
	const SORT_CODE_BY_DATE_ASC = 'sort_by_date_asc';

	// Do not change - Sort by least comments
	const SORT_CODE_BY_NUMBER_COMMENTS_ASC = 'sort_by_number_comments_asc';

	// Do not change - Sort by most comments
	const SORT_CODE_BY_NUMBER_COMMENTS_DESC = 'sort_by_number_comments_desc';

	// Do not change - Sort by title desc
	const SORT_CODE_BY_TITLE_S_DESC = 'sort_by_title_s_desc';

	// Do not change - Sort by title asc
	const SORT_CODE_BY_TITLE_S_ASC = 'sort_by_title_s_asc';

	// Do not change - Sort by post id desc
	const SORT_CODE_BY_PID_DESC = 'sort_by_pid_desc';

	// Do not change - Sort by post id asc
	const SORT_CODE_BY_PID_ASC = 'sort_by_pid_asc';

	// Do not change - Sort by author asc
	const SORT_CODE_BY_AUTHOR_ASC = 'sort_by_author_asc';

	// Do not change - Sort by author desc
	const SORT_CODE_BY_AUTHOR_DESC = 'sort_by_author_desc';

	// Do not change - Sort by author id asc
	const SORT_CODE_BY_AUTHOR_ID_ASC = 'sort_by_author_id_asc';

	// Do not change - Sort by author id desc
	const SORT_CODE_BY_AUTHOR_ID_DESC = 'sort_by_author_id_desc';

	// Do not change - Sort by post type desc
	const SORT_CODE_BY_POST_TYPE_DESC = 'sort_by_post_type_desc';

	// Do not change - Sort by post type asc
	const SORT_CODE_BY_POST_TYPE_ASC = 'sort_by_post_type_asc';

	// Do not change - Sort by last modified desc
	const SORT_CODE_BY_LAST_MODIFIED_DESC = 'sort_by_last_modified_desc';

	// Do not change - Sort by last modified asc
	const SORT_CODE_BY_LAST_MODIFIED_ASC = 'sort_by_last_modified_asc';

	// Do not change - Sort by menu_order desc
	const SORT_CODE_BY_MENU_ORDER_DESC = 'sort_by_menu_order_desc';

	// Do not change - Sort by menu_order asc
	const SORT_CODE_BY_MENU_ORDER_ASC = 'sort_by_menu_order_asc';

	// Do not change - Sort by random
	const SORT_CODE_BY_RANDOM = 'sort_by_random';


	// All default sorts
	static $SORT_CODES_DEFAULT = [
		self::SORT_CODE_BY_RELEVANCY_DESC,
		self::SORT_CODE_BY_DATE_DESC,
		self::SORT_CODE_BY_DATE_ASC,
		self::SORT_CODE_BY_NUMBER_COMMENTS_ASC,
		self::SORT_CODE_BY_NUMBER_COMMENTS_DESC,
		self::SORT_CODE_BY_TITLE_S_DESC,
		self::SORT_CODE_BY_TITLE_S_ASC,
		self::SORT_CODE_BY_PID_DESC,
		self::SORT_CODE_BY_PID_ASC,
		self::SORT_CODE_BY_AUTHOR_ASC,
		self::SORT_CODE_BY_AUTHOR_DESC,
		self::SORT_CODE_BY_AUTHOR_ID_ASC,
		self::SORT_CODE_BY_AUTHOR_ID_DESC,
		self::SORT_CODE_BY_POST_TYPE_DESC,
		self::SORT_CODE_BY_POST_TYPE_ASC,
		self::SORT_CODE_BY_LAST_MODIFIED_DESC,
		self::SORT_CODE_BY_LAST_MODIFIED_ASC,
		self::SORT_CODE_BY_MENU_ORDER_DESC,
		self::SORT_CODE_BY_MENU_ORDER_ASC,
		self::SORT_CODE_BY_RANDOM,
	];

	// Default maximum number of items returned by facet
	const DEFAULT_MAX_NB_ITEMS_BY_FACET = 10;

	// Defaut minimum count for a facet to be returned
	const DEFAULT_MIN_COUNT_BY_FACET = 1;

	// Default maximum size of highliting fragments
	const DEFAULT_HIGHLIGHTING_FRAGMENT_SIZE = 100;

	// Default highlighting prefix
	const DEFAULT_HIGHLIGHTING_PREFIX = '<b>';

	// Default highlighting postfix
	const DEFAULT_HIGHLIGHTING_POSFIX = '</b>';

	const PARAMETER_HIGHLIGHTING_FIELD_NAMES = 'field_names';
	const PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE = 'fragment_size';
	const PARAMETER_HIGHLIGHTING_PREFIX = 'prefix';
	const PARAMETER_HIGHLIGHTING_POSTFIX = 'postfix';

	const PARAMETER_FACET_FIELD_NAMES = 'field_names';
	const PARAMETER_FACET_LIMIT = 'limit';
	const PARAMETER_FACET_MIN_COUNT = 'min_count';
	const PARAMETER_FACET_SORT_ALPHABETICALLY = 'index';


	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

	static protected $default_status_to_filter_out = [
		'draft',
		'pending',
		'trash',
		'future',
		'private',
		'auto-draft',
	];

	/** @var bool */
	protected $is_archive = false;

	protected array $facets_can_not_exists;

	/**
	 * @var WPSOLR_Query
	 */
	protected WPSOLR_Query $wpsolr_query;
	protected \Exception $exception;


	protected WPSOLR_AbstractResultsClient $results;
	protected static array $cached_results;
	protected array $relation_facet_filter = [];

	/**
	 * Build the query
	 *
	 */
	abstract public function search_engine_client_build_query();

	/**
	 * Ping the index
	 *
	 * @param array $index_parameters
	 *
	 * @throws \Exception
	 */
	public function admin_ping( &$index_parameters ) {

		$is_index_exist = $this->admin_is_index_exists( false );

		if ( ! $is_index_exist ) {
			// Create the index
			$this->admin_create_index( $index_parameters );
		}

		$this->admin_index_update( $index_parameters );

		// Will throw an error if index does not exist or server cannot be reached.
		$this->admin_is_index_exists( true );
	}


	/**
	 * Delete the index and it's content
	 */
	abstract public function admin_delete_index();

	/**
	 * Create the index
	 *
	 * @param array $index_parameters
	 *
	 */
	abstract protected function admin_create_index( &$index_parameters );

	/**
	 * Add a configuration to the index if missing.
	 *
	 * @param array $index_parameters
	 *
	 */
	abstract protected function admin_index_update( &$index_parameters );

	/**
	 * Does index exists ?
	 *
	 * @param $is_throw_error
	 *
	 * @return bool
	 * @throws \Exception
	 */
	abstract protected function admin_is_index_exists( $is_throw_error = false );

	/**
	 * Create a client using a configuration
	 *
	 * @param array $config
	 *
	 * @return WPSOLR_AbstractSearchClient
	 * @throws \Exception
	 */
	static function create_from_config( $config ) {

		switch ( $config['index_engine'] ) {

			case static::ENGINE_ELASTICSEARCH:
				return new WPSOLR_SearchElasticsearchClient( $config );

			case static::ENGINE_OPENSEARCH:
				return new WPSOLR_SearchOpenSearchClient( $config );

			case static::ENGINE_WEAVIATE:
				return new WPSOLR_Search_Weaviate_Client( $config );

			case static::ENGINE_VESPA:
				return new WPSOLR_Search_Vespa_Client( $config );

			case static::ENGINE_REDISEARCH:
				return new WPSOLR_Search_Redisearch_Client( $config );

			default:
				return new WPSOLR_SearchSolariumClient( $config );
				break;

		}
	}


	/**
	 * Constructor used by factory WPSOLR_Service_Container
	 * Create using the default index configuration
	 *
	 * @return WPSOLR_SearchSolariumClient
	 */
	static function global_object( $index_indice = null ) {

		return static::create_from_index_indice( $index_indice );
	}

	// Create using an index configuration

	/**
	 * @param $index_indice
	 *
	 * @return WPSOLR_SearchElasticsearchClient|WPSOLR_SearchSolariumClient
	 */
	static function create_from_index_indice( $index_indice ) {

		// Build config from the default indexing Solr index
		WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );
		$options_indexes = new WPSOLR_Option_Indexes();
		$config          = $options_indexes->build_config( $index_indice, null, static::DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND );

		return static::create_from_config( $config );
	}

	/**
	 * WPSOLR_AbstractSearchClient constructor.
	 *
	 * @param $config
	 */
	public function __construct( $config = null ) {

		$this->init( $config );

		$this->search_engine_client = $this->create_search_engine_client( $config );
	}

	/**
	 * Get suggestions from Solr (keywords or posts).
	 *
	 * @param string $suggestion_uuid
	 * @param string $query Keywords to suggest from
	 * @param bool $is_search_admin
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_suggestions_html( $suggestion_uuid, $query, $is_search_admin ) {

		$suggestion = WPSOLR_Option_Suggestions::get_suggestion( $suggestion_uuid );

		// Extract parameters from Ajax
		$wpsolr_query = WPSOLR_Query_Parameters::CreateQuery();
		$wpsolr_query->wpsolr_set_is_admin( $is_search_admin );
		$wpsolr_query->wpsolr_set_view_uuid( WPSOLR_Option_View::get_current_view_uuid() );
		$wpsolr_query->set_wpsolr_image_size( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IMAGE_SIZE ] ?? '' );

		switch ( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_TYPE ] ) {

			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT:
			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_QUESTIONS_ANSWERS:
				$template_data = $this->get_suggestions_content_data( $wpsolr_query, $suggestion, $query );
				break;

			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED:
				$template_data = $this->get_suggestions_content_grouped_data( $wpsolr_query, $suggestion, $query );
				break;

			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS:
				$template_data = $this->get_suggestions_keywords_data( $wpsolr_query, $suggestion, $query );
				break;

			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTAINER:
				$template_data = $this->get_suggestions_container_data( $wpsolr_query, $suggestion, $query );
				break;

			default:
				$template_data = [];
				break;
		}

		/**
		 * Build the suggestions HTML from the template
		 */
		return WPSOLR_Service_Container::get_template_builder()->load_template(
			WPSOLR_Option_Suggestions::get_suggestion_layout_file( $suggestion_uuid ),
			$template_data
		);
	}


	/**
	 * Get suggestions template data from search.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param array $suggestion
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_suggestions_content_data( WPSOLR_Query $wpsolr_query, $suggestion, $query ) {

		/**
		 * Retrieve suggestions
		 */
		$wpsolr_query->set_wpsolr_query( $query );
		$wpsolr_query->wpsolr_set_suggestion( $suggestion );
		$wpsolr_query->wpsolr_set_nb_results_by_page( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] );
		$suggestions = WPSOLR_Service_Container::get_solr_client()->get_results_data( $wpsolr_query, [], false );

		/**
		 * Build the template data from results
		 */
		$template_data            = [];
		$template_data['results'] = [];
		foreach ( $suggestions['results']['items'] as $results ) {

			$template_data['results'][] = $results;
		}

		/*
		 * Build the template data from $suggestion
		 */
		$template_data['settings'] = $suggestion;


		return $template_data;
	}

	/**
	 * Get suggestions container template data from search.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param array $suggestion
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_suggestions_container_data( WPSOLR_Query $wpsolr_query, $suggestion, $query ) {

		/**
		 * Retrieve suggestions
		 */
		#$wpsolr_query->set_wpsolr_query( $query );
		#$wpsolr_query->wpsolr_set_suggestion( $suggestion );
		#$wpsolr_query->wpsolr_set_nb_results_by_page( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] );
		#$suggestions = WPSOLR_Service_Container::get_solr_client()->get_results_data( $wpsolr_query, [], false );

		/**
		 * Build the template data from results
		 */
		$template_data = [];
		#$template_data['results'] = [];

		/*
		 * Build the template data from $suggestion
		 */
		$template_data['settings'] = $suggestion;


		return $template_data;
	}

	/**
	 * Get suggestions template data from search, grouped by content type.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param array $suggestion
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_suggestions_content_grouped_data( WPSOLR_Query $wpsolr_query, $suggestion, $query ) {

		/**
		 * Retrieve suggestions
		 */
		$wpsolr_query->set_wpsolr_query( $query );
		$wpsolr_query->wpsolr_set_suggestion( $suggestion );
		$wpsolr_query->wpsolr_set_nb_results_by_page( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] );
		$suggestions = WPSOLR_Service_Container::get_solr_client()->get_results_data( $wpsolr_query, [], false );

		/**
		 * Build the template data from results
		 */
		$template_data            = [];
		$template_data['results'] = $suggestions['results']['items'];

		/*
		 * Build the template data from $suggestion
		 */
		$template_data['settings'] = $suggestion;


		return $template_data;
	}

	/**
	 * Get suggestions from the engine.
	 *
	 * @param array $suggestion
	 * @param string $query
	 * @param array $contexts
	 * @param bool $is_error
	 *
	 * @return WPSOLR_AbstractResultsClient
	 */
	public function search_engine_client_get_suggestions_keywords( $suggestion, $query, $contexts, $is_error = false ) {
		throw new \Exception( esc_html( static::QUERY_SUGGESTIONS_NOT_SUPPORTED ) );
	}

	/**
	 * Get suggestions template data from keywords
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param array $suggestion
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 */
	public function get_suggestions_keywords_data( WPSOLR_Query $wpsolr_query, $suggestion, $query ) {

		$is_search_admin       = $wpsolr_query->wpsolr_get_is_admin();
		$post_types_for_search = [];

		/**
		 * Retrieve suggestions from relevant context (post types which can be searched on front-end, and multi-domain slaves)
		 */
		$contexts = [];

		if ( true ) { //WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_post_type_admin() ) {
			// If search admin is selected, then we must apply $contexts to current suggestions. To prevent suggesting orders on front-end for instance.
			$post_types_for_search    = $this->_get_post_type_filter( $wpsolr_query );
			$contexts['context_type'] = $post_types_for_search;
		}


		$results_set = $this->search_engine_client_get_suggestions_keywords( $suggestion, $query, $contexts );
		$suggestions = $results_set->get_suggestions();

		/**
		 * Build the template data
		 */
		$template_data            = [];
		$template_data['results'] = [];

		if ( $is_search_admin && ! empty( $post_types_for_search ) ) {
			$suggestion_link_search_tmpl = ( 'wc_order' === $post_types_for_search[0] ) ?
				'/wp-admin/admin.php?s=%s&page=wc-orders' :
				'/wp-admin/edit.php?s=%s&post_type=%s';
		}
		foreach ( $suggestions as $term => $termResult ) {

			foreach ( $termResult as $suggestion_string ) {

				$template_data['results'][] = [
					'url'     => isset( $suggestion_link_search_tmpl )
						? sprintf( $suggestion_link_search_tmpl, urlencode( $suggestion_string ), $post_types_for_search[0] )
						: sprintf( WPSOLR_Option_Suggestions::get_suggestion_redirection_pattern( $suggestion ), urlencode( $suggestion_string ) ),
					'keyword' => $suggestion_string,
				];
			}
		}

		/*
		* Build the template data from $suggestion
		 * */
		$suggestion['query']       = $query;
		$template_data['settings'] = $suggestion;

		return $template_data;
	}

	/**
	 * Retrieve or create the search page
	 */
	static function get_search_page() {

		// Let other plugins (POLYLANG, ...) modify the search page slug
		$search_page_slug = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_PAGE_SLUG, WPSOLR_Service_Container::getOption()->get_search_ajax_search_page_slug() );

		// Search page is found by it's path (hard-coded).
		$search_page = get_page_by_path( $search_page_slug );

		if ( ! $search_page ) {

			$search_page = static::create_default_search_page();

		} else {

			if ( 'publish' !== $search_page->post_status ) {

				$search_page->post_status = 'publish';

				wp_update_post( $search_page );
			}
		}


		return $search_page;
	}


	/**
	 * Create a default search page
	 *
	 * @return \WP_Post The search page
	 */
	static function create_default_search_page() {

		// Let other plugins (POLYLANG, ...) modify the search page slug
		$search_page_slug = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_PAGE_SLUG, WPSOLR_Service_Container::getOption()->get_search_ajax_search_page_slug() );

		$_search_page = [
			'post_type'      => 'page',
			'post_title'     => 'Search Results',
			'post_content'   => '[solr_search_shortcode]',
			'post_status'    => 'draft', // prevent indexing the post. Will be published later in code.
			'post_author'    => 1,
			'comment_status' => 'closed',
			'post_name'      => $search_page_slug,
		];

		// Let other plugins (POLYLANG, ...) modify the search page
		$_search_page = apply_filters( WPSOLR_Events::WPSOLR_FILTER_BEFORE_CREATE_SEARCH_PAGE, $_search_page );

		$search_page_id = wp_insert_post( $_search_page );

		update_post_meta( $search_page_id, 'bwps_enable_ssl', '1' );
		update_post_meta( $search_page_id, WPSOLR_Metabox::METABOX_FIELD_IS_DO_NOT_INDEX, WPSOLR_Metabox::METABOX_CHECKBOX_YES ); // Do not index wpsolr search page

		// Now that the post is created, and its 'do not index' field is set, we can publish it without fear of indexing it.
		wp_publish_post( $search_page_id );

		return get_post( $search_page_id );
	}

	/**
	 * Get all sort by options available
	 *
	 * @param string $sort_code_to_retrieve
	 *
	 * @return array
	 */
	public
	static function get_sort_options() {

		$results = [

			[
				'code'  => static::SORT_CODE_BY_RELEVANCY_DESC,
				'label' => 'Most relevant',
			],
			[
				'code'  => static::SORT_CODE_BY_DATE_DESC,
				'label' => 'Newest',
			],
			[
				'code'  => static::SORT_CODE_BY_DATE_ASC,
				'label' => 'Oldest',
			],
			[
				'code'  => static::SORT_CODE_BY_NUMBER_COMMENTS_DESC,
				'label' => 'More comments',
			],
			[
				'code'  => static::SORT_CODE_BY_NUMBER_COMMENTS_ASC,
				'label' => 'Less comments',
			],


			[
				'code'  => static::SORT_CODE_BY_PID_ASC,
				'label' => 'Post ID ascending',
			],
			[
				'code'  => static::SORT_CODE_BY_PID_DESC,
				'label' => 'Post ID descending',
			],
			[
				'code'  => static::SORT_CODE_BY_MENU_ORDER_ASC,
				'label' => 'Menu order ascending',
			],
			[
				'code'  => static::SORT_CODE_BY_MENU_ORDER_DESC,
				'label' => 'Menu order descending',
			],
			[
				'code'  => static::SORT_CODE_BY_RANDOM,
				'label' => 'Random sort',
			],
			[
				'code'  => static::SORT_CODE_BY_LAST_MODIFIED_ASC,
				'label' => 'Last modified ascending',
			],
			[
				'code'  => static::SORT_CODE_BY_LAST_MODIFIED_DESC,
				'label' => 'Last modified descending',
			],
			[
				'code'  => static::SORT_CODE_BY_POST_TYPE_ASC,
				'label' => 'Post type ascending',
			],
			[
				'code'  => static::SORT_CODE_BY_POST_TYPE_DESC,
				'label' => 'Post type descending',
			],
			[
				'code'  => static::SORT_CODE_BY_AUTHOR_ASC,
				'label' => 'Author ascending',
			],
			[
				'code'  => static::SORT_CODE_BY_AUTHOR_DESC,
				'label' => 'Author descending',
			],
			[
				'code'  => static::SORT_CODE_BY_AUTHOR_ID_ASC,
				'label' => 'Author ID ascending',
			],
			[
				'code'  => static::SORT_CODE_BY_AUTHOR_ID_DESC,
				'label' => 'Author ID descending',
			],
			[
				'code'  => static::SORT_CODE_BY_TITLE_S_ASC,
				'label' => 'Title ascending',
			],
			[
				'code'  => static::SORT_CODE_BY_TITLE_S_DESC,
				'label' => 'Title descending',
			],
		];

		return $results;
	}

	/**
	 * Get all sort by options available
	 *
	 * @param string $sort_code_to_retrieve
	 * @param array $sort_options
	 *
	 * @return array
	 */
	public static function get_sort_option_from_code( $sort_code_to_retrieve, $sort_options = null ) {

		if ( null === $sort_options ) {
			$sort_options = static::get_sort_options();
		}

		if ( null !== $sort_code_to_retrieve ) {
			foreach ( $sort_options as $sort ) {

				if ( $sort['code'] === $sort_code_to_retrieve ) {
					return $sort;
				}
			}
		}


		return [];
	}


	/**
	 * Create a query.
	 *
	 * @return object
	 */
	abstract public function search_engine_client_create_query_select();

	/**
	 * Set query's default operator.
	 *
	 * @param string $operator
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_default_operator( $operator = 'AND' );

	/**
	 * Set query's start.
	 *
	 * @param int $start
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_start( $start );


	/**
	 * Set query's page.
	 *
	 * @param int $page
	 *
	 * @return
	 */
	public function search_engine_client_set_paged( $page ) {
		// do nothing in general.
	}

	/**
	 * Set query's certainty.
	 *
	 * @param float $certainty
	 *
	 * @return
	 */
	public function search_engine_client_set_distance( $certainty ) {
		// do nothing in general.
	}

	/**
	 * Set query's alpha.
	 *
	 * @param float $alpha
	 *
	 * @return
	 */
	public function search_engine_client_set_alpha( $alpha ) {
		// do nothing in general.
	}

	/**
	 * Set query's autocut.
	 *
	 * @param int $autocut
	 *
	 * @return
	 */
	public function search_engine_client_set_autocut( $autocut ) {
		// do nothing in general.
	}

	/**
	 * Set query's filter type ($filter).
	 *
	 * @param string $certainty
	 *
	 * @return
	 */
	public function search_engine_client_set_filter( $filter ) {
		// do nothing in general.
	}

	/**
	 * Set query's rows.
	 *
	 * @param int $rows
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_rows( $rows );

	/**
	 * Remove limit on all results displayed?.
	 *
	 * @param bool $is_show_all_results
	 */
	public function search_engine_client_set_is_show_all_results( $is_show_all_results = false ) {
		// To be defined
	}

	/**
	 * Set query's cursor mark.
	 *
	 * @param string $cursor_mark
	 *
	 * @return
	 */
	public function search_engine_client_set_cursor_mark( $cursor_mark ) {
		// To be defined
		//throw new \Exception( 'search_engine_client_set_cursor_mark( $cursor_mark ) not implemented.' );
	}

	/**
	 * Convert a $wpsolr_query in a query select
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return Object The query select
	 */
	public function set_query_select( WPSOLR_Query $wpsolr_query ) {

		// Get a chance to update the WPSOLR_Query
		/* @var WPSOLR_Query $wpsolr_query */
		$wpsolr_query = apply_filters( WPSOLR_Events::WPSOLR_FILTER_UPDATE_WPSOLR_QUERY, $wpsolr_query );
		$this->set_wpsolr_query( $wpsolr_query );

		/**
		 * Set recommendation
		 */
		if ( $wpsolr_query->wpsolr_get_is_recommendation() ) {
			$this->set_recommendation( $wpsolr_query->wpsolr_get_recommendation() );
		}

		// Create the query
		$this->query_select = $this->search_engine_client_create_query_select();


		// Set the query keywords.
		$this->set_keywords( $wpsolr_query->get_wpsolr_query() );

		// Set default operator
		$this->search_engine_client_set_default_operator( 'AND' );

		// Limit nb of results
		$this->search_engine_client_set_start( $wpsolr_query->wpsolr_get_start() );
		$this->search_engine_client_set_rows( $wpsolr_query->get_nb_results_by_page() );
		$this->search_engine_client_set_paged( $wpsolr_query->get_wpsolr_paged() );
		$this->search_engine_client_set_is_show_all_results( WPSOLR_Service_Container::getOption()->get_search_is_show_all_results() );

		/// Set search type
		$this->search_engine_client_set_distance( WPSOLR_Service_Container::getOption()->get_search_certainty() );
		$this->search_engine_client_set_alpha( WPSOLR_Service_Container::getOption()->get_search_alpha() );
		$this->search_engine_client_set_autocut( WPSOLR_Service_Container::getOption()->get_search_autocut() );
		$this->search_engine_client_set_filter( WPSOLR_Service_Container::getOption()->get_search_filter() );

		// Cursor mark
		$this->search_engine_client_set_cursor_mark( $wpsolr_query->wpsolr_get_cursor_mark() );

		/**
		 * Add sort field(s)
		 */
		$this->add_sort_field( $wpsolr_query );

		/**
		 * Add facet fields
		 */
		if ( ! $wpsolr_query->wpsolr_get_is_suggestion()
		     && ! $wpsolr_query->wpsolr_get_is_recommendation()
		) {
			// No facets for suggestions

			$this->add_facet_fields(
				[
					static::PARAMETER_FACET_FIELD_NAMES => WPSOLR_Service_Container::getOption()->get_facets_to_display(),
					static::PARAMETER_FACET_LIMIT       => WPSOLR_Service_Container::getOption()->get_search_max_nb_items_by_facet(),
					static::PARAMETER_FACET_MIN_COUNT   => static::DEFAULT_MIN_COUNT_BY_FACET,
					'wpsolr_query'                      => $wpsolr_query,
				]
			);
		}

		/**
		 * Add filter on ids excluded from search
		 */
		if ( ! $wpsolr_query->wpsolr_get_is_admin() ) {
			$this->add_ids_excluded_from_search_filter_query_fields( $wpsolr_query );
		}

		/**
		 * Add default filter query parameters
		 */
		$this->add_default_filter_query_fields( $wpsolr_query );


		/**
		 * Add archives filters to query fields
		 */
		$this->add_archive_filters( $wpsolr_query );

		/**
		 * Add post type filter
		 */
		$this->add_post_type_filter( $wpsolr_query );

		/**
		 * Add filter query fields
		 */
		$this->add_filter_query_fields( $wpsolr_query );

		/**
		 * Add highlighting fields
		 */
		$this->add_highlighting_fields(
			[
				static::PARAMETER_HIGHLIGHTING_FIELD_NAMES   => apply_filters( WPSOLR_Events::WPSOLR_FILTER_HIGHLIGHTING_FIELDS, [
					WpSolrSchema::_FIELD_NAME_TITLE,
					WpSolrSchema::_FIELD_NAME_CONTENT,
					WpSolrSchema::_FIELD_NAME_COMMENTS,
				] ),
				static::PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE => WPSOLR_Service_Container::getOption()->get_search_max_length_highlighting(),
				static::PARAMETER_HIGHLIGHTING_PREFIX        => apply_filters( WPSOLR_Events::WPSOLR_FILTER_HIGHLIGHTING_PREFIX, static::DEFAULT_HIGHLIGHTING_PREFIX ),
				static::PARAMETER_HIGHLIGHTING_POSTFIX       => apply_filters( WPSOLR_Events::WPSOLR_FILTER_HIGHLIGHTING_POSFIX, static::DEFAULT_HIGHLIGHTING_POSFIX ),
			]
		);

		/**
		 * Add fields
		 */
		$this->add_fields( $wpsolr_query );

		/**
		 * Add top hits sub-aggregation on field type
		 */
		if ( $wpsolr_query->wpsolr_get_is_suggestion_type_content_grouped() ) {
			// Add the suggestion aggregations

			$suggestion = $wpsolr_query->wpsolr_get_suggestion();

			if ( $wpsolr_query->wpsolr_get_is_suggestion_type_content_grouped_sorted_by_position() ) {

				$this->search_engine_client_add_facet_top_hits_sorted( WpSolrSchema::_FIELD_NAME_TYPE, $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] );

			} else {
				$this->search_engine_client_add_facet_top_hits( WpSolrSchema::_FIELD_NAME_TYPE, $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] );
			}

		}

		/**
		 * Action to change the solarium query
		 */
		do_action( WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY,
			[
				WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_WPSOLR_QUERY    => $wpsolr_query,
				WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_QUERY  => $this->query_select,
				WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_TERMS    => $wpsolr_query->get_wpsolr_query(),
				WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_USER     => wp_get_current_user(),
				WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_CLIENT => $this,
			]
		);


		// Done
		return $this->query_select;
	}

	/**
	 * Execute a WPSOLR query.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return WPSOLR_AbstractResultsClient
	 * @throws \Exception
	 */
	public function execute_wpsolr_query( WPSOLR_Query $wpsolr_query, $is_use_cache = true, array &$data = [] ) {

		$wpsolr_query_cache_key = $wpsolr_query->get_wpsolr_query_cache_key();
		if ( $is_use_cache ) {
			if ( isset( static::$cached_results[ $wpsolr_query_cache_key ] ) ) {
				// Return results already in cache
				return static::$cached_results[ $wpsolr_query_cache_key ];

			} else {
				// No results in cache (due to previous errors?)
				throw $this->exception ?? new \Exception( 'Cache is empty.' );
			}
		}


		WPSOLR_Option_View::set_current_view_uuid( $wpsolr_query->wpsolr_get_view_uuid() );
		WPSOLR_Option_View::set_current_index_uuid( $this->index_indice );

		do_action( WPSOLR_Events::WPSOLR_ACTION_PRE_EXECUTE_QUERY, $wpsolr_query );

		// Perform the query, return the result set
		$max_trials = 2;
		for ( $i = 1; $i <= $max_trials; $i ++ ) {
			try {

				// (re) Create the query from the wpsolr query
				$this->set_query_select( $wpsolr_query );

				// Perform the query, return the result set
				$results = $this->execute_query();

				/**
				 * No results: try a new query if "Did you mean" is activated
				 */
				if ( empty( $results->get_nb_results() ) ) {
					$results = $this->get_results_did_you_mean_localized( $results, $wpsolr_query );
				}

				// Done
				break;

			} catch ( \Exception $e ) {

				$this->exception = $e;

				if ( $i < $max_trials ) {
					// Fix the issue, eventually
					$this->search_engine_client_execute_fix_error( $e, $this->search_engine_client, $this->query_select );

				} else {
					// Could not fix it.

					$this->_log_query_as_string();

					throw $e;
				}

			}
		}


		if ( empty( static::$cached_results ) ) {
			static::$cached_results = [];
		}
		static::$cached_results[ $wpsolr_query_cache_key ] = $results;

		return $results;
	}

	/**
	 * Execute a query.
	 * Used internally, or when fine tuned select query is better than using a WPSOLR query.
	 *
	 * @return WPSOLR_AbstractResultsClient
	 * @throws \Exception
	 */
	public function execute_query() {

		$start_time = $this->_start_log_timer();

		try {
			// Perform the query, return the result set
			$this->results = $this->search_engine_client_execute( $this->search_engine_client, $this->query_select );

		} catch ( \Exception $e ) {
			$this->_end_log_timer( $this->results ?? null, $start_time, $e );

			throw $e;
		}

		$this->_end_log_timer( $this->results, $start_time );

		return $this->results;
	}


	/**
	 * Get suggestions for did you mean.
	 *
	 * @param string $keywords
	 *
	 * @return string Did you mean keyword
	 */
	abstract public function search_engine_client_get_did_you_mean_suggestions( $keywords );

	/**
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param array $widget_options
	 * @param bool $is_use_cache
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_results_data( WPSOLR_Query $wpsolr_query, $widget_options = [], $is_use_cache = true ) {

		$data                 = [];
		$localization_options = OptionLocalization::get_options();

		/**
		 * Execute query
		 */
		$results = $this->execute_wpsolr_query( $wpsolr_query, $is_use_cache, $data );

		$nb_results = $results->get_nb_results();

		/**
		 * Results
		 */
		$page_size              = $wpsolr_query->get_nb_results_by_page();
		$did_you_mean_localized = empty( $wpsolr_query->wpsolr_get_did_you_mean() ) ? '' :
			sprintf( OptionLocalization::get_term( $localization_options, 'results_header_did_you_mean' ), $wpsolr_query->wpsolr_get_did_you_mean() );

		$data['results'] = [
			'items'                  => $this->get_results_results( $wpsolr_query, $results, $localization_options ),
			'nb_results'             => $nb_results,
			'info_localized'         => $this->get_results_info_localized( $wpsolr_query, $nb_results, $localization_options ),
			'did_you_mean_localized' => $did_you_mean_localized,
			'loading_image'          => WPSOLR_PLUGIN_DIR_IMAGE_URL . 'gif-load.gif',
			'pages'                  => [
				'current_page'        => $wpsolr_query->get_wpsolr_paged(),
				'nb_results_per_page' => $page_size,
				'nb_pages_in_results' => ceil( $nb_results / $page_size ),
			],
			'no_results_localized'   => sprintf( OptionLocalization::get_term( $localization_options, 'results_header_no_results_found' ), $wpsolr_query->get_wpsolr_query( '', true ) ),
			'cursor_mark'            => $results->get_cursor_mark(),
		];

		/**
		 * Facets
		 */
		if ( ! $wpsolr_query->wpsolr_get_is_suggestion()
		     && ! $wpsolr_query->wpsolr_get_is_recommendation()
		) {
			// No facets for suggestions

			$data['facets'] = $this->get_results_facets( $wpsolr_query, $results, $localization_options, $widget_options );
		}

		/**
		 * Sort
		 */
		$data['sort'] = WPSOLR_Data_Sort::get_data(
			WPSOLR_Service_Container::getOption()->get_sortby_items_as_array(),
			WPSOLR_Service_Container::getOption()->get_sortby_items_labels(),
			WPSOLR_Service_Container::get_query()->get_wpsolr_sort(),
			$localization_options
		);


		// Done!
		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_RESULTS, $data );
	}

	/**
	 * Set minimum count of facet items to retrieve a facet.
	 *
	 * @param $facet_name
	 * @param $min_count
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_facets_min_count( $facet_name, $min_count );

	/**
	 * Create a facet field.
	 *
	 * @param string $facet_name
	 * @param string $field_name
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_facet_field( $facet_name, $field_name );

	/**
	 * Create a facet range regular.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $range_gap
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_facet_range_regular( $facet_name, $field_name, $range_start, $range_end, $range_gap );

	/**
	 * Create top hits aggregation sorted by relevancy
	 *
	 * @param string $facet_name
	 * @param int $size
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_facet_top_hits( $facet_name, $size );

	/**
	 * Create top hits aggregation sorted
	 *
	 * @param string $facet_name
	 * @param int $size
	 *
	 * @return
	 */
	public function search_engine_client_add_facet_top_hits_sorted( $facet_name, $size ) {
		// Define in children if necessary
		$this->search_engine_client_add_facet_top_hits( $facet_name, $size );
	}

	/**
	 * Create a facet stats.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_facet_stats( $facet_name, $exclude );


	/**
	 * Set facets limit.
	 *
	 * @param $facet_name
	 * @param int $limit
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_facets_limit( $facet_name, $limit );

	/**
	 * Sort a facet field alphabetically.
	 *
	 * @param $facet_name
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_facet_sort_alphabetical( $facet_name );

	/**
	 * Set facet field excludes.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_facet_excludes( $facet_name, $exclude );

	/**
	 * Add facet fields to the query
	 *
	 * @param $facets_parameters
	 */
	public function add_facet_fields(
		$facets_parameters
	) {

		// Field names
		$field_names = isset( $facets_parameters[ static::PARAMETER_FACET_FIELD_NAMES ] )
			? $facets_parameters[ static::PARAMETER_FACET_FIELD_NAMES ]
			: [];

		// Limit
		$limit = isset( $facets_parameters[ static::PARAMETER_FACET_LIMIT ] )
			? $facets_parameters[ static::PARAMETER_FACET_LIMIT ]
			: static::DEFAULT_MAX_NB_ITEMS_BY_FACET;

		// Min count
		$min_count = isset( $facets_parameters[ static::PARAMETER_FACET_MIN_COUNT ] )
			? $facets_parameters[ static::PARAMETER_FACET_MIN_COUNT ]
			: static::DEFAULT_MIN_COUNT_BY_FACET;


		if ( count( $field_names ) ) {

			foreach ( $field_names as $facet_with_str_extension ) {

				$facet = $this->_convert_field_name( $facet_with_str_extension );

				$fact = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $facet );

				// Only display facets that contain data
				$this->search_engine_client_set_facets_min_count( $fact, $min_count );

				$fact = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_NAME_SUBSTITUTE, $fact, $facets_parameters['wpsolr_query'], $this, 10, 3 );
				switch ( $this->get_facet_type( $facet_with_str_extension ) ) {

					case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:
						$this->search_engine_client_add_facet_range_regular( $fact, $fact,
							WPSOLR_Service_Container::getOption()->get_facets_range_regular_start( $facet_with_str_extension ),
							WPSOLR_Service_Container::getOption()->get_facets_range_regular_end( $facet_with_str_extension ),
							WPSOLR_Service_Container::getOption()->get_facets_range_regular_gap( $facet_with_str_extension )
						);
						break;

					case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX:
						$this->search_engine_client_add_facet_stats( $fact, $facet );

						break;

					default:
						// Add the facet
						$this->search_engine_client_add_facet_field( $fact, $fact );

						if ( ! empty( $limit ) ) {

							$this->search_engine_client_set_facets_limit( $fact, $limit );
						}

						if ( $this->is_facet_sorted_alphabetically( $facet_with_str_extension ) ) {

							$this->search_engine_client_set_facet_sort_alphabetical( $fact );
						}

						break;
				}


				if ( $this->is_facet_exclusion( $facet_with_str_extension ) || ( WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX === $this->get_facet_type( $facet_with_str_extension ) ) ) {
					// Exclude the tag corresponding to this facet. The tag was set on filter query.
					$exclude = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $facet ); // same as the query
					$this->search_engine_client_set_facet_excludes( $fact, $exclude );
				}

			}
		}

	}

	/**
	 * Set highlighting.
	 *
	 * @param string[] $field_names
	 * @param string $prefix
	 * @param string $postfix
	 * @param int $fragment_size
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_highlighting( $field_names, $prefix, $postfix, $fragment_size );

	/**
	 * Add highlighting fields to the query
	 *
	 * @param array $highlighting_parameters
	 */
	public
	function add_highlighting_fields(
		$highlighting_parameters
	) {

		if ( $this->is_query_wildcard ) {
			// Wildcard queries do not need highlighting.
			return;
		}

		// Field names
		$field_names = $highlighting_parameters[ static::PARAMETER_HIGHLIGHTING_FIELD_NAMES ] ?? [];

		// Fragment size
		$fragment_size = $highlighting_parameters[ static::PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE ] ?? static::DEFAULT_HIGHLIGHTING_FRAGMENT_SIZE;

		// Prefix
		$prefix = $highlighting_parameters[ static::PARAMETER_HIGHLIGHTING_PREFIX ] ?? static::DEFAULT_HIGHLIGHTING_PREFIX;

		// Postfix
		$postfix = $highlighting_parameters[ static::PARAMETER_HIGHLIGHTING_POSTFIX ] ?? static::DEFAULT_HIGHLIGHTING_POSFIX;

		if ( empty( $field_names ) ||
		     empty( $fragment_size ) ||
		     empty( $prefix ) ||
		     empty( $postfix ) ) {
			// No highlighting
			return;
		}

		$this->search_engine_client_set_highlighting( $field_names, $prefix, $postfix, $fragment_size );
	}


	/**
	 * Add default query fields filters.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 */
	private function add_default_filter_query_fields( WPSOLR_Query $wpsolr_query ) {

		$request_uri = WPSOLR_Sanitize::sanitize_request_uri();
		if ( ( false !== strpos( $request_uri, '/wp-admin/edit.php' ) ) || // admin post type listing menu
		     ( false !== strpos( $request_uri, '/wp-admin/upload.php' ) ) || // media library listing menu
		     ( isset( $_REQUEST['action'] ) && ( 'query-attachments' === WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ 'action' ] ) ) ) // media attachment popup listing
		) {
			// Do not add default filter in admin post type search or admin media search
			return;
		}

		$filter_query_fields = $wpsolr_query->get_filter_query_fields();

		if ( empty( $filter_query_fields ) ) {
			foreach ( WPSOLR_Service_Container::getOption()->get_facets_items_is_default() as $default_facet_name => $default_facet_contents ) {

				if ( ! empty( $default_facet_contents ) ) {
					// The default facet is not yet in the parameters: add it.
					foreach ( array_keys( $default_facet_contents ) as $default_facet_content ) {
						array_push( $filter_query_fields, sprintf( '%s:%s', $default_facet_name, $default_facet_content ) );
					}
				}
			}
			if ( ! empty( $filter_query_fields ) ) {
				$wpsolr_query->set_filter_query_fields( $filter_query_fields );
			}
		}
	}

	/**
	 * Add a filter on excluded ids from search
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 */
	protected function add_ids_excluded_from_search_filter_query_fields( WPSOLR_Query $wpsolr_query ) {

		$excluded_ids_from_searching = WPSOLR_Service_Container::getOption()->get_option_index_post_excludes_ids_from_searching();

		if ( ! empty( $excluded_ids_from_searching ) ) {

			$this->search_engine_client_add_filter_not_in_terms( 'all ids excluded from searching',
				WpSolrSchema::_FIELD_NAME_PID,
				$excluded_ids_from_searching
			);

		}
	}

	/**
	 * Add a simple filter term.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param $facet_is_or
	 * @param string $field_value
	 *
	 * @param string $filter_tag
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_filter_term( $filter_name, $field_name, $facet_is_or, $field_value, $filter_tag = '' );

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param bool $facet_is_or
	 * @param string $range_start
	 *
	 * @param string $range_end
	 * @param bool $is_date
	 * @param string $filter_tag
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_filter_range_upper_strict( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' );

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param $facet_is_or
	 * @param string $range_start
	 *
	 * @param string $range_end
	 * @param $is_date
	 * @param string $filter_tag
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_filter_range_upper_included( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' );


	/**
	 * Add a negative filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 */
	abstract public function search_engine_client_add_filter_not_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' );

	/**
	 * Add a negative filter on terms for sites not $site_id.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $site_id
	 */
	abstract public function search_engine_client_add_filter_not_in_terms_of_other_sites( $filter_name, $field_name, $field_values, $site_id );

	/**
	 * Add a 'OR' filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 */
	abstract public function search_engine_client_add_filter_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' );

	/**
	 * Create a 'OR' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_in_terms( $field_name, $field_values );


	/**
	 * Create a wildcard filter.
	 *
	 * @param string $field_name *test*
	 * @param string $field_value
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_wildcard( $field_name, $field_value );

	/**
	 * Create a not wildcard filter.
	 *
	 * @param string $field_name *test*
	 * @param string $field_value
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_wildcard_not( $field_name, $field_value );

	/**
	 * Add a 'AND' filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 */
	abstract public function search_engine_client_add_filter_in_all_terms( $filter_name, $field_name, $field_values, $filter_tag = '' );

	/**
	 * Create a 'AND' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_in_all_terms( $field_name, $field_values );

	/**
	 * Create a 'OR' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_not_in_terms( $field_name, $field_values );

	/**
	 * Create a '<' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_lt( $field_name, $field_values );

	/**
	 * Create a '<=' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_lte( $field_name, $field_values );

	/**
	 * Create a '>' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_gt( $field_name, $field_values );


	/**
	 * Create a '>=' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_gte( $field_name, $field_values );


	/**
	 * Create a between filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_between( $field_name, $field_values );

	/**
	 * Create a bot between filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_not_between( $field_name, $field_values );

	/**
	 * Create a 'only numbers' filter.
	 *
	 * @param string $field_name
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_only_numbers( $field_name );

	/**
	 * Create a 'empty or absent' filter.
	 *
	 * @param string $field_name
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_no_values( $field_name );

	/**
	 * Create a 'OR' between 2 terms: first with 'not'
	 *
	 * @param string[] $field_names
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	public function search_engine_client_create_or_betwwen_not_and_in_terms( $field_names, $field_values ) {
		return $this->search_engine_client_create_or(
			[
				$this->search_engine_client_create_filter_not_in_terms(
					$field_names[0],
					$field_values[0]
				),
				$this->search_engine_client_create_filter_in_terms(
					$field_names[1],
					$field_values[1]
				)
			] );
	}

	abstract public function search_engine_client_create_or( $queries );

	/**
	 * Create a nested query from filters.
	 *
	 * @param string $path
	 * @param FilterQuery|array $query
	 *
	 * @return FilterQuery|array
	 * @throws \Exception
	 */
	public function search_engine_client_create_nested_query( $path, $query ) {
		// Define in children. Some cannot manage nested queries
		throw new \Exception( 'Nested queries are not supported by this search engine.' );
	}

	/**
	 * Create a nested filter from filters.
	 *
	 * @param string $path
	 * @param FilterQuery|array $query
	 *
	 * @return FilterQuery|array
	 * @throws \Exception
	 */
	public function search_engine_client_create_nested_filter( $path, $query ) {
		// Define in children. Some cannot manage nested filters
		throw new \Exception( 'Nested filters are not supported by this search engine.' );
	}

	/**
	 * Add a query
	 *
	 * @param mixed Query
	 *
	 * @throws \Exception
	 */
	public function search_engine_client_add_query( $query ) {
		// Define in children. Some cannot manage adding queries
		throw new \Exception( 'Adding query is not supported by this search engine.' );
	}

	/**
	 * Add a filter
	 *
	 * @param string $filter_name
	 * @param FilterQuery|array $filter
	 */
	abstract public function search_engine_client_add_filter( $filter_name, $filter );


	/**
	 * Create a 'AND' from filters.
	 *
	 * @param FilterQuery|array $queries
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_and( $queries );

	/**
	 * Create a 'NOT' from filter.
	 *
	 * @param FilterQuery|array $query
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_not( $query );

	/**
	 * Add a filter on: empty or in terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 *
	 */
	abstract public function search_engine_client_add_filter_empty_or_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' );

	/**
	 * Filter fields with values
	 *
	 * @param $filter_name
	 * @param $field_name
	 */
	abstract public function search_engine_client_add_filter_exists( $filter_name, $field_name );

	/**
	 * Filter fields with values
	 *
	 * @param $field_name
	 *
	 * @return FilterQuery|array
	 */
	abstract public function search_engine_client_create_filter_exists( $field_name );

	/**
	 * Add decay functions to the search query
	 *
	 * @param array $decays
	 *
	 */
	abstract public function search_engine_client_add_decay_functions( array $decays );

	/**
	 * Add filter query fields to the query
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 */
	private
	function add_filter_query_fields(
		WPSOLR_Query $wpsolr_query
	) {

		if ( ! is_admin() ) {
			// Make sure unwanted statuses are not returned by any query.
			$posts_to_filter_out = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_STATUSES_TO_FILTER_OUT, static::$default_status_to_filter_out );
			if ( ! empty( $posts_to_filter_out ) ) {
				$this->search_engine_client_add_filter_not_in_terms( 'bad_statuses',
					WpSolrSchema::_FIELD_NAME_STATUS_S,
					$posts_to_filter_out,
					'' );
			}
		}

		$filter_query_fields = $wpsolr_query->get_filter_query_fields_group_by_name();



		foreach ( $filter_query_fields as $filter_query_field_name_original_with_str => $filter_query_field_values ) {

			if ( ! empty( $filter_query_field_values ) ) {

				$filter_query_field_name_original_with_str = strtolower( $filter_query_field_name_original_with_str );

				// Escape special characters
				$filter_query_field_values = array_map( [
					$this,
					'search_engine_client_escape_term'
				], $filter_query_field_values );


				if ( ! empty( $filter_query_field_name_original_with_str ) ) {

					$filter_query_field_name          = $this->_convert_field_name( $filter_query_field_name_original_with_str );
					$filter_query_field_name_with_str = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $filter_query_field_name );
					$is_flat_hierarchy                = ( $filter_query_field_name_with_str !== $filter_query_field_name );

					$fac_fd = "$filter_query_field_name_with_str";


					/**
					 * Build the filter tag
					 */
					$filter_key = sprintf( '%s:%s', $fac_fd, implode( ',', $filter_query_field_values ) );
					$filter_tag = '';
					if ( $this->is_facet_exclusion( $filter_query_field_name_original_with_str ) ||
					     ( WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX == $this->get_facet_type( $filter_query_field_name_original_with_str ) ) ) {
						// Add the exclusion tab for the facets excluded.
						$filter_tag = sprintf( static::FILTER_QUERY_TAG_FACET_EXCLUSION, $filter_query_field_name_with_str );
					}

					$facet_is_or = $this->get_facet_is_or( $filter_query_field_name_original_with_str );

					switch ( $this->get_facet_type( $filter_query_field_name_original_with_str ) ) {
						case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:
							foreach ( $filter_query_field_values ?? [] as $filter_query_field_value ) {
								$range = explode( '-', $filter_query_field_value, 2 );
								$this->search_engine_client_add_filter_range_upper_strict( $filter_key, $fac_fd,
									$facet_is_or, $range[0], $range[1], false, $filter_tag );
							}
							break;

						case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX:
							$range = explode( '-', $filter_query_field_values[0], 2 );

							$is_field_type_date = ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_DATE ===
							                        WpSolrSchema::get_custom_field_dynamic_type(
								                        WpSolrSchema::replace_field_name_extension_with(
									                        $fac_fd, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING )
							                        ) );

							$this->search_engine_client_add_filter_range_upper_included( $filter_key, $fac_fd,
								$facet_is_or, $range[0], $range[1], $is_field_type_date, $filter_tag );
							break;

						default:
							if ( $this->_is_facet_can_not_exists( $filter_query_field_name_original_with_str ) ) {
								// Field can be not existing
								$this->search_engine_client_add_filter_empty_or_in_terms( $filter_key, $fac_fd,
									$filter_query_field_values, $filter_tag );
							} else {

								$fac_fd_array = [ $fac_fd ];

								/**
								 * Add filter on relation too
								 */
								$parent_field_name_str = ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $filter_query_field_name_original_with_str ) ? WpSolrSchema::_FIELD_NAME_CATEGORY_STR : $filter_query_field_name_original_with_str;
								$relation_children     = WPSOLR_Service_Container::getOption()->get_option_index_custom_field_relation_children( $parent_field_name_str );
								if ( ! empty( $relation_children ) ) {
									/**
									 * Add parent to filters: "post_taxo_1_str" ===> "post_taxo_1_nobj.post_taxo_1_str"
									 */
									$parent_field_name_obj = WpSolrSchema::replace_field_name_extension_with( $filter_query_field_name_original_with_str, WpSolrSchema::_SOLR_DYNAMIC_TYPE_EMBEDDED_OBJECT );
									$relation_parent_name  = sprintf( static::PATTERN_NESTED_PARENT_CHILD_FIELD, $parent_field_name_obj, $parent_field_name_str );
									$fac_fd_array[]        = $relation_parent_name;

									/**
									 * Link child relation facet to its parent relation filter
									 * ["post_taxo_1_nobj.cf_relation_cat_f" => "post_taxo_1_nobj.post_taxo_1_str"]
									 */
									foreach ( $relation_children as $relation_child_name_str ) {
										$relation_facet_name                                 = sprintf( static::PATTERN_NESTED_PARENT_CHILD_FIELD, $parent_field_name_obj, WpSolrSchema::replace_field_name_extension( $relation_child_name_str ) );
										$this->relation_facet_filter[ $relation_facet_name ] = $relation_parent_name;
									}
								}


								foreach ( $fac_fd_array as $fac_fd_item ) {
									if ( $facet_is_or ) {
										$this->search_engine_client_add_filter_in_terms( $filter_key . $fac_fd_item, $fac_fd_item,
											$filter_query_field_values, $filter_tag );
									} else {
										$this->search_engine_client_add_filter_in_all_terms( $filter_key . $fac_fd_item, $fac_fd_item,
											$filter_query_field_values, $filter_tag );
									}
								}

							}
							break;
					}
				}
			}
		}
	}

	/**
	 * Add a sort to the query
	 *
	 * @param string $sort
	 * @param string $sort_by
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_sort( $sort, $sort_by, $args = [] );

	/**
	 * Set a sort to the query
	 *
	 * @param string $sort
	 * @param string $sort_by
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_sort( $sort, $sort_by );

	/**
	 * Add a random sort
	 *
	 * @param $seed
	 *
	 * @return
	 */
	public function search_engine_client_add_sort_random( $seed ) {
		throw new \Exception( esc_html( static::ERROR_MSG_RANDOM_SORT_IS_NOT_SUPPORTED ) );
	}

	/**
	 * Add a geo distance sort.
	 * The field is already in the sorts. It will be replaced with geo sort specific syntax.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 */
	abstract public function search_engine_client_add_sort_geolocation_distance( $field_name, $geo_latitude, $geo_longitude );

	/**
	 * Add a geo distance filter.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 */
	abstract public function search_engine_client_add_filter_geolocation_distance( $field_name, $geo_latitude, $geo_longitude, $distance );

	/**
	 * Add sort field to the query
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 */
	private
	function add_sort_field(
		WPSOLR_Query $wpsolr_query
	) {

		if ( $wpsolr_query->wpsolr_get_is_recommendation() ) {
			// No sort with recommeandtions
			return;
		}

		$sort_field_names   = [];
		$sort_field_names[] = trim( $wpsolr_query->get_wpsolr_sort() );

		if ( ( static::SORT_CODE_BY_RANDOM !== $sort_field_names[0] ) && ! empty( $secondary_sort = $wpsolr_query->get_wpsolr_sort_secondary() ) ) {
			// Random sort does not need a secondary sort
			$sort_field_names[] = trim( $secondary_sort );
		}

		foreach ( $sort_field_names as $sort_field_name ) {

			switch ( $sort_field_name ) {
				case '':
					break;

				case static::SORT_CODE_BY_DATE_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_DATE, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_DATE_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_DATE, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_NUMBER_COMMENTS_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_NUMBER_COMMENTS_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_RELEVANCY_DESC:
					// None is relevancy by default
					break;

				case static::SORT_CODE_BY_TITLE_S_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_TITLE_S, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_TITLE_S_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_TITLE_S, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_PID_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_PID_I, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_PID_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_PID_I, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_AUTHOR_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_AUTHOR, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_AUTHOR_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_AUTHOR, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_AUTHOR_ID_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_AUTHOR_ID_S, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_AUTHOR_ID_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_AUTHOR_ID_S, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_POST_TYPE_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_TYPE, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_POST_TYPE_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_TYPE, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_LAST_MODIFIED_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_MODIFIED, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_LAST_MODIFIED_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_MODIFIED, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_MENU_ORDER_DESC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_MENU_ORDER_I, static::SORT_DESC );
					break;

				case static::SORT_CODE_BY_MENU_ORDER_ASC:
					$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_MENU_ORDER_I, static::SORT_ASC );
					break;

				case static::SORT_CODE_BY_RANDOM:
					$this->search_engine_client_add_sort_random( wp_get_session_token() );
					break;

				default:
					// A custom field

					// Get field name without _asc or _desc ('price_str_asc' => 'price_str')
					$sort_field_without_order = WpSolrSchema::get_field_without_sort_order_ending( $sort_field_name );

					if ( $this->get_is_field_sortable( $sort_field_without_order ) ) {
						// extract asc or desc ('price_str_asc' => 'asc')
						$sort_field_order = WPSOLR_Regexp::extract_last_separator( $sort_field_name, '_' );

						switch ( $sort_field_order ) {

							case static::SORT_DESC:
							case static::SORT_ASC:

								// Standard sort field
								$this->search_engine_client_add_sort( $this->_convert_field_name( $sort_field_without_order ), $sort_field_order );
						}
					}

					break;
			}

		}

		// Let a chance to add custom sort options
		$solarium_query = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SORT, $this->query_select, $sort_field_name, $wpsolr_query, $this );
	}

	/**
	 * Set the fields to be returned by the query.
	 *
	 * @param array $fields
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_fields( $fields );

	/**
	 * Set fields returned by the query.
	 * We do not ask for 'content', because it can be huge for attachments, and is anyway replaced by highlighting.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 */
	private
	function add_fields(
		WPSOLR_Query $wpsolr_query
	) {

		// We add '*' to dynamic fields, else they are not returned by Solr (Solr bug ?)
		$this->search_engine_client_set_fields(
			$wpsolr_query->wpsolr_get_fields() ??
			apply_filters(
				WPSOLR_Events::WPSOLR_FILTER_FIELDS,
				[
					WpSolrSchema::_FIELD_NAME_ID,
					WpSolrSchema::_FIELD_NAME_PID,
					WpSolrSchema::_FIELD_NAME_TYPE,
					WpSolrSchema::_FIELD_NAME_META_TYPE_S,
					WpSolrSchema::_FIELD_NAME_TITLE,
					WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS,
					WpSolrSchema::_FIELD_NAME_COMMENTS,
					WpSolrSchema::_FIELD_NAME_DISPLAY_DATE,
					WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED,
					'*' . WpSolrSchema::_FIELD_NAME_CATEGORIES_STR,
					WpSolrSchema::_FIELD_NAME_AUTHOR,
					'*' . WpSolrSchema::_FIELD_NAME_POST_THUMBNAIL_HREF_STR,
					'*' . WpSolrSchema::_FIELD_NAME_POST_HREF_STR,
					WpSolrSchema::_FIELD_NAME_SNIPPET_S,
				],
				$wpsolr_query,
				$this
			)
		);
	}

	/**
	 * Escape special characters in a query term.
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	public function search_engine_client_escape_term( $keywords ) {
		return $keywords;
	}

	/**
	 * Escape special characters in a query keywords.
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	public function search_engine_client_escape_keywords( $keywords ) {
		return $keywords;
	}

	/**
	 * Escape special characters in a query keywords.
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	public function search_engine_client_escape_double_quoted_keywords( $keywords ) {
		return $this->search_engine_client_escape_keywords( $keywords );
	}

	/**
	 * Set keywords of a query select.
	 *
	 * @param string $keywords
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_query_keywords( $keywords );


	/**
	 * Replace default query field by query fields, with their eventual boost.
	 *
	 * @param array $query_fields
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_query_fields( array $query_fields );

	/**
	 * Set boosts field values.
	 *
	 * @param string $boost_field_values
	 *
	 * @return
	 */
	abstract public function search_engine_client_set_boost_field_values( $boost_field_values );

	/**
	 * Set the query keywords.
	 *
	 * @param string $keywords
	 */
	public
	function set_keywords(
		$keywords,
		$extra_keywords = []
	) {

		$query_field_name = '';

		$keywords = trim( $keywords );

		if ( trim( $keywords, '"' ) !== $keywords ) {
			// Keywords are inside double quotes (exact match)
			$keywords = $this->search_engine_client_escape_double_quoted_keywords( $keywords );

		} else {
			// Escape special terms causing errors.
			$keywords = $this->search_engine_client_escape_keywords( $keywords );
		}

		if ( ! WPSOLR_Service_Container::getOption()->get_search_fields_is_active() ) {

			// No search fields selected, use the default search field
			$query_field_name = WpSolrSchema::_FIELD_NAME_DEFAULT_QUERY . ':';

		} else {

			/// Use search fields with their boost defined in qf instead of default field 'text'
			$query_fields = $this->get_query_fields();
			if ( ! empty( $query_fields ) ) {

				$this->search_engine_client_set_query_fields( $query_fields );
			}

			/// Add boosts on field values
			$boost_field_values = $this->get_query_boosts_fields();
			if ( ! empty( $boost_field_values ) ) {

				$this->search_engine_client_set_boost_field_values( $boost_field_values );
			}
		}


		if ( ! empty( $keywords ) ) {
			if ( WPSOLR_Service_Container::getOption()->get_search_is_partial_matches() ) {

				$partial_keywords = '';
				foreach ( explode( ' ', $keywords ) as $word ) {
					$partial_keywords .= sprintf( ' (%s OR %s*)', $word, $word );
				}

				$keywords = $partial_keywords;

				// Use 'OR' to ensure results include the exact keywords also (not only beginning with keywords) if there is one word only
				//$keywords = sprintf( '(%s) OR (%s)', $keywords, $keywords1 );

			} elseif ( WPSOLR_Service_Container::getOption()->get_search_is_fuzzy_matches() ) {

				$keywords = preg_replace( '/(\S+)/i', '$1~1', $keywords ); // keyword => keyword~1
			}
		}

		$this->is_query_wildcard = ( empty( $keywords ) || ( '*' === $keywords ) );

		// Final form of the main query
		$keywords = sprintf( '%s(%s)', $query_field_name, ( ! $this->is_query_wildcard ? $keywords : '*' ) );

		// Add extra search queries
		$this->add_field_keywords( $keywords, $extra_keywords );

		$this->search_engine_client_set_query_keywords( $keywords );
	}


	/**
	 * Add field keywords to the main query
	 * text:(house) AND (_location_t:(New York) AND _field1_t:(value1))
	 *
	 * @param string $main_keywords Current main keywords
	 */
	protected function add_field_keywords( &$main_keywords, $extra_keywords = [] ) {

		$results = [];

		$field_keywords = apply_filters( WPSOLR_Events::WPSOLR_FILTER_QUERY_ADD_EXTRA_FIELD_QUERIES, $extra_keywords );

		foreach ( $field_keywords as $field_name => $keyword ) {

			if ( ! empty( trim( $keyword ) ) ) {
				$results[] = sprintf( '%s:(%s)', $field_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT, $keyword );
			}
		}


		if ( ! empty( $results ) ) {
			$main_keywords .= ' AND ' . implode( ' AND ', $results );
		}
	}

	/**
	 * Build a query with boosts values
	 *
	 * @return string
	 */
	private
	function get_query_boosts_fields() {

		$option_search_fields_terms_boosts = WPSOLR_Service_Container::getOption()->get_search_fields_terms_boosts();

		$query_boost = [];
		foreach ( $option_search_fields_terms_boosts as $search_field_name => $search_field_term_boost_lines ) {

			$search_field_term_boost_lines = trim( $search_field_term_boost_lines );

			if ( ! empty( $search_field_term_boost_lines ) ) {

				if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $search_field_name ) {

					// Field 'categories' are now treated as other fields (dynamic string type)
					$search_field_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
				}

				$search_field_name = WpSolrSchema::replace_field_name_extension( $search_field_name );

				foreach ( preg_split( "/(\r\n|\n|\r)/", $search_field_term_boost_lines ) as $search_field_term_boost_line ) {

					// Transform apache solr^2 in "apache solr"^2
					$search_field_term_boost_line = preg_replace( "/(.*)\^(.*)/", '"$1"^$2', $search_field_term_boost_line );

					// Add field and it's boost term value.
					$query_boost[] = sprintf( '%s:%s', $search_field_name, $search_field_term_boost_line );
				}

			}
		}

		// Force 'OR' between boost values, else it will be the default search AND/OR
		$query_boost_str = implode( ' OR ', $query_boost );

		return $query_boost_str;
	}

	/**
	 * Build a query fields with boosts
	 *
	 * @return array
	 */
	private
	function get_query_fields() {

		$option_search_fields_boosts = WPSOLR_Service_Container::getOption()->get_search_fields_boosts();


		// Build a query fields with boosts
		$query_fields = [];
		foreach ( $option_search_fields_boosts as $search_field_name => $search_field_boost ) {

			// Boosts are applied to _t dynamic types, to use analysers.
			$search_field_name = $this->copy_field_name( $search_field_name );

			switch ( $search_field_name ) {
				case WpSolrSchema::_FIELD_NAME_CATEGORIES:
					$search_field_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
					break;

			}

			if ( '1' === $search_field_boost ) {

				// Boost of '1' is a default value. No need to add it with it's field.
				$query_fields[] = trim( sprintf( ' %s ', $search_field_name ) );

			} else {

				// Add field and it's (non default) boost value.
				$query_fields[] = trim( sprintf( ' %s^%s ', $search_field_name, $search_field_boost ) );
			}
		}

		return $query_fields;
	}

	/**
	 * Is a facet sorted alphabetically
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	protected
	function is_facet_sorted_alphabetically(
		$facet_name
	) {

		$facets_sort = WPSOLR_Service_Container::getOption()->get_facets_sort();

		return ! empty( $facets_sort ) && ! empty( $facets_sort[ $facet_name ] );
	}

	/**
	 * Is a facet exclusion
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	private
	function is_facet_exclusion(
		$facet_name
	) {

		$facets_exclusion = WPSOLR_Service_Container::getOption()->get_facets_is_exclusion();

		return ! empty( $facets_exclusion ) && ! empty( $facets_exclusion[ $facet_name ] );
	}

	/**
	 * Is a facet 'OR'
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	private
	function get_facet_is_or(
		$facet_name
	) {

		$facets_is_or = WPSOLR_Service_Container::getOption()->get_facets_is_or();

		return ! empty( $facets_is_or ) && ! empty( $facets_is_or[ $facet_name ] );
	}

	/**
	 * Retrieve a post thumbnail, from local database, or from the index content.
	 *
	 * @param mixed $document document
	 * @param int $post_id
	 * @param string $image_size
	 *
	 * @return array|false
	 */
	protected
	function get_post_thumbnail(
		$document, $post_id, $image_size
	) {

		// $post_id is in local database, use the standard way
		$results = wp_get_attachment_image_src( ( 'attachment' === $document->type ) ? $post_id : ( 'taxonomy' === ( $document->meta_type_s ?? '' ) ? get_term_meta( $post_id, 'thumbnail_id', true ) : get_post_thumbnail_id( $post_id ) ), $image_size );

		return ! empty( $results ) ? ( is_array( $results ) ? $results[0] : $results ) : null;
	}

	/**
	 * Retrieve a post url, from local database, or from the index content.
	 *
	 * @param bool $url_is_edit
	 * @param WPSOLR_Model_Abstract $model
	 * @param mixed $document document
	 * @param string $post_id
	 *
	 * @return string
	 */
	protected
	function get_post_url(
		$url_is_edit, $model, $document, $post_id
	) {

		// $post_id is in local database, use the standard way
		return $model->get_permalink( $url_is_edit, $post_id );
	}

	/**
	 * Return posts from Solr results post PIDs
	 *
	 * @param array $query_vars
	 *
	 * @return array
	 * @throws \Exception
	 */
	public
	function get_posts_from_pids(
		$query_vars
	) {

		if ( $this->results->get_nb_results() === 0 ) {
			return [ 'posts' => [], 'documents' => [] ];
		}

		return $this->_get_posts_from_pids();
	}


	/**
	 * Generate a distance query for a field, and name the query
	 *
	 * @param $field_prefix
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 * @return string
	 *
	 */
	abstract public function get_named_geodistance_query_for_field( $field_prefix, $field_name, $geo_latitude, $geo_longitude );

	/**
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facet_type( $facet_name ) {

		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_TYPE, WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD, $facet_name );
	}

	/**
	 * @param string $facet_name
	 *
	 * @return string
	 */
	public function get_facet_layout_id( $facet_name ) {

		return WPSOLR_Service_Container::getOption()->get_facets_layout_id( $facet_name );
	}

	/**
	 * Remove extra 0 decimal.
	 * 5.1 => 5.1, 5.0 => 5, * => *
	 *
	 * @param string $value
	 *
	 * @return int|float|string
	 */
	public function remove_empty_decimal( $value ) {

		if ( is_numeric( $value ) ) {
			if ( false !== strpos( $value, '.' ) ) {
				return 0 + floatval( $value );
			} else {
				return 0 + intval( $value );
			}
		} else {
			return $value;
		}

	}

	/**
	 * Remove extra 0 decimal pf a range.
	 * 5.1-10.2 => 5.1-10.2, 5.0-10.0 => 5-0, 5.0-* => 5-*
	 *
	 * @param string $range
	 *
	 * @return string
	 */
	public function remove_range_empty_decimal( $range ) {

		if ( false === strpos( $range, '-' ) ) {
			return $this->remove_empty_decimal( $range );
		}

		$ranges = explode( '-', $range );

		return sprintf( '%s-%s', $this->remove_empty_decimal( $ranges[0] ), $this->remove_empty_decimal( $ranges[1] ) );
	}

	/**
	 * Add an archive filter
	 *
	 * @param array $filter_query_fields
	 * @param string $field_name
	 */
	private function add_archive_filter( &$filter_query_fields, $field_name = '' ) {
		global $wp_query;

		$this->is_archive = true;

		/** WPSOLR_Query $wp_query */
		if ( $wp_query->wpsolr_get_is_admin() || is_post_type_archive() ) {

			$filter_query_fields[] = sprintf( '%s:%s', $field_name, get_query_var( 'post_type' ) );

		} elseif ( is_author() ) {

			$filter_query_fields[] = sprintf( '%s:%s', $field_name, get_query_var( 'author_name' ) );

		} elseif ( is_year() ) {

			$filter_query_fields[] = sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_DATE . WpSolrSchema::_FIELD_NAME_YEAR_I, get_query_var( 'year' ) );

		} elseif ( is_month() ) {

			$filter_query_fields[] = sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_DATE . WpSolrSchema::_FIELD_NAME_YEAR_I, get_query_var( 'year' ) );
			$filter_query_fields[] = sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_DATE . WpSolrSchema::_FIELD_NAME_YEAR_MONTH_I, get_query_var( 'monthnum' ) );

		} elseif ( is_day() ) {

			$filter_query_fields[] = sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_DATE . WpSolrSchema::_FIELD_NAME_YEAR_I, get_query_var( 'year' ) );
			$filter_query_fields[] = sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_DATE . WpSolrSchema::_FIELD_NAME_YEAR_MONTH_I, get_query_var( 'monthnum' ) );
			$filter_query_fields[] = sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_DATE . WpSolrSchema::_FIELD_NAME_MONTH_DAY_I, get_query_var( 'day' ) );

		} else {

			$archive_object = $wp_query->get_queried_object();
			if ( is_object( $archive_object ) && isset( $archive_object->name ) ) {

				if ( is_tax() && ! empty( $archive_object->taxonomy ) ) {
					// Name of the custom taxonomy
					$field_name = $archive_object->taxonomy . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;
				}

				$field_name_hierarchy = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $field_name, true );

				// Add parents filter also
				$term_parents_names = [];
				$term_parents_ids   = array_reverse( get_ancestors( $archive_object->term_id, $archive_object->taxonomy ) );
				array_push( $term_parents_ids, $archive_object->term_id );
				foreach ( $term_parents_ids as $term_parent_id ) {
					$term_parent = get_term( $term_parent_id, $archive_object->taxonomy );

					// Add the term to the non-flat hierarchy (for filter queries on all the hierarchy levels)
					array_push( $term_parents_names, $term_parent->name );
				}

				$filter_query_fields[] = sprintf( '%s:%s', $field_name_hierarchy, implode( static::get_facet_hierarchy_separator(), $term_parents_names ) );
			}

		}

	}

	/**
	 * Add an post type filter
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 */
	private function add_post_type_filter( $wpsolr_query ) {

		$model_type_indexed_for_search = $wpsolr_query->wpsolr_get_post_types() ?? $this->_get_post_type_filter( $wpsolr_query );

		// post_type url parameter, among those authorized
		if ( isset( $wpsolr_query->query['post_type'] ) && ! empty( $query_post_type = $wpsolr_query->query['post_type'] ) ) {
			$query_post_types              = is_array( $query_post_type ) ? $query_post_type : [ $query_post_type ];
			$model_type_indexed_for_search = array_intersect( $query_post_types, $model_type_indexed_for_search );
		}


		if ( ! empty( $model_type_indexed_for_search ) ) {

			$this->search_engine_client_add_filter_in_terms(
				'authorized indexed types',
				WpSolrSchema::_FIELD_NAME_TYPE,
				$model_type_indexed_for_search,
				''
			);

		}

	}

	/**
	 * Get post types filter
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return string[]
	 */
	protected function _get_post_type_filter( $wpsolr_query ) {

		$filter_query_fields = $wpsolr_query->get_filter_query_fields_group_by_name();

		if ( ! empty( $filter_query_fields ) && ! empty( $filter_query_fields[ WpSolrSchema::_FIELD_NAME_TYPE ] ) ) {
			// Post type filter is already set in the query. Use it.

			$results = $filter_query_fields[ WpSolrSchema::_FIELD_NAME_TYPE ];

		} else {
			// Get all post types available for search

			$results = WPSOLR_Model_Builder::get_model_types_for_search( $wpsolr_query->wpsolr_get_is_suggestion(), WPSOLR_Service_Container::getOption()->get_option_index_post_types() );
		}

		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_TYPES, $results, $wpsolr_query );
	}

	/**
	 * Add all archives to filters
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 */
	private function add_archive_filters( $wpsolr_query ) {

		$archive_filter_query_fields = [];


		if ( $wpsolr_query->wpsolr_get_is_admin() ) {

			/**
			 *
			 * Admin archives
			 *
			 */

			// Retrieve the admin archive post type
			$this->add_archive_filter( $archive_filter_query_fields, WpSolrSchema::_FIELD_NAME_TYPE );

		} else {

			/**
			 *
			 * Front-end archives
			 *
			 */

			/**
			 * Add filter post types archives
			 */
			if ( is_post_type_archive() && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_post_type_front_end() ) {
				$this->add_archive_filter( $archive_filter_query_fields, WpSolrSchema::_FIELD_NAME_TYPE );
			}

			/**
			 * Add filter categories archives
			 */
			$is_tax = is_tax();
			if ( ( is_category() || $is_tax ) && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_category_front_end() ) {
				$this->add_archive_filter( $archive_filter_query_fields, WpSolrSchema::_FIELD_NAME_CATEGORIES_STR );
			}

			/**
			 * Add filter tags archives
			 */
			if ( ( is_tag() || $is_tax ) && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_tag_front_end() ) {
				$this->add_archive_filter( $archive_filter_query_fields, WpSolrSchema::_FIELD_NAME_TAGS );
			}

			/**
			 * Add filter years archives
			 */
			if ( is_year() && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_year() ) {
				$this->add_archive_filter( $archive_filter_query_fields );
			}

			/**
			 * Add filter months archives
			 */
			if ( is_month() && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_month() ) {
				$this->add_archive_filter( $archive_filter_query_fields );
			}

			/**
			 * Add filter days archives
			 */
			if ( is_day() && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_day() ) {
				$this->add_archive_filter( $archive_filter_query_fields );
			}

			/**
			 * Add filter author archives
			 */
			if ( is_author() && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_author() ) {
				$this->add_archive_filter( $archive_filter_query_fields, WpSolrSchema::_FIELD_NAME_AUTHOR );
			}

		}

		// Set archive filters
		$wpsolr_query->set_archive_filter_query_fields( $archive_filter_query_fields );


		// Copy archive filters in filters
		$filter_query_fields = $wpsolr_query->get_filter_query_fields();
		foreach ( $archive_filter_query_fields as $archive_filter_query_field ) {
			$filter_query_fields[] = $archive_filter_query_field;
		}
		$wpsolr_query->set_filter_query_fields( $filter_query_fields );

	}

	/**
	 * Get facets from results
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param WPSOLR_AbstractResultsClient $result_set
	 * @param array $localization_options
	 * @param array $widget_options
	 *
	 * @return array
	 */
	public function get_results_facets( WPSOLR_Query $wpsolr_query, WPSOLR_AbstractResultsClient $result_set, $localization_options, $widget_options ) {

		$facets_data          = [];
		$facets_to_display    = WPSOLR_Service_Container::getOption()->get_facets_to_display();
		$fields_group_by_name = $wpsolr_query->get_filter_query_fields_group_by_name();

		if ( count( $facets_to_display ) ) {
			$facet_limit = WPSOLR_Service_Container::getOption()->get_search_max_nb_items_by_facet();

			foreach ( $facets_to_display as $facet ) {

				$min_count = static::DEFAULT_MIN_COUNT_BY_FACET;
				$fact      = $this->_convert_field_name( $facet );
				$fact      = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $fact );

				$facet_data = [];
				$facet_type = $this->get_facet_type( $facet );
				$fact       = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_NAME_SUBSTITUTE, $fact, $wpsolr_query, $this, 10, 3 );
				switch ( $facet_type ) {
					case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX:
						$facet_res = $result_set->get_stats( "$fact",
							[ 'is_date' => ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_DATE === WpSolrSchema::get_custom_field_dynamic_type( $facet ) ) ] );
						break;

					default:
						$facet_res = $result_set->get_facet( "$fact" );
						$facet_res = $this->resort_numeric_by_alphabetical_order( $facet, $facet_res, $facet_type );
						$facet_res = $this->limit_facets_after_search( $facet, $facet_res, $facet_limit ); // Limit after sort to get correct results
						break;
				}

				foreach ( ! empty( $facet_res ) ? $facet_res : [] as $value => $count ) {
					if ( $count >= $min_count ) {
						switch ( $facet_type ) {
							case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:

								if ( ! isset( $facet_range_start ) ) {
									$facet_range_start = WPSOLR_Service_Container::getOption()->get_facets_range_regular_start( $facet );
								}
								if ( ! isset( $facet_range_gap ) ) {
									$facet_range_gap = WPSOLR_Service_Container::getOption()->get_facets_range_regular_gap( $facet );
								}

								$value = $this->remove_range_empty_decimal( $value );
								$value = ( false === strpos( $value, '-' ) ) ? sprintf( '%s-%s', $value, (float) $value + (int) $facet_range_gap ) : $value;
								break;
						}
						$facet_data['values'][] = [ 'value' => $value, 'count' => $count ];
					}
				}

				if ( ! empty( $facet_data['values'] ) ) {
					$facet_data['facet_type'] = $facet_type;

					$facet_layout_id = $this->get_facet_layout_id( $facet );
					if ( ! empty( $facet_layout_id ) ) {
						$facet_data['facet_layout_id'] = $facet_layout_id;
					}

					if ( WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE === $facet_data['facet_type'] ) {
						$facet_data['facet_range_start'] = $facet_range_start;
						$facet_data['facet_range_end']   = WPSOLR_Service_Container::getOption()->get_facets_range_regular_end( $facet );
						$facet_data['facet_range_gap']   = $facet_range_gap;
						$facet_data['facet_template']    = WPSOLR_Service_Container::getOption()->get_facets_range_regular_template( $facet );
					}

				}

				$facets_data[ $facet ] = $facet_data;
			}
		}

		$results = WPSOLR_Data_Facets::get_data(
			WPSOLR_Service_Container::get_query()->get_filter_query_fields_group_by_name( $this->get_facet_hierarchy_separator() ),
			WPSOLR_Service_Container::getOption()->get_facets_to_display(),
			$facets_data,
			$localization_options,
			$widget_options,
			$this->is_engine_indexing_force_html_encoding(),
			$this->get_facet_hierarchy_separator()
		);


		return $results;
	}

	/**
	 * Get results from results
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param WPSOLR_AbstractResultsClient $result_set
	 * @param array $localization_options
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function get_results_results( WPSOLR_Query $wpsolr_query, WPSOLR_AbstractResultsClient $result_set, $localization_options ) {


		$data_results     = [];
		$has_highlighting = false;

		if ( $wpsolr_query->wpsolr_get_is_suggestion_type_content_grouped() ) {

			$grouped_results = $result_set->get_top_hits( WpSolrSchema::_FIELD_NAME_TYPE );

			/**
			 * Get only the number of suggestions required by group
			 */
			$suggestion        = $wpsolr_query->wpsolr_get_suggestion();
			$suggestions_max   = ! empty( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] : 0;
			$suggestions_count = 0;
			foreach ( $grouped_results as $model => &$grouped_result ) {

				// Update group label
				if ( ! empty( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL ] ) ) {

					$suggestion_label = $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL ];

					if ( ! empty( $suggestion_label ) ) {

						$suggestion_label = WPSOLR_Translate::translate_field_custom_field(
							WPSOLR_Option::TRANSLATION_DOMAIN_SUGGESTION_LABEL,
							sprintf( '%s: %s', $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_LABEL ], $model ), // each suggestion have its own translation,
							$suggestion_label
						);
					}

				} else {

					$model_type       = WPSOLR_Model_Builder::get_model_type_object( $model );
					$suggestion_label = $model_type->get_label();
				}

				$data_results[ $model ] = [
					'label' => $suggestion_label,
					'items' => [],
				];

				if ( isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] ) &&
				     isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model ] ) ) {

					$model_nb           = ! empty( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_NB ] )
						? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_NB ] : $suggestions_max;
					$model_nb_remaining = min( $model_nb, $suggestions_max - $suggestions_count );
					if ( $model_nb_remaining > 0 ) {
						$grouped_result = array_slice( $grouped_result, 0, $model_nb_remaining );
						// Update nb of results
						$suggestions_count += count( $grouped_result );

					} else {
						// Enough results collected. Stop here.
						unset( $grouped_results[ $model ] );
					}

				}

			}

			/**
			 * Reorder groups of results by their position as defined in the settings
			 */
			if ( $wpsolr_query->wpsolr_get_is_suggestion_type_content_grouped_sorted_by_position() && ! empty( $data_results ) ) {
				$data_results_sorted = [];
				foreach ( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] ?? [] as $group => $results ) {
					if ( isset( $data_results[ $group ] ) ) {
						$data_results_sorted[ $group ] = $data_results[ $group ];
					}
				}
				$data_results = $data_results_sorted;
			}

		} elseif ( $wpsolr_query->wpsolr_get_is_suggestion_type_question_answer() ) {

			$grouped_results = [ '' => $result_set->get_questions_answers_results() ];

		} else {

			// No groups: flat results
			$grouped_results  = [ '' => $result_set->get_results() ];
			$has_highlighting = true;
		}


		$indexing_options     = WPSOLR_Service_Container::getOption()->get_option_index();
		$solr_res_options     = WPSOLR_Service_Container::getOption()->get_option_search();
		$are_comments_indexed = WPSOLR_Service_Container::getOption()->get_index_are_comments_indexed();
		$url_is_edit          = ( $wpsolr_query->wpsolr_get_is_admin() && $wpsolr_query->wpsolr_get_is_suggestion() );
		foreach ( $grouped_results as $group => $results ) {

			$i       = 1;
			$cat_arr = [];
			foreach ( $results as $document ) {

				if ( ! is_object( $document ) ) {
					$document = (object) $document;
				}

				$data_result = [];

				try {
					$model = WPSOLR_Model_Builder::get_model_from_document( $document );
				} catch ( \Exception $e ) {
					// Document existing in the index, but no more in the database
					continue;
				}

				$post_id       = $document->PID;
				$title         = $this->property_exists( $document, 'title' ) ? $document->title : $model->get_title();
				$content       = '';
				$has_content   = false;
				$field_snippet = WpSolrSchema::_FIELD_NAME_SNIPPET_S;

				$image_url = $this->get_post_thumbnail( $document, $post_id, $wpsolr_query->get_wpsolr_image_size() );

				if ( $are_comments_indexed && $this->property_exists( $document, 'comments' ) ) {
					$comments = $document->comments;
				}
				$date = ( $this->property_exists( $document, 'displaydate' ) && ! empty( $document->displaydate ) ) ? wp_date( 'm/d/Y', is_numeric( $document->displaydate ) ? $document->displaydate : strtotime( $document->displaydate ) ) : '';

				// Dynamic fields not always present
				foreach (
					[
						WpSolrSchema::_FIELD_NAME_STATUS_S,
						WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED,
						WpSolrSchema::_FIELD_NAME_DISPLAY_DATE,
					] as $field_name
				) {
					$data_result[ $field_name ] = $this->property_exists( $document, $field_name ) ? $document->$field_name : '';
				}

				if ( $this->property_exists( $document, 'categories_str' ) ) {
					$cat_arr = $document->categories_str ?? [];
				}


				$cat  = implode( ',', $cat_arr );
				$auth = $this->property_exists( $document, 'author' ) ? $document->author : '';

				$url = $this->get_post_url( $url_is_edit, $model, $document, $post_id );

				$comm_no         = 0;
				$highlighted_doc = $has_highlighting ? $result_set->get_highlighting( $document ) : [];
				if ( $highlighted_doc ) {

					foreach ( $highlighted_doc as $field => $highlight ) {

						if ( ! is_array( $highlight ) ) {
							$highlight = [ $highlight ];
						}
						switch ( $field ) {
							case WpSolrSchema::_FIELD_NAME_TITLE:

								$title = implode( ' (...) ', $highlight );
								break;

							case  WpSolrSchema::_FIELD_NAME_CONTENT:

								$content     = implode( ' (...) ', $highlight );
								$has_content = true;
								break;

							case WpSolrSchema::_FIELD_NAME_COMMENTS:

								$comments = implode( ' (...) ', $highlight );
								$comm_no  = 1;
								break;
						}
					}
				}

				if ( ! $has_content && ! empty( $document->$field_snippet ) ) {
					// Empty highlighting, use snippet instead
					$content = $document->$field_snippet;
				}
				if ( is_array( $content ) ) {
					$content = empty( $content ) ? '' : $content[0];
				}

				$data_result[ wpsolrschema::_FIELD_NAME_TYPE ] = $document->type;
				$data_result[ WpSolrSchema::_FIELD_NAME_ID ]   = $document->id;
				$data_result[ WpSolrSchema::_FIELD_NAME_PID ]  = $post_id;
				$data_result['title']                          = [ 'href' => $url, 'title' => $title ];

				// Display first image
				if ( ! empty( $image_url ) ) {
					$data_result['image_src'] = $image_url;
				}

				// No highlighting, try something else.
				$model->set_content_if_no_highlighted_results( $content,
					isset( $indexing_options['is_shortcode_expanded'] ),
					( ( isset( $solr_res_options['highlighting_fragsize'] ) && is_int( $solr_res_options['highlighting_fragsize'] ) )
						? (int) $solr_res_options['highlighting_fragsize'] : 0 )
				);

				// Format content text a little bit
				$content = str_replace( '&nbsp;', '', $content );
				$content = str_replace( '  ', ' ', $content );
				$content = ucfirst( trim( $content ) );
				$content .= '...';

				$data_result['content'] = $content;
				if ( $comm_no === 1 ) {
					$comment_link_title     = OptionLocalization::get_term( $localization_options, 'results_row_comment_link_title' );
					$data_result['comment'] = [
						'text'  => $comments,
						'href'  => $url,
						'title' => $comment_link_title
					];
				}

				// Groups bloc - Bottom right
				$wpsolr_groups_message = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SOLR_RESULTS_DOCUMENT_GROUPS_INFOS, [], get_current_user_id(), $document );
				if ( ! empty( $wpsolr_groups_message ) ) {
					// Display groups of this user which owns at least one the document capability
					$data_result['groups'] = $wpsolr_groups_message['message'];
				}

				$append_custom_html = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SOLR_RESULTS_APPEND_CUSTOM_HTML, '', get_current_user_id(), $document, $wpsolr_query );
				if ( ! empty( $append_custom_html ) ) {
					$data_result['custom_html'] = $append_custom_html;
				}

				// Informative bloc - Bottom right
				$no_comments                     = $this->property_exists( $document, 'numcomments' ) ? $document->numcomments : '';
				$data_result['meta_information'] = [
					'author'          => sprintf( OptionLocalization::get_term( $localization_options, 'results_row_by_author' ), $auth ),
					'category'        => empty( $cat ) ? "" : "<span class='pcat'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_in_category' ), $cat ),
					'date'            => sprintf( OptionLocalization::get_term( $localization_options, 'results_row_on_date' ), $date ),
					'comments_number' => empty( $no_comments ) ? "" : "<span class='pcat'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_number_comments' ), $no_comments ),
				];

				// Q&A meta infos
				if ( $this->property_exists( $document, 'wpsolr_questions_answers' ) ) {
					$data_result['wpsolr_questions_answers'] = $document->wpsolr_questions_answers;
				}

				if ( empty( $group ) ) {

					array_push( $data_results, $data_result );

				} else {

					$data_results[ $group ]['items'][] = $data_result;
				}

				$i = $i + 1;
			}
		}

		return $data_results;
	}

	/**
	 * Get results info
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 * @param $nb_results
	 * @param array $localization_options
	 *
	 * @return string
	 */
	protected
	function get_results_info_localized(
		WPSOLR_Query $wpsolr_query, $nb_results, array $localization_options
	) {

		$information_header = '';

		if ( WPSOLR_Service_Container::getOption()->get_search_is_display_results_info() ) {

			$first = $wpsolr_query->wpsolr_get_start() + 1;
			$last  = $wpsolr_query->wpsolr_get_start() + $wpsolr_query->get_nb_results_by_page();
			if ( $last > $nb_results ) {
				$last = $nb_results;
			}
			if ( WPSOLR_Service_Container::getOption()->get_search_is_infinitescroll() ) {

				$information_header = sprintf( OptionLocalization::get_term( $localization_options, 'infinitescroll_results_header_pagination_numbers' ), $nb_results );

			} else {

				$information_header = sprintf( OptionLocalization::get_term( $localization_options, 'results_header_pagination_numbers' ), $first, $last, $nb_results );
			}
		}

		return $information_header;
	}

	/**
	 * @param WPSOLR_AbstractResultsClient $results
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return WPSOLR_AbstractResultsClient
	 */
	protected
	function get_results_did_you_mean_localized(
		$results, WPSOLR_Query $wpsolr_query
	) {

		if ( ( WPSOLR_Service_Container::getOption()->get_search_is_did_you_mean() ) ) {

			$did_you_mean_keyword = $this->search_engine_client_get_did_you_mean_suggestions( $wpsolr_query->get_wpsolr_query() );

			if ( ! empty( $did_you_mean_keyword ) && ( $did_you_mean_keyword !== $wpsolr_query->get_wpsolr_query() ) ) {

				// Store did you mean
				$wpsolr_query->wpsolr_set_did_you_mean( $did_you_mean_keyword );

				// Replace keywords with did you mean keywords
				$wpsolr_query->set_wpsolr_query( $did_you_mean_keyword );

				try {
					$results = $this->execute_wpsolr_query( $wpsolr_query, false );

				} catch ( \Exception $e ) {
					// Sometimes, the spelling query returns errors
					// java.lang.StringIndexOutOfBoundsException: String index out of range: 15\n\tat java.lang.AbstractStringBuilder.charAt(AbstractStringBuilder.java:203)\n\tat
					// java.lang.StringBuilder.charAt(StringBuilder.java:72)\n\tat org.apache.solr.spelling.SpellCheckCollator.getCollation(SpellCheckCollator.java:164)\n\tat

				}

			}
		}

		return $results;
	}

	/**
	 * @param object $document
	 * @param string $field_name
	 *
	 * @return bool
	 */
	abstract protected function property_exists( $document, $field_name );

	/**
	 * Reorder numeric facets alphabetically is the search engine cannot
	 *
	 * @param string $facet_name
	 * @param array $facet_values
	 * @param string $facet_type
	 *
	 * @return array
	 */
	protected function resort_numeric_by_alphabetical_order( $facet_name, array $facet_values, string $facet_type ) {
		return $facet_values;
	}

	/**
	 * Limit facets after search if the search engine cannot serverside
	 *
	 * @param string $facet_name
	 * @param array $facet_values
	 * @param int $facet_limit
	 *
	 * @return array
	 */
	protected function limit_facets_after_search( $facet_name, array $facet_values, int $facet_limit ) {
		return $facet_values;
	}

	/**
	 * Does the search engine force encoding of HTML, like '&amp;' => '&' ?
	 *
	 * @return false
	 */
	protected function is_engine_indexing_force_html_encoding() {
		return false;
	}

	/**
	 * @param string $facet_name_str
	 *
	 * @return bool
	 */
	private function _is_facet_can_not_exists( string $facet_name_str ): bool {
		if ( ! isset( $this->facets_can_not_exists ) ) {
			$this->facets_can_not_exists = WPSOLR_Service_Container::getOption()->get_facets_is_can_not_exist();
		}

		return isset( $this->facets_can_not_exists[ $facet_name_str ] );
	}

	/**
	 * @return WPSOLR_Query
	 */
	public function get_wpsolr_query(): WPSOLR_Query {
		return $this->wpsolr_query ?? new WPSOLR_Query();
	}

	/**
	 * @param WPSOLR_Query $wpsolr_query
	 */
	public function set_wpsolr_query( WPSOLR_Query $wpsolr_query ): void {
		$this->wpsolr_query = $wpsolr_query;
	}

	/**
	 *
	 * @param string $field_name_str
	 *
	 * @return string
	 */
	protected function _convert_field_name( string $field_name_str ): string {

		/**
		 * _price_str => _price_f
		 * title => title
		 */
		$field_name = WpSolrSchema::replace_field_name_extension( $field_name_str );

		/**
		 * Manage relations to this field
		 * Generate a relation field name from parent ('taxo_str') and child ('price_str'):
		 * 'price_str' ===> 'taxo_obj.price_f'
		 * 'taxo_str' ===> taxo_obj.taxo_str
		 */
		if ( empty( $parent_field_name_str = WPSOLR_Service_Container::getOption()->get_option_index_custom_field_relation_parent( $field_name_str ) ) ) {
			/*$parent_field_name_str = ! empty( WPSOLR_Service_Container::getOption()->get_option_index_custom_field_relation_children( $field_name_str ) ) ?
				$field_name_str :
				$parent_field_name_str;*/
		}
		if ( ! empty( $parent_field_name_str ) ) {
			$parent_field_name_obj = WpSolrSchema::replace_field_name_extension_with( $parent_field_name_str, WpSolrSchema::_SOLR_DYNAMIC_TYPE_EMBEDDED_OBJECT );
			$field_name            = sprintf( static::PATTERN_NESTED_PARENT_CHILD_FIELD, $parent_field_name_obj, $field_name );
		}

		return $field_name;
	}

	/**
	 * @return array[]
	 * @throws \Exception
	 */
	protected function _get_posts_from_pids(): array {
		// Fetch all posts from the documents ids, in ONE call.
		// Local search: return posts from local database

		$model_pids           = [];
		$model_post_meta_type = WPSOLR_Model_Meta_Type_Post::META_TYPE;
		foreach ( WPSOLR_Model_Builder::get_all_meta_types() as $meta_type ) {
			$model_pids[ $meta_type ] = [];
		}
		foreach ( $this->results->get_results() as $document ) {
			// Group documents by meta type, else there could be documents with same PID (in 2 distinct meta types, for instance a post and a term).
			$document_meta_type                                  = empty( $document->{WpSolrSchema::_FIELD_NAME_META_TYPE_S} ) ? $model_post_meta_type : $document->{WpSolrSchema::_FIELD_NAME_META_TYPE_S};
			$document_meta_type                                  = ( is_array( $document_meta_type ) && ! empty( $document_meta_type ) ) ? $document_meta_type[0] : $document_meta_type;
			$model_pids[ $document_meta_type ][ $document->PID ] = $document;
		}

		if ( empty( $model_pids ) ) {
			return [ 'posts' => [], 'documents' => [] ];
		}

		$results = [ 'posts' => [], 'documents' => [] ];
		if ( isset( $query_vars['fields'] ) && ( 'ids' === $query_vars['fields'] ) ) {

			foreach ( $model_pids[ $model_post_meta_type ] as $post_id => $document ) {
				array_push( $results['posts'], $post_id );
				array_push( $results['documents'], $document );
			}

		} else {

			$indexed_post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();
			array_push( $indexed_post_types, 'attachment' ); // Insure attachments are also returned.
			$posts = get_posts(
				apply_filters(
					WPSOLR_Events::WPSOLR_FILTER_QUERY_RESULTS_GET_POSTS_ARGUMENTS,
					[
						'is_wpsolr'           => true,
						'numberposts'         => count( $model_pids[ $model_post_meta_type ] ),
						'post_type'           => $indexed_post_types,
						'post_status'         => [ 'any', 'trash' ],
						// Added 'trash' for admin archives. 'any' does not retrieve 'trash'.
						'post__in'            => array_keys( $model_pids[ $model_post_meta_type ] ),
						'orderby'             => 'post__in',
						'exclude_from_search' => false,
						// Get posts in same order as documents in Solr results.
					]
				)
			);

			foreach ( $posts as $post ) {
				if ( isset( $model_pids[ $model_post_meta_type ][ $post->ID ] ) ) {
					array_push( $results['posts'], $post );
					array_push( $results['documents'], $model_pids[ $model_post_meta_type ][ $post->ID ] );
				}
			}

		}

		return $results;
	}

	/**
	 * @param string $facet_name
	 * @param string $post_filter_field_name
	 *
	 * @return bool
	 */
	protected function is_filter_can_be_applied_to_facet( string $facet_name, string $post_filter_field_name ): bool {

		// A post filter cannot be applied to its excluded facet
		if ( $facet_name === $post_filter_field_name ) {
			return false;
		}

		// Nested filter added on the facet
		if ( isset( $this->relation_facet_filter[ $facet_name ] ) && ( $post_filter_field_name === $this->relation_facet_filter[ $facet_name ] ) ) {
			return true;
		}

		// Nested filter selected
		if ( $this->is_filter_of_facet( $post_filter_field_name ) ) {
			return false;
		}

		return true;

	}

	/**
	 * @param string $post_filter_field_name
	 *
	 * @return bool
	 */
	protected function is_filter_of_facet( string $post_filter_field_name ): bool {

		foreach ( $this->relation_facet_filter ?? [] as $re_facet_name => $rel_filter_name ) {
			if ( $rel_filter_name === $post_filter_field_name ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @inheridoc
	 */
	public function get_recommendations_html( $recommendation, $wpsolr_query ) {

		$template_data = $this->get_recommendations_items_data( $wpsolr_query, $recommendation );

		/**
		 * Build the recommendations HTML from the template
		 */
		if ( empty( $template_data['results'] ) ) {
			return '';
		}

		return WPSOLR_Service_Container::get_template_builder()->load_template(
			WPSOLR_Option_Recommendations::get_recommendation_layout_file( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_UUID ] ),
			$template_data
		);
	}

	/**
	 * Get recommendation items data from request.
	 *
	 * @param string $index_uuid
	 * @param WPSOLR_Query $wpsolr_query
	 * @param array $recommendation
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_recommendations_items_data( WPSOLR_Query $wpsolr_query, array $recommendation ): array {

		/**
		 * Retrieve recommendations
		 */
		//$wpsolr_query->set_wpsolr_query( $query );
		$wpsolr_query->set_wpsolr_query_cache_key( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_UUID ] );
		$wpsolr_query->wpsolr_set_recommendation( $recommendation );
		$wpsolr_query->wpsolr_set_nb_results_by_page( $recommendation[ WPSOLR_Option::OPTION_SUGGESTION_NB ] );
		$recommendations = $this->get_results_data( $wpsolr_query, [], false );

		/**
		 * Build the template data from results
		 */
		$template_data            = [];
		$template_data['results'] = [];
		foreach ( $recommendations['results']['items'] as $results ) {

			$template_data['results'][] = $results;
		}

		/**
		 * Tracking events
		 */
		if ( isset( $recommendations['event_tracking'] ) ) {
			$template_data['event_tracking'] = $recommendations['event_tracking'];
		}
		/*
		 * Build the template data from $recommendation
		 */
		$template_data['settings'] = $recommendation;

		return $template_data;
	}

}
