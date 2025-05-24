<?php

namespace wpsolr\core\classes\admin\ui\ajax;

use wpsolr\core\classes\engines\configuration\builder\WPSOLR_Analyser_Abstract;
use wpsolr\core\classes\engines\configuration\WPSOLR_Configurations_Builder_Factory;


/**
 * Retrieve terms
 *
 * Class WPSOLR_Admin_UI_Ajax_Search_Index_Configurationsextends
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Search_Index_Configurations extends WPSOLR_Admin_UI_Ajax_Search {

	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		$term       = empty( $parameters[ self::PARAMETER_TERM ] ) ? '' : $parameters[ self::PARAMETER_TERM ];
		$exclude    = empty( $parameters[ self::PARAMETER_EXCLUDE ] ) ? '' : $parameters[ self::PARAMETER_EXCLUDE ];
		$index_uuid = empty( $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['index_uuid'] ) ? '' : $parameters[ self::PARAMETER_PARAMS_EXTRAS ]['index_uuid'];

		$results = [];

		/** @var WPSOLR_Analyser_Abstract $analyser */
		foreach ( WPSOLR_Configurations_Builder_Factory::get_configurations() as $id => $analyser ) {

			if ( empty( $term ) || ( false !== strpos( strtolower( $analyser->get_label() ), strtolower( $term ) ) ) ) {
				if ( empty( $exclude ) || ( $exclude !== $analyser->get_id() ) ) {
					$results[] = [
						'id'    => $id,
						'label' => sprintf( '%s - %s', $analyser->get_label(), $analyser->get_id() )
					];
				}
			}
		}

		return $results;
	}


}