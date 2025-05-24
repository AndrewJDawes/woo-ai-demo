<?php

namespace wpsolr\core\classes\admin\ui\ajax;

use wpsolr\core\classes\engines\configuration\builder\WPSOLR_Configuration_Builder_Factory;


/**
 * Retrieve Filters
 *
 * Class WPSOLR_Admin_UI_Ajax_Search_Index_Configurations_Tokenizer_Filters
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Search_Index_Configurations_Tokenizer_Filters extends WPSOLR_Admin_UI_Ajax_Search {

	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		$term       = empty( $parameters[ self::PARAMETER_TERM ] ) ? '' : $parameters[ self::PARAMETER_TERM ];
		$exclude    = empty( $parameters[ self::PARAMETER_EXCLUDE ] ) ? '' : $parameters[ self::PARAMETER_EXCLUDE ];
		$index_uuid = empty( $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['index_uuid'] ) ? '' : $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['index_uuid'];

		$results = [];

		foreach ( WPSOLR_Configuration_Builder_Factory::get_tokenizer_filters() as $builder_class_name ) {
			if ( empty( $term ) || ( false !== strpos( strtolower( $builder_class_name::get_factory_class_name() ), strtolower( $term ) ) ) ) {
				if ( empty( $exclude ) || ( $exclude !== $builder_class_name::get_factory_class_name() ) ) {
					$results[] = [
						'id'    => $builder_class_name::get_factory_class_name(),
						'label' => $builder_class_name::get_factory_class_name()
					];
				}
			}
		}

		return $results;
	}

}