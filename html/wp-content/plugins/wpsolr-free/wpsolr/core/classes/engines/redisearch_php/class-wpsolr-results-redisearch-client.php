<?php

namespace wpsolr\core\classes\engines\redisearch_php;

use Ehann\RediSearch\Query\SearchResult;
use wpsolr\core\classes\engines\WPSOLR_AbstractResultsClient;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_Results_Redisearch_Client extends WPSOLR_AbstractResultsClient {

	/** var SearchResult */
	protected $results;

	/**
	 * WPSOLR_ResultsSolariumClient constructor.
	 *
	 * @param SearchResult $results
	 */
	public function __construct( $results ) {
		$this->results = $results;
	}

	/**
	 * @inheritDoc
	 */
	protected function _get_results() {
		return $this->results->getDocuments();
	}

	/**
	 * @return mixed
	 */
	public function get_suggestions() {
		return $this->get_results();
	}

	/**
	 * Get nb of results.
	 *
	 * @return int
	 */
	public function get_nb_results() {

		return $this->results->getCount();
	}

	/**
	 * Get a facet
	 *
	 * @return mixed
	 */
	public function get_facet( $facet_name ) {

		return [];

		$facets = $this->results->getDocuments();

		$results = empty( $facets ) ? null : $this->results->getFacetSet()->getFacet( $facet_name );

		// For ranges, add the before and after counts to the values
		// No need of this section when using intervals.
		if ( method_exists( $results, 'getAfter' ) ) {
			$values_original = $results->getValues();
			$values          = [];

			$before = $results->getBefore();
			if ( ! empty( $before ) ) {
				$values[ sprintf( '*-%s', $results->getStart() ) ] = $before;
			}

			foreach ( $values_original as $value => $count ) {
				$values[ $value ] = $count;
			}

			$after = $results->getAfter();
			if ( ! empty( $after ) ) {
				$values[ sprintf( '%s-*', $results->getEnd() ) ] = $after;
			}

			return $values;
		}

		return $results->getValues();
	}

	/**
	 * https://oss.redislabs.com/redisearch/Highlight.html
	 *
	 * @inheritDoc
	 */
	public function get_highlighting( $result ) {


		return [];

		$highlights = $this->results->getHighlighting();

		if ( $highlights ) {
			$highlight = $highlights->getResult( $result->id );
			if ( $highlight ) {
				return $highlight->getFields();
			}
		}

		return [];
	}

	/**
	 * @inheridoc
	 */
	public function get_stats( $facet_name, array $options = []  ) {

		$stats = $this->results->getStats();

		foreach ( $stats as $field ) {
			/** @var \Solarium\QueryType\Select\Result\Stats\Result $field */
			if ( $facet_name === $field->getName() ) {
				// Solr returns dates as string containing "-". Hence the range delimitor.
				//return [ sprintf( '%s%s%s', $field->getMin(), WPSOLR_AbstractSearchClient::RANGE_DELIMITOR, $field->getMax() ) => $field->getCount() ];
				return [
					// totototo
					sprintf( '%s-%s',
						$this->convert_date_to_epoch( $facet_name, $field->getMin() ),
						$this->convert_date_to_epoch( $facet_name, $field->getMax() )
					) => $field->getCount()
				];
			}
		}

		return [];
	}

	/**
	 * @param $field_name
	 * @param $field_date_value
	 *
	 * @return string|int
	 */
	protected function convert_date_to_epoch( $field_name, $field_date_value ) {
		$field_type = WpSolrSchema::get_custom_field_dynamic_type(
			WpSolrSchema::replace_field_name_extension_with( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING )
		);

		// Add Solr dates ms as expected by the Range Slider js.
		return ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_DATE === $field_type ) ? sprintf( '%s000', strtotime( $field_date_value ) ) : $field_date_value;
	}

	/**
	 * https://solarium.readthedocs.io/en/latest/queries/select-query/result-of-a-select-query/component-results/grouping-result/
	 *
	 * @inheritdoc
	 */
	public function get_top_hits( $field_name ) {

		try {

			$groups = $this->results->getGrouping();

			// Convert
			$top_hits = [];
			foreach ( $groups as $key => $group ) {
				/** @var \Solarium\QueryType\Select\Result\Grouping\ValueGroup $value_group */
				foreach ( $group as $value_group ) {
					foreach ( $value_group as $document ) {
						$top_hits[ $value_group->getValue() ][] = $document;
					}
				}
			}

			return $top_hits;

		} catch ( \Exception $e ) {
			// Prevent the error.
			return null;
		}

	}
}
