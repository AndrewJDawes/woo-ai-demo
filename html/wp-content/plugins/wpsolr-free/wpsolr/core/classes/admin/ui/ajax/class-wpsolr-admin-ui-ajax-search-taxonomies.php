<?php

namespace wpsolr\core\classes\admin\ui\ajax;


/**
 * Retrieve taxonomies
 *
 * Class WPSOLR_Admin_UI_Ajax_Search_Taxonomies
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Search_Taxonomies extends WPSOLR_Admin_UI_Ajax_Search_Filter_Object_List {


	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		// Retrieve taxonomies
		$results = get_taxonomies( [], 'objects' );

		return $results;
	}

}