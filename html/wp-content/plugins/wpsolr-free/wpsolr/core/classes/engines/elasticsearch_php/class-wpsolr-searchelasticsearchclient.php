<?php

namespace wpsolr\core\classes\engines\elasticsearch_php;

use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_SearchElasticsearchClient extends WPSOLR_AbstractSearchClient {
	use WPSOLR_ElasticsearchClient;

	const MORE_LIKE_THIS_FIELDS = [ 'text' ];

	const IS_LOG_QUERY_TIME_IMPLEMENTED = true;

	// Scripts in painless: https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting-painless-syntax.html
	const SCRIPT_LANGUAGE_PAINLESS = 'painless';
	const SCRIPT_PAINLESS_DISTANCE = 'doc[params.field].empty ? params.empty_value : doc[params.field].planeDistance(params.lat,params.lon)*0.001';

	const FIELD_SEARCH_AUTO_COMPLETE = 'autocomplete';
	const FIELD_SEARCH_SPELL = 'spell';
	const SUGGESTER_NAME = 'wpsolr_spellcheck';
	protected const ES_SCROLL_ID = 'scroll_id';
	protected const ES_SCROLL = 'scroll';
	protected const ES_SCROLL_TIME_DELAY = '10s';

	/* @var array $query */
	protected $query;

	/* @var array $query */
	protected $query_url_params = [];

	// https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-query-string-query.html
	/* @var array $query_string */
	protected $query_string;

	/* @var array $query_filters */
	protected array $query_filters;
	protected array $query_filters_by_field_name;

	/* @var array $query_post_filters */
	protected $query_post_filters;

	/* @var array $query_script_fields */
	protected $query_script_fields;

	/* @var array $facets_filters */
	protected $facets_filters;

	/* @var array $facets_ranges */
	protected $facets_ranges;

	/* @var array */
	protected $facets_fields;

	/* @var array $completion $facets_fields */
	protected $completion;

	/* @var bool $is_did_you_mean */
	protected $is_did_you_mean = false;

	/* @var bool $is_query_built */
	protected $is_query_built = false;

	/* @var string $boost_field_values */
	protected $boost_field_values;

	/* @var array $function_score */
	protected $function_score;

	/* @var array $stats */
	protected $stats;

	/** @var int */
	protected $random_sort_seed = 0;

	/** @var array */
	protected $highlighting_fields;

	/** @var array */
	protected $source_fields;

	/**
	 * @var array
	 */
	protected $the_query;
	protected array $facets_terms_filters;

	/**
	 * Execute an update query with the client.
	 *
	 * @param \Elasticsearch\Client $search_engine_client
	 *
	 * @return WPSOLR_ResultsElasticsearchClient
	 */
	public function search_engine_client_execute( $search_engine_client, $random_score ) {

		$this->search_engine_client_build_query();

		$the_query = array_merge( $this->query_url_params, $this->_create_query( isset( $this->completion ) ? $this->completion : $this->query ) );


		$scroll_id = $the_query['body'][ self::ES_SCROLL_ID ] ?? '';

		if ( empty( $scroll_id ) ) {

			$this->the_query = $the_query;
			$results         = $this->search_engine_client->search( $the_query );

		} else {

			$results = $this->search_engine_client->scroll( [
				self::ES_SCROLL => $the_query[ self::ES_SCROLL ],
				'body'          => [ self::ES_SCROLL_ID => $scroll_id ],
			] );
		}

		return new WPSOLR_ResultsElasticsearchClient( $results );
	}


	/**
	 * @inheritDoc
	 */
	protected function _log_query_as_string() {
		return wp_json_encode( $this->the_query['body'], JSON_PRETTY_PRINT );
	}

	/**
	 * Build the query.
	 *
	 */
	public function search_engine_client_build_query() {

		if ( $this->is_query_built ) {

			if ( $this->is_did_you_mean ) {
				// Add a phrase suggester on keywords.

				$keywords = $this->query_string['query'];
				$keywords = preg_replace( '/(.*):/', '', $keywords ); // keyword => keyword, text:keyword => keyword

				if ( ! empty( $keywords ) && ! strpos( $keywords, '*' ) && WPSOLR_Service_Container::getOption()->get_search_is_did_you_mean() ) {
					// Add did you mean if the keywords are not empty or wilcard

					$this->query['suggest'] = [
						self::SUGGESTER_NAME => [
							"phrase" => [
								"field" => self::FIELD_SEARCH_SPELL,
								"size"  => 1,
							],
							"text"   => $keywords,
						]
					];

				}

				// Another query with the suggested keyword will be executed just after
				$this->is_query_built = false;
			}

			// Already done.
			return;
		}

		$this->is_query_built = true;

		// Filter out the attachment ID
		$this->search_engine_client_add_filter_not_in_terms( 'Filter out attachment ID', WpSolrSchema::_FIELD_NAME_INTERNAL_ID, [ $this->WPSOLR_DOC_ID_ATTACHMENT ] );

		if ( ! isset( $this->completion ) ) {
			// Normal search.

			if ( isset( $this->query_filters ) ) {

				// Only way to get facets correctly with filters: a bool query.
				// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-filtered-query.html
				$query_filters = [];

				$query_filters['must'] = $this->_add_bool( $this->query_filters_by_field_name );

				$the_query['bool'] = [
					'must'   => [ 'query_string' => $this->query_string ],
					'filter' => [ 'bool' => $query_filters ],
				];

				$recommendation = $this->get_recommendation();
				if ( ! empty( $recommendation ) ) {
					switch ( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] ) {
						case WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_MORE_LIKE_THIS:
							$object_id = $this->get_wpsolr_query()->get_wpsolr_ajax_context()['object_id'];
							if ( ! ( $object_id && ( $post = get_post( $object_id ) ) ) ) {
								throw new \Exception( sprintf( 'Model "%s" requires post_id.',
									esc_html( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] ) ) );
							}
							$the_query['bool']['must'] = [
								'more_like_this' => [
									'fields'        => static::MORE_LIKE_THIS_FIELDS,
									'like'          => [ [ '_id' => $object_id ] ],
									'min_term_freq' => 1,
								]
							];

							break;

						default:
							throw new \Exception( sprintf( 'Model "%s" is unknown.',
								esc_html( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] ) ) );
					}
				}


				if ( ! empty( $this->random_sort_seed ) ) {
					// Replace score with random

					$this->function_score = [
						'function_score' => [
							'random_score' => [
								'seed'  => $this->random_sort_seed,
								'field' => '_seq_no'
							]
						]
					];
				}

				if ( isset( $this->function_score ) ) {
					// Score functions

					$this->function_score['function_score']['query'] = $the_query;
					$the_query                                       = $this->function_score;
				}

				$this->query['query'] = $the_query;

			} else {
				// No filters.

				$the_query = $this->query_string;

				if ( ! empty( $this->random_sort_seed ) ) {
					// Replace score with random

					$this->function_score = [
						'function_score' => [
							'random_score' => [
								'seed'  => $this->random_sort_seed,
								'field' => '_seq_no'
							]
						]
					];
				}

				if ( isset( $this->function_score ) ) {
					// Score functions

					$this->function_score['function_score']['query'] = $this->query_string;
					$the_query                                       = $this->function_score;
				}

				$this->query['query'] = $the_query;

			}

			if ( isset( $this->query_post_filters ) ) {
				// Remove relation children from filters: relation children only filter facets, not results (see facets below)
				$this->query['post_filter'] = [ 'bool' => [ 'must' => $this->_add_bool( $this->query_post_filters, true ) ] ];
			}

			// Add script fields (for geo distance fields)
			if ( isset( $this->query_script_fields ) ) {

				$this->query['script_fields'] = $this->query_script_fields;
			}

			// Add facet post filters (filters on facets with an exclusion)
			if ( isset( $this->facets_filters ) ) {
				foreach ( $this->facets_filters as $facet_name => $facet_term ) {

					$parent_facet_obj = $this->_get_nested_name( $facet_name );

					/**
					 * Post filters are not nested. They must be put above the nested facet.
					 */
					$facet_post_filters = []; //$this->query['post_filter'];
					foreach ( $this->query_post_filters ?? [] as $post_filter_field_name => $query ) {
						/**
						 * Current post filters applies to all facets not excluded
						 */
						if ( $this->is_filter_can_be_applied_to_facet( $facet_name, $post_filter_field_name ) ) {
							if ( $this->_get_nested_name( $post_filter_field_name ) !== $parent_facet_obj ) {
								// Not nested: use a post filter
								$facet_post_filters[ $post_filter_field_name ] = $query;
							} else {
								// Nested: use an aggregation filter (post filter does not work)
								if ( ! empty( $query['must'] ) || ! empty( $query['should'] ) ) {
									$facet_term['filter'] = [ 'bool' => $query ];
								} else {
									$facet_term['filter'] = [ 'bool' => [ 'must' => $query ] ];
								}
							}
						}
					}
					if ( ! empty( $facet_post_filters ) ) {
						$facet_post_filters = [ 'bool' => [ 'must' => $this->_add_bool( $facet_post_filters ) ] ];
					}


					if ( $this->_is_nested( $facet_name ) ) {

						/**
						 * Add parent filters to nested child aggregation
						 */
						foreach ( $this->query_filters_by_field_name as $filter_field_name => $query ) {
							if ( ! empty( $query ) ) {
								$parent_filter_obj = $this->_get_nested_name( $filter_field_name );
								if ( ( $parent_facet_obj === $parent_filter_obj )
								     && ( $facet_name === $filter_field_name )
								) {
									if ( ! empty( $query['must'] ) || ! empty( $query['should'] ) ) {
										$facet_term['filter'] = [ 'bool' => $query ];
									} else {
										$facet_term['filter'] = [ 'bool' => [ 'must' => $query ] ];
									}
								}
							}
						}

						/**
						 * Ready to add the nested aggregation layer
						 */
						$facet_term = $this->_create_outer_aggs_nested( $facet_name, $facet_term );
					}

					if ( ! empty( $facet_post_filters ) ) {
						$facet_term = $this->_create_outer_aggs( $facet_post_filters, [ $facet_name => $facet_term ] );
					}

					$this->query['aggs'][ $facet_name ] = $facet_term;
				}
			}

		}

	}

	/**
	 * Does index exists ?
	 *
	 * @param $is_throw_error
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected
	function admin_is_index_exists(
		$is_throw_error = false
	) {

		// this methods throws an error if index is not responding.
		try {

			$stats = $this->get_index_stats();

		} catch ( \Exception $e ) {

			if ( $is_throw_error ) {
				throw $e;
			}

			return false;
		}

		$this->throw_exception_if_error( $stats );

		// Index exists.
		return true;
	}

	/**
	 * @param array|string $data
	 *
	 * @throws \Exception
	 */
	protected
	function throw_exception_if_error(
		$data
	) {

		if ( is_string( $data ) ) {
			// Elasticpress returns a string

			$error = $data;

		} elseif ( ! empty( $data ) && ! empty( $data['error'] ) ) {

			$error = $data['error'];
		}

		if ( ! empty( $error ) ) {
			// Connexion error: cannot be recovered. For instance, AWS security not set properly.
			throw new \Exception( sprintf( "Problem while connecting to your index :<br><br> \"%s\"", esc_html( $error ) ) );
		}

	}

	/**
	 * Create the index
	 *
	 * @param array $index_parameters
	 */
	protected
	function admin_create_index(
		&$index_parameters
	) {
		$settings = $this->get_and_decode_configuration_file();

		$settings['index']                                  = $this->get_index_label();
		$settings['body']['settings']['number_of_shards']   = $this->config['extra_parameters'][ self::ENGINE_ELASTICSEARCH ][ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_SHARDS ];
		$settings['body']['settings']['number_of_replicas'] = $this->config['extra_parameters'][ self::ENGINE_ELASTICSEARCH ][ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_REPLICAS ];

		$this->search_engine_client->indices()->create( $settings );
	}

	/**
	 * Delete the index
	 * @throws \Exception
	 */
	public
	function admin_delete_index() {
		$response = $this->search_engine_client->indices()->delete( $this->get_index() );

		$this->throw_exception_if_error( $response );
	}

	/**
	 * Add a configuration to the index if missing.
	 */
	protected
	function admin_index_update(
		&$index_parameters
	) {

		try {

			$mapping = $this->search_engine_client->indices()->getMapping( $this->get_index() );

		} catch ( \Exception $e ) {

			// Since 5.5.1, no type mapping yet triggers an exception. We continue anyway.
		}

		if ( empty( $mapping ) || empty( $mapping[ $this->get_index_label() ] ) || empty( $mapping[ $this->get_index_label() ]['mappings'] ) ) {

			$this->search_engine_client->indices()->close( $this->get_index() );
			$this->search_engine_client->indices()->putSettings( $this->get_and_decode_configuration_file() );
			$this->search_engine_client->indices()->open( $this->get_index() );
		}

	}

	/**
	 * Create a query select.
	 *
	 * @return  array
	 */
	public
	function search_engine_client_create_query_select() {

		$this->query = [];

		$this->query_string                = [];
		$this->query_filters_by_field_name = [];

		return $this->query;
	}

	/**
	 * @inheritDoc
	 *
	 * From \Elastica\Util::escapeTerm
	 *
	 */
	public
	function search_engine_client_escape_keywords(
		$keywords
	) {

		$result = $keywords;

		// \ escaping has to be first, otherwise escaped later once again
		$escapableChars = [
			'\\',
			'+',
			'-',
			'&&',
			'||',
			'!',
			'(',
			')',
			'{',
			'}',
			'[',
			']',
			'^',
			'"',
			'~',
			'*',
			'?',
			':',
			'/'
		];

		foreach ( $escapableChars as $char ) {
			$result = str_replace( $char, '\\' . $char, $result );
		}

		// < and > cannot be escaped, so they should be removed
		// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#_reserved_characters
		$nonEscapableChars = [ '<', '>' ];

		foreach ( $nonEscapableChars as $char ) {
			$result = str_replace( $char, '', $result );
		}

		return $result;
	}

	/**
	 * Set keywords of a query select.
	 *
	 * @param $keywords
	 *
	 * @return string
	 */
	public
	function search_engine_client_set_query_keywords(
		$keywords
	) {

		if ( ! empty( $this->boost_field_values ) ) {
			/**
			 * We add each boost value to a boost
			 */
			if ( is_null( $this->function_score ) ) {
				$this->function_score = [];
			}

			/**
			 * https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html
			 */
			foreach ( explode( ' OR ', $this->boost_field_values ) as $field_boost_values ) {
				$field_boost_value                                     = explode( ':', $field_boost_values );
				$field_boost_value_str                                 = WPSOLR_Regexp::extract_first_separator_before( $field_boost_value[1], '^' );
				$field_boost_value_str                                 = trim( $field_boost_value_str, '"' );
				$this->function_score['function_score']['functions'][] = [
					'filter' => [ 'match' => [ $field_boost_value[0] => $field_boost_value_str ], ],
					'weight' => floatval( WPSOLR_Regexp::extract_last_separator( $field_boost_value[1], '^' ) ),
				];
			}
		}

		$this->query_string['query'] = $keywords;
	}

	/**
	 * Set query's default operator.
	 *
	 * @param string $operator
	 */
	public
	function search_engine_client_set_default_operator(
		$operator = 'AND'
	) {
		$this->query_string['default_operator'] = $operator;
	}

	/**
	 * Set query's start.
	 *
	 * @param int $start
	 */
	public
	function search_engine_client_set_start(
		$start
	) {
		$this->query['from'] = $start;
	}

	/**
	 * Set query's rows.
	 *
	 * @param int $rows
	 */
	public
	function search_engine_client_set_rows(
		$rows
	) {
		$this->query['size'] = $rows;
	}

	/**
	 * @inerhitDoc
	 */
	public function search_engine_client_set_is_show_all_results( $is_show_all_results = false ) {
		if ( $is_show_all_results ) {
			// Remove the 10K results limit: https://www.elastic.co/guide/en/elasticsearch/reference/current/search-your-data.html#track-total-hits
			$this->query['track_total_hits'] = true;
		}
	}

	/**
	 * @inheritDoc
	 */
	public
	function search_engine_client_set_cursor_mark(
		$cursor_mark
	) {

		unset( $this->query[ self::ES_SCROLL_ID ] );
		unset( $this->query_url_params[ self::ES_SCROLL ] );

		if ( ! is_null( $cursor_mark ) ) {

			if ( ! empty( $cursor_mark ) ) {
				$this->query[ self::ES_SCROLL_ID ] = $cursor_mark;
			}
			$this->query_url_params[ self::ES_SCROLL ] = self::ES_SCROLL_TIME_DELAY;// Time between scrolls before automatic deletion of the cursor
		}

	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_add_sort(
		$sort, $sort_by, $args = []
	) {
		$sort_def = [ 'order' => $sort_by ];

		if ( isset( $args['mode'] ) ) {
			$sort_def['mode'] = $args['mode'];
		}
		if ( isset( $args['nested'] ) ) {
			$sort_def['nested'] = $args['nested'];
		}

		$this->query['sort'][] = $this->_create_outer_sort_nested( $sort, [ $sort => $sort_def ] );
	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_set_sort(
		$sort, $sort_by
	) {
		$this->query['sort'] = $this->_create_outer_sort_nested( $sort, [ $sort => [ 'order' => $sort_by ] ] );
	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_add_sort_random(
		$seed
	) {

		$this->random_sort_seed = (int) preg_replace( "/[^0-9]/", "", $seed ); // Remove all non-digit caracters from seed
	}

	/**
	 * Add a simple filter term.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param $facet_is_or
	 * @param string $field_value
	 * @param string $filter_tag
	 */
	public
	function search_engine_client_add_filter_term(
		$filter_name, $field_name, $facet_is_or, $field_value, $filter_tag = ''
	) {

		$term = [ 'term' => [ $field_name => $field_value ] ];

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $term, $filter_tag );
	}

	/**
	 * Add a negative filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @param string $filter_tag
	 *
	 */
	public
	function search_engine_client_add_filter_not_in_terms(
		$filter_name, $field_name, $field_values, $filter_tag = ''
	) {

		$terms = [ 'terms' => [ $field_name => $field_values ] ];

		$this->_add_filter_query( $field_name, $this->search_engine_client_create_not( $terms ) );
	}

	/**
	 * @param string $field_name
	 * @param array $query
	 *
	 * @return array
	 *
	 */
	protected
	function _add_filter_query(
		$field_name, $query
	) {

		$this->query_filters[] = $query;
		if ( empty( $this->query_filters_by_field_name[ $field_name ] ) ) {

			$this->query_filters_by_field_name[ $field_name ] = $query;

		} else {
			/**
			 * If one select a child category on its archive parent category, the filter is added twice with a 'must'
			 */

			// Existing filter previously added
			$field_name_existing_query = $this->query_filters_by_field_name[ $field_name ];
			$field_name_existing_query = $field_name_existing_query['must'] ?? $field_name_existing_query;

			$query                                            = array_merge(
				( ( 1 === count( $field_name_existing_query ) ) ? [ $field_name_existing_query ] : $field_name_existing_query ),
				isset( $query['bool'] ) ? [ $query ] : [ [ 'bool' => $query ] ]
			);
			$this->query_filters_by_field_name[ $field_name ] = [ 'must' => $query ];
		}

		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_add_filter_not_in_terms_of_other_sites(
		$filter_name, $field_name, $field_values, $site_id
	) {

		$terms_not     = $this->search_engine_client_create_filter_not_in_terms( $field_name, $field_values );
		$terms_site_id = $this->search_engine_client_create_filter_in_terms( WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, [ $site_id ] );

		// not terms OR site_id
		$this->_add_filter_query( $field_name, $this->search_engine_client_create_or( [
			$terms_not,
			$terms_site_id
		] ) );
	}

	/**
	 * Add a 'OR' filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 *
	 */
	public
	function search_engine_client_add_filter_in_terms(
		$filter_name, $field_name, $field_values, $filter_tag = ''
	) {
		$this->search_engine_client_add_filter_any( $filter_name, $field_name, false, $this->search_engine_client_create_filter_in_terms( $field_name, $field_values ), $filter_tag );
	}

	/**
	 * @inherit
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_in_terms(
		$field_name, $field_values
	) {
		// https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-terms-query.html

		return [ 'terms' => [ $field_name => $field_values ] ];
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_wildcard(
		$field_name, $field_value
	) {

		$wildcard = [
			'wildcard' => [
				$field_name => [
					'value' => $field_value,
					'boost' => 1,
				]
			]
		];

		return $wildcard;
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_wildcard_not(
		$field_name, $field_value
	) {

		return $this->search_engine_client_create_not( $this->search_engine_client_create_filter_wildcard( $field_name, $field_value ) );
	}

	/**
	 * @inheritdoc
	 *
	 */
	public
	function search_engine_client_add_filter_in_all_terms(
		$filter_name, $field_name, $field_values, $filter_tag = ''
	) {
		$this->search_engine_client_add_filter_any( $filter_name, $field_name, false, $this->search_engine_client_create_filter_in_all_terms( $field_name, $field_values ), $filter_tag );
	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_create_filter_in_all_terms(
		$field_name, $field_values
	) {

		$queries = [];
		foreach ( $field_values as $field_value ) {

			$queries[] = [ 'terms' => [ $field_name => [ $field_value ] ] ];
		}

		return $this->search_engine_client_create_and( $queries );
	}


	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_not_in_terms(
		$field_name, $field_values
	) {

		return $this->search_engine_client_create_not( [ 'terms' => [ $field_name => $field_values ] ] );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_lt(
		$field_name, $field_values
	) {

		return $this->_create_filter_range_terms( $field_name, $field_values, 'lt' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_lte(
		$field_name, $field_values
	) {

		return $this->_create_filter_range_terms( $field_name, $field_values, 'lte' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_gt(
		$field_name, $field_values
	) {

		return $this->_create_filter_range_terms( $field_name, $field_values, 'gt' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_gte(
		$field_name, $field_values
	) {

		return $this->_create_filter_range_terms( $field_name, $field_values, 'gte' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_between(
		$field_name, $field_values
	) {

		return $this->search_engine_client_create_and( [
			[
				'range' => [
					$field_name =>
						[
							'from' => $field_values[0],
							'to'   => $field_values[1]
						]
				]
			]
		] );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_not_between(
		$field_name, $field_values
	) {

		return $this->search_engine_client_create_not(
			$this->search_engine_client_create_filter_between( $field_name, $field_values )
		);
	}

	/**
	 *
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $operator
	 *
	 * @return array
	 */
	protected
	function _create_filter_range_terms(
		$field_name, $field_values, $operator
	) {

		$results = [];

		foreach ( $field_values as $field_value ) {
			$results[] = [ 'range' => [ $field_name => [ $operator => $field_value ] ] ];
		}

		return $this->search_engine_client_create_and( $results );
	}

	/**
	 * Create a 'only numbers' filter.
	 *
	 * @param string $field_name
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_only_numbers(
		$field_name
	) {
		return $this->search_engine_client_create_not( [ 'regexp' => [ $field_name => '[^0-9]*' ] ] );
	}

	/**
	 * Create a 'empty or absent' filter.
	 *
	 * @param string $field_name
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_filter_no_values(
		$field_name
	) {

		return $this->search_engine_client_create_not( [ 'exists' => [ 'field' => $field_name ] ] );
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $queries
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_or(
		$queries
	) {

		return [ 'should' => $this->_add_bool( $queries ) ];
	}

	/**
	 * https://www.elastic.co/guide/en/elasticsearch/reference/7.9/query-dsl-nested-query.html
	 *
	 * @inheritDoc
	 */
	public
	function search_engine_client_create_nested_query(
		$path, $query
	) {

		return [
			'nested' => [
				'path'  => $path,
				'query' => [ 'bool' => $query ],
			]
		];

	}

	/**
	 * https://www.elastic.co/guide/en/elasticsearch/reference/7.9/query-dsl-nested-query.html
	 *
	 * @inheritDoc
	 */
	public
	function search_engine_client_create_nested_filter(
		$path, $query
	) {

		return [
			'nested' => [
				'path'   => $path,
				'filter' => [ 'bool' => $query ],
			]
		];

	}

	/**
	 * Add a query
	 *
	 * @throws \Exception
	 */
	public
	function search_engine_client_add_query(
		$query
	) {
		$this->query[] = $query;
	}

	/**
	 * add 'bool' to all queries
	 *
	 * @param array $queries
	 *
	 * @return array
	 */
	protected
	function _add_bool(
		$queries, $is_remove_nested = false
	) {

		$results = [];

		foreach ( $queries as $field_name => $query ) {

			if ( is_array( $query ) && ! empty( $query ) && ! ( $is_remove_nested && $this->is_filter_of_facet( $field_name ) ) ) {
				if ( isset( $query['nested'] ) ||
				     isset( $query['terms'] ) ||
				     isset( $query['exists'] ) ||
				     isset( $query['range'] ) ||
				     isset( $query['wildcard'] ) ) {

					// bool not supported
					$result = $query;

				} else {
					$result = [ 'bool' => $query ];
				}

				$results[] = $this->_is_nested( $field_name ) ? $this->_create_outer_filter_nested( $field_name, $result ) : $result;
				//$results[] = $result;
			}

		}

		return $results;
	}

	/**
	 * @inheritdoc
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_not(
		$query
	) {

		return [ 'must_not' => $this->_add_bool( [ $query ] ) ];
	}

	/**
	 * Add a filter
	 *
	 * @param string $filter_name
	 * @param array $filter
	 */
	public
	function search_engine_client_add_filter(
		$filter_name, $filter
	) {
		$this->_add_filter_query( $filter_name, $filter );
	}

	/**
	 * Create a 'AND' from filters.
	 *
	 * @param array $queries
	 *
	 * @return array
	 */
	public
	function search_engine_client_create_and(
		$queries
	) {

		return [ 'must' => $this->_add_bool( $queries ) ];
	}

	/**
	 * Add a filter on: empty or in terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 *
	 */
	public
	function search_engine_client_add_filter_empty_or_in_terms(
		$filter_name, $field_name, $field_values, $filter_tag = ''
	) {

		// 'IN' terms
		$in_terms = [ 'terms' => [ $field_name => $field_values ] ];

		// 'empty': not exists
		$empty = $this->search_engine_client_create_not( $this->search_engine_client_create_filter_exists( $field_name ) );

		// 'empty' OR 'IN'
		$this->_add_filter_query( $field_name,
			$this->search_engine_client_create_or(
				[
					$empty,
					$in_terms
				]
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_add_filter_exists(
		$filter_name, $field_name
	) {

		// Add 'exists'
		$this->_add_filter_query( $field_name, $this->search_engine_client_create_filter_exists( $field_name ) );

	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_create_filter_exists(
		$field_name
	) {

		return [ 'must' => [ 'exists' => [ 'field' => $field_name ] ] ];
	}

	/**
	 * Set highlighting.
	 *
	 * @param string[] $field_names
	 * @param string $prefix
	 * @param string $postfix
	 * @param int $fragment_size
	 */
	public
	function search_engine_client_set_highlighting(
		$field_names, $prefix, $postfix, $fragment_size
	) {

		// https://www.elastic.co/guide/en/elasticsearch/reference/5.2/search-request-highlighting.html#_highlight_query

		$fields = [];

		foreach ( $field_names as $field_name ) {

			$fields[ $field_name ] = [
				'fragment_size'       => $fragment_size,
				'number_of_fragments' => 1,
			];
		}

		$this->highlighting_fields = [
			'require_field_match' => false,
			// Show highlighted fields even if they are not part of the query.
			'pre_tags'            => [ $prefix ],
			'post_tags'           => [ $postfix ],
			'fields'              => $fields,
		];
		$this->query['highlight']  = $this->highlighting_fields;
	}

	/**
	 * Get facet terms.
	 *
	 * @param string $facet_name
	 *
	 * @return array
	 */
	protected
	function &get_or_create_facets_field(
		$facet_name
	) {
		if ( ! isset( $this->facets_filters ) ) {

			$this->facets_filters = [];
		}

		if ( isset( $this->facets_filters[ $facet_name ] ) ) {
			return $this->facets_filters[ $facet_name ]['aggs'][ $facet_name ];
		}

		$facet      = [ 'terms' => [ 'field' => $facet_name ] ];
		$agg_filter = $this->_create_outer_aggs( [], [ $facet_name => $facet ] );

		$this->facets_filters[ $facet_name ] = $agg_filter;

		return $facet;
	}


	/**
	 * @inerhitDoc
	 */
	public
	function search_engine_client_set_facets_min_count(
		$facet_name, $min_count
	) {

		$this->get_or_create_facets_field( $facet_name )['terms']['min_doc_count'] = $min_count;
	}

	/**
	 * @inerhitDoc
	 */
	public
	function search_engine_client_add_facet_field(
		$facet_name, $field_name
	) {

		$this->get_or_create_facets_field( $field_name );
	}

	/**
	 * Set facets limit.
	 *
	 * @param $facet_name
	 * @param int $limit
	 */
	public
	function search_engine_client_set_facets_limit(
		$facet_name, $limit
	) {
		$this->get_or_create_facets_field( $facet_name )['terms']['size'] = $limit;
	}

	/**
	 * Sort a facet field alphabetically.
	 *
	 * @param $facet_name
	 */
	public
	function search_engine_client_set_facet_sort_alphabetical(
		$facet_name
	) {

		$this->get_or_create_facets_field( $facet_name )['terms']['order']['_key'] = 'asc';
	}

	/**
	 * Set facet field excludes.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 */
	public
	function search_engine_client_set_facet_excludes(
		$facet_name, $exclude
	) {

		/**
		 * Nothing done here.
		 * Done in the facet, and in the filter.
		 *
		 * - Excluded terms are put in the post_filter, and are added as filter to each facet not excluded.
		 */

	}

	/**
	 * Set the fields to be returned by the query.
	 *
	 * @param array $fields
	 */
	public
	function search_engine_client_set_fields(
		$fields
	) {
		$this->source_fields    = $fields;
		$this->query['_source'] = $fields;
	}

	/**
	 * Get suggestions from the engine.
	 *
	 * @inheritdoc
	 *
	 * @return WPSOLR_ResultsElasticsearchClient
	 */
	public
	function search_engine_client_get_suggestions_keywords(
		$suggestion, $query, $contexts, $is_error = false
	) {

		/*
		{
			"suggest": {
				"wpsolr_spellcheck": {
					"completion": {
						"field": "autocomplete",
						"size": "10"
					},
					"text": "title"
				}
			}
		}
		*/

		$completion = [
			'field'           => self::FIELD_SEARCH_AUTO_COMPLETE,
			'size'            => $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ],
			'skip_duplicates' => true,
		];

		// Add the context: https://www.elastic.co/guide/en/elasticsearch/reference/7.2/suggester-context.html
		if ( ! empty( $contexts ) && WPSOLR_Service_Container::getOption()->get_option_indexes_version_suggester_has_context( $this->index_indice, ! $is_error ) ) {
			$completion['contexts'] = $contexts;
		}

		$this->completion = [
			'suggest' => [
				self::SUGGESTER_NAME => [
					'text'       => $query,
					'completion' => $completion,
				],
			]
		];

		try {

			return $this->search_engine_client_execute( $this->search_engine_client, null );

		} catch ( \Exception $e ) {

			if ( ! $is_error && $this->_try_to_fix_error_doc_type( $e->getMessage() ) ) {
				// Retry with the fix
				return $this->search_engine_client_get_suggestions_keywords( $suggestion, $query, $contexts, true );
			}

			// Could not fix the problem, continue
			throw $e;
		}
	}


	/**
	 * Get suggestions for did you mean.
	 *
	 * @param string $keywords
	 *
	 * @return string Did you mean keyword
	 */
	public
	function search_engine_client_get_did_you_mean_suggestions(
		$keywords
	) {

		$this->is_did_you_mean = true;

		$results = $this->search_engine_client_execute( $this->search_engine_client, null );

		$suggestions = $results->get_suggestions();

		return ! empty( $suggestions ) ? $suggestions[0]['text'] : '';
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
	public
	function search_engine_client_add_sort_geolocation_distance(
		$field_name, $geo_latitude, $geo_longitude
	) {

		$sorts = $this->query['sort'] ?? [];

		if ( ! empty( $sorts ) ) {

			foreach ( $sorts as $position => &$sort_item ) {

				if ( ! empty( $sort_item[ $field_name ] ) ) {

					// Replace geo sort
					$sort_item = [
						'_geo_distance' => [
							$field_name     => [ 'lat' => $geo_latitude, 'lon' => $geo_longitude ],
							'order'         => $sort_item[ $field_name ]['order'],
							'unit'          => 'km',
							'mode'          => 'min',
							'distance_type' => 'plane',
						],
					];

					// Replace sorts.
					$this->query['sort'] = $sorts;

					// sort found and replaced.
					break;
				}
			}
		}

	}

	/**
	 * Generate a distance script for a field, and name the query
	 *
	 * @param $field_prefix
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 * @return string
	 *
	 */
	public
	function get_named_geodistance_query_for_field(
		$field_prefix, $field_name, $geo_latitude, $geo_longitude
	) {

		if ( ! isset( $this->query_script_fields ) ) {
			$this->query_script_fields = [];
		}

		// Create the distance field name: field_name1_str => wpsolr_distance_field_name1
		$distance_field_name = $field_prefix . WPSOLR_Regexp::remove_string_at_the_end( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING );

		// Add distance field script to field scripts
		$this->query_script_fields[ $distance_field_name ] = [
			'script' => [
				'source' => self::SCRIPT_PAINLESS_DISTANCE,
				'params' => [
					'field'       => WpSolrSchema::replace_field_name_extension( $field_name ),
					// field_name1_str => field_name1_ll
					'empty_value' => 40000,
					'lat'         => floatval( $geo_latitude ),
					'lon'         => floatval( $geo_longitude ),
				],
				'lang'   => self::SCRIPT_LANGUAGE_PAINLESS,
			],
		];

		return $distance_field_name;
	}

	/**
	 * Replace default query field by query fields, with their eventual boost.
	 *
	 * @param array $query_fields
	 */
	public
	function search_engine_client_set_query_fields(
		array $query_fields
	) {
		$this->query_string['fields'] = $query_fields;
	}

	/**
	 * Set boosts field values.
	 *
	 * @param array $boost_field_values
	 */
	public
	function search_engine_client_set_boost_field_values(
		$boost_field_values
	) {
		// Store it. Will be added to the query later.

		// Add 'OR' condition, else empty results if boost value is not found.
		$this->boost_field_values = $boost_field_values;
	}


	/**
	 * Get facet terms.
	 *
	 * @param string $facet_name
	 * @param int $range_start
	 * @param int $range_end
	 * @param int $range_gap
	 *
	 * @return array
	 */
	protected
	function get_or_create_facets_range(
		$facet_name, $range_start, $range_end, $range_gap
	) {
		if ( ! isset( $this->facets_ranges ) ) {

			$this->facets_ranges = [];
		}

		if ( isset( $this->facets_ranges[ $facet_name ] ) ) {
			return $this->facets_ranges[ $facet_name ];
		}

		// Not found. Create the facet.
		$ranges = [];

		// Add a range for values before start
		$ranges[] = [ 'to' => $range_start ];

		// No gap parameter. We build the ranges manually.
		foreach ( range( $range_start, $range_end, $range_gap ) as $start ) {
			if ( $start < $range_end ) {
				$ranges[] = [ 'from' => $start, 'to' => $start + $range_gap ];
			}
		}

		// Add a range for values after end
		$ranges[] = [ 'from' => $range_end ];

		$agg_filter = $this->_create_outer_aggs( [],
			[
				$facet_name => [
					'range' => [
						'field'  => $facet_name,
						'ranges' => $ranges
					]
				]
			] );

		$this->facets_filters[ $facet_name ] = $agg_filter;

		return [];
	}

	/**
	 * Create a facet range regular.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $range_gap
	 */
	public
	function search_engine_client_add_facet_range_regular(
		$facet_name, $field_name, $range_start, $range_end, $range_gap
	) {

		$this->get_or_create_facets_range( $field_name, $range_start, $range_end, $range_gap );
	}

	/**
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-top-hits-aggregation.html
	 *
	 * @@inheritdoc
	 */
	public
	function search_engine_client_add_facet_top_hits(
		$facet_name, $size
	) {

		if ( ! isset( $this->facets_top_hits ) ) {

			$this->facets_top_hits = [];
		}

		if ( isset( $this->facets_top_hits[ $facet_name ] ) ) {
			return $this->facets_top_hits[ $facet_name ];
		}

		// Not found. Create the aggregation.

		$outer_agg = $this->_create_outer_aggs(
			[],
			[
				$facet_name => [
					'terms' => [
						'field' => $facet_name,
						'order' => [ 'top_hit' => 'desc' ],
					],
					'aggs'  => [
						'top_hits' => [
							'top_hits' => [
								'_source' => $this->source_fields,
								'size'    => $size,
							],
						],
						'top_hit'  => [
							'max' => [
								'script' => [
									'source' => '_score'
								]
							]
						],

					],
				],
			]
		);

		$this->facets_filters[ $facet_name ]  = $outer_agg;
		$this->facets_top_hits[ $facet_name ] = $outer_agg;

	}

	/**
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-top-hits-aggregation.html
	 *
	 * @@inheritdoc
	 */
	public
	function search_engine_client_add_facet_top_hits_sorted(
		$facet_name, $size
	) {

		if ( empty( $this->query['sort'] ) ) {
			$this->search_engine_client_add_facet_top_hits( $facet_name, $size );

			return;
		}

		if ( ! isset( $this->facets_top_hits ) ) {

			$this->facets_top_hits = [];
		}

		if ( isset( $this->facets_top_hits[ $facet_name ] ) ) {
			return $this->facets_top_hits[ $facet_name ];
		}

		$sort      = key( $this->query['sort'][0] );
		$sort_by   = $this->query['sort'][0][ $sort ];
		$outer_agg = $this->_create_outer_aggs(
			[],
			[
				$facet_name => [
					'terms' => [
						'field' => $facet_name,
					],
					'aggs'  => [
						'top_hits' => [
							'top_hits' => [
								'_source' => $this->source_fields,
								'size'    => $size,
								'sort'    => [
									$sort => $sort_by,
								],
							],
						],
					],
				],
			]
		);

		$this->facets_filters[ $facet_name ]  = $outer_agg;
		$this->facets_top_hits[ $facet_name ] = $outer_agg;
	}

	/**
	 * Add a filter.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param bool $facet_is_or
	 * @param array $filter
	 * @param string $filter_tag
	 */
	public
	function search_engine_client_add_filter_any(
		$filter_name, $field_name, $facet_is_or, $filter, $filter_tag = ''
	) {

		$operator   = $facet_is_or ? 'should' : 'must';
		$bool_query = isset( $filter[ $operator ] ) ? [ 'bool' => $filter ] : $filter;

		if ( empty( $filter_tag ) ) {

			$this->_add_filter_query( $field_name, [ $operator => $bool_query ] );

		} else {

			/**
			 * No exclusion as simple as in Solr: we replace the filter by a postfilter, and apply the filter to all facets but the current facet.
			 */


			// Tag. Add to the post query filters.
			$this->query_post_filters[ $field_name ][ $operator ][] = $bool_query;

			// Add exclusion filter to all facets, but the excluded.
			/*
			if ( ! empty( $this->facets_filters ) ) {

				foreach ( $this->facets_filters as &$facet_filter ) {

					$facet_filter_name = key( $facet_filter['aggs'] );

					// Verify that the filter field name is not the facet
					if ( str_replace( self::_FIELD_NAME_NON_FLAT_HIERARCHY, self::_FIELD_NAME_FLAT_HIERARCHY, $field_name ) !== $facet_filter_name ) {

						if ( ! isset( $this->facets_terms_filters ) ) {
							$this->facets_terms_filters = [];
						}
						if ( ! isset( $this->facets_terms_filters[ $field_name ] ) ) {
							$this->facets_terms_filters[ $field_name ] = [];
						}

						if ( ! isset( $this->facets_terms_filters[ $field_name ][ $facet_filter_name ] ) ) {
							$this->facets_terms_filters[ $field_name ][ $facet_filter_name ] = [ 'bool' => [] ];
						}

						if ( $facet_is_or ) {

							$this->facets_terms_filters[ $field_name ][ $facet_filter_name ]['bool']['should'][] = $filter;

						} else {

							$this->facets_terms_filters[ $field_name ][ $facet_filter_name ]['bool']['must'][] = $filter;
						}

						$facet_filter['filter'] = $this->_is_nested( $field_name ) ?
							$this->_create_outer_filter_nested( $field_name, $this->facets_terms_filters[ $field_name ][ $facet_filter_name ] ) :
							$this->facets_terms_filters[ $field_name ][ $facet_filter_name ];
					}
				}
			}
			*/
		}
	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_add_filter_range_upper_strict(
		$filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = ''
	) {

		if ( $range_start === $range_end ) {

			$this->search_engine_client_add_filter_range_upper_included( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag );

		} else {


			$range_values = [];
			if ( '*' !== $range_start ) {
				$range_values['from'] = $range_start;
			}
			if ( '*' !== $range_end ) {
				$range_values['lt'] = $range_end; // aggregation upper ranges are 'lt'
			}

			$range = [ 'range' => [ $field_name => $range_values ] ];

			$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $range, $filter_tag );
		}
	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_add_filter_range_upper_included(
		$filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = ''
	) {

		$range_values = [];
		if ( '*' !== $range_start ) {
			$range_values['from'] = $range_start;
		}
		if ( '*' !== $range_end ) {
			$range_values['to'] = $range_end;
		}

		$range = [ 'range' => [ $field_name => $range_values ] ];

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $range, $filter_tag );
	}

	/**
	 * Add decay functions to the search query
	 *
	 * @param array $decays
	 *
	 */
	public
	function search_engine_client_add_decay_functions(
		array $decays
	) {

		if ( empty( $decays ) ) {
			// Nothing to do
			return;
		}

		if ( is_null( $this->function_score ) ) {
			$this->function_score = [];
		}

		foreach ( $decays as $decay_def ) {

			$origin = $decay_def['origin'];
			if ( WPSOLR_Option::OPTION_SCORING_DECAY_ORIGIN_DATE_NOW === $decay_def['origin'] ) {
				$origin = 'now';
			}

			switch ( $decay_def['unit'] ) {
				case WPSOLR_Option_Scoring::DECAY_DATE_UNIT_DAY:
					$unit = 'd';
					break;

				case WPSOLR_Option_Scoring::DECAY_DATE_UNIT_KM:
					$unit = 'km';
					break;

				case WPSOLR_Option_Scoring::DECAY_DATE_UNIT_NONE:
					$unit = '';
					break;

				default:
					throw new \Exception( sprintf( 'Unit %s not recognized for field %s.',
						esc_html( $decay_def['unit'] ), esc_html( $decay_def['field'] ) ) );
					break;
			}

			$this->function_score['function_score']['functions'][] = [
				$decay_def['function'] =>
					[
						$decay_def['field'] => // displaydate_dt
							[
								'origin' => $origin, // 'now', '0', 'lat,long'
								'scale'  => sprintf( '%s%s', $decay_def['scale'], $unit ), // '10d', '10', '10km'
								'offset' => sprintf( '%s%s', $decay_def['offset'], $unit ), // '2d', '2', '2km'
								'decay'  => $decay_def['decay'], // '0.5'
							]
					]
			];

		}
	}

	/**
	 * Add a geo distance filter.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 */
	public
	function search_engine_client_add_filter_geolocation_distance(
		$field_name, $geo_latitude, $geo_longitude, $distance
	) {

		$geo_distance_query = [
			'geo_distance' => [
				'distance'      => sprintf( '%skm', $distance ),
				'distance_type' => 'plane',
				$field_name     => [
					'lat' => $geo_latitude,
					'lon' => $geo_longitude,
				],
			]
		];

		$this->search_engine_client_add_filter_any( sprintf( '%s %s', 'max distance for', $field_name ),
			$field_name,
			false,
			$geo_distance_query,
			'post filter'
		);
	}

	/**
	 * Get facet stats.
	 *
	 * @param string $facet_name
	 *
	 * @return array
	 */
	protected
	function get_or_create_facets_stats(
		$facet_name
	) {
		if ( ! isset( $this->stats ) ) {

			$this->stats = [];
		}

		if ( isset( $this->stats[ $facet_name ] ) ) {
			return $this->stats[ $facet_name ];
		}

		// Not found. Create the stats.
		$stats = $this->_create_outer_aggs(
			[],
			[
				$facet_name => [
					'stats' => [
						'field' => $facet_name
					],
				],
			]
		);

		$this->facets_filters[ $facet_name ] = $stats;

		return $stats;
	}

	/**
	 * Create a facet stats.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 */
	public
	function search_engine_client_add_facet_stats(
		$facet_name, $exclude
	) {
		$this->get_or_create_facets_stats( $facet_name );
	}


	/**
	 * Build the outer aggs from its inner content
	 *
	 * @param array $inner_aggs
	 * @param array $inner_filter
	 *
	 * @return array
	 */
	protected function _create_outer_aggs(
		array $inner_filter, array $inner_aggs
	) {
		return [
			'filter' => empty( $inner_filter ) ?
				[
					'match_all' => new \stdClass(),
				] :
				$inner_filter,
			'aggs'   => $inner_aggs,
		];

	}

	/**
	 * @param string $facet_name
	 * @param array $agg_nested
	 *
	 * @return array
	 */
	protected
	function _create_outer_aggs_nested(
		$facet_name, array $agg_nested
	) {

		if ( $this->_is_nested( $facet_name ) ) {
			return [
				'nested' => [
					'path' => $this->_get_nested_name( $facet_name ),
				],
				'aggs'   => [ $facet_name => $agg_nested, ]
			];
		}

		return $agg_nested;
	}

	/**
	 * @param string $facet_name
	 * @param array $agg_nested
	 *
	 * @return array
	 */
	protected
	function _create_outer_filter_nested(
		$facet_name, array $filter
	) {

		if ( $this->_is_nested( $facet_name ) ) {
			return [
				'nested' => [
					'path'  => $this->_get_nested_name( $facet_name ),
					'query' => $filter
				],
			];
		}

		return $filter;
	}

	/**
	 * @href https://www.elastic.co/guide/en/elasticsearch/reference/current/sort-search-results.html#_nested_sorting_examples
	 *
	 * @param string $facet_name
	 * @param array $filter_nested
	 *
	 * @return array
	 */
	protected
	function _create_outer_sort_nested(
		$facet_name, array $filter_nested
	) {

		if ( $this->_is_nested( $facet_name ) ) {
			$filter_nested[ $facet_name ]['nested'] = [
				'path' => $this->_get_nested_name( $facet_name ),
			];
		}

		return $filter_nested;
	}

	/**
	 * @inheritDoc
	 */
	protected
	function property_exists(
		$document, $field_name
	) {
		return property_exists( $document, $field_name );
	}

}
