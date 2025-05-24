<?php

namespace wpsolr\core\classes\ui\shortcode;

use wpsolr\core\classes\services\WPSOLR_Service_Container;

/**
 * Class WPSOLR_Shortcode_Sort
 */
class WPSOLR_Shortcode_Sort extends WPSOLR_Shortcode_Abstract {

	const SHORTCODE_NAME = 'wpsolr_sort';

	/**
	 * @inheritdoc
	 */
	public static function get_html( $attributes = [] ) {

		$results = WPSOLR_Service_Container::get_solr_client()->get_results_data( WPSOLR_Service_Container::get_query() );
		$html    = WPSOLR_Service_Container::get_template_builder()->load_template_sort( $results['sort'] );

		return sprintf( '<div id="res_sort">%s</div>', $html );
	}

}