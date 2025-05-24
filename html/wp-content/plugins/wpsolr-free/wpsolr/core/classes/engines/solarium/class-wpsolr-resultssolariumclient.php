<?php

namespace wpsolr\core\classes\engines\solarium;

use Solarium\Component\Result\Grouping\ValueGroup;
use Solarium\Component\Result\Stats\Result;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result as SelectResult;
use Solarium\QueryType\Update\Result as UpdateResult;
use wpsolr\core\classes\engines\WPSOLR_AbstractResultsClient;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_ResultsSolariumClient
 *
 * @property SelectResult $results
 */
class WPSOLR_ResultsSolariumClient extends WPSOLR_AbstractResultsClient {

	/**
	 * WPSOLR_ResultsSolariumClient constructor.
	 *
	 * @param SelectResult|UpdateResult $results
	 */
	public function __construct( $results ) {
		$this->results = $results;
	}

	protected function _get_results() {
		return $this->results->getDocuments();
	}

	/**
	 * @return mixed
	 */
	public function get_suggestions() {
		return $this->results;
	}

	/**
	 * Get nb of results.
	 *
	 * @return int
	 */
	public function get_nb_results() {

		return $this->results->getNumFound();
	}

	/**
	 * @inheridoc
	 */
	public function get_nb_rows() {
		return $this->results->count();
	}

	/**
	 * Get a facet
	 *
	 * @return mixed
	 */
	public function get_facet( $facet_name ) {
		$facets = $this->results->getFacetSet();

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
	 * Get highlightings of a results.
	 *
	 * @param Document $result
	 *
	 * @return array Result highlights.
	 */
	public function get_highlighting( $result ) {

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
	public function get_stats( $facet_name, array $options = [] ) {

		$stats = $this->results->getStats();

		foreach ( $stats as $field ) {
			/** @var Result $field */
			if ( $facet_name === $field->getName() ) {
				return [
					sprintf( '%s-%s',
						$this->convert_date_to_epoch( $facet_name, $field->getStatValue( 'min' ) ), // $field->getMin() fails on epoch as it expect float and returns a string
						$this->convert_date_to_epoch( $facet_name, $field->getStatValue( 'max' ) )
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
				/** @var ValueGroup $value_group */
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