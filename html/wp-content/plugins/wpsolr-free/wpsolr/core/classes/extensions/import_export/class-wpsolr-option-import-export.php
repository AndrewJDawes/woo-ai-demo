<?php

namespace wpsolr\core\classes\extensions\import_export;

use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;

/**
 *
 * Import / Export WPSOLR PRO settings to / from a file.
 */
class WPSOLR_Option_Import_Export extends WPSOLR_Extension {


	/**
	 * Constructor
	 * Subscribe to actions/filters
	 **/
	function __construct() {
	}

	static function get_all_option_views_to_export(): array {

		$option_views_to_export = [];
		foreach ( wp_load_alloptions() as $option_name => $option_data ) {
			if ( ( ( 0 === strpos( $option_name, 'wdm_', 0 ) ) ||
			       ( 0 === strpos( $option_name, 'wpsolr_', 0 ) ) ||
			       ( 0 === strpos( $option_name, 'solr_', 0 ) )
			     ) &&
			     get_option( $option_name ) ) {

				if ( ! ( WPSOLR_Option::OPTIONS_DEFINITIONS[ $option_name ]['is_exported'] ?? true ) ) {
					// Option is not to be exported
					continue;
				}

				$option_name_uuid         = WPSOLR_Regexp::extract_last_separator( $option_name, '_' );
				$option_name_without_uuid = substr( $option_name, 0, strlen( $option_name ) - strlen( '_' . $option_name_uuid ) );
				$view                     = $views_option[ $option_name_uuid ] ?? [];
				$index                    = $indexes_option[ $option_name_uuid ] ?? [];
				$label                    = 'all views and indexes';

				if ( ! ( WPSOLR_Option::OPTIONS_DEFINITIONS[ $option_name_without_uuid ]['is_exported'] ?? true ) ) {
					// Option is not to be exported
					continue;
				}

				if ( WPSOLR_Service_Container::getOption()->get_is_option_type_view( $option_name_without_uuid ) ) {
					if ( isset( $view[ WPSOLR_Option::OPTION_VIEW_LABEL ] ) ) {
						$label = sprintf( 'view "%s"', $view[ WPSOLR_Option::OPTION_VIEW_LABEL ] );
					} else {
						$label = 'All views';
					}
				} elseif ( WPSOLR_Service_Container::getOption()->get_is_option_type_index( $option_name_without_uuid ) ) {
					if ( isset( $index['index_name'] ) ) {
						$label = sprintf( 'index "%s"', $index['index_name'] );
					} else {
						$label = 'All indexes';
					}
				}

				$description = WPSOLR_Option::OPTIONS_DEFINITIONS[ $option_name ]['description'] ??
				               WPSOLR_Option::OPTIONS_DEFINITIONS[ $option_name_without_uuid ]['description'] ??
				               sprintf( 'Missing: %s', $option_name );

				$option_views_to_export[ $label ][ $option_name ] = [
					'description' => $description,
					'data'        => get_option( $option_name ),
				];

			}
		}

		return $option_views_to_export;
	}

	static function get_all_options_to_export( $option_views_to_export = [] ): array {
		$exports = [];
		foreach ( $option_views_to_export ?: static::get_all_option_views_to_export() as $view_name => $option_views ) {
			foreach ( $option_views as $option_name => $option ) {

				if ( empty( $options ) || isset( $options[ $option_name ] ) ) {
					// Export options selected

					$exports[ $option_name ] = $option['data'];
				}
			}
		}

		return $exports;
	}

	public static function import_data( array $wpsolr_data_to_import ): void {
		foreach ( $wpsolr_data_to_import as $option_name => $option_data ) {
			if ( ! empty( $option_data ) ) {
				// Save the option
				update_option( $option_name, $option_data );
			} else {
				delete_option( $option_name );
			}
		}
	}


}
