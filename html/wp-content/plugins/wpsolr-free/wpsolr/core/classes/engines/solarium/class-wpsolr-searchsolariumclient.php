<?php

namespace wpsolr\core\classes\engines\solarium;

use Exception;
use Solarium\Component\Facet\FacetInterface;
use Solarium\Component\Facet\Field;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;
use wpsolr\core\classes\engines\solarium\admin\WPSOLR_Solr_Admin_Api_Core;
use wpsolr\core\classes\engines\WPSOLR_AbstractResultsClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_SearchSolariumClient
 *
 */
class WPSOLR_SearchSolariumClient extends WPSOLR_AbstractSearchClient {
	use WPSOLR_SolariumClient;

	const IS_LOG_QUERY_TIME_IMPLEMENTED = true;

	// Multi-value sort
	const SORT_MULTIVALUE_FIELD = 'field(%s,%s)';

	// Constants for filter patterns
	const FILTER_PATTERN_EMPTY_OR_IN = '(*:* -%s:[* TO *]) OR %s:(%s)';
	const FILTER_PATTERN_EXISTS = '%s:*';

	// Template for the geolocation distance field(s)
	const TEMPLATE_NAMED_GEODISTANCE_QUERY_FOR_FIELD = '%s%s:%s';

	// Function to calculate distance
	const GEO_DISTANCE = 'geodist()';

	// Filter range Solr syntax
	const SOLR_FILTER_RANGE_UPPER_STRICT = '%s:[%s TO %s}';
	const SOLR_FILTER_RANGE_UPPER_INCLUDED = '%s:[%s TO %s]';

	// Template for the geolocation distance sort field(s)
	const TEMPLATE_ANONYMOUS_GEODISTANCE_QUERY_FOR_FIELD = 'geodist(%s,%s,%s)'; // geodist between field and 'lat,long'

	/* @var string[] $filter_queries_or */
	protected $filter_queries_or;

	/* @var Query */
	protected $query_select;

	/* @var \Solarium\Client */
	protected $search_engine_client;

	/** @var array */
	protected $sorts = [];

	/**
	 * Prepare query execute
	 */
	public function search_engine_client_pre_execute() {

		if ( ! empty( $this->filter_queries_or ) ) {

			foreach ( $this->filter_queries_or as $field_name => $filter_query_or ) {

				$this->query_select->createFilterQuery( $field_name )->setQuery( $filter_query_or['query'] )->setTags( is_array( $filter_query_or['tag'] ) ? $filter_query_or['tag'] : [ $filter_query_or['tag'] ] );
			}

			// Used: clear it.
			$this->filter_queries_or = [];
		}

	}


	/**
	 * Does index exists ?
	 *
	 * @param bool $is_throw_error
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function admin_is_index_exists( $is_throw_error = false ) {
		$result = true;

		try {

			$core_admin_api = WPSOLR_Hosting_Api_Abstract::new_solr_admin_api_by_id( $this->config['extra_parameters']['index_hosting_api_id'], $this->config, $this->search_engine_client );

			if ( WPSOLR_Solr_Admin_Api_Core::class === get_class( $core_admin_api ) ) {

				$this->search_engine_client->ping( $this->search_engine_client->createPing() );

			} else {

				$core_admin_api->ping();
			}

		} catch ( Exception $e ) {

			if ( $is_throw_error ) {
				throw $e;
			}

			$result = false;
		}

		return $result;
	}

	/**
	 * Create the index
	 *
	 * @param array $index_parameters
	 *
	 * @throws Exception
	 */
	protected function admin_create_index( &$index_parameters ) {

		$core_admin_api = WPSOLR_Hosting_Api_Abstract::new_solr_admin_api_by_id( $this->config['extra_parameters']['index_hosting_api_id'], $this->config, $this->search_engine_client );

		if ( self::ENGINE_SOLR_CLOUD === $this->config['index_engine'] ) {

			$core_admin_api->create_solrcloud_index( $this->config['extra_parameters'][ self::ENGINE_SOLR_CLOUD ], $index_parameters );

		} else {

			$core_admin_api->create_solr_index( $index_parameters );
		}
	}

	/**
	 * Delete the index
	 * @throws Exception
	 */
	public function admin_delete_index() {

		$core_admin_api = WPSOLR_Hosting_Api_Abstract::new_solr_admin_api_by_id( $this->config['extra_parameters']['index_hosting_api_id'], $this->config, $this->search_engine_client );

		if ( self::ENGINE_SOLR_CLOUD === $this->config['index_engine'] ) {

			$core_admin_api->delete_solrcloud_index();

		} else {

			$core_admin_api->delete_solr_index();
		}
	}

	/**
	 * Add a configuration to the index if missing.
	 * @throws Exception
	 */
	protected function admin_index_update( &$index_parameters ) {

		//$solr_admin_api_schema = new WPSOLR_Solr_Admin_Api_Schema( $this->search_engine_client );
		//$solr_admin_api_schema->update_schema();

		//$solr_admin_api_config = new WPSOLR_Solr_Admin_Api_Config( $this->search_engine_client );
		//$solr_admin_api_config->update_config();

		$core_admin_api = WPSOLR_Hosting_Api_Abstract::new_solr_admin_api_by_id( $this->config['extra_parameters']['index_hosting_api_id'], $this->config, $this->search_engine_client );

		$core_admin_api->admin_index_update( $index_parameters );

	}

	/**
	 * Create a select query.
	 *
	 * @return Query
	 */
	public function search_engine_client_create_query_select() {
		// get a select query instance

		if ( $this->_get_is_more_like_this() ) {
			$object_id      = $this->get_wpsolr_query()->get_wpsolr_ajax_context()['object_id'];
			$recommendation = $this->get_recommendation();
			if ( ! ( $object_id && ( $post = get_post( $object_id ) ) ) ) {
				throw new \Exception( sprintf( 'Model "%s" requires post_id.',
					esc_html( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] ) ) );
			}

			$query = $this->search_engine_client->createMoreLikeThis();
			$query->setQuery( sprintf( 'id:%s', $object_id ) )
			      ->setMltFields( [ 'title', 'text' ] )
			      ->setMinimumDocumentFrequency( 1 )
			      ->setMinimumTermFrequency( 1 )
			      ->setMatchInclude( true )
			      ->setBoost( true )
			      ->setInterestingTerms( 'details' );

		} else {
			$query   = $this->search_engine_client->createSelect();// this query is now a dismax query
			$edismax = $query->getEDisMax();
		}

		return $query;
	}

	/**
	 * @inheridoc
	 */
	public function search_engine_client_escape_keywords( $keywords ) {
		// return $this->query_select->getHelper()->escapePhrase( $keywords );
		return $keywords; // Nor escape else the edismax syntax does not work (NOT yellow, AND, OR)
	}

	/**
	 * @inheridoc
	 */
	public function search_engine_client_escape_double_quoted_keywords( $keywords ) {
		// Exact Solr syntax between double quotes
		return $this->query_select->getHelper()->escapePhrase( $keywords );
	}

	/**
	 * Set keywords of a query select.
	 *
	 * @param $keywords
	 *
	 * @return string
	 */
	public function search_engine_client_set_query_keywords( $keywords ) {
		if ( $this->_get_is_more_like_this() ) {
			return;
		}

		if ( ! empty( $this->query_select->getEDisMax()->getQueryFields() ) ) {
			// Syntax (my keyword) does not work for str fields. Need syntax "my keyword" also.
			$keywords_transformed = WPSOLR_Regexp::extract_parenthesis( $keywords ); // my keyword
			$keywords             = sprintf( '("%s" OR %s)', $keywords_transformed, $keywords ); // ("my keyword" OR (my keyword))
		}

		$this->query_select->setQuery( $keywords );
	}

	/**
	 * Set query's default operator.
	 *
	 * @param string $operator
	 */
	public function search_engine_client_set_default_operator( $operator = 'AND' ) {
		$this->query_select->setQueryDefaultOperator( $operator );
	}

	/**
	 * Set query's start.
	 *
	 * @param int $start
	 */
	public function search_engine_client_set_start( $start ) {
		$this->query_select->setStart( $start );
	}

	/**
	 * Set query's rows.
	 *
	 * @param int $rows
	 */
	public function search_engine_client_set_rows( $rows ) {
		$this->query_select->setRows( $rows );
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_add_sort( $sort, $sort_by, $args = [] ) {

		if ( WPSOLR_Service_Container::getOption()->get_sortby_is_field_multivalue( $sort ) ) {
			// Use field(_, min|max) to be able to sort multi-value fields: https://cwiki.apache.org/confluence/display/solr/Function+Queries#FunctionQueries-field

			$this->query_select->addSort( sprintf( self::SORT_MULTIVALUE_FIELD, $sort, Query::SORT_ASC === $sort_by ? 'min' : 'max' ), $sort_by );

		} else {
			// Standard sort on single-value field

			if ( ! isset( $this->sorts[ $sort ] ) ) {
				// Solarium addSort() does "add", it replaces. Prevent secondary sort with same field replacing the primary sort.

				$this->sorts[ $sort ] = true;
				$this->query_select->addSort( $sort, $sort_by );
			}

		}
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_set_sort( $sort, $sort_by ) {

		if ( WPSOLR_Service_Container::getOption()->get_sortby_is_field_multivalue( $sort ) ) {
			// Use field(_, min|max) to be able to sort multi-value fields: https://cwiki.apache.org/confluence/display/solr/Function+Queries#FunctionQueries-field

			$this->query_select->setSorts( [ sprintf( self::SORT_MULTIVALUE_FIELD, $sort, Query::SORT_ASC === $sort_by ? 'min' : 'max' ) => $sort_by ] );

		} else {
			// Standard sort on single-value field

			// Solarium addSort() does "add", it replaces. Prevent secondary sort with same field replacing the primary sort.

			$this->sorts[ $sort ] = true;
			$this->query_select->setSorts( [ $sort => $sort_by ] );

		}
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_add_sort_random( $seed ) {
		// https://stackoverflow.com/questions/25234102/solr-return-random-results-sort-by-random

		$this->query_select->addSort( 'random_' . $seed, Query::SORT_ASC );
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
	public function search_engine_client_add_filter_term( $filter_name, $field_name, $facet_is_or, $field_value, $filter_tag = '' ) {

		$field_value = $this->_escape_term( $field_value );

		if ( ( strpos( $field_value, ':(', 0 ) === false ) ) {
			// In case the facet contains white space, we enclose it with "".
			// Enclosing parenthesis should not be escaped (contain more complex filter term like A OR B)
			$field_value_escaped = "\"$field_value\"";
		} else {
			$field_value_escaped = $field_value;
		}

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, "$field_name:$field_value_escaped", $filter_tag );
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
	public function search_engine_client_add_filter_not_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( '-%s:(%s)', $field_name, '"' . implode( '" OR "', $field_values ) . '"' ),
			]
		);

	}

	/**
	 * @inheritdoc
	 */
	public
	function search_engine_client_add_filter_not_in_terms_of_other_sites(
		$filter_name, $field_name, $field_values, $site_id
	) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( 'NOT (-%s:("%s") AND %s:(%s))', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $site_id, $field_name, '"' . implode( '" OR "', $field_values ) . '"' ),
			]
		);

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
	public function search_engine_client_add_filter_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->query_select->addFilterQuery(
			$this->search_engine_client_create_filter_in_terms( $field_name, $field_values )->setKey( $filter_name )->setTags( [ $filter_tag ] )
		);

	}

	/**
	 * Create a 'OR' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_in_terms( $field_name, $field_values ) {

		$field_values = $this->_escape_terms( $field_values );

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '%s:(%s)', $field_name, '"' . implode( '" OR "', $field_values ) . '"' ),
			]
		);
	}

	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_wildcard( $field_name, $field_value ) {

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '%s:(%s)', $field_name, $field_value ),
			]
		);

	}

	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_wildcard_not( $field_name, $field_value ) {

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(*:* NOT (%s:(%s)))', $field_name, $field_value ),
			]
		);

	}

	/**
	 * Create a 'NOR' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_not_in_terms( $field_name, $field_values ) {

		$field_values = $this->_escape_terms( $field_values );

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '-%s:(%s)', $field_name, implode( ' OR ', $field_values ) ),
			]
		);
	}

	/**
	 * @inheritdoc
	 *
	 */
	public function search_engine_client_create_filter_in_all_terms( $field_name, $field_values ) {

		$field_values = $this->_escape_terms( $field_values );

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '%s:(%s)', $field_name, '"' . implode( '" AND "', $field_values ) . '"' ),
			]
		);
	}

	/**
	 * @inheritdoc
	 *
	 */
	public function search_engine_client_add_filter_in_all_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->query_select->addFilterQuery(
			$this->search_engine_client_create_filter_in_all_terms( $field_name, $field_values )->setKey( $filter_name )->setTags( [ $filter_tag ] )
		);
	}


	/**
	 * Create a 'only numbers' filter.
	 *
	 * @param string $field_name
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_only_numbers( $field_name ) {
		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(*:* -%s:[a TO z])', $field_name ),
			]
		);
	}

	/**
	 * Create a 'empty or absent' filter.
	 *
	 * @param string $field_name
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_no_values( $field_name ) {
		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(*:* -%s:*)', $field_name ),
			]
		);
	}

	/**
	 * Create a 'OR' from filters.
	 *
	 * @param FilterQuery[] $queries
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_or( $queries ) {

		$query_texts = [];
		foreach ( $queries as $query ) {
			$query_texts[] = sprintf( '(%s)', $query->getQuery() );
		}

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(%s)', implode( ' OR ', $query_texts ) ),
			]
		);
	}

	/**
	 * Add a filter
	 *
	 * @param string $filter_name
	 * @param FilterQuery $filter
	 */
	public function search_engine_client_add_filter( $filter_name, $filter ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => $filter->getQuery(),
			]
		);
	}

	/**
	 * Create a 'AND' from filters.
	 *
	 * @param FilterQuery[] $queries
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_and( $queries ) {

		$query_texts = [];
		foreach ( $queries as $query ) {
			$query_texts[] = sprintf( '%s', $query->getQuery() );
		}

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( ( count( $query_texts ) <= 1 ) ? '%s' : '(%s)', implode( ' AND ', $query_texts ) ),
			]
		);
	}

	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_not( $query ) {

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(*:* NOT (%s))', $query->getQuery() ),
			]
		);
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
	public function search_engine_client_add_filter_empty_or_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( self::FILTER_PATTERN_EMPTY_OR_IN, $field_name, $field_name, implode( ' OR ', $field_values ) ),
			]
		);
	}

	/**
	 * Create a filter on: empty or in terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery|array
	 */
	public function search_engine_client_create_filter_empty_or_in_terms( $field_name, $field_values ) {

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( self::FILTER_PATTERN_EMPTY_OR_IN, $field_name, $field_name, implode( ' OR ', $field_values ) ),
			]
		);
	}

	/**
	 * @inheridoc
	 */
	public function search_engine_client_create_or_betwwen_not_and_in_terms( $field_names, $field_values ) {
		return $this->search_engine_client_create_or(
			[
				$this->search_engine_client_create_filter_not_in_terms(
					$field_names[0],
					$field_values[0]
				),
				$this->search_engine_client_create_filter_empty_or_in_terms(
					$field_names[1],
					$field_values[1]
				)
			] );
	}

	/**
	 * Filter fields with values
	 *
	 * @param $filter_name
	 * @param $field_name
	 */
	public function search_engine_client_add_filter_exists( $filter_name, $field_name ) {

		$filter_query = $this->search_engine_client_create_filter_exists( $field_name );
		$filter_query->setKey( $filter_name );

		$this->query_select->addFilterQuery( $filter_query );
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_create_filter_exists( $field_name ) {
		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( self::FILTER_PATTERN_EXISTS, $field_name ),
			]
		);
	}

	/**
	 * Set highlighting.
	 *
	 * @param string[] $field_names
	 * @param string $prefix
	 * @param string $postfix
	 * @param int $fragment_size
	 */
	public function search_engine_client_set_highlighting( $field_names, $prefix, $postfix, $fragment_size ) {

		$highlighting = $this->query_select->getHighlighting();

		foreach ( $field_names as $field_name ) {

			$highlighting->getField( $field_name )->setSimplePrefix( $prefix )->setSimplePostfix( $postfix );

			// Max size of each highlighting fragment for post content
			$highlighting->getField( $field_name )->setFragSize( $fragment_size );
		}

	}


	/**
	 * Set minimum count of facet items to retrieve a facet.
	 *
	 * @param $facet_name
	 * @param $min_count
	 */
	public function search_engine_client_set_facets_min_count( $facet_name, $min_count ) {

		// Only display facets that contain data
		$this->query_select->getFacetSet()->setMinCount( $min_count );
	}

	/**
	 * Create a facet field.
	 *
	 * @param $facet_name
	 * @param $field_name
	 */
	public function search_engine_client_add_facet_field( $facet_name, $field_name ) {

		$this->query_select->getFacetSet()->createFacetField( "$facet_name" )->setField( "$field_name" );
	}

	/**
	 * Set facets limit.
	 *
	 * @param $facet_name
	 * @param int $limit
	 */
	public function search_engine_client_set_facets_limit( $facet_name, $limit ) {

		$this->query_select->getFacetSet()->setLimit( $limit );
	}

	/**
	 * @param string $facet_name
	 *
	 * @return null|FacetInterface
	 */
	protected function get_facet( $facet_name ) {

		$facets = $this->query_select->getFacetSet()->getFacets();

		if ( ! empty( $facets[ $facet_name ] ) ) {
			return $facets[ $facet_name ];
		}

		return null;
	}

	/**
	 * Sort a facet field alphabetically.
	 *
	 * @param $facet_name
	 */
	public function search_engine_client_set_facet_sort_alphabetical( $facet_name ) {

		/** @var Field $facet */
		$facet = $this->get_facet( $facet_name );

		if ( $facet ) {
			$facet->setSort( self::PARAMETER_FACET_SORT_ALPHABETICALLY );
		}

	}

	/**
	 * Set facet field excludes.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 */
	public function search_engine_client_set_facet_excludes( $facet_name, $exclude ) {

		/** @var Field $facet */
		$facet = $this->get_facet( $facet_name );

		if ( $facet ) {
			$facet->getLocalParameters()->setExcludes( [ sprintf( self::FILTER_QUERY_TAG_FACET_EXCLUSION, $exclude ) ] );
		}

	}

	/**
	 * Set the fields to be returned by the query.
	 *
	 * @param array $fields
	 */
	public function search_engine_client_set_fields( $fields ) {
		$this->query_select->setFields( $fields );
	}

	/**
	 * Get suggestions from the engine.
	 *
	 * @inheritDoc
	 *
	 * @return WPSOLR_ResultsSolariumClient
	 */
	public function search_engine_client_get_suggestions_keywords( $suggestion, $query, $contexts, $is_error = false ) {

		if ( ! empty( $contexts ) && ( $this->is_galaxy_slave || WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_post_type_admin() ) ) {
			// Contexts are not supported by the spelling component.
			// https://lucene.apache.org/solr/guide/6_6/suggester.html#Suggester-ContextFiltering
			// Other suggesters are very different (retrieve a full sentence, rather than a similar word)
			// For tests, some commands:
			// http://dev-wpsolr-search-engine.test:7573/solr/admin/collections?action=DELETE&name=test1
			// http://dev-wpsolr-search-engine.test:7573/solr/admin/configs?action=DELETE&name=test1

			throw new \Exception( esc_html( self::SOLR_DOES_NOT_SUPPORT_SUGGESTER_CONTEXTS ) );
		}

		$suggester_query = $this->search_engine_client->createSpellcheck();
		$suggester_query->setHandler( 'suggest' );
		$suggester_query->setDictionary( 'suggest' );
		$suggester_query->setQuery( $query );
		$suggester_query->setCount( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] );
		$suggester_query->setCollate( true );
		$suggester_query->setOnlyMorePopular( true );


		return $this->search_engine_client_execute( $this->search_engine_client, $suggester_query );
	}

	/**
	 * Get suggestions for did you mean.
	 *
	 * @param string $keywords
	 *
	 * @return string Did you mean keyword
	 */
	public function search_engine_client_get_did_you_mean_suggestions( $keywords ) {

		// Add spellcheck to current query
		$spell_check = $this->query_select->getSpellcheck();
		$spell_check->setCount( 10 );
		$spell_check->setCollate( true );
		$spell_check->setCollateExtendedResults( true );
		$spell_check->setExtendedResults( true );
		$spell_check->setQuery( $keywords ); // Mandatory for Solr >= 5.5

		// Excecute the query modified
		$result_set = $this->execute_query();

		// Parse spell check results
		$spell_check_results = $result_set->get_raw_results()->getSpellcheck();

		$did_you_mean_keyword = ''; // original query

		if ( $spell_check_results && ! $spell_check_results->getCorrectlySpelled() ) {

			$collations = $spell_check_results->getCollations();
			foreach ( $collations as $collation ) {

				foreach ( $collation->getCorrections() as $input => $correction ) {
					$did_you_mean_keyword = str_replace( $input, is_array( $correction ) ? $correction[0] : $correction, $keywords );
					break;
				}
			}
		}

		return $did_you_mean_keyword;
	}

	/**
	 * Build the query
	 *
	 */
	public function search_engine_client_build_query() {
		// Nothing. Query is built incrementally.
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
	public function search_engine_client_add_sort_geolocation_distance( $field_name, $geo_latitude, $geo_longitude ) {

		$sorts = $this->query_select->getSorts();
		if ( ! empty( $sorts ) && ! empty( $sorts[ $field_name ] ) ) {

			// Use the sort by distance
			$this->query_select->addSort( $this->get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude ), $sorts[ $field_name ] );

			// Filter out results without coordinates
			/*
			 * does not work with some Solr versions
			 $solarium_query->addFilterQuery(
				array(
					'key'   => 'geo_exclude_empty',
					'query' => sprintf( '%s:[-90,-180 TO 90,180]', $sort_field_name ),
				)
			);*/

		}

		// Remove the field from the sorts, as we use a function instead,
		// or we do not use the field as sort because geolocation is missing.
		$this->query_select->removeSort( $field_name );

	}

	/**
	 * Generate a distance query for a field
	 * 'field_name1' => geodist(field_name1_ll, center_point_lat, center_point_long)
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 * @return string
	 *
	 */
	public function get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude ) {
		return sprintf( self::TEMPLATE_ANONYMOUS_GEODISTANCE_QUERY_FOR_FIELD,
			WpSolrSchema::replace_field_name_extension( $field_name ),
			$geo_latitude,
			$geo_longitude
		);
	}

	/**
	 * Generate a distance query for a field, and name the query
	 * 'field_name1' => wpsolr_distance_field_name1:geodist(field_name1_ll, center_point_lat, center_point_long)
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
		return sprintf( self::TEMPLATE_NAMED_GEODISTANCE_QUERY_FOR_FIELD,
			$field_prefix,
			WPSOLR_Regexp::remove_string_at_the_end( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
			$this->get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude )
		);
	}

	/**
	 * Replace default query field by query fields, with their eventual boost.
	 *
	 * @param array $query_fields
	 */
	public function search_engine_client_set_query_fields( array $query_fields ) {
		if ( $this->_get_is_more_like_this() ) {
			return;
		}
		$this->query_select->getEDisMax()->setQueryFields( implode( ' ', $query_fields ) );
	}

	/**
	 * Set boosts field values.
	 *
	 * @param string $boost_field_values
	 */
	public function search_engine_client_set_boost_field_values( $boost_field_values ) {
		if ( $this->_get_is_more_like_this() ) {
			return;
		}

		$this->query_select->getEDisMax()->setBoostQuery( $boost_field_values );
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

		/**
		 * https://cwiki.apache.org/confluence/display/solr/Faceting#Faceting-IntervalFaceting
		 * https://cwiki.apache.org/confluence/display/solr/DocValues
		 *
		 * Intervals are requiring docValues and Solr 4.10. We're therefore using ranges with before and after sections.
		 */
		$this->query_select->getFacetSet()
		                   ->createFacetRange( "$facet_name" )
		                   ->setField( "$field_name" )
		                   ->setStart( $range_start )
		                   ->setEnd( $range_end )
		                   ->setGap( $range_gap )
		                   ->setInclude( 'lower' )
		                   ->setOther( 'all' );


		/*
		$intervals = [];

		// Add a range for values before start
		$intervals[ sprintf( '%s-%s', '*', $range_start ) ] = sprintf( '[%s,%s)', '*', $range_start );

		// No gap parameter. We build the ranges manually.
		for ( $start = $range_start; $start < $range_end; $start += $range_gap ) {
			$intervals[ sprintf( '%s-%s', $start, $start + $range_gap ) ] = sprintf( '[%s,%s)', $start, $start + $range_gap );
		}

		// Add a range for values after end
		$intervals[ sprintf( '%s-%s', $range_end, '*' ) ] = sprintf( '[%s,%s)', $range_end, '*' );


		$this->query_select->getFacetSet()
		                   ->createFacetInterval( "$facet_name" )
		                   ->setField( "$field_name" )
		                   ->setSet( $intervals );
		*/


	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_range( $range_parameters, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag = '' ) {

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, sprintf( $range_parameters, $field_name, $range_start, $range_end ), $filter_tag );
	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $range_start
	 * @param string $range_end
	 * @param $is_date
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_range_upper_strict( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' ) {

		if ( $range_start === $range_end ) {

			$this->search_engine_client_add_filter_range_upper_included( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag );

		} else {

			$this->search_engine_client_add_filter_range( self::SOLR_FILTER_RANGE_UPPER_STRICT, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function search_engine_client_add_filter_range_upper_included( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $is_date, $filter_tag = '' ) {

		if ( $is_date ) {
			// Remove Range Slider js dates ms, and convert, as expected by Solr.
			$range_start = $this->query_select->getHelper()->formatDate( (int) substr( $range_start, 0, - 3 ) );
			$range_end   = $this->query_select->getHelper()->formatDate( (int) substr( $range_end, 0, - 3 ) );
		}

		$this->search_engine_client_add_filter_range( self::SOLR_FILTER_RANGE_UPPER_INCLUDED, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag );
	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $filter_query
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $filter_query, $filter_tag = '' ) {

		if ( $facet_is_or ) {

			if ( ! isset( $this->filter_queries_or[ $field_name ] ) ) {
				$this->filter_queries_or[ $field_name ] = [ 'query' => '', 'tag' => $filter_tag ];
			}

			$this->filter_queries_or[ $field_name ]['query'] .= sprintf( ' %s %s ', empty( $this->filter_queries_or[ $field_name ]['query'] ) ? '' : ' OR ', $filter_query );

		} else {

			$this->query_select->createFilterQuery( $filter_name )->setQuery( $filter_query )->setTags( [ $filter_tag ] );
		}
	}

	/**
	 * Add decay functions to the search query
	 *
	 * @param array $decays
	 *
	 */
	public function search_engine_client_add_decay_functions( array $decays ) {
		// TODO: Implement search_engine_client_add_decay_functions() method.
	}

	/**
	 * Fix an error while querying the engine.
	 *
	 * @param Exception $e
	 * @param $search_engine_client
	 * @param $update_query
	 */
	protected function search_engine_client_execute_fix_error( Exception $e, $search_engine_client, $update_query ) {

		if ( 1 === preg_match( '/sort param could not be parsed as a query, and is not a field that exists in the index: field\((.*),./m', $e->getMessage(), $no_multi_sort_matches ) ) {
			// Solr version does not accept multi-value sort for this field

			$this->remove_multivalue_sort( $no_multi_sort_matches[1] );

		} elseif ( 1 === preg_match( '/"can not sort on multivalued field: (.*)"/m', $e->getMessage(), $multi_sort_matches ) ) {
			// The index schema requires multi-value sort fort this field

			$this->add_multivalue_sort( $multi_sort_matches[1] );
		}

	}

	/**
	 * Add a geo distance filter.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 * @param $distance
	 */
	public function search_engine_client_add_filter_geolocation_distance( $field_name, $geo_latitude, $geo_longitude, $distance ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => sprintf( 'distance %s', $field_name ),
				'query' => $this->query_select->getHelper()->geofilt(
					$field_name,
					$geo_latitude,
					$geo_longitude,
					$distance
				),
			] );
	}

	/**
	 * Create a facet stats.
	 * @href https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/stats-component/
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 */
	public function search_engine_client_add_facet_stats( $facet_name, $exclude ) {

		$this->query_select->getStats()->createField( sprintf( '{!ex=%s}%s',
			sprintf( self::FILTER_QUERY_TAG_FACET_EXCLUSION, $exclude ), $facet_name ) );
	}

	/**
	 * Generator a comparison filter
	 *
	 * @param string $field_name
	 * @param int|int[] $field_values
	 * @param bool $is_and
	 * @param string $operator '<', '>', '<=', '>='
	 *
	 * @return FilterQuery
	 */
	private function _generate_comparison_filter( $field_name, $field_values, $is_and, $operator ) {

		$results      = [];
		$field_values = is_array( $field_values ) ? $field_values : [ $field_values ];
		foreach ( $field_values as $field_value ) {
			$results[] = sprintf( $operator, $field_value );
		}

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(%s:%s)', $field_name, implode( $is_and ? ' AND ' : ' OR ', $results ) ),
			]
		);
	}


	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_lt( $field_name, $field_values ) {
		return $this->_generate_comparison_filter( $field_name, $field_values, true, '[* TO %s}' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_lte( $field_name, $field_values ) {
		return $this->_generate_comparison_filter( $field_name, $field_values, true, '[* TO %s]' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_gt( $field_name, $field_values ) {
		return $this->_generate_comparison_filter( $field_name, $field_values, true, '{%s TO *]' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_gte( $field_name, $field_values ) {
		return $this->_generate_comparison_filter( $field_name, $field_values, true, '[%s TO *]' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_between( $field_name, $field_values ) {
		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '%s:[%s TO %s]', $field_name, $field_values[0], $field_values[1] ),
			]
		);

	}

	/**
	 * @inheritdoc
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_not_between( $field_name, $field_values ) {
		return $this->search_engine_client_create_not(
			$this->search_engine_client_create_filter_between( $field_name, $field_values )
		);
	}


	/**
	 * https://lucene.apache.org/solr/guide/6_6/result-grouping.html
	 * We do not use https://lucene.apache.org/solr/guide/6_6/result-grouping.html, because it requires routing with SolrCloud
	 *
	 * https://solarium.readthedocs.io/en/latest/queries/select-query/result-of-a-select-query/component-results/grouping-result/
	 *
	 * @inheritdoc
	 */
	public function search_engine_client_add_facet_top_hits( $facet_name, $size ) {
		$group = $this->query_select->getGrouping();
		$group->addField( $facet_name );
		$group->setLimit( $size );
	}

	/**
	 * @inheritDoc
	 */
	protected function property_exists( $document, $field_name ) {
		return isset( $document->$field_name ); // call the document magic _isset
	}

	/**
	 * @inheritDoc
	 */
	protected function _log_query_as_string() {

		return wp_json_encode( [
			(array) $this->query_select,
		], JSON_PRETTY_PRINT );
	}

	/**
	 *
	 * @param WPSOLR_AbstractResultsClient $results
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function _log_results_as_string( $results ) {
		return wp_json_encode( json_decode( $results->get_raw_results()->getResponse()->getBody(), true ), JSON_PRETTY_PRINT );
	}

	/**
	 * @param array $field_values
	 *
	 * @return array
	 */
	protected function _escape_terms( array $field_values ): array {
		$results = [];
		foreach ( $field_values as $field_value ) {
			$results[] = $this->_escape_term( $field_value );
		}

		return $results;
	}

	/**
	 * @param string $field_value
	 *
	 * @return string
	 */
	protected function _escape_term( string $field_value ): string {
		// if $field_value contains 'AND|OR|TO', extra double quotes are added outside the result. Remove them with a trim to prevent Solr syntax error.
		return trim( $this->query_select->getHelper()->escapeTerm( $field_value ), '"' );
	}
}
