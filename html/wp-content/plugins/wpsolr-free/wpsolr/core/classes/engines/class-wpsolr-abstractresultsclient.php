<?php

namespace wpsolr\core\classes\engines;

/**
 * Class WPSOLR_AbstractResultsClient
 *
 * Abstract class for search results.
 */
abstract class WPSOLR_AbstractResultsClient {

	protected $results;
	protected $raw_results;
	protected int $_force_search_nb_results;
	protected array $_processed_results;

	/**
	 * Raw results
	 * @return mixed
	 */
	public function get_raw_results() {
		return $this->raw_results ?? $this->results;
	}

	/**
	 * @return array
	 */
	protected function _get_results() {
		return $this->results;
	}

	/**
	 * @return array
	 */
	public function get_results() {
		return $this->_post_process_results( $this->_get_results() );
	}

	/**
	 * @return array
	 */
	public function get_results_ids(): array {
		return empty( $results = $this->get_results() ) ?
			[] :
			array_map(
				function ( $result ) {
					return $result->id;
				},
				$results
			);
	}

	/**
	 * @return mixed
	 */
	abstract public function get_suggestions();

	/**
	 * Get nb of results.
	 *
	 * @return int
	 * @throws \Exception
	 */
	abstract public function get_nb_results();

	/**
	 * Get nb of rows returned (limited by the limit parameter)
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function get_nb_rows() {
		// To be defined
		throw new \Exception( 'get_nb_rows() not implemented.' );
	}

	/**
	 * Get cursor mark during a scroll
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_cursor_mark() {
		// To be defined
		//throw \new Exception( 'get_cursor_mark() not implemented.' );

		return '';
	}

	/**
	 * Get a facet
	 *
	 * @param string $facet_name
	 *
	 * @return array
	 */
	abstract public function get_facet( $facet_name );

	/**
	 * Get highlighting
	 *
	 * @param \Solarium\QueryType\Select\Result\Document|\Elastica\Result $result |object
	 *
	 * @return array
	 */
	abstract public function get_highlighting( $result );

	/**
	 * Get stats
	 *
	 * @param string $facet_name
	 * @param array $options
	 *
	 * @return array
	 */
	abstract public function get_stats( $facet_name, array $options = [] );

	/**
	 * Get top hits aggregation
	 *
	 * @param string $field_name
	 *
	 * @return array
	 */
	public function get_top_hits( $field_name ) {
		throw new \Exception( 'Group suggestions are not defined for this search engine.' );
	}

	/**
	 * @return mixed
	 */
	public function get_questions_answers_results() {
		// Override in children implementing Q&A
		return $this->get_results();
	}

	public function set_force_nb_results( int $force_search_nb_results ) {
		$this->_force_search_nb_results = $force_search_nb_results;
	}

	/**
	 * @param mixed $results
	 *
	 * @return array
	 */
	protected function _post_process_results( mixed $results ) {

		if ( isset( $this->_processed_results ) ) {
			return $this->_processed_results;
		}

		$reordered_results = [];

		if ( false
		) {
		} else {
			return ( $this->_processed_results = isset( $this->_force_search_nb_results ) ?
				array_slice( $results, 0, $this->_force_search_nb_results ) :
				$results
			);
		}

		return ( $this->_processed_results = $reordered_results );
	}


}
