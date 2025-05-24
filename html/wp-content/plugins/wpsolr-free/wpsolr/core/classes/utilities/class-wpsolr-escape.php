<?php

namespace wpsolr\core\classes\utilities;

/**
 * Escape output
 *
 */
class WPSOLR_Escape {

	/**
	 * @param string $json
	 * @param bool $html
	 *
	 * Inspired from wc_esc_json()
	 *
	 * @return string
	 */
	public static function esc_json( string $json, bool $html = true ) {
		if ( ! $html ) {
			return _wp_specialchars(
				$json,
				$html ? ENT_NOQUOTES : ENT_QUOTES, // Escape quotes in attribute nodes only.
				'UTF-8',                           // json_encode() outputs UTF-8 (really just ASCII), not the blog's charset.
				true                               // Double escape entities: `&amp;` -> `&amp;amp;`.
			);
		}

		// Already done
		return $json;
	}

	/**
	 * @param string $textarea
	 *
	 * @return string
	 */
	public static function esc_textarea( string $textarea ): string {
		// We use escape attributes in WPSOLR
		#return esc_textarea( $textarea );
		return self::esc_attr( $textarea, false );
	}

	/**
	 * @param string $url
	 *
	 */
	public static function esc_url( string $url ) {
		return esc_url( $url );
	}

	/**
	 * @param string $attr
	 * @param bool $is_strict
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function esc_attr_jquery( string $attr ): string {
		// jQuery's selectors are HTML
		return static::esc_attr( $attr, false );
	}

	/**
	 * @param string $attr
	 * @param bool $is_strict
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function esc_attr( string $attr, bool $is_strict = true ): string {
		$esc_attr = esc_attr( $attr );
		if ( $is_strict && ( $attr !== $esc_attr ) ) {
			static::error_log( sprintf( '(WPSOLR) Attribute is different from escaped value: %s => %s', $attr, $esc_attr ) );
		}

		return $esc_attr;
	}

	/**
	 * @param string $attr
	 *
	 */
	public static function echo_esc_attr_jquery( string $attr ) {
		// jQuery's selectors are HTML
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo static::esc_attr_jquery( $attr );
	}

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	public static function esc_html( string $html ): string {
		return esc_html( $html );
	}

	/**
	 * @param string $escaped
	 *
	 * @return string
	 */
	public static function esc_escaped( string $escaped ): string {
		// Do nothing on already escaped
		return $escaped;
	}

	/**
	 * @param string $escaped
	 *
	 */
	public static function echo_escaped( string $escaped ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo static::esc_escaped( $escaped );
	}

	/**
	 * @param string $attr
	 *
	 * @throws \Exception
	 */
	public static function echo_esc_attr( string $attr ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo static::esc_attr( $attr );
	}

	/**
	 * @param string $html
	 *
	 */
	public static function echo_esc_html( string $html ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo static::esc_html( $html );
	}

	/**
	 * @param string $url
	 *
	 */
	public static function echo_esc_url( string $url ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo static::esc_url( $url );
	}

	/**
	 * @param string $json
	 *
	 */
	public static function echo_esc_json( string $json, bool $html = true ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo static::esc_json( $json, $html );
	}

	/**
	 * @param string $textarea
	 *
	 * @return void
	 */
	public static function echo_esc_textarea( string $textarea ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo static::esc_textarea( $textarea );
	}

	/**
	 * @param string $js_script
	 *
	 * @return void
	 */
	public static function echo_esc_js( string $js_script ) {
		echo esc_js( $js_script );
	}

	public static function esc_html_but_href( string $html ) {
		return wp_kses( $html, [ 'a' => [ 'href' => [], 'title' => [], 'target' => [] ], ] );
	}

	public static function error_log( string $message ) {
		if ( WP_DEBUG === true ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $message );
		}
	}

}
