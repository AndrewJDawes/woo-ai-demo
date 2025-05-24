<?php

namespace wpsolr\core\classes\models;


use wpsolr\core\classes\models\post\WPSOLR_Model_Meta_Type_Post;
use wpsolr\core\classes\models\taxonomy\WPSOLR_Model_Meta_Type_Taxonomy;
use wpsolr\core\classes\models\user\WPSOLR_Model_Meta_Type_User;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_Model_Builder
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model_Builder {

	/** @var string[] */
	protected static $all_model_types;


	/**
	 * @throws \Exception
	 */
	static public function get_all_meta_types() {

		return array_map(
			function ( $class_modeL_type ) {
				/** @var WPSOLR_Model_Meta_Type_Abstract $class_modeL_type */
				return $class_modeL_type::get_meta_type();
			},
			WPSOLR_Model_Meta_Type_Abstract::get_all_model_types()
		);

	}

	/**
	 * Get all the models that can be indexed
	 *
	 * @return string[]
	 * @throws \Exception
	 */
	static private function _get_all_model_types() {

		if ( isset( static::$all_model_types ) ) {
			return static::$all_model_types;
		}

		static::$all_model_types = [];


		foreach ( WPSOLR_Model_Meta_Type_Abstract::get_all_model_types() as $model_type ) {

			switch ( $model_type ) {

				case WPSOLR_Model_Meta_Type_Post::class:
					/**
					 * Add all post types, but a few ones
					 */
					foreach ( get_post_types() as $post_type ) {
						if ( ! in_array( $post_type, [ 'xxattachment', 'xxxrevision', 'xxxnav_menu_item' ] ) ) {
							array_push( static::$all_model_types, $post_type );
						}
					}
					break;

				case WPSOLR_Model_Meta_Type_Taxonomy::class:
					/**
					 * Add all taxonomies
					 */
					foreach ( get_taxonomies( [ 'public' => true ] ) as $taxonomy ) {
						array_push( static::$all_model_types, $taxonomy );
					}
					break;

				case WPSOLR_Model_Meta_Type_User::class:
					/**
					 * Add custom User type
					 */
					array_push( static::$all_model_types, WPSOLR_Model_Meta_Type_User::TYPE );
					break;




			}

		}

		return static::$all_model_types;
	}


	/**
	 * @param string[] $model_types
	 * @param bool $is_get_all_if_none
	 *
	 * @return WPSOLR_Model_Meta_Type_Abstract[]
	 * @throws \Exception
	 */
	static public function get_model_type_objects( $model_types = [], $is_get_all_if_none = true ) {

		if ( empty( $model_types ) && $is_get_all_if_none ) {
			$model_types = static::_get_all_model_types();
		}

		return WPSOLR_Model_Meta_Type_Abstract::get_model_type_objects( $model_types );
	}


	/**
	 * @param bool $is_suggestion
	 * @param string[] $model_types
	 *
	 * @return string[]
	 * @throws \Exception
	 */
	static public function get_model_types_for_search( $is_suggestion, $model_types = [] ) {

		$model_type_objects = WPSOLR_Model_Meta_Type_Abstract::get_model_type_objects( $model_types );

		$results = [];
		foreach ( $model_type_objects as $model_type_object ) {
			if ( $is_suggestion || $model_type_object->get_is_search() ) {
				$results[] = $model_type_object->get_type();
			}

		}

		return $results;
	}

	/**
	 * @param string $model_type
	 *
	 * @return WPSOLR_Model_Meta_Type_Abstract
	 * @throws \Exception
	 */
	static public function get_model_type_object( $model_type ) {

		$model_types = static::get_model_type_objects( [ $model_type ] );
		if ( empty( $model_types ) ) {
			return null;
		}

		return $model_types[0];
	}

	/**
	 * Retrieve a model from type and an id
	 *
	 * @param WPSOLR_Model_Meta_Type_Abstract $model_type
	 * @param string $model_id
	 *
	 * @return null|WPSOLR_Model_Abstract
	 * @throws \Exception
	 */
	public static function get_model( $model_type, $model_id ) {

		$model = $model_type::get_model( $model_type, $model_id );

		return $model;
	}

	/**
	 * Retrieve a model from type and an id
	 *
	 * @param mixed $document
	 *
	 * @return null|WPSOLR_Model_Abstract
	 * @throws \Exception
	 */
	public static function get_model_from_document( $document ) {

		$model_type = WPSOLR_Model_Builder::get_model_type_object( $document->{WpSolrSchema::_FIELD_NAME_TYPE} );

		$model = $model_type::get_model( $model_type, $document->{WpSolrSchema::_FIELD_NAME_PID} );

		return $model;
	}
}
