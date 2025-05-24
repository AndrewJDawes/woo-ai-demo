<?php
/**
 * Custom namespace autoload
 */

namespace wpsolr;


use wpsolr\core\classes\utilities\WPSOLR_Escape;

/**
 * Class WPSOLR_Autoloader
 * @package wpsolr
 */
class WPSOLR_Autoloader {

	/**
	 * Autoload classes based on namespaces beginning with 'wpsolr'
	 *
	 * @param $className
	 *
	 * @return bool
	 */
	static function Load( $className ) {

		if ( substr( $className, 0, strlen( 'wpsolr' ) ) === 'wpsolr' ) {

			$file_name = str_replace( '\\', '/', $className );

			$base_name = basename( $file_name );
			$base_name = strtolower( $base_name );

			if (
				( ! str_ends_with( $className, 'Acceptance' ) ) &&
				( ! str_ends_with( $className, 'Acceptance_Abstract' ) ) &&
				( ! str_ends_with( $className, 'AcceptanceTest' ) )
			) {
				// phpunit tests must start with wpsolr_, not WPSOLR plugin files
				$base_name = str_replace( '_', '-', $base_name );
				$base_name = 'class-' . $base_name;
			}

			$file_name = ( defined( 'WPSOLR_PLUGIN_DIR' ) ? WPSOLR_PLUGIN_DIR : WPSOLR_PLUGIN_DIR ) . '/' . dirname( $file_name ) . '/' . $base_name . '.php';
			$file_name = str_replace( '/wpsolr_root', '', $file_name ); // Namespace wpsolr_root is mapped on root folder

			if ( file_exists( $file_name ) ) {

				require_once( $file_name );

				if ( class_exists( $className ) || trait_exists( $className ) || interface_exists( $className ) ) {

					return true;

				} else {

					WPSOLR_Escape::echo_escaped( sprintf( 'WPSOLR autoload error: class %s not found in file %s', WPSOLR_Escape::esc_html( $className ), WPSOLR_Escape::esc_html( $file_name ) ) );
				}

			} else {
				WPSOLR_Escape::echo_escaped( sprintf( 'WPSOLR autoload error: file %s not found for class %s', WPSOLR_Escape::esc_html( $file_name ), WPSOLR_Escape::esc_html( $className ) ) );
			}

			die();

		}

		return false;
	}
}

// autoloader declaration for phpunit bootstrap script in phpunit.xml
spl_autoload_register( [ WPSOLR_Autoloader::CLASS, 'Load' ] );
