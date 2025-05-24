<?php

namespace wpsolr\core\classes\ui;

use wpsolr\core\classes\services\WPSOLR_Service_Container;

/**
 * Display sort list
 *
 * Class WPSOLR_UI_Sort
 * @package wpsolr\core\classes\ui
 */
class WPSOLR_UI_Sort {

	/**
	 * Build sort list UI
	 *
	 * @param $sorts
	 *
	 * @return string
	 * @throws \Exception
	 */
	public
	static function build(
		$sorts
	) {

		return WPSOLR_Service_Container::get_template_builder()->load_template_sort( $sorts );
	}
}
