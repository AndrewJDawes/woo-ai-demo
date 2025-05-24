<?php

namespace wpsolr\core\classes\admin\ui\ajax;


/**
 * Super class to manage all calls to wp_filter_object_list()
 *
 * Class WPSOLR_Admin_UI_Ajax_Search_Filter_Object_List
 * @package wpsolr\core\classes\admin\ui\ajax
 */
abstract class WPSOLR_Admin_UI_Ajax_Search_Filter_Object_List extends WPSOLR_Admin_UI_Ajax_Search {


	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		// Retrieve terms
		$results = get_post_types(
			[
			],
			'objects'
		);

		return $results;
	}

	/**
	 * @inheritDoc
	 */
	public static function format_results( $parameters, $not_formatted_results ) {

		$results = [];

		$includes      = empty( $parameters[ self::PARAMETER_INCLUDE ] ) ? [] : explode( ',', $parameters[ self::PARAMETER_INCLUDE ] );
		$excludes      = empty( $parameters[ self::PARAMETER_EXCLUDE ] ) ? [] : explode( ',', $parameters[ self::PARAMETER_EXCLUDE ] );
		$schema_fields = empty( $parameters[ self::PARAMETER_PARAMS_FILTERS ] ) ? [] : explode( ',', $parameters[ self::PARAMETER_PARAMS_FILTERS ] );

		if ( empty( $not_formatted_results->errors ) ) {
			$nb_results = 0;
			// Format results
			foreach ( $not_formatted_results as $result ) {
				// Filter results by term
				if ( ! empty( $parameters[ self::PARAMETER_TERM ] ) && ( false === strpos( $result->name, $parameters[ self::PARAMETER_TERM ] ) ) ) {
					// Not found
					continue;
				}
				// Filter results by inclusion list
				if ( ! empty( $parameters[ self::PARAMETER_INCLUDE ] ) && ( false === array_search( $result->name, $includes, true ) ) ) {
					// Not found
					continue;
				}

				// Filter results by exclusion list
				if ( ! empty( $parameters[ self::PARAMETER_EXCLUDE ] ) && ( false !== array_search( $result->name, $excludes, true ) ) ) {
					// Not found
					continue;
				}

				// Filter results by schema field list
				if ( ! empty( $parameters[ self::PARAMETER_PARAMS_FILTERS ] ) && ( false === array_search( $result->name, $schema_fields, true ) ) ) {
					// Not found
					continue;
				}

				$results[] = [ 'id' => $result->name, 'label' => $result->label ];
				$nb_results ++;

				if ( $nb_results >= 10 ) {
					break;
				}

			}
		}

		return $results;
	}

}