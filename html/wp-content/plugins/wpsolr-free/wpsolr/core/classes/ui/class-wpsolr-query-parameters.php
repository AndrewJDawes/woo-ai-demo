<?php

namespace wpsolr\core\classes\ui;

use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Manage search parameters, from url or Ajax.
 */
class WPSOLR_Query_Parameters {

	/**
	 * Search parameters used in url or ajax
	 */
	const SEARCH_PARAMETER_AJAX_URL_PARAMETERS = 'url_parameters';
	const SEARCH_PARAMETER_S = 's'; // Standard WP seach query
	const SEARCH_PARAMETER_SEARCH = 'search'; // Old query name, here for compatibility
	const SEARCH_PARAMETER_Q = 'wpsolr_q'; // New query name
	const SEARCH_PARAMETER_FQ = 'wpsolr_fq';
	const SEARCH_PARAMETER_PAGE = 'wpsolr_page';
	const SEARCH_PARAMETER_SORT = 'wpsolr_sort';
	const SEARCH_PARAMETER_LATITUDE = 'wpsolr_lat';
	const SEARCH_PARAMETER_LONGITUDE = 'wpsolr_long';
	const SEARCH_PARAMETER_GEO_USER_AGREEMENT = 'wpsolr_is_geo';
	const PARAMETER_VALUE_YES = 'y';
	const PARAMETER_VALUE_NO = 'n';
	const SEARCH_PARAMETER_RESULTS_OBJECT_ID = 'wpsolr_roid';
	const SEARCH_PARAMETER_RESULTS_INDEX = 'wpsolr_idx';
	const SEARCH_PARAMETER_RESULTS_QUERY_ID = 'wpsolr_rq';
	const SEARCH_PARAMETER_RESULTS_POSITION = 'wpsolr_rp';
	const SEARCH_PARAMETER_RESULTS_FILTERS = 'wpsolr_rf';
	const SEARCH_PARAMETER_EVENT_IS_TO_BE_RESENT = 'wpsolr_evr';
	const SEARCH_PARAMETER_EVENT_IS_SEND_ALREADY = 'wpsolr_evs';
	const SEARCH_PARAMETER_VIEW_UUID = 'wpsolr_v';
	const SEARCH_PARAMETER_USER_TOKEN = 'wpsolr_ut';
	const SEARCH_PARAMETER_EVENT_TRACKING_NAME = 'wpsolr_e';
	const SEARCH_PARAMETER_EVENT_TRACKING_NAME_CLICK_RESULT = 'cr';
	const SEARCH_PARAMETER_EVENT_TRACKING_NAME_CLICK_FILTER = 'cf';
	const SEARCH_PARAMETER_EVENT_TRACKING_NAME_PURCHASE_ORDER = 'po';

	/**
	 * Copy url parameters to query.
	 */
	public static function copy_parameters_to_query( WPSOLR_Query $wpsolr_query, $url_parameters ) {

		if ( isset( $url_parameters[ self::SEARCH_PARAMETER_Q ] ) ) {
			$wpsolr_query->set_wpsolr_query( $url_parameters[ self::SEARCH_PARAMETER_Q ] );
		}

		if ( isset( $url_parameters[ self::SEARCH_PARAMETER_FQ ] ) ) {
			$wpsolr_query->set_filter_query_fields( $url_parameters[ self::SEARCH_PARAMETER_FQ ] );
		}

		if ( isset( $url_parameters[ self::SEARCH_PARAMETER_PAGE ] ) ) {
			$wpsolr_query->set_wpsolr_paged( $url_parameters[ self::SEARCH_PARAMETER_PAGE ] );
		}

		if ( isset( $url_parameters[ self::SEARCH_PARAMETER_SORT ] ) ) {
			$wpsolr_query->set_wpsolr_sort( $url_parameters[ self::SEARCH_PARAMETER_SORT ] );
		}

		if ( isset( $url_parameters[ self::SEARCH_PARAMETER_LATITUDE ] ) ) {
			$wpsolr_query->set_wpsolr_latitude( $url_parameters[ self::SEARCH_PARAMETER_LATITUDE ] );
		}

		if ( isset( $url_parameters[ self::SEARCH_PARAMETER_LONGITUDE ] ) ) {
			$wpsolr_query->set_wpsolr_longitude( $url_parameters[ self::SEARCH_PARAMETER_LONGITUDE ] );
		}

		// Action to update the url parameters
		do_action( WPSOLR_Events::WPSOLR_ACTION_URL_PARAMETERS,
			$wpsolr_query,
			$url_parameters
		);

	}

	/**
	 * Extract query from Ajax parameters.
	 * @return WPSOLR_Query
	 */
	public static function CreateQuery( WPSOLR_Query $wpsolr_query = null ) {

		$wpsolr_query = isset( $wpsolr_query ) ? $wpsolr_query : WPSOLR_Query::Create();

		if ( isset( $_POST[ self::SEARCH_PARAMETER_AJAX_URL_PARAMETERS ] ) ) {
			// It is an Ajax call

			if (
				( ! isset( $_POST['security'] ) || ! wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'security' ] ), 'nonce_for_autocomplete' ) )
				&& ! WPSOLR_Service_Container::getOption()->get_search_is_no_ajax_nonce_verification_front_end()
			) {
				die();
			}

			// Parameters are in the url
			$url_parameters_str = ltrim( wp_unslash( WPSOLR_Sanitize::sanitized( $_POST, [ self::SEARCH_PARAMETER_AJAX_URL_PARAMETERS ] ) ), '?' );

			// Extract url parameters
			parse_str( $url_parameters_str, $url_parameters );
			$url_parameters = WPSOLR_Sanitize::sanitize_text_field( $url_parameters );

		} else {
			// It is a GET url

			// Array of parameters
			$url_parameters = [];

			// Extract all url parameters in an array
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				parse_str( WPSOLR_Sanitize::sanitize_query_string(), $url_parameters );
				$url_parameters = WPSOLR_Sanitize::sanitize_text_field( $url_parameters );
			}

		}

		// Compatibility: copy old WPSOLR query and standard WP query in current WPSOLR query
		foreach (
			[
				self::SEARCH_PARAMETER_SEARCH,
				self::SEARCH_PARAMETER_S
			] as $query_parameter
		) {
			if ( isset( $url_parameters[ $query_parameter ] ) ) {
				// Copy old parameter value to new parameter
				$url_parameters[ self::SEARCH_PARAMETER_Q ] = $url_parameters[ $query_parameter ];
				unset( $url_parameters[ $query_parameter ] );
			}
		}

		// Copy url parameters to query
		self::copy_parameters_to_query( $wpsolr_query, $url_parameters );

		return $wpsolr_query;
	}

}
