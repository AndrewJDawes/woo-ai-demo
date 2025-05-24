<?php

namespace wpsolr\core\classes\utilities;

/**
 * Sanitize variables
 *
 */
class WPSOLR_Sanitize {

	/**
	 * @param mixed $to_be_sanitized
	 * @param array $path_keys
	 *
	 * $data = [
	 *   'field1' => [
	 *      'field2' => [
	 *          'field3' => 'final value'
	 *      ]
	 *   ]
	 * ];
	 *
	 * $path = ['field1', 'field2', 'field3'];
	 * $value = _get_nested_value($data, $path);
	 *
	 * $value: 'final value'
	 *
	 * @return mixed
	 */
	private static function _get_nested_value( mixed $to_be_sanitized, array $path_keys, mixed $default_value = '' ): mixed {

		if ( ! is_array( $to_be_sanitized ) || empty( $path_keys ) ) {
			return $to_be_sanitized;
		}

		foreach ( $path_keys as $key ) {
			if ( ! is_array( $to_be_sanitized ) || ! array_key_exists( $key, $to_be_sanitized ) ) {
				return $default_value;
			}
			$to_be_sanitized = $to_be_sanitized[ $key ];
		}

		return $to_be_sanitized;
	}

	/**
	 * Recursive sanitization of string and arrays
	 *
	 * @param string|array $to_be_sanitized
	 *
	 * @return string|array
	 */
	public static function sanitize_text_field( mixed $to_be_sanitized, array $path_keys = [], mixed $default_value = '' ) {

		$to_be_sanitized = static::_get_nested_value( $to_be_sanitized, $path_keys, $default_value );

		if ( is_array( $to_be_sanitized ) ) {

			// Array
			$results = [];
			foreach ( $to_be_sanitized as $key => $value ) {
				$results[ $key ] = static::sanitize_text_field( $value );
			}

			return $results;

		} else {
			// Simple text
			return sanitize_text_field( $to_be_sanitized );
		}

	}

	/**
	 * Recursive sanitization of string and arrays
	 *
	 * @param string|array $to_be_sanitized
	 *
	 * @return string|array
	 */
	public static function sanitize_int_field( mixed $to_be_sanitized, array $path_keys = [], mixed $default_value = '' ) {

		$to_be_sanitized = static::_get_nested_value( $to_be_sanitized, $path_keys, $default_value );

		if ( is_array( $to_be_sanitized ) ) {

			// Array
			$results = [];
			foreach ( $to_be_sanitized as $key => $value ) {
				$results[ $key ] = static::sanitize_int_field( $value );
			}

			return $results;

		} else {
			// Remove all non-digit characters
			$sanitized = filter_var( $to_be_sanitized, FILTER_SANITIZE_NUMBER_INT );

			// Convert the sanitized string to an integer
			return intval( $sanitized );
		}

	}

	/**
	 * Do not remove line breaks for certificates for instance
	 *
	 * @param string $to_be_sanitized
	 *
	 * @return string
	 */
	public static function sanitize_textarea_field( mixed $to_be_sanitized, array $path_keys = [], mixed $default_value = '' ): string {
		$to_be_sanitized = static::_get_nested_value( $to_be_sanitized, $path_keys, $default_value );

		return sanitize_textarea_field( $to_be_sanitized );
	}

	public static function sanitized( mixed $to_be_sanitized, array $path_keys = [], mixed $default_value = '' ) {
		// Do not sanitize. For instance during import of json settings containing also HTML and JS.
		return static::_get_nested_value( $to_be_sanitized, $path_keys, $default_value );
	}

	public static function sanitize_request_uri(): string {
		return isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	}

	public static function sanitize_title_for_query( mixed $to_be_sanitized, array $path_keys = [], mixed $default_value = '' ): string {
		$to_be_sanitized = static::_get_nested_value( $to_be_sanitized, $path_keys, $default_value );

		return sanitize_title_for_query( $to_be_sanitized );
	}

	public static function sanitize_query_string(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return wp_unslash( $_SERVER['QUERY_STRING'] ?? '' ); # not sanitized, else query no more valid
	}

	public static function sanitized_register_setting( $option_value ): mixed {
		// Do nothing. Would break things. Protected with nonce.
		return $option_value;
	}

}
