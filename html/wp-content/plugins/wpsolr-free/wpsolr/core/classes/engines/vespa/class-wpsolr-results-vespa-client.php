<?php

namespace wpsolr\core\classes\engines\vespa;

use wpsolr\core\classes\engines\WPSOLR_AbstractResultsClient;

class WPSOLR_Results_Vespa_Client extends WPSOLR_AbstractResultsClient {
	use WPSOLR_Vespa_Client;

	/**
	 * @var array
	 */
	protected $results_aggregation;
	protected $facets;
	protected $facets_stats;
	protected $hits = [];

	/**
	 * WPSOLR_Results_Vespa_Client constructor.
	 *
	 * @param array $results
	 */
	public function __construct( $results ) {

		/**
		 * https://docs.vespa.ai/en/reference/default-result-format.html
		 */

		$this->raw_results = $results ?? [];

		// Collect facet fields
		foreach ( $results['results']['children'] ?? [] as $child ) {
			if ( ! empty( $child->children ) && ( 0 === strpos( $child->id, 'group:' ) ) ) {
				foreach ( $child->children as $facet ) {
					foreach ( $facet->children as $facet_item ) {
						/**
						 * Range grouping
						 */
						if ( 0 === strpos( $facet->id, 'grouplist:predefined(' ) ) {
							preg_match( '/^predefined\(([^,]*),.*$/', $facet->label, $match_facet_label ); // predefined(wpsolr__price_f, bucket(...) ....)
							$this->facets[ $match_facet_label[1] ][] = [
								sprintf( '%s - %s', $facet_item->limits->from, $facet_item->limits->to ) => $facet_item->fields->{'count()'},
							];

						} elseif ( isset( $facet_item->fields->{'count()'} ) ) {
							/**
							 * Field grouping
							 */

							$this->facets[ $facet->label ][] = [ $facet_item->value => $facet_item->fields->{'count()'} ];

						} elseif ( 0 === strpos( $facet->id, 'grouplist:"' ) ) {
							/**
							 * Stats grouping
							 * {"min(wpsolr__price_f)":100,"max(wpsolr__price_f)":999}
							 */
							preg_match( '/\"(.*)\"/', $facet->id, $match_facet_label ); // 'grouplist:"wpsolr__price_f"'

							$this->facets_stats[ $match_facet_label[1] ] = [
								$facet_item->fields->{sprintf( 'min(%s)', $match_facet_label[1] )} =>
									$facet_item->fields->{sprintf( 'max(%s)', $match_facet_label[1] )}
							];

						} elseif ( 0 === strpos( $facet_item->id, 'group:string:' ) ) {
							/**
							 * Hits
							 */
							$this->hits[ $facet_item->value ] = [];
							foreach ( $facet_item->children[0]->children as $hit ) {
								$result = [];
								foreach ( (array) $hit->fields as $field_name => $field_value ) {
									$result[ $this->unconvert_field_name( $field_name ) ] = $field_value;
								}
								$this->hits[ $facet_item->value ][] = $result;
							}

						} else {
							throw new \Exception( sprintf( 'Facet %s has unknow children type.', esc_html( $facet->label ) ) );
						}
					}
				}
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
		if ( isset( $suggests[ WPSOLR_Search_Vespa_Client::SUGGESTER_NAME ] ) ) {
			foreach ( $suggests[ WPSOLR_Search_Vespa_Client::SUGGESTER_NAME ][0]['options'] as $option ) {
				array_push( $suggests_array, [ 'text' => $option['text'] ] );
			}
		}

		return $suggests_array;
	}

	/**
	 * @inheritDoc
	 *
	 * @inheritDoc
	 */
	protected function _get_results() {

		$results = [];

		foreach ( $this->raw_results['results']['children'] ?? [] as $child ) {

			if ( 0 === strpos( $child->id, 'index:' ) ) {
				$result = [];
				foreach ( $child->fields ?? [] as $property_name => $property_value ) {

					// Reverse the custom field name conversion
					$result[ $this->unconvert_field_name( $property_name ) ] = $property_value;

				}

				$results[] = (object) $result;
			}
		}

		return $results;
	}


	/**
	 * Get nb of results.
	 *
	 * @return int
	 */
	public function get_nb_results() {
		return $this->raw_results['results']['fields']->totalCount ?? 0;
	}

	/**
	 * @inheridoc
	 */
	public function get_nb_rows() {
		return count( $this->raw_results['results'] );
	}


	/**
	 * @inheritdoc
	 */
	public function get_facet( $facet_name ) {

		$results = [];
		foreach ( $this->facets[ $this->convert_field_name( $facet_name ) ] ?? [] as $facet ) {
			$results[ key( $facet ) ] = $facet[ key( $facet ) ];
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
		return (array) $result;
	}

	/**
	 * Get stats
	 *
	 * @param array $options *
	 *
	 * @return array
	 */
	public function get_stats( $facet_name, array $options = [] ) {

		/**
		 * Vespa stores dates as epoch seconds. We convert it here in ms for ionrange.
		 */

		$converted_field_name = $this->convert_field_name( $facet_name );

		$facet_stats = $this->facets_stats[ $converted_field_name ] ?? [];

		$is_date = ( $options['is_date'] ?? false );

		return empty( $facet_stats ) ? [] :
			[
				sprintf( '%s-%s',
					$is_date ? ( 1000 * key( $facet_stats ) ) : key( $facet_stats ),
					$is_date ? ( 1000 * $facet_stats[ key( $facet_stats ) ] ) : $facet_stats[ key( $facet_stats ) ] )
				=> 1
			];
	}

	/**
	 * @inheritdoc
	 *
	 */
	public function get_top_hits( $agg_name ) {

		return $this->hits;
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
