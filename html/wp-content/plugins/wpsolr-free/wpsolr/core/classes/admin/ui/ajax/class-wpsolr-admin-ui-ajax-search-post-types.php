<?php

namespace wpsolr\core\classes\admin\ui\ajax;


/**
 * Retrieve post types
 *
 * Class WPSOLR_Admin_UI_Ajax_Search_Post_Types
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Search_Post_Types extends WPSOLR_Admin_UI_Ajax_Search_Filter_Object_List {


	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		// Retrieve terms
		$results = get_post_types(
			[], //
			'objects'
		);

		return $results;
	}

}