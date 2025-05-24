<?php

namespace wpsolr\core\classes\ui;

use wpsolr\core\classes\services\WPSOLR_Service_Container;


/**
 * Display facets
 *
 * Class WPSOLR_UI_Facets
 * @package wpsolr\core\classes\ui
 */
class WPSOLR_UI_Facets {

	/**
	 * Build facets UI
	 *
	 * @param array $facets
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function Build( $facets ) {

		return WPSOLR_Service_Container::get_template_builder()->load_template_facets( $facets );
	}

}
