<?php

namespace wpsolr\core\classes\admin\ui\ajax;

/**
 * Retrieve terms
 *
 * Class WPSOLR_Admin_UI_Ajax_Search_Terms
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Search_Terms extends WPSOLR_Admin_UI_Ajax_Search {

	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		// Retrieve taxonomies
		$taxonomies = explode( ',', $parameters[ self::PARAMETER_PARAMS_FILTERS ] );

		// Retrieve terms
		$results = get_terms(
			[
				'taxonomy' => $taxonomies,
				'search'   => $parameters[ self::PARAMETER_TERM ],
				'number'   => $parameters[ self::PARAMETER_LIMIT ],
				'include'  => $parameters[ self::PARAMETER_INCLUDE ],
				'exclude'  => $parameters[ self::PARAMETER_EXCLUDE ],
			] );

		return $results;
	}

	/**
	 * @inheritDoc
	 */
	public static function format_results( $parameters, $not_formatted_results ) {

		$results = [];

		if ( empty( $not_formatted_results->errors ) ) {
			// Format results
			foreach ( $not_formatted_results as $term ) {
				$results[ $term->term_id ] = sprintf( '%s', $term->name );
			}
		}

		return $results;
	}

}