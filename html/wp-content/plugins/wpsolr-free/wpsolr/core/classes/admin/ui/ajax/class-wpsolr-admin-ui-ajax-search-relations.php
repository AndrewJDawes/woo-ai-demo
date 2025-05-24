<?php

namespace wpsolr\core\classes\admin\ui\ajax;


use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Retrieve taxonomies
 *
 * Class WPSOLR_Admin_UI_Ajax_Search_Relations
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Search_Relations extends WPSOLR_Admin_UI_Ajax_Search_Filter_Object_List {


	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		$object_type = $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['object_type'] ?? '';

		$model_type_object     = WPSOLR_Model_Builder::get_model_type_object( $object_type );
		$model_type_fields     = $model_type_object->get_fields();
		$model_type_taxonomies = $model_type_object->get_taxonomies();

		$results = [];
		foreach ( array_merge( $model_type_fields, $model_type_taxonomies ) as $slug ) {
			$results[] = (object) ( [ 'name' => $slug . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING, 'label' => $slug ] );
		}

		return $results;
	}


}