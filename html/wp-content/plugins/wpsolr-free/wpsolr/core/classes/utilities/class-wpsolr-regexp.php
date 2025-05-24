<?php

namespace wpsolr\core\classes\utilities;

/**
 * Common Regexp expressions used in WPSOLR.
 *
 * Class WPSOLR_Regexp
 * @package wpsolr\core\classes\utilities
 */
class WPSOLR_Regexp {

	/**
	 * Extract values from a range query parameter
	 * '[5 TO 30]' => ['5', '30']
	 *
	 * @param $text
	 *
	 * @return array
	 */
	static function extract_filter_range_values( $text ) {

		// Replace separator literals by a single special character. Much easier, because negate a literal is difficult with regexp.
		$text = str_replace( [ ' TO ', '[', ']' ], ' | ', $text );

		// Negate all special caracters to get the 'field:value' array
		preg_match_all( '/[^|\s]+/', $text, $matches );

		// Trim results
		$results_with_some_empty_key = ! empty( $matches[0] ) ? array_map( 'trim', $matches[0] ) : [];

		// Remove empty array rows (it happens), prevent duplicates.
		$results = [];
		foreach ( $results_with_some_empty_key as $result ) {
			if ( ! empty( $result ) ) {
				array_push( $results, $result );
			}
		}

		return $results;
	}

	/**
	 * Extract last occurence of a separator
	 * 'field1' => ''
	 * 'field1_asc' => 'asc'
	 * 'field1_notme_asc' => 'asc'
	 *
	 * @param $text
	 * @param $text_to_find
	 *
	 * @return string
	 */
	static function extract_last_separator( $text, $separator ) {

		$separator_escaped = preg_quote( $separator, '/' );
		preg_match( sprintf( '/[%s]+[^%s]*$/', $separator_escaped, $separator_escaped ), $text, $matches );

		return ! empty( $matches ) ? substr( $matches[0], strlen( $separator ) ) : $text;
	}

	/**
	 * Extract first occurence of a separator
	 * 'field1' => 'field1'
	 * 'field1_asc' => 'field1'
	 * 'field1_notme_asc' => 'field1'
	 *
	 * @param $text
	 * @param $text_to_find
	 *
	 * @return string
	 */
	static function extract_first_separator_before( $text, $separator ) {

		if ( empty( $text ) || empty( $separator ) ) {
			return '';
		}

		$separator_escaped = preg_quote( $separator, '/' );
		preg_match( sprintf( '/^[^%s]+/', $separator_escaped ), $text, $matches );

		return ! empty( $matches ) ? $matches[0] : '';
	}

	/**
	 * Extract first occurence of a separator
	 * 'field1' => ''
	 * 'field1_asc' => 'asc'
	 * 'field1_notme_asc' => 'notme_asc'
	 *
	 * @param $text
	 * @param $text_to_find
	 *
	 * @return string
	 */
	static function extract_first_separator_after( $text, $separator ) {

		if ( empty( $text ) || empty( $separator ) ) {
			return '';
		}

		$separator_escaped = preg_quote( $separator, '/' );
		preg_match( sprintf( '/(?<=%s).*$/', $separator_escaped ), $text, $matches );

		return ! empty( $matches ) ? $matches[0] : '';
	}

	/**
	 * Remove $text_to_remove at the end of $text
	 *
	 * @param $text
	 * @param $text_to_remove
	 *
	 * @return string
	 */
	static function remove_string_at_the_end( $text, $text_to_remove ) {

		if ( '' === $text ) {
			return '';
		}

		if ( '' === $text_to_remove ) {
			return $text;
		}

		return preg_replace( sprintf( '/%s$/', preg_quote( $text_to_remove, '/' ) ), '', $text );
	}

	/**
	 * Remove $text_to_remove at the beginning of $text
	 *
	 * @param $text
	 * @param $text_to_remove
	 *
	 * @return string
	 */
	static function remove_string_at_the_begining( $text, $text_to_remove ) {

		if ( '' === $text ) {
			return '';
		}

		if ( '' === $text_to_remove ) {
			return $text;
		}

		return preg_replace( sprintf( '/^%s/', preg_quote( $text_to_remove, '/' ) ), '', $text );
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function str_replace_first( $from, $to, $subject ) {
		$from = '/' . preg_quote( $from, '/' ) . '/';

		return preg_replace( $from, $to, $subject, 1 );
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function str_replace_end( $from, $to, $subject ) {
		return self::remove_string_at_the_end( $subject, $from ) . $to;
	}

	/**
	 * @param string $text
	 * @param $pattern
	 *
	 * @return bool
	 */
	public static function is_match_pattern( $text, $pattern ) {

		if ( empty( $text ) || empty( $pattern ) ) {
			return '';
		}

		preg_match( $pattern, $text, $matches );

		return ! empty( $matches );
	}


	/**
	 * Escape control characters (Solr error)
	 *
	 * @param mixed $value_to_strip
	 *
	 * @return void
	 */
	public static function replace_recursive( &$value_to_strip, $pattern, $replacement ) {

		if ( empty( $value_to_strip ) || is_null( $value_to_strip ) || is_numeric( $value_to_strip ) ) {
			return;
		}

		if ( is_array( $value_to_strip ) ) {
			// recursive
			foreach ( $value_to_strip as $field_name => &$field_value ) {

				self::replace_recursive( $field_value, $pattern, $replacement );
			}

		} else {
			$value_to_strip = preg_replace( $pattern, $replacement, $value_to_strip );
		}

	}

	/**
	 * Extract inside first level of parenthesis
	 * 'text:(text)' => 'text'
	 * 'text' => 'text'
	 *
	 * @param $text
	 *
	 * @return string
	 */
	static function extract_parenthesis( $text ) {

		preg_match( sprintf( '/\%s(.*)\%s/', '(', ')' ), $text, $matches );

		return ! empty( $matches ) ? $matches[1] : $text;
	}

	/**
	 * increment_string_with_dash("file");    // Output: file-1
	 * increment_string_with_dash("file-1");  // Output: file-2
	 * increment_string_with_dash("file-99"); // Output: file-100
	 */
	static function increment_string_with_dash( $string ) {
		// Regular expression to match the string ending with a dash followed by a number
		if ( preg_match( '/^(.*?)-(\d+)$/', $string, $matches ) ) {
			// If the string ends with a dash followed by a number, increment the number
			$base   = $matches[1];
			$number = (int) $matches[2] + 1;

			return $base . '-' . $number;
		} else {
			// If the string doesn't end with a dash and a number, append "-1"
			return $string . '-1';
		}
	}

	public static function format_multiline_regexp( string $regexp ): string {

		$clean_regex_lines = [];
		foreach ( explode( "\n", $regexp ) as $line ) {
			$line = trim( $line, "\r/ " ); // Trim spaces and slashes
			if ( ! empty( $line ) ) {
				$clean_regex_lines[] = $line;
			}
		}

		if ( empty( $clean_regex_lines ) ) {
			// No regexp
			return '';
		}

		$final_regex = '/' . implode( '|', $clean_regex_lines ) . '/m'; // Join regex lines with OR (|) operator
		if ( false === @preg_match( $final_regex, '' ) ) {
			throw new \Exception( sprintf( "Invalid regular expression: %s", esc_html( $regexp ) ) );
		}

		return $final_regex;
	}
}
