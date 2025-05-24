<?php

namespace wpsolr\core\classes\engines\weaviate;

use wpsolr\core\classes\engines\WPSOLR_AbstractResultsClient;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;

class WPSOLR_Results_Weaviate_Client extends WPSOLR_AbstractResultsClient {
	use WPSOLR_Weaviate_Client;

	const WEAVIATE_CANNOT_SUGGEST_GROUPED_RESULTS = 'Weaviate cannot suggest grouped results.';

	/**
	 * @var array
	 */
	protected $results_aggregation;
	protected $facets;
	protected $facets_stats;

	/**
	 * WPSOLR_Results_Weaviate_Client constructor.
	 *
	 * @param array $results
	 */
	public function __construct( $results ) {

		$this->raw_results         = $results ?? [];
		$this->results             = array_values( (array) $results[ static::$alias_get ] )[0] ?? [];
		$this->results_aggregation = isset( $results[ static::$alias_aggregate_search_count ] ) ? array_values( (array) $results[ static::$alias_aggregate_search_count ] )[0] : [];


		// Collect facet fields
		foreach ( $results as $alias => $result ) {
			if ( $alias !== ( $facet_name = WPSOLR_Regexp::remove_string_at_the_begining( $alias, static::$alias_aggregate_type_field_prefix ) ) ) {
				$this->facets[ $facet_name ] = (array) array_values( (array) $result )[0] ?? [];
			}
			if ( $alias !== ( $facet_name = WPSOLR_Regexp::remove_string_at_the_begining( $alias, static::$alias_aggregate_type_stats_prefix ) ) ) {
				$this->facets_stats[ $facet_name ] = (array) array_values( (array) $result )[0] ?? [];
			}
		}

		//$this->facets = array_merge( $this->results[0]['facets'] ?? [], $this->results[1]['facets'] ?? [] );
		//$this->facets_stats = array_merge( $this->results[0]['facets_stats'] ?? [], $this->results[1]['facets_stats'] ?? [] );

	}

	/**
	 * @return mixed
	 */
	public function get_suggestions() {

		$suggests = $this->results[0]['suggest'] ?? [];

		$suggests_array = [];
		if ( isset( $suggests[ WPSOLR_Search_Weaviate_Client::SUGGESTER_NAME ] ) ) {
			foreach ( $suggests[ WPSOLR_Search_Weaviate_Client::SUGGESTER_NAME ][0]['options'] as $option ) {
				array_push( $suggests_array, [ 'text' => $option['text'] ] );
			}
		}

		return $suggests_array;
	}

	/**
	 * @inheritDoc
	 */
	protected function _get_results() {

		$results = [];

		foreach ( $this->results ?? [] as $object ) {

			$result = [];
			foreach ( $object as $property_name => $property_value ) {

				// Reverse the custom field name conversion
				$result[ $this->unconvert_field_name( $property_name ) ] = $property_value;

			}

			$results[] = (object) $result;
		}

		return $results;
	}


	/**
	 * Get nb of results.
	 *
	 * @return int
	 */
	public function get_nb_results() {
		return empty( $this->results_aggregation ) ? 0 : $this->results_aggregation[0]->meta->count;
	}

	/**
	 * @inheridoc
	 */
	public function get_nb_rows() {
		return count( $this->results );
	}


	/**
	 * @inheritdoc
	 */
	public function get_facet( $facet_name ) {

		$results = [];
		foreach ( $this->facets[ $this->convert_field_name( $facet_name ) ] ?? [] as $facet ) {
			$results[ $facet->groupedBy->value ] = $facet->meta->count;
		}

		return $results;
	}

	/**
	 * Get highlighting
	 *
	 * @param object $result
	 *
	 * @return array
	 */
	public function get_highlighting( $result ) {
		return $result->wpsolr_highlight ?? [];
	}

	/**
	 * @inheridoc
	 */
	public function get_stats( $facet_name, array $options = [] ) {

		$converted_field_name = $this->convert_field_name( $facet_name );

		$facet_stats = $this->facets_stats[ $converted_field_name ] ?? [];

		return empty( $facet_stats ) ? [] :
			[
				sprintf( '%s-%s',
					$this->_convert_to_unix_time_if_date( $facet_stats[0]->$converted_field_name->minimum ),
					$this->_convert_to_unix_time_if_date( $facet_stats[0]->$converted_field_name->maximum ) )
				=> 1
			];
	}

	/**
	 * @inheritdoc
	 *
	 */
	public function get_top_hits( $agg_name ) {

		$top_hits = [];
		foreach ( $this->get_results() as $result ) {
			$top_hits[ $result->type ][] = $result;
		}

		return $top_hits;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_questions_answers_results() {
		$results = [];

		foreach ( $this->get_results() as $result ) {
			if ( $result->_additional->answer->hasAnswer ) {
				$result->wpsolr_questions_answers = [
					'certainty' => $result->_additional->answer->certainty,
					'field'     => $result->_additional->answer->property,
					'answer'    => $result->_additional->answer->result,
				];
				unset( $result->_additional );
				$results[] = $result;
			}
		}

		return $results;
	}
}
