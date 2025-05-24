<?php

namespace wpsolr\core\classes\extensions\localization;

use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class OptionLocalization
 *
 * Manage localization options
 */
class OptionLocalization extends WPSOLR_Extension {


	/*
	 * Section code constants. Do not change.
	 */
	const TERMS = 'terms';
	const SECTION_CODE_SEARCH_FORM = 'section_code_search_form';
	const SECTION_CODE_SORT = 'section_code_sort';
	const SECTION_CODE_FACETS = 'section_code_facets';

	/*
	 * Array key constants. Do not change.
	 */
	const KEY_SECTION_NAME = 'section_name';
	const KEY_SECTION_TERMS = 'section_terms';


	/**
	 * Get the whole array of default options
	 *
	 * @return array Array of default options
	 */
	static function get_default_options() {

		$is_intern = WPSOLR_Service_Container::getOption()->get_localization_is_internal();

		return [
			/* Choice of localization method */
			'localization_method' => 'localization_by_admin_options',
			/* Localization terms */
			self::TERMS           => [
				/* Search Form */
				'search_form_button_label'                                     => $is_intern ? _x( 'Search', 'Search form button label', 'wpsolr-free' ) : self::_x( 'search_form_button_label', 'Search', 'Search form button label' ),
				'search_form_edit_placeholder'                                 => $is_intern ? _x( 'Search ....', 'Search edit placeholder', 'wpsolr-free' ) : self::_x( 'search_form_edit_placeholder', 'Search ....', 'Search edit placeholder' ),
				'sort_header'                                                  => $is_intern ? _x( 'Sort by', 'Sort list header', 'wpsolr-free' ) : self::_x( 'sort_header', 'Sort by', 'Sort list header' ),
				/* Sort */
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC       => $is_intern ? _x( 'More relevant', 'Sort list element', 'wpsolr-free' ) : self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC, 'More relevant', 'Sort list element' ),
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_ASC             => $is_intern ? _x( 'Oldest', 'Sort list element', 'wpsolr-free' ) : self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_ASC, 'Oldest', 'Sort list element' ),
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_DESC            => $is_intern ? _x( 'Newest', 'Sort list element', 'wpsolr-free' ) : self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_DESC, 'Newest', 'Sort list element' ),
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_DESC => $is_intern ? _x( 'The more commented', 'Sort list element', 'wpsolr-free' ) : self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_DESC, 'The more commented', 'Sort list element' ),
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_ASC  => $is_intern ? _x( 'The least commented', 'Sort list element', 'wpsolr-free' ) : self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_ASC, 'The least commented', 'Sort list element' ),
				'facets_header'                                                => $is_intern ? _x( 'Filters', 'Facets list header', 'wpsolr-free' ) : self::_x( 'facets_header', 'Filters', 'Facets list header' ),
				/* Facets */
				/* translators: placeholder contains the search filter name, like 'color'. */
				'facets_title'                                                 => $is_intern ? _x( 'By %s', 'Facets list title', 'wpsolr-free' ) : self::_x( 'facets_title', 'By %s', 'Facets list title' ),
				'facets_element_all_results'                                   => $is_intern ? _x( 'All results', 'Facets list element all results', 'wpsolr-free' ) : self::_x( 'facets_element_all_results', 'All results', 'Facets list element all results' ),
				/* translators: 1: search filter name like 'blue', 2: search filter count like 32. */
				'facets_element'                                               => $is_intern ? _x( '%1$s (%2$d)', 'Facets list element name with #results', 'wpsolr-free' ) : self::_x( 'facets_element', '%s (%d)', 'Facets list element name with #results' ),
				'facets_show_more'                                             => $is_intern ? _x( 'Show more', 'Link "Show more"', 'wpsolr-free' ) : self::_x( 'facets_show_more', 'Show more', 'Link "Show more"' ),
				'facets_show_less'                                             => $is_intern ? _x( 'Show less', 'Link "Show less"', 'wpsolr-free' ) : self::_x( 'facets_show_less', 'Show less', 'Link "Show less"' ),
				/* Results header */
				/* translators: placeholder contains a search spell correction, like 'red t-shirt' if 'red t-chirt' was used. */
				'results_header_did_you_mean'                                  => $is_intern ? _x( 'Did you mean: %s', 'Results header: did you mean ?', 'wpsolr-free' ) : self::_x( 'results_header_did_you_mean', 'Did you mean: %s', 'Results header: did you mean ?' ),
				/* translators: 1: search results from like 40, 2: search results to like 60, 3: search results total like 9999. */
				'results_header_pagination_numbers'                            => $is_intern ? _x( 'Showing %1$d to %2$d results out of %3$d', 'Results header: pagination numbers', 'wpsolr-free' ) : self::_x( 'results_header_pagination_numbers', 'Showing %d to %d results out of %d', 'Results header: pagination numbers' ),
				/* translators: placeholder contains the number of search results, like 20. */
				'infinitescroll_results_header_pagination_numbers'             => $is_intern ? _x( 'Showing %d results', 'Results header: infinitescroll pagination numbers', 'wpsolr-free' ) : self::_x( 'infinitescroll_results_header_pagination_numbers', 'Showing %d results', 'Results header: infinitescroll pagination numbers' ),
				/* translators: placeholder contains the search query, like 't-shirts with discounts'. */
				'results_header_no_results_found'                              => $is_intern ? _x( 'No results found for %s', 'Results header: no results found', 'wpsolr-free' ) : self::_x( 'results_header_no_results_found', 'No results found for %s', 'Results header: no results found' ),
				/* translators: placeholder contains the search result author, like 'johnny654'. */
				'results_row_by_author'                                        => $is_intern ? _x( 'By %s', 'Result row information box: by author', 'wpsolr-free' ) : self::_x( 'results_row_by_author', 'By %s', 'Result row information box: by author' ),
				/* translators: placeholder contains the current search category, like 'Shoes'. */
				'results_row_in_category'                                      => $is_intern ? _x( ', in %s', 'Result row information box: in category', 'wpsolr-free' ) : self::_x( 'results_row_in_category', ', in %s', 'Result row information box: in category' ),
				/* translators: placeholder contains the search result published date, like '2025/04/01'. */
				'results_row_on_date'                                          => $is_intern ? _x( ', on %s', 'Result row information box: on date', 'wpsolr-free' ) : self::_x( 'results_row_on_date', ', on %s', 'Result row information box: on date' ),
				/* translators: placeholder contains the current number of post comments, like 4. */
				'results_row_number_comments'                                  => $is_intern ? _x( ', %d comments', 'Result row information box: number of comments', 'wpsolr-free' ) : self::_x( 'results_row_number_comments', ', %d comments', 'Result row information box: number of comments' ),
				'results_row_comment_link_title'                               => $is_intern ? _x( '-Comment match', 'Result row comment box: comment link title', 'wpsolr-free' ) : self::_x( 'results_row_comment_link_title', '-Comment match', 'Result row comment box: comment link title' ),
				'infinitescroll_loading'                                       => $is_intern ? _x( 'Loading ...', 'Text displayed while infinite scroll is loading next page of results', 'wpsolr-free' ) : self::_x( 'infinitescroll_loading', 'Loading ...', 'Text displayed while infinite scroll is loading next page of results' ),
				'geolocation_ask_user'                                         => $is_intern ? _x( 'Use my current location', 'Geolocation, ask user', 'wpsolr-free' ) : self::_x( 'geolocation_ask_user', 'Use my current location', 'Geolocation, ask user' ),
			]
		];
	}

	/**
	 * @param string $name
	 * @param string $text Text to translate.
	 * @param string $context Context information for the translators.
	 *                        Default 'default'.
	 *
	 * @return string Translated context string without pipe.
	 */
	static function _x( $name, $text, $context ) {

		// Creates/uses string
		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_LOCALIZATION_STRING, $text,
			[
				'context' => $context,
				'domain'  => 'wpsolr',
				'name'    => $name,
				'text'    => $text,
			] );
	}

	/**
	 * Get the presentation array
	 *
	 * @return array Array presentation options
	 */
	static function get_presentation_options() {

		return array(
			'Search Form box'            =>
				array(
					self::KEY_SECTION_TERMS => array(
						'search_form_button_label'     => array( 'Search form button label' ),
						'search_form_edit_placeholder' => array( 'Search edit placeholder' ),
					),
				),
			'Sort list box'              =>
				array(
					self::KEY_SECTION_TERMS => array(
						'sort_header'                                                  => array( 'Sort list header' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC       => array( 'Sort list element' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_ASC             => array( 'Sort list element' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_DESC            => array( 'Sort list element' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_ASC  => array( 'Sort list element' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_DESC => array( 'Sort list element' ),
					),
				),
			'Facets box'                 =>
				array(
					self::KEY_SECTION_TERMS => array(
						'facets_header'              => array( 'Facets list header' ),
						'facets_title'               => array( 'Facets list title' ),
						'facets_element_all_results' => array( 'Facets list element all results' ),
						'facets_element'             => array( 'Facets list element name with #results' ),
						'facets_show_more'           => array( 'Facets link "Show All"' ),
						'facets_show_less'           => array( 'Facets link "Show Less"' ),
					),
				),
			'Results Header box'         =>
				array(
					self::KEY_SECTION_TERMS => array(
						'results_header_did_you_mean'       => array( 'Did you mean (automatic keyword spell correction)' ),
						'results_header_pagination_numbers' => array( 'Pagination header on top of results' ),
						'results_header_no_results_found'   => array( 'Message no results found' ),
					),
				),
			'Result Row information box' =>
				array(
					self::KEY_SECTION_TERMS => array(
						'results_row_by_author'          => array( 'Author of the result row' ),
						'results_row_in_category'        => array( 'Category of the result row' ),
						'results_row_on_date'            => array( 'Date of the result row' ),
						'results_row_number_comments'    => array( 'Number of comments of the result row' ),
						'results_row_comment_link_title' => array( 'Comment link title' ),
					),
				),
			'Infinite Scroll'            =>
				array(
					self::KEY_SECTION_TERMS => array(
						'infinitescroll_loading'                           => array( 'Text displayed while Infinite Scroll is loading the next page' ),
						'infinitescroll_results_header_pagination_numbers' => array( 'Pagination header on top of results' ),
					),
				),
			'Geolocation'                =>
				array(
					self::KEY_SECTION_TERMS => array(
						'geolocation_ask_user' => array( 'Text accompanying the checkbox asking user agreement to use his location' ),
					),
				),
		);
	}

	/**
	 * Get the whole array of options.
	 * Merge between default options and customized options.
	 *
	 * @param $is_internal_localized boolean Force internal options
	 *
	 * @return array Array of options
	 */
	static function get_options( $is_internal_localized = null ) {

		$default_options = self::get_default_options();

		$database_options = WPSOLR_Service_Container::getOption()->get_option_localization();
		if ( $database_options !== null
		     && isset( $default_options[ self::TERMS ] )
		     && isset( $default_options[ self::TERMS ]['search_form_button_label'] )
		     && ( $default_options[ self::TERMS ]['search_form_button_label'] === 'Search' ) // Override only default language 'en', not translations
		) {
			// Replace default values with by database (customized) values with same key.
			// Why do that ? Because we can have added new terms in the default terms,
			// and they must be used even not customized by the user.

			return array_replace_recursive( $default_options, $database_options );
		}

		// Return default options not customized
		return $default_options;
	}


	/**
	 * Get the whole array of localized terms.
	 *
	 * @param array $options Array of options
	 *
	 * @return array Array of localized terms
	 */
	static function get_terms( $options ) {

		return ( isset( $options ) && isset( $options[ self::TERMS ] ) )
			? $options[ self::TERMS ]
			: [];
	}


	/**
	 * Get terms of a presentation section
	 *
	 * @param array $section Section
	 *
	 * @return array Terms of the section
	 */
	static function get_section_terms( $section ) {

		return
			( ! empty( $section ) )
				? $section[ self::KEY_SECTION_TERMS ]
				: [];
	}

	/**
	 * Get a localized term.
	 * If it does not exist, send the term code instead.
	 *
	 * @param array $option
	 * @param string $term_code A term code
	 *
	 * @return string Term
	 */
	static function get_term( $option, $term_code ) {

		$value = ( isset( $option[ self::TERMS ][ $term_code ] ) ) ? $option[ self::TERMS ][ $term_code ] : $term_code;

		return $value;
	}

}
