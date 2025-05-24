<?php

namespace wpsolr\core\classes\admin\ui\ajax;

use wpsolr\core\classes\engines\solarium\admin\WPSOLR_Solr_Admin_Api_Opensolr;

/**
 * Retrieve terms
 *
 * Class WPSOLR_Admin_UI_Ajax_Search_Environments
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Search_Environments extends WPSOLR_Admin_UI_Ajax_Search {

	const IS_SORT_ASC = false;

	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		$email   = empty( $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['email'] ) ? '' : $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['email'];
		$api_key = empty( $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['api_key'] ) ? '' : $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['api_key'];


		$hosting_admin_api = new WPSOLR_Solr_Admin_Api_Opensolr( [
			'extra_parameters' => [
				'index_email'   => $email,
				'index_api_key' => $api_key
			]
		], null );

		$term = ! empty( $parameters[ self::PARAMETER_TERM ] ) ? $parameters[ self::PARAMETER_TERM ] : '';

		$results = $hosting_admin_api->get_environments( $term );

		return $results;
	}

	/**
	 * @inheritDoc
	 */
	public static function format_results( $parameters, $not_formatted_results ) {

		return $not_formatted_results;
	}

}