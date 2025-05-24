<?php

namespace wpsolr\core\classes\engines\vespa;

use Exception;
use GraphQL\QueryBuilder\QueryBuilder;
use wpsolr\core\classes\engines\vespa\php_client\WPSOLR_Php_Search_Client;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Vespa_None;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_Search_Vespa_Client extends WPSOLR_AbstractSearchClient {
	use WPSOLR_Vespa_Client;

	/**
	 * @href https://www.semi.technology/developers/vespa/current/graphql-references/filters.html#where-filter
	 */
	const FILTER_BOOLEAN = '{operator: %s, operands: [%s]}';
	const FILTER_VALUE = '{path: ["%s"], operator: %s, %s: "%s"}';
	const FILTER_VALUE_FLOAT = '{path: ["%s"], operator: %s, %s: %f}';
	const FILTER_VALUE_INT = '{path: ["%s"], operator: %s, %s: %d}';
	const FILTER_OR = ' or ';
	const FILTER_AND = ' and ';
	const FILTER_NOT = '!';
	const FILTER_EQUAL = '=';
	const FILTER_NOTEQUAL = '!=';
	const FILTER_GREATERTHAN = ' > ';
	const FILTER_GREATERTHANEQUAL = ' >= ';
	const FILTER_LESSTHAN_ = ' < ';
	const FILTER_LESSTHANEQUAL = ' <= ';
	const FILTER_LIKE = 'Like';
	const FILTER_WITHINGEORANGE = 'WithinGeoRange';
	const FILTER_VALUEINT = 'valueInt';
	const FILTER_VALUEBOOLEAN = 'valueBoolean';
	const FILTER_VALUESTRING = 'valueString';
	const FILTER_VALUETEXT = 'valueText';
	const FILTER_VALUENUMBER = 'valueNumber';
	const FILTER_VALUEDATE = 'valueDate';

	const SEARCH_OPERATOR_NEAREST_NEIGHBOR = "({label:'%s',approximate:true,targetHits: 100}nearestNeighbor(%s, q_%s))";
	const SEARCH_OPERATOR_NEAREST_NEIGHBOR_TEXT = 'wpsolr_search_operator_nearest_neighbor_text';
	const SEARCH_OPERATOR_NEAREST_NEIGHBOR_IMAGE = 'wpsolr_search_operator_nearest_neighbor_image';


	protected QueryBuilder $query_builder_root, $query_builder_search, $query_builder_search_index;


	const IS_LOG_QUERY_TIME_IMPLEMENTED = true;

	const _FIELD_NAME_FLAT_HIERARCHY = 'flat_hierarchy_'; // field contains hierarchy as a string with separator (filter)
	const _FIELD_NAME_NON_FLAT_HIERARCHY = 'non_flat_hierarchy_'; // field contains hierarchy as an array (facet)

	// Scripts in painless: https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting-painless-syntax.html
	const SCRIPT_LANGUAGE_PAINLESS = 'painless';
	const SCRIPT_PAINLESS_DISTANCE = 'doc[params.field].empty ? params.empty_value : doc[params.field].planeDistance(params.lat,params.lon)*0.001';

	const FIELD_SEARCH_AUTO_COMPLETE = 'autocomplete';
	const FIELD_SEARCH_SPELL = 'spell';
	const SUGGESTER_NAME = 'wpsolr_spellcheck';

	const QUERY_SUGGESTIONS_NOT_SUPPORTED = 'Query suggestions not supported by Vespa.';

	/* @var array */
	protected static $fields_in_settings = [];

	/* @var array $query */
	protected $query;

	// https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-query-string-query.html
	/* @var array $query_string */
	protected $query_string;

	/* @var array $query_filters */
	protected $query_filters;

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

	/** @var int */
	protected $random_sort_seed = 0;

	/** @var array */
	protected $highlighting_fields;

	/** @var array */
	protected $search_parameters = [];

	/** @var array */
	protected $sorts;

	/**
	 * @var string[]
	 */
	protected $query_facets_type_field = [];

	/**
	 * @var string[]
	 */
	protected $query_facets_type_stats = [];

	/**
	 * @var string[]
	 */
	protected $index_facets = [];

	/**
	 * @var string[]
	 */
	protected $excluded_fields = [];

	/**
	 * @var array
	 */
	protected $queries = [];

	/**
	 * @var string[]
	 */
	protected $filters_str = [];

	/**
	 * @var string
	 */
	protected $filters_excluded_str;

	/**
	 * @var string[]
	 */
	protected $filtered_fields = [];

	/**
	 * @var array
	 */
	protected $filters_bool = [];
	protected float $distance, $alpha;
	protected string $filter;

	protected array $selected_fields;

	/**
	 * Execute a search with the client.
	 *
	 * @param WPSOLR_Php_Search_Client $search_engine_client
	 * @param array $query
	 *
	 * @return WPSOLR_Results_Vespa_Client
	 * @throws Exception
	 */
	public function search_engine_client_execute( $search_engine_client, $query ) {

		$query = $this->search_engine_client_build_query();

		try {
			$raw_results = $this->get_search_index()->search( $query );
		} catch ( Exception $e ) {
			if ( preg_match( '/prop (.*) not found/', $e->getMessage(), $matches ) ) {
				$missing_converted_field_name = $matches[1];

				// Get the unconverted field name
				$field_name = $this->unconvert_field_name( $missing_converted_field_name );

				// Add the field to the schema
				$this->_add_index_fields_definitions( [ $field_name ] );

				// Now, retry once
				$raw_results = $this->get_search_index()->search( $query );
			} else {
				// Nothing we can do to prevent the search error
				throw $e;
			}
		}

		return new WPSOLR_Results_Vespa_Client( [
			'results' => $raw_results->get_results(),
			'trace'   => $raw_results->get_trace(),
		] );
	}

	/**
	 * Build the query
	 * @return array
	 */
	public function search_engine_client_build_query(): array {


		$is_suggestion = $this->get_wpsolr_query()->wpsolr_get_is_suggestion();

		$groups = [];
		if ( ! $is_suggestion ) {
			/**
			 * Build YQL group query from facets
			 * https://docs.vespa.ai/en/grouping.html
			 */
			foreach ( $this->facets_fields ?? [] as $facet_name => $facet_limit ) {
				$groups[] = sprintf( 'all(group(%s) %s max(%s) each(output(count())))',
					$this->convert_field_name( $facet_name ),
					isset( $this->search_parameters['sortFacetValuesBy'][ $facet_name ] ) ? '' : 'order(-count())',
					$facet_limit
				);
			}

			/**
			 * Range grouping: https://docs.vespa.ai/en/grouping.html#range-grouping
			 */
			foreach ( $this->facets_ranges ?? [] as $facet_name => $facet_ranges ) {
				$groups[] = sprintf( 'all(group(predefined(%s, %s)) each(output(count())))',
					$this->convert_field_name( $facet_name ),
					implode( ', ', $facet_ranges )
				);
			}

			/**
			 * Stats grouping: https://docs.vespa.ai/en/grouping.html#range-grouping
			 */
			foreach ( $this->query_facets_type_stats ?? [] as $facet_name ) {
				// Waiting feature request: https://github.com/vespa-engine/vespa/issues/23968
				$groups[] = sprintf( 'all(group("%s") each(output(min(%s)) output(max(%s))) )',
					$this->convert_field_name( $facet_name ),
					$this->convert_field_name( $facet_name ),
					$this->convert_field_name( $facet_name )
				);
			}

		} else {
			/**
			 * Suggestions
			 */
			if ( $this->get_wpsolr_query()->wpsolr_get_is_suggestion_type_content_grouped() ||
			     $this->get_wpsolr_query()->wpsolr_get_is_suggestion_type_content_grouped_sorted_by_position() ) {
				// https://docs.vespa.ai/en/grouping.html#hits-per-group
				$max_group_results = 0;
				foreach ( $this->get_wpsolr_query()->wpsolr_get_suggestion()['models'] as $model ) {
					// Vespa cannot set a max results per aggregation: calculate the max from suggestion groups
					$max_group_results = max( $max_group_results, $model['model_nb'] );
				}
				$groups[] = sprintf( 'all(group(%s) each(max(%s) each(output(summary())))) ',
					$this->convert_field_name( WpSolrSchema::_FIELD_NAME_TYPE ),
					$max_group_results
				);
			}

		}

		/**
		 * Create the filter argument for search and aggregation queries
		 **/
		$filter_arguments = '';
		if ( ! empty( $this->query_string['query'] ) ) {
			$text_vector_component_id   = 'wpsolr_component_vector_text';
			$index_text_vector_model_id = $this->get_config()['extra_parameters']['text_vectorizer_id'] ?? ''; // 'wpsolr_multilingual_e5_small_onnx';
			$field_vector               = WPSOLR_Hosting_Api_Vespa_None::WPSOLR_FIELD_VECTOR_TEXT;

			$is_query_vector = ! empty( $index_text_vector_model_id ) &&
			                   ! empty( $text_vector_model_def = WPSOLR_Hosting_Api_Vespa_None::get_components_vectorizer_text()[ $index_text_vector_model_id ] ) &&
			                   WPSOLR_Service_Container::getOption()->get_search_vespa_query_mode_is_vector();
			$is_query_text   = WPSOLR_Service_Container::getOption()->get_search_vespa_query_mode_is_text();

			if ( $is_query_vector ) {
				# NN search
				$this->query[ sprintf( 'input.query(q_%s)', $text_vector_model_def['vector_dimension'] ) ] =
					sprintf( 'embed(%s, "%s")', $index_text_vector_model_id, sprintf( $text_vector_model_def['query_pattern'], $this->query_string['query'] ) );
			}

			if ( $is_query_text ) {
				# https://docs.vespa.ai/en/reference/query-api-reference.html
				$this->query['query'] = $this->query_string['query'];
			}

			if ( ! empty( $query_type = WPSOLR_Service_Container::getOption()->get_search_vespa_query_type() ) ) {
				$this->query['type'] = $query_type;
			}

			if ( empty( $rank_profile = WPSOLR_Service_Container::getOption()->get_search_vespa_rank_profile() ) ) {
				switch ( $query_mode = WPSOLR_Service_Container::getOption()->get_search_vespa_query_mode() ) {
					case WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_VECTOR:
						$rank_profile = 'wpsolr_rank_text_vector';
						break;

					case WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT:
						$rank_profile = 'wpsolr_rank_text';
						break;

					case WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_HYBRID:
						$rank_profile = 'wpsolr_rank_text_hybrid';
						break;

					default:
						throw new \Exception( sprintf( 'Query mode "%s" has no default rank profile.', esc_html( $query_mode ) ) );
						break;
				}
			}
			$this->query['ranking'] = $rank_profile;

			# https://docs.vespa.ai/en/reference/query-language-reference.html#userinput
			$user_input_annotations = [ 'defaultIndex' => 'wpsolr_fieldset_input_text' ];
			if ( $is_query_text && ! empty( $fieldset = WPSOLR_Service_Container::getOption()->get_search_vespa_fieldset() ) ) {
				$user_input_annotations['defaultIndex'] = $fieldset;
			}

			/**
			 * Add input queries filter
			 */
			$query_operators = [];
			if ( $is_query_text ) {
				$query_operators[] = sprintf( '([%s]userInput(@query))', json_encode( $user_input_annotations ) );
			}
			if ( $is_query_vector ) {
				$query_operators[] = sprintf( self::SEARCH_OPERATOR_NEAREST_NEIGHBOR,
					self::SEARCH_OPERATOR_NEAREST_NEIGHBOR_TEXT,
					$field_vector, $text_vector_model_def['vector_dimension']
				);
			}
			if ( ! empty( $query_operators ) ) {
				$this->filters_str[] = ( 1 === count( $query_operators ) ) ?
					$query_operators[0] :
					$this->_create_filter_boolean( $query_operators, static::FILTER_OR );
			}

		}
		foreach ( $this->filters_bool as $field_name => $filter_bool ) {
			foreach ( $filter_bool as $operator => $operator_filters ) {
				$this->filters_str[] = $this->_create_filter_boolean( $operator_filters, $operator );
			}
		}
		if ( ! empty( $this->filters_str ) ) {
			$filter_arguments = $this->_create_filter_boolean( $this->filters_str, static::FILTER_AND );
		}

		/**
		 *  Sort: https://docs.vespa.ai/en/reference/sorting.html
		 */
		$sorts = [];
		foreach ( $this->sorts ?? [] as $field_name => $order ) {
			$sorts[] = sprintf( '%s %s', $field_name, $order );
		}

		/**
		 * Summary
		 */
		//$this->query['summary'] = 'wpsolr_summary';

		/**
		 * Generate the YQL query
		 */
		$this->query['yql'] = sprintf( 'select %s from %s where %s %s %s',
			implode( ', ', $this->selected_fields ),
			$this->index_label,
			$filter_arguments,
			empty( $sorts ) ? '' : sprintf( 'order by %s', implode( ', ', $sorts ) ),
			$groups ? sprintf( '| all(%s)', implode( ' ', $groups ) ) : ''
		);

		//throw new \Exception(sprintf('<div style="background-color:white;">%s</div>', json_encode($this->query)));


		if ( ! empty( $trace_level = WPSOLR_Service_Container::getOption()->get_search_log_query_trace_level() ) ) {
			$this->query['trace.level'] = (int) $trace_level;
		}

		return $this->query;
	}

	/**
	 * Does index exists ?
	 *
	 * @param $is_throw_error
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function admin_is_index_exists( $is_throw_error = false ) {

		return $this->get_search_index()->has_index();
	}

	/**
	 * @param array|string $data
	 *
	 * @throws \Exception
	 */
	protected function throw_exception_if_error( $data ) {

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
	 * Create a query select.
	 *
	 * @return  array
	 */
	public function search_engine_client_create_query_select() {

		$this->query = [];

		$this->query_string = [];

		return $this->query;
	}

	/**
	 * Set keywords of a query select.
	 *
	 * @param $keywords
	 *
	 * @return string
	 */
	public function search_engine_client_set_query_keywords( $keywords ) {
		$new_keywords = trim( WPSOLR_Regexp::extract_parenthesis( $keywords ) );

		// To prevent Error sent from Vespa: Syntax Error GraphQL request (3:1387) Invalid character escape sequence: \\'.
		$new_keywords = str_replace( "\'", "'", $new_keywords );

		$this->query_string['query'] = ( '*' === $new_keywords ) ? '' : $new_keywords;
	}

	/**
	 * @inheritDoc
	 */
	public function search_engine_client_set_default_operator( $operator = 'AND' ) {
		// No equivallent parameter apparently
	}

	/**
	 * @inheritDoc
	 */
	public function search_engine_client_set_start( $start ) {
		// https://docs.vespa.ai/en/reference/query-language-reference.html#limit-offset
		$this->query['offset'] = $start;
	}

	/**
	 * @inheritDoc
	 */
	public function search_engine_client_set_rows( $rows ) {
		// https://docs.vespa.ai/en/reference/query-language-reference.html#limit-offset
		$this->query['hits'] = $rows;
	}

	/**
	 * @inerhitDoc
	 */
	public function search_engine_client_set_distance( $distance ) {
		$this->distance = $distance;
	}

	/**
	 * @inerhitDoc
	 */
	public function search_engine_client_set_alpha( $alpha ) {
		$this->alpha = $alpha;
	}

	/**
	 * @inerhitDoc
	 */
	public function search_engine_client_set_filter( $filter ) {
		$this->filter = $filter;
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_add_sort( $sort, $sort_by, $args = [] ) {
		if ( empty( $this->sorts[ $this->convert_field_name( $sort ) ] ) ) {
			$this->sorts[ $this->convert_field_name( $sort ) ] = $sort_by;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_set_sort( $sort, $sort_by ) {
		$this->sorts = [];
		$this->search_engine_client_add_sort( $sort, $sort_by );
	}

	/**
	 * @inheritDoc
	 */
	public function search_engine_client_add_filter_term( $filter_name, $field_name, $facet_is_or, $field_value, $filter_tag = '' ) {

		$term = $this->search_engine_client_create_filter_in_terms( $field_name, [ $field_value ] );

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

		$terms = $this->search_engine_client_create_filter_in_terms( $field_name, $field_values );

		$this->_add_filter_query( $field_name, $this->search_engine_client_create_not( $terms ) );
	}

	/**
	 * @param string $field_name
	 * @param string $query
	 *
	 * @return string
	 */
	protected function _add_filter_query( $field_name, $query ) {

		$this->filters_str[] = $query;

		if ( ! is_null( $field_name ) ) {

			if ( ! in_array( $field_name, $this->filtered_fields ) ) {
				$this->filtered_fields[] = $field_name;
			}

			if ( ! in_array( $field_name, $this->excluded_fields ) ) {
				// Field non excluded: can participate to the filters for the secondary query
				$this->filters_excluded_str .= sprintf( ' %s', $query );
			}
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
	 * @inheritDoc
	 */
	public function search_engine_client_add_filter_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->_add_filter_query( $field_name, $this->search_engine_client_create_filter_in_terms( $field_name, $field_values ) );
	}

	/**
	 * @inherit
	 *
	 * https://www.vespa.com/doc/api-reference/api-parameters/filters/
	 *
	 * @return string
	 */
	public function search_engine_client_create_filter_in_terms( $field_name, $field_values ) {

		return $this->_create_filter_terms( $field_name, $field_values, static::FILTER_OR );
	}

	/**
	 *
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $operator 'OR', 'AND'
	 *
	 * @return string
	 */
	protected function _create_filter_terms( $field_name, $field_values, $operator ): string {

		if ( empty( $field_values ) ) {
			return '';
		}

		$results = [];
		foreach ( $field_values as $field_value ) {
			$results[] = $this->_create_filter_term( $field_name, $field_value );
		}

		return $this->_create_filter_boolean( $results, $operator );
	}

	/**
	 * @param string[] $queries
	 * @param string $operator
	 *
	 * @return string
	 */
	protected function _create_filter_boolean( $queries, $operator ) {
		$queries = $this->_remove_empty_queries( $queries );

		if ( static::FILTER_NOT === $operator ) {
			foreach ( $queries as &$query ) {
				$query = sprintf( "%s(%s)", $operator, $query );
			}
		}

		return empty( $queries ) ? '' :
			( 1 === count( $queries ) ? $queries[0] : sprintf( '(%s)', implode( $operator, $queries ) ) );
	}

	/**
	 * Create an individual filter term value, depending on the field's type
	 *
	 * @param string $field_name
	 * @param mixed $field_value
	 * @param string $field_type
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function _create_filter_term( $field_name, $field_value, $field_type = '' ): string {

		if ( empty( $field_value ) ) {
			throw new \Exception( sprintf( 'Empty filter value for %s', esc_html( $field_name ) ) );
		}

		$converted_field_name = $this->convert_field_name( $field_name );

		/**
		 * " and \ must be backlashed: https://docs.vespa.ai/en/reference/query-language-reference.html
		 */
		$field_value = str_replace( '\\', '\\\\', $field_value );
		$field_value = str_replace( '"', '\"', $field_value );

		switch ( $field_type = empty( $field_type ) ? WpSolrSchema::get_custom_field_dynamic_type( $field_name ) : $field_type ) {
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_INTEGER:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_INTEGER_LONG:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_FLOAT:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_FLOAT_DOUBLE:
				$result = sprintf( '%s = %s', $converted_field_name, $field_value );
				break;

			case '':
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_S:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING1:
				$result = sprintf( '%s contains ({stem: false}"%s")', $converted_field_name, $field_value );
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT:
				$result = sprintf( '%s contains "%s"', $converted_field_name, $field_value );
				break;

			default:
				throw new \Exception( sprintf( 'Cannot filter term %s of type %s',
					esc_html( $field_name ), esc_html( $field_type ) ) );
		}


		return sprintf( '(%s)', $result );
	}

	/**
	 * Create a simple filter value
	 *
	 * @param string $converted_field_name
	 * @param string $filter_operator
	 * @param string $filter_value_type
	 * @param string $field_value
	 *
	 * @return string
	 */
	protected function _create_single_filter_value_string( $converted_field_name, $filter_operator, $filter_value_type, $field_value ) {

		// For qraphQL: escape " or error
		$field_value = str_replace( '"', '\"', $field_value );

		return sprintf( self::FILTER_VALUE, $converted_field_name, $filter_operator, $filter_value_type, $field_value );
	}

	/**
	 * Create a simple filter value
	 *
	 * @param string $converted_field_name
	 * @param string $filter_operator
	 * @param string $filter_value_type
	 * @param string $field_value
	 *
	 * @return string
	 */
	protected function _create_single_filter_value_int( $converted_field_name, $filter_operator, $filter_value_type, $field_value ) {
		return sprintf( self::FILTER_VALUE_INT, $converted_field_name, $filter_operator, $filter_value_type, $field_value );
	}

	/**
	 * Create a simple filter value
	 *
	 * @param string $converted_field_name
	 * @param string $filter_operator
	 * @param string $filter_value_type
	 * @param string $field_value
	 *
	 * @return string
	 */
	protected function _create_single_filter_value_float( $converted_field_name, $filter_operator, $filter_value_type, $field_value ) {
		return sprintf( self::FILTER_VALUE_FLOAT, $converted_field_name, $filter_operator, $filter_value_type, $field_value );
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function search_engine_client_create_filter_wildcard( $field_name, $field_value ) {
		// Not used. The "LIKE" search filter is not supported by Vespa.
		return '';
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function search_engine_client_create_filter_wildcard_not( $field_name, $field_value ) {
		// Not used. The "NOT LIKE" search filter is not supported by Vespa.
		return '';
	}

	/**
	 * @inheritdoc
	 *
	 */
	public function search_engine_client_add_filter_in_all_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->_add_filter_query( $field_name, $this->search_engine_client_create_filter_in_all_terms( $field_name, $field_values ) );
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_create_filter_in_all_terms( $field_name, $field_values ) {

		return $this->_create_filter_terms( $field_name, $field_values, static::FILTER_AND );
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function search_engine_client_create_filter_not_in_terms( $field_name, $field_values ) {

		return $this->search_engine_client_create_not( $this->search_engine_client_create_filter_in_terms( $field_name, $field_values ) );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public function search_engine_client_create_filter_lt( $field_name, $field_values ) {

		return $this->_create_filter_range_terms( $field_name, $field_values, '<' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public function search_engine_client_create_filter_lte( $field_name, $field_values ) {

		return $this->_create_filter_range_terms( $field_name, $field_values, '<=' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public function search_engine_client_create_filter_gt( $field_name, $field_values ) {

		return $this->_create_filter_range_terms( $field_name, $field_values, '>' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public function search_engine_client_create_filter_gte( $field_name, $field_values ) {

		return $this->_create_filter_range_terms( $field_name, $field_values, '>=' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function search_engine_client_create_filter_between( $field_name, $field_values ) {

		$field_name = $this->convert_field_name( $field_name );

		return $this->_create_filter_range( '<=', sprintf( 'between %s', $field_name ), $field_name, false, $field_values[0], $field_values[1], false );
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function search_engine_client_create_filter_not_between( $field_name, $field_values ) {

		return $this->search_engine_client_create_not(
			$this->search_engine_client_create_filter_between( $this->convert_field_name( $field_name ), $field_values )
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
	protected function _create_filter_range_terms( $field_name, $field_values, $operator ) {

		$results = [];

		foreach ( $field_values as $field_value ) {
			$results[] = sprintf( '(%s %s %s)', $this->convert_field_name( $field_name ), $operator, $field_value );
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
	public function search_engine_client_create_filter_only_numbers( $field_name ) {
		return $this->search_engine_client_create_not( [ 'regexp' => [ $field_name => '[^0-9]*' ] ] );
	}

	/**
	 * Create a 'empty or absent' filter.
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function search_engine_client_create_filter_no_values( $field_name ) {
		return $this->search_engine_client_create_not( $this->search_engine_client_create_filter_exists( $field_name ) );
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $queries
	 *
	 * @return string
	 */
	public function search_engine_client_create_or( $queries ) {
		return $this->_create_filter_boolean( $queries, static::FILTER_OR );
	}

	/**
	 * Remove empty queries
	 *
	 * @param string[] $queries
	 *
	 * @return string[]
	 */
	public function _remove_empty_queries( $queries ) {

		$results = [];

		foreach ( $queries as $query ) {
			if ( ! empty( trim( $query ) ) ) {
				$results[] = $query;
			}
		}

		return $results;
	}


	/**
	 * @inheritdoc
	 *
	 * @param string $query
	 *
	 * @return string
	 */
	public function search_engine_client_create_not( $query ) {
		return $this->_create_filter_boolean( [ $query ], static::FILTER_NOT );
	}

	/**
	 * @inheritDoc
	 */
	public function search_engine_client_add_filter( $filter_name, $filter ) {
		$this->_add_filter_query( null, $filter );
	}

	/**
	 * @inerhitDoc
	 */
	public function search_engine_client_create_and( $queries ) {
		return $this->_create_filter_boolean( $queries, static::FILTER_AND );
	}

	/**
	 * @inerhitDoc
	 */
	public function search_engine_client_add_filter_empty_or_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		// 'IN' terms
		$in_terms = $this->search_engine_client_create_filter_in_terms( $field_name, $field_values );

		// 'empty': not exists
		$empty = $this->search_engine_client_create_not( $this->search_engine_client_create_filter_exists( $field_name ) );

		// 'empty' OR 'IN'
		$this->_add_filter_query(
			$field_name, $this->search_engine_client_create_or(
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
	public function search_engine_client_add_filter_exists( $filter_name, $field_name ) {

		// Add 'exists'
		$this->_add_filter_query( $field_name, $this->search_engine_client_create_filter_exists( $field_name ) );

	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_create_filter_exists( $field_name ) {

		return $this->search_engine_client_create_filter_not_in_terms( $field_name, [ static::FIELD_VALUE_UNDEFINED ] );
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

		// https://www.vespa.com/doc/api-reference/api-parameters/attributesToHighlight/

		$this->search_parameters['highlightPreTag']  = $prefix;
		$this->search_parameters['highlightPostTag'] = $postfix;

		$field_snippets = [];
		foreach ( $field_names as $field_name ) {
			$field_snippets[] = sprintf( '%s:%s', $field_name, $fragment_size );
		}
		$this->search_parameters['attributesToHighlight'] = $field_snippets;

	}

	/**
	 * @inheritDoc
	 *
	 */
	protected
	function &get_or_create_facets_field(
		$facet_name
	) {

		$facet_name = $this->_convert_field_name_if_date( $facet_name );
		$this->add_attribute_for_faceting( $facet_name );

		return $facet_name;
	}


	/**
	 * @inheritDoc
	 */
	public
	function search_engine_client_set_facets_min_count(
		$facet_name, $min_count
	) {
		// Not implemented in vespa
		$this->get_or_create_facets_field( $facet_name );
	}

	/**
	 * Create a facet field.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 * @return void
	 * @internal param $exclusion
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
		$this->facets_fields[ $facet_name ] = $limit;
	}

	/**
	 * @inheritDoc
	 */
	public
	function search_engine_client_set_facet_sort_alphabetical(
		$facet_name
	) {
		// https://docs.vespa.ai/en/grouping.html#ordering-groups
		$this->search_parameters['sortFacetValuesBy'][ $facet_name ] = 'alpha';
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

		if ( ! in_array( $facet_name, $this->excluded_fields ) ) {
			$this->excluded_fields[] = $facet_name;
		}

	}

	/**
	 * @inheritDoc
	 */
	public function search_engine_client_set_fields( $fields ) {
		$this->selected_fields = [];

		//if ( ! empty( WPSOLR_Service_Container::getOption()->get_search_max_length_highlighting() ) ) {
		if ( true ) {
			// We add WpSolrSchema::_FIELD_NAME_CONTENT for highlitings
			$fields = array_merge( $fields, [ WpSolrSchema::_FIELD_NAME_CONTENT ] );
		}

		foreach ( $fields as $field_name ) {
			if ( false === strpos( $field_name, '*' ) ) {
				$this->selected_fields[] = $this->convert_field_name( $field_name );
			}
		}
	}

	/**
	 * Get suggestions for did you mean.
	 *
	 * @param string $keywords
	 *
	 * @return string Did you mean keyword
	 */
	public function search_engine_client_get_did_you_mean_suggestions( $keywords ) {

		$this->is_did_you_mean = true;

		$results = $this->search_engine_client_execute( $this->search_engine_client, null );

		$suggestions = $results->get_suggestions();

		return ! empty( $suggestions ) ? $suggestions[0]['text'] : '';
	}


	/**
	 * https://www.vespa.com/doc/guides/managing-results/relevance-overview/in-depth/ranking-criteria/#geo-if-applicable
	 *
	 * @inheritDoc
	 */
	public function search_engine_client_add_sort_geolocation_distance( $field_name, $geo_latitude, $geo_longitude ) {
		// Automatically sorted with geo distance if geo filter is used. Nothing to do here.
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
	public function get_named_geodistance_query_for_field( $field_prefix, $field_name, $geo_latitude, $geo_longitude ) {

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
	public function search_engine_client_set_query_fields( array $query_fields ) {
		$this->query_string['fields'] = $query_fields;
	}

	/**
	 * Set boosts field values.
	 *
	 * @param array $boost_field_values
	 */
	public function search_engine_client_set_boost_field_values( $boost_field_values ) {
		// Store it. Will be added to the query later.

		// Add 'OR' condition, else empty results if boost value is not found.
		$this->boost_field_values = sprintf( ' OR (%s) ', $boost_field_values );
	}


	/**
	 * https://docs.vespa.ai/en/grouping.html#range-grouping
	 *
	 * Get facet ranges
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
		$ranges[] = sprintf( "bucket(-inf, %s)", $range_start );

		// No gap parameter. We build the ranges manually.
		foreach ( range( $range_start, $range_end, $range_gap ) as $start ) {
			if ( $start < $range_end ) {
				$ranges[] = sprintf( "bucket(%s, %s)", $start, $start + $range_gap );
			}
		}

		// Add a range for values after end
		$ranges[] = sprintf( "bucket(%s, inf)", $range_end );

		$this->facets_ranges[ $facet_name ] = $ranges;

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
	public function search_engine_client_add_facet_range_regular( $facet_name, $field_name, $range_start, $range_end, $range_gap ) {

		$this->get_or_create_facets_range( $field_name, $range_start, $range_end, $range_gap );
	}

	/**
	 * Get facet grouped by.
	 *
	 * @param string $facet_name
	 * @param int $size
	 *
	 * @return array
	 * @link https://www.vespa.com/doc/api-reference/api-parameters/distinct/
	 *
	 */
	protected
	function get_or_create_distinct(
		$facet_name, $size
	) {
		// $facet_name is already selected at indexing time in the index settings
		$this->search_parameters['distinct'] = $size;
	}

	/**
	 * @@inheritdoc
	 */
	public function search_engine_client_add_facet_top_hits( $facet_name, $size ) {

		$this->get_or_create_distinct( $facet_name, $size );
	}

	/**
	 * Add a filter.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param bool $facet_is_or
	 * @param string[] $filter
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $filter, $filter_tag = '' ) {

		if ( ! isset( $this->filters_bool[ $field_name ][ $facet_is_or ? static::FILTER_OR : static::FILTER_AND ] ) ||
		     ! in_array( $filter, $this->filters_bool[ $field_name ][ $facet_is_or ? static::FILTER_OR : static::FILTER_AND ] ) ) {
			$this->filters_bool[ $field_name ][ $facet_is_or ? static::FILTER_OR : static::FILTER_AND ][] = $filter;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_add_filter_range_upper_strict( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' ) {

		$field_name = $this->convert_field_name( $field_name );
		if ( $range_start === $range_end ) {

			$this->_add_filter_range( '<=', $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' );

		} else {

			$this->_add_filter_range( '<', $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_add_filter_range_upper_included( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' ) {

		$field_name = $this->convert_field_name( $field_name );
		$this->_add_filter_range( '<=', $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' );
	}

	/**
	 *
	 */
	public function _add_filter_range( $upper_operation, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' ) {

		$range = $this->_create_filter_range( $upper_operation, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' );

		$this->search_engine_client_add_filter_any( $filter_name,
			$field_name,
			$facet_is_or,
			$range,
			$filter_tag );

	}

	/**
	 *
	 */
	public function _create_filter_range( $upper_operation, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' ) {

		$range_start = $this->_convert_to_unix_time_if_date( $range_start );
		$range_end   = $this->_convert_to_unix_time_if_date( $range_end );

		if ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_DATE === WpSolrSchema::get_custom_field_dynamic_type( $field_name ) ) {
			/**
			 * Convert ms slider dates to Vespa sec
			 */
			if ( is_numeric( $range_start ) ) {
				$range_start = intval( $range_start / 1000 );
			}
			if ( is_numeric( $range_end ) ) {
				$range_end = intval( $range_end / 1000 );
			}
		}

		//$field_name = $this->_convert_field_name_if_date( $field_name );

		$range_values = [];
		if ( ( '*' !== $range_start ) && ( '*' !== $range_end ) ) {
			/*
			 * range(year, 2000, 2018) is inclusive left and right
			 * ({bounds:"leftOpen"}range(year, 2000, 2018))
			 * ({bounds:"rightOpen"}range(year, 2000, 2018))
			 * ({bounds:"open"}range(year, 2000, 2018))
			 */
			$range_values[] = sprintf( '(%srange(%s, %s, %s))', ( '<' === $upper_operation ) ? '{bounds:"rightOpen"}' : '', $field_name, $range_start, $range_end );

		} elseif ( '*' !== $range_start ) {

			$range_values[] = sprintf( '%s >= %s', $field_name, $range_start );

		} elseif ( '*' !== $range_end ) {
			$range_values[] = sprintf( '%s %s %s', $field_name, $upper_operation, $range_end );
		}

		$range = sprintf( '(%s)', implode( ' and ', $range_values ) );

		return $range;
	}

	/**
	 * Add decay functions to the search query
	 *
	 * @param array $decays
	 *
	 */
	public function search_engine_client_add_decay_functions( array $decays ) {
	}

	/**
	 * Add a geo distance filter.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 */
	public function search_engine_client_add_filter_geolocation_distance( $field_name, $geo_latitude, $geo_longitude, $distance ) {

		// https://www.vespa.com/doc/guides/managing-results/refine-results/geolocation/how-to/filter-results-around-a-location/
		// https://www.vespa.com/doc/api-reference/api-parameters/aroundRadius/

		$this->search_parameters['aroundRadius'] = empty( $distance ) ? 'All' : 1000 * $distance; // convert distance in meters
		$this->search_parameters['aroundLatLng'] = sprintf( '%s, %s', $geo_latitude, $geo_longitude );
	}

	/**
	 * @inerhitDoc
	 */
	public function search_engine_client_add_facet_stats( $field_name, $exclude ) {
		if ( ! in_array( $field_name, $this->query_facets_type_stats ) ) {
			// For the query
			$this->query_facets_type_stats[] = $field_name;

			if ( ! in_array( $field_name, $this->_get_index_fields_in_settings() ) ) {
				// For the index settings
				$this->index_facets[] = $field_name;
			}
		}
	}


	/**
	 * Build the outer aggs from its inner content
	 *
	 * @param array $inner_aggs
	 *
	 * @return array
	 */
	protected function _create_outer_aggs( $inner_aggs ) {
		return [
			'filter' => [
				'match_all' => new \stdClass(),
			],
			'aggs'   => $inner_aggs,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function property_exists( $document, $field_name ) {
		return property_exists( $document, $field_name );
	}

	/**
	 * Add field as a 'attributesForFaceting' settings
	 * https://www.vespa.com/doc/api-reference/api-parameters/attributesForFaceting/
	 *
	 * @param $field_name
	 */
	protected function add_attribute_for_faceting( $field_name ) {

		if ( ! in_array( $field_name, $this->query_facets_type_field ) ) {
			// For the query
			$this->query_facets_type_field[] = $field_name;

			if ( ! in_array( $field_name, $this->_get_index_fields_in_settings() ) ) {
				// For the index settings
				$this->index_facets[] = $field_name;
			}
		}
	}

	/**
	 * Get the fields already set on the index settings
	 *
	 * @return array
	 */
	protected function _get_index_fields_in_settings() {

		$index_name = $this->get_search_index()->get_index_label();

		// In cache?
		if ( isset( static::$fields_in_settings[ $index_name ] ) ) {
			return static::$fields_in_settings[ $index_name ];
		}

		// Retrieve it and put it in cache
		$fields_in_settings = WPSOLR_Service_Container::getOption()->get_option_index_filtered_fields();

		static::$fields_in_settings[ $index_name ] = empty( $fields_in_settings[ $index_name ] ) ? [] : $fields_in_settings[ $index_name ];

		return static::$fields_in_settings[ $index_name ];
	}

	/**
	 * Add a disjontive query to exclude facets
	 *
	 * @param array $query
	 */
	protected function add_disjonctive_query( $query ) {

		$result = [];

		if ( ! empty( $this->excluded_fields ) ) {

			// Add all filtered field as filterOnly facets
			// Only excluded fields are normal facets and returned in results
			$facets = [];
			foreach ( $this->filtered_fields as $filtered_field ) {
				$facets[] = in_array( $filtered_field, $this->excluded_fields ) ? $filtered_field : sprintf( 'filterOnly(%s)', $filtered_field );
			}

			$result = [
				'facets'                => $facets,

				// Those parameters are set to minimize the work of the engine. We don't
				// care about the results, we only care about the list of facets.
				'hitsPerPage'           => 0,
				'page'                  => 0,
				'attributesToRetrieve'  => [],
				'attributesToHighlight' => [],
				'attributesToSnippet'   => [],
				'analytics'             => false,
				'clickAnalytics'        => false,
			];

			// Remove the filter
			if ( ! empty( $filters_excluded_str = $this->_fix_filters_syntax( $this->filters_excluded_str ) ) ) {
				$result['filters'] = $filters_excluded_str;
			}


		}

		return $result;
	}

	/**
	 * Fix the filters syntax
	 *
	 * @param string $filters_str
	 *
	 * @return string
	 */
	protected function _fix_filters_syntax( $filters_str ) {

		$filters_str = str_replace( ') (', ')(', $filters_str );
		$filters_str = str_replace( ')(', ') AND (', $filters_str );

		return trim( $filters_str );
	}

	/**
	 * Generate boolean filters
	 *
	 * @param string $filters_str
	 *
	 * @return string
	 */
	protected function _generate_bool_filters( $filters_str ) {

		$filters[] = $filters_str;

		foreach ( $this->filters_bool as $field_name => $filter_bool ) {
			$filter_bool_query_str = '';
			foreach ( $filter_bool as $bool => $bool_filters ) {
				$filter_bool_query_str .= sprintf( self::FILTER_BOOLEAN, $bool, implode( ', ', $bool_filters ) );
			}

			if ( ! empty( $filter_bool_query_str ) ) {
				$filters[] = $filter_bool_query_str;

				// Add to the filtered query
				//$this->_add_filter_query( $field_name, $filter_bool_query_str );
			}

		}

		return ( 1 === count( $filters ) ) ? $filters[0] : sprintf( self::FILTER_BOOLEAN, static::FILTER_AND, implode( ', ', $filters ) );
	}

	/**
	 * @param string $facet_type *
	 *
	 * @inheritDoc
	 */
	protected function resort_numeric_by_alphabetical_order( $facet_name, array $facet_values, string $facet_type ) {
		if ( ! empty( $facet_values ) && $this->is_facet_sorted_alphabetically( $facet_name )
		     && WpSolrSchema::get_custom_field_is_numeric_type( $facet_name ) ) {
			ksort( $facet_values );
		}

		return $facet_values;
	}

	/**
	 * @inheritDoc
	 */
	protected function _log_query_as_string() {
		return json_encode( $this->query );
	}

	/**
	 * @return QueryBuilder
	 */
	protected function _get_gql_query_builder_search_index(): QueryBuilder {
		if ( ! isset( $this->query_builder_search_index ) ) {

			// Create search query
			$this->query_builder_search = $this->_gql_new_query_get( static::$alias_get );

			// Add index query to search query
			$this->query_builder_search_index = $this->_gql_new_query_index();
			$this->query_builder_search->selectField( $this->query_builder_search_index );

			// Add search query to root query
			$this->_get_gql_query_builder_root()->selectField( $this->query_builder_search );
		}


		return $this->query_builder_search_index;
	}

	/**
	 * Top level GraphQL query containing at least a Get query and several Aggregate queries
	 * @return QueryBuilder
	 */
	protected function _get_gql_query_builder_root(): QueryBuilder {
		return $this->query_builder_root ?? ( $this->query_builder_root = $this->_gql_new_query( '', 'results' ) );
	}

	/**
	 * @param $name
	 *
	 * @return QueryBuilder
	 * @throws Exception
	 */
	protected function _add_gql_query_builder_aggregation( $name, $field_name, $aggregation_type ): QueryBuilder {

		// Create aggregation query
		$query_builder_aggregation = $this->_gql_new_query_aggregate( $name );

		// Add index query to aggregation query
		$query_builder_index = $this->_gql_new_query_index();


		/**
		 * Add the specific aggregation type query
		 */
		switch ( $aggregation_type ) {
			case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD:

				$query_builder_index->selectField(
					$this->_gql_new_query( 'meta' )->selectField( 'count' )
				);

				if ( ! empty( $field_name ) ) {
					$query_builder_index->setArgument( 'groupBy', $this->_gql_new_argument_group_by_value_string( $field_name ) );
					$query_builder_index->selectField(
						$this->_gql_new_query( 'groupedBy' )->selectField( 'value' )
					);
				}
				break;

			case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX:
				$new_query = $this->_gql_new_query( $field_name );
				$new_query->selectField( 'minimum' );
				$new_query->selectField( 'maximum' );
				$query_builder_index->selectField( $new_query );
				break;

			default:
				throw new \Exception( sprintf( "Undefined aggregation type %s", esc_html( $aggregation_type ) ) );
		}

		// Add field aggregation to the query
		$query_builder_aggregation->selectField( $query_builder_index );

		// Add aggregation query to root query
		$this->_get_gql_query_builder_root()->selectField( $query_builder_aggregation );

		return $query_builder_index;
	}

	/**
	 * Build an aggregation query for a count
	 *
	 * @param $field_name
	 *
	 * @return QueryBuilder
	 * @throws Exception
	 */
	protected function _add_gql_query_builder_aggregation_type_count( $field_name ): QueryBuilder {

		return $this->_add_gql_query_builder_aggregation(
			$field_name,
			'',
			WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD
		);
	}

	/**
	 * Build an aggregation query for a facet
	 *
	 * @param $field_name
	 *
	 * @return QueryBuilder
	 * @throws Exception
	 */
	protected function _add_gql_query_builder_aggregation_type_field( $field_name ): QueryBuilder {

		$field_name_converted_str = $this->convert_field_name( $field_name );

		return $this->_add_gql_query_builder_aggregation(
			sprintf( '%s%s', static::$alias_aggregate_type_field_prefix, $field_name_converted_str ),
			$field_name_converted_str,
			WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD
		);
	}

	/**
	 * Build an aggregation min/max query for a facet
	 *
	 * @param $field_name
	 *
	 * @return QueryBuilder
	 * @throws Exception
	 */
	protected function _add_gql_query_builder_aggregation_type_stats( $field_name ): QueryBuilder {

		$field_name_converted_str = $this->convert_field_name( $field_name );

		return $this->_add_gql_query_builder_aggregation(
			sprintf( '%s%s', static::$alias_aggregate_type_stats_prefix, $field_name_converted_str ),
			$field_name_converted_str,
			WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX
		);
	}

	/**
	 * Set arguments to a query builder
	 *
	 * @param QueryBuilder $query_builder
	 * @param array $arguments
	 */
	protected function _set_gql_query_builder_arguments( QueryBuilder $query_builder, array $arguments ) {
		foreach ( $arguments as $argument_name => $argument_value ) {
			if ( ! empty( $argument_value ) ) {
				$query_builder->setArgument( $argument_name, $argument_value );
			}
		}
	}

}
