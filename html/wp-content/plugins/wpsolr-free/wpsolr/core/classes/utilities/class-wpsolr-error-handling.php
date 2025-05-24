<?php

namespace wpsolr\core\classes\utilities;

class WPSOLR_Error_Handling {

	/**
	 * @return void
	 */
	static function log_ajax_error_handling(): void {
		set_error_handler( [ static::class, '_set_error_handler' ] );
		register_shutdown_function( [ static::class, '_register_shutdown_function' ] );
	}

	/**
	 * @return void
	 */
	static function deactivate_deprecated_warnings(): void {
		set_error_handler( function ( $errno, $errstr ) {
			// Nothing. Just continue.
			if ( 0 == error_reporting() ) {
				// Error reporting is currently turned off or suppressed with @
				return;
			}
		}, E_USER_DEPRECATED );
	}

	/**
	 * Fatal errors not captured by try/catch in Ajax calls.
	 */
	/**
	 * Handler for fatal errors.
	 *
	 * @param $code
	 * @param $message
	 * @param $file
	 * @param $line
	 */
	static function _set_error_handler( $code, $message, $file, $line ): void {

		/**
		 * Deprecated warning messages from PHP8.1 should not stop the process
		 */
		if ( ( E_DEPRECATED !== $code ) &&
		     ( false === strpos( $message, 'fopen' ) ) ## fopen can trigger an error that must be catched in code later
		) {
			WPSOLR_Escape::echo_esc_json( wp_json_encode(
				[
					'nb_results'        => 0,
					'status'            => $code,
					'message'           => sprintf( 'Error on line %s of file %s: %s', $line, $file, $message ),
					'indexing_complete' => false,
				]
			) );
			die();
		}
	}

	/**
	 * Catch fatal errors, and call the handler.
	 */
	static function _register_shutdown_function(): void {

		$last_error = error_get_last();
		if ( ! is_null( $last_error ) && is_array( $last_error ) && isset( $last_error['type'] ) && ( E_ERROR === $last_error['type'] ) ) {
			// fatal error
			static::_set_error_handler( E_ERROR, $last_error['message'], $last_error['file'], $last_error['line'] );
		}
	}

}