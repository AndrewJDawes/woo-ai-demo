<?php

namespace wpsolr\core\classes\extensions\suggestions;

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

class WPSOLR_Option_Suggestions extends WPSOLR_Extension {

	// Default keyword redirection pattern (can be updated in suggestion option for WooCommerce, or bbPress ...)
	const SUGGESTION_REDIRECTION_PATTERN_DEFAULT = '/?s=%s';


	const CLASS_SUGGESTION_TYPE = 'wpsolr_suggestion_type';
	const CLASS_SUGGESTION_LAYOUT = 'wpsolr_suggestion_layout';
	const CLASS_SUGGESTION_GROUPS = 'wpsolr_suggestion_groups';

	const SUGGESTION_LAYOUTS = [];

	/**
	 * Folder containing all the templates, under plugin or theme.
	 */
	const TEMPLATE_ROOT_DIR = 'wpsolr-templates';
	const DIR_PHP = 'php';
	const DIR_TWIG = 'twig';
	const TEMPLATE_BUILDER = 'wpsolr_template_builder';

	/**
	 * Predefined template argements
	 */
	const TEMPLATE_SUGGESTIONS_ARGS_NAME = 'suggestions';

	/**
	 * Fancy templates
	 */
	const OPTION_SUGGESTION_LAYOUT_ID_KEYWORDS_FANCY = 'layout_id_keywords_fancy';
	const OPTION_SUGGESTION_LAYOUT_ID_KEYWORDS_FANCY_2 = 'layout_id_keywords_fancy_2';
	const OPTION_SUGGESTION_LAYOUT_ID_KEYWORDS_FANCY_5 = 'layout_id_keywords_fancy_5';
	const TEMPLATE_SUGGESTIONS_KEYWORDS_FANCY = 'suggestions/fancy/suggestions-keywords.twig';
	const TEMPLATE_SUGGESTIONS_KEYWORDS_FANCY_5 = 'suggestions/fancy-5/suggestions-keywords.twig';
	const TEMPLATE_SUGGESTIONS_KEYWORDS_FANCY_2 = 'suggestions/fancy-2/suggestions-keywords.twig';
	const OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY = 'layout_id_content_fancy';
	const OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY_3 = 'layout_id_content_fancy_3';
	const OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY_4 = 'layout_id_content_fancy_4';
	const OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY_5 = 'layout_id_content_fancy_5';
	const TEMPLATE_SUGGESTIONS_CONTENT_FANCY = 'suggestions/fancy/suggestions-content.twig';
	const TEMPLATE_SUGGESTIONS_CONTENT_FANCY_3 = 'suggestions/fancy-3/suggestions-content.twig';
	const TEMPLATE_SUGGESTIONS_CONTENT_FANCY_4 = 'suggestions/fancy-4/suggestions-content.twig';
	const TEMPLATE_SUGGESTIONS_CONTENT_FANCY_5 = 'suggestions/fancy-5/suggestions-content.twig';
	const OPTION_SUGGESTION_LAYOUT_ID_CONTENT_GROUPED_FANCY = 'layout_id_content_grouped_fancy';
	const OPTION_SUGGESTION_LAYOUT_ID_CONTENT_GROUPED_FANCY_3 = 'layout_id_content_grouped_fancy_3';
	const OPTION_SUGGESTION_LAYOUT_ID_CONTENT_GROUPED_FANCY_4 = 'layout_id_content_grouped_fancy_4';
	const TEMPLATE_SUGGESTIONS_CONTENT_GROUPED_FANCY = 'suggestions/fancy/suggestions-content-grouped.twig';
	const TEMPLATE_SUGGESTIONS_CONTENT_GROUPED_FANCY_3 = 'suggestions/fancy-3/suggestions-content-grouped.twig';
	const TEMPLATE_SUGGESTIONS_CONTENT_GROUPED_FANCY_4 = 'suggestions/fancy-4/suggestions-content-grouped.twig';
	const OPTION_SUGGESTION_LAYOUT_ID_QUESTIONS_ANSWERS_FANCY = 'layout_id_questions_answers_fancy';
	const TEMPLATE_SUGGESTIONS_QUESTIONS_ANSWERS_FANCY = 'suggestions/fancy/suggestions-questions-answers.twig';


	const TEMPLATE_FACETS = 'search/facets.twig';
	const TEMPLATE_FACETS_ARGS_NAME = 'facets';

	const TEMPLATE_SEARCH = 'search/search.twig';
	const TEMPLATE_SEARCH_ARGS_NAME = 'search';

	const TEMPLATE_SORT_LIST = 'search/sort.twig';
	const TEMPLATE_SORT_LIST_ARGS_NAME = 'sort';

	const TEMPLATE_RESULTS_INFINISCROLL = 'search/results-infiniscroll.twig';
	const TEMPLATE_RESULTS_INFINISCROLL_ARGS_NAME = 'search';

	/**
	 * Name of variable containing the template data
	 */
	const TEMPLATE_ARGS = 'wpsolr_template_data';

	/**
	 * Build class from uuid
	 */
	const SUGGESTION_CLASS_PATTERN = 'c%s';

	/**
	 * Template type definitions
	 */
	const SUGGESTION_TEMPLATE_TYPE_DEFINITIONS = [
		WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS          =>
			[
				'fields'        => [
					WPSOLR_Option::OPTION_SUGGESTION_NB,
					WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_CSS,
					WPSOLR_Option::OPTION_SUGGESTION_REDIRECTION_PATTERN,
				],
				'template_args' => self::TEMPLATE_SUGGESTIONS_ARGS_NAME,
			],
		WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_QUESTIONS_ANSWERS =>
			[
				'fields'        => [
					WPSOLR_Option::OPTION_SUGGESTION_NB,
					WPSOLR_Option::OPTION_SUGGESTION_IS_ARCHIVE,
					WPSOLR_Option::OPTION_SUGGESTION_IS_SHOW_TEXT,
					WPSOLR_Option::OPTION_SUGGESTION_IMAGE_WIDTH_PCT,
					WPSOLR_Option::OPTION_SUGGESTION_IMAGE_SIZE,
					WPSOLR_Option::OPTION_SUGGESTION_MODELS,
					WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_CSS,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_RATING,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_PRICE,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_ADD_TO_CART,
				],
				'template_args' => self::TEMPLATE_SUGGESTIONS_ARGS_NAME,
			],
		WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT           =>
			[
				'fields'        => [
					WPSOLR_Option::OPTION_SUGGESTION_NB,
					WPSOLR_Option::OPTION_SUGGESTION_IS_ARCHIVE,
					WPSOLR_Option::OPTION_SUGGESTION_IS_SHOW_TEXT,
					WPSOLR_Option::OPTION_SUGGESTION_IMAGE_WIDTH_PCT,
					WPSOLR_Option::OPTION_SUGGESTION_IMAGE_SIZE,
					WPSOLR_Option::OPTION_SUGGESTION_MODELS,
					WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_CSS,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_RATING,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_PRICE,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_ADD_TO_CART,
				],
				'template_args' => self::TEMPLATE_SUGGESTIONS_ARGS_NAME,
			],
		WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED   =>
			[
				'fields'        => [
					WPSOLR_Option::OPTION_SUGGESTION_NB,
					WPSOLR_Option::OPTION_SUGGESTION_IMAGE_WIDTH_PCT,
					WPSOLR_Option::OPTION_SUGGESTION_IMAGE_SIZE,
					WPSOLR_Option::OPTION_SUGGESTION_IS_ARCHIVE,
					WPSOLR_Option::OPTION_SUGGESTION_IS_SHOW_TEXT,
					WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY,
					WPSOLR_Option::OPTION_SUGGESTION_MODELS,
					WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_CSS,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_RATING,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_PRICE,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_ADD_TO_CART,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL,
					WPSOLR_Option::OPTION_SUGGESTION_MODEL_NB,
				],
				'template_args' => self::TEMPLATE_SUGGESTIONS_ARGS_NAME,
			],
	];


	/**
	 * Constructor
	 * Subscribe to actions
	 */

	function __construct() {

		add_action( WPSOLR_Events::WPSOLR_FILTER_POST_TYPES, [
			$this,
			'wpsolr_filter_post_types',
		], 10, 2 );

		add_filter( WPSOLR_Events::WPSOLR_FILTER_JAVASCRIPT_FRONT_LOCALIZED_PARAMETERS, [
			$this,
			'wpsolr_filter_javascript_front_localized_parameters',
		], 10, 1 );
	}

	/**
	 * Add suggestions options
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function wpsolr_filter_javascript_front_localized_parameters( $parameters ) {
		global $wp_query;

		$parameters['data']['wpsolr_autocomplete_is_active']      = true;
		$parameters['data']['wpsolr_autocomplete_selector']       = $this->get_active_suggestions_js_options();
		$parameters['data']['wpsolr_autocomplete_action']         = WPSOLR_AJAX_AUTO_COMPLETE_ACTION;
		$parameters['data']['wpsolr_autocomplete_nonce_selector'] = ( '#' . WPSOLR_AUTO_COMPLETE_NONCE_SELECTOR );
		$parameters['data']['wpsolr_is_search_admin']             = ( $wp_query instanceof WPSOLR_Query ) && $wp_query->wpsolr_get_is_admin();


		return $parameters;
	}

	/**
	 * Filter post types according to the suggestion
	 *
	 * @param string[] $post_types
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return array
	 */
	public
	function wpsolr_filter_post_types(
		$post_types, $wpsolr_query
	) {

		$suggestion = $wpsolr_query->wpsolr_get_suggestion();
		if ( isset( $suggestion ) ) {
			switch ( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_TYPE ] ) {
				case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT:
				case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED:
				case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_QUESTIONS_ANSWERS:

					/**
					 * Filter by types selected on the suggestion.
					 * If none selected, then use all indexed types.
					 */
					if ( empty( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] ) ) {

						$post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();

					} else {

						$post_types = [];
						foreach ( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] as $post_type => $model_def ) {
							if ( isset( $model_def[ WPSOLR_Option::OPTION_SUGGESTION_MODEL_ID ] ) ) {
								$post_types[] = $post_type;
							}
						}

					}

					break;
			}
		}

		return $post_types;
	}

	/**
	 * Get the default layout of a suggestion type
	 *
	 * @param $suggestion_type
	 *
	 * @return string
	 */
	public static function get_type_default_layout( $suggestion_type ) {

		$result = self::OPTION_SUGGESTION_LAYOUT_ID_KEYWORDS_FANCY;

		foreach ( self::get_type_definitions() as $type_definition ) {
			if ( $suggestion_type === $type_definition['code'] ) {
				$result = $type_definition['default_layout'];
				break;
			}
		}

		return $result;
	}

	/**
	 * Get the file template of a suggestion  by uuid
	 *
	 * @param string $suggestion_uuid
	 *
	 * @return string[]
	 * @throws \Exception
	 */
	public static function get_suggestion_layout_file( $suggestion_uuid ) {

		$result = [];

		$suggestion = self::get_suggestion( $suggestion_uuid );

		foreach ( self::get_template_definitions() as $layout_definition ) {
			if ( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_LAYOUT_ID ] === $layout_definition['code'] ) {
				$result = [
					'template_file' => $layout_definition['template_file'],
					'template_args' => $layout_definition['template_args'],
				];
				break;
			}
		}

		if ( empty( $result ) ) {
			throw new \Exception( sprintf( "The suggestion '%s' has no selected template in WPSOLR settings 2.3.",
				esc_html( $suggestion_uuid ) ) );
		}

		return $result;
	}

	/**
	 * Get the type of suggestion by uuid
	 *
	 * @param string $suggestion_uuid
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function get_suggestion_type( $suggestion_uuid ) {

		$suggestion = self::get_suggestion( $suggestion_uuid );

		return $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_TYPE ];
	}

	/**
	 * Get the redirection pattern of suggestion
	 *
	 * @param array $suggestion
	 *
	 * @return string
	 */
	public static function get_suggestion_redirection_pattern( $suggestion ) {

		return empty( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_REDIRECTION_PATTERN ] ) ? self::SUGGESTION_REDIRECTION_PATTERN_DEFAULT : $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_REDIRECTION_PATTERN ];
	}

	/**
	 * Get the type of suggestion by uuid
	 *
	 * @param string $suggestion_uuid
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function get_suggestion( $suggestion_uuid ) {

		$suggestions = WPSOLR_Service_Container::getOption()->get_option_suggestions_suggestions();
		if ( empty( $suggestions ) || empty( $suggestions[ $suggestion_uuid ] ) ) {
			throw new \Exception( sprintf( "The suggestion '%s' is missing in WPSOLR settings 2.3.", esc_html( $suggestion_uuid ) ) );
		}
		if ( empty( $suggestions[ $suggestion_uuid ][ WPSOLR_Option::OPTION_SUGGESTION_IS_ACTIVE ] ) ) {
			throw new \Exception( sprintf( "The suggestion '%s' is not active in WPSOLR settings 2.3.",
				esc_html( $suggestions[ $suggestion_uuid ][ WPSOLR_Option::OPTION_SUGGESTION_LABEL ] ) ) );
		}

		/**
		 * Add uuid to $suggestion. Easier to manipulate later.
		 */
		$suggestion                                          = $suggestions[ $suggestion_uuid ];
		$suggestion[ WPSOLR_Option::OPTION_SUGGESTION_UUID ] = $suggestion_uuid;

		return $suggestion;
	}

	/**
	 * Return the suggestions types in the options page select box
	 *
	 * @return array
	 */
	static function get_type_definitions() {
		global $license_manager;

		$index_uuid         = WPSOLR_Service_Container::getOption()->get_view_index_uuid();
		$option_indexes     = WPSOLR_Service_Container::getOption()->get_option_indexes();
		$search_engine      = '';
		$search_engine_name = '';
		if ( isset( $option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_uuid ] ) ) {
			$search_engine      = $option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_uuid ][ WPSOLR_AbstractEngineClient::ENGINE ];
			$search_engine_name = ( new WPSOLR_Option_Indexes() )->get_search_engine_name( $search_engine );
		}

		$definitions = [
			[
				'code'           => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS,
				'label'          => 'Suggest Keywords',
				'default_layout' => self::OPTION_SUGGESTION_LAYOUT_ID_KEYWORDS_FANCY,
				'not_engines'    => [
					WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE,
				],
				'engines_soon'   => [
				],
			],
			[
				'code'           => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT,
				'label'          => 'Suggest content',
				'disabled'       => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ),
				'default_layout' => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY,
			],
			[
				'code'           => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'label'          => 'Suggest content group by type',
				'disabled'       => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ),
				'default_layout' => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_GROUPED_FANCY,
				'not_engines'    => [
				]
			],
			[
				'code'           => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_QUESTIONS_ANSWERS,
				'label'          => 'Questions & Answers',
				'default_layout' => self::OPTION_SUGGESTION_LAYOUT_ID_QUESTIONS_ANSWERS_FANCY,
				'engines'        => [
					WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE,
				]

			],
		];

		foreach ( $definitions as &$definition ) {
			if ( isset( $definition['not_engines'] ) && in_array( $search_engine, $definition['not_engines'] ) ) {
				// Disable this definition
				$definition['label']    = sprintf( '%s - Not available with %s.', $definition['label'], $search_engine_name );
				$definition['disabled'] = true;
				$engine_names           = [];
				foreach ( $definition['not_engines'] as $engine ) {
					if ( $search_engine !== $engine ) {
						$engine_names[] = ( new WPSOLR_Option_Indexes() )->get_search_engine_name( $engine );
					}
				}
				if ( ! empty( $engine_names ) ) {
					$definition['label'] = sprintf( '%s Nor with %s.', $definition['label'], implode( ' or ', $engine_names ) );
				}
			} elseif ( isset( $definition['engines'] ) && ! in_array( $search_engine, $definition['engines'] ) ) {
				// Disable this definition
				$definition['label']    = sprintf( '%s - Not available with %s.', $definition['label'], $search_engine_name );
				$definition['disabled'] = true;
				$engine_names           = [];
				foreach ( $definition['engines'] as $engine ) {
					if ( $search_engine !== $engine ) {
						$engine_names[] = ( new WPSOLR_Option_Indexes() )->get_search_engine_name( $engine );
					}
				}
				if ( ! empty( $engine_names ) ) {
					$definition['label'] = sprintf( '%s Only with %s.', $definition['label'], implode( ' or ', $engine_names ) );
				}
			} elseif ( isset( $definition['engines_soon'] ) && in_array( $search_engine, $definition['engines_soon'] ) ) {
				// Soom message
				$definition['label']    = sprintf( '%s - Will be available soon.', $definition['label'] );
				$definition['disabled'] = false;
			} else {
				$definition['disabled'] = false;
			}
		}

		return $definitions;
	}

	/**
	 * Return the template in the options page select box
	 *
	 * @return array
	 */
	static function get_template_definitions() {

		$templates = [

			/**
			 * Fancy templates
			 */
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_KEYWORDS_FANCY,
				'label'         => 'WPSOLR - Fancy',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS,
				'template_file' => self::TEMPLATE_SUGGESTIONS_KEYWORDS_FANCY,
			],
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_KEYWORDS_FANCY_2,
				'label'         => 'WPSOLR - Fancy 2',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS,
				'template_file' => self::TEMPLATE_SUGGESTIONS_KEYWORDS_FANCY_2,
			],
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_KEYWORDS_FANCY_5,
				'label'         => 'WPSOLR - Fancy 5',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS,
				'template_file' => self::TEMPLATE_SUGGESTIONS_KEYWORDS_FANCY_5,
			],

			/**
			 * Content
			 */
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY,
				'label'         => 'WPSOLR - Fancy',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT,
				'template_file' => self::TEMPLATE_SUGGESTIONS_CONTENT_FANCY,
			],
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY_3,
				'label'         => 'WPSOLR - Fancy 3',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT,
				'template_file' => self::TEMPLATE_SUGGESTIONS_CONTENT_FANCY_3,
			],
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY_4,
				'label'         => 'WPSOLR - Fancy 4',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT,
				'template_file' => self::TEMPLATE_SUGGESTIONS_CONTENT_FANCY_4,
			],
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_FLAT_FANCY_5,
				'label'         => 'WPSOLR - Fancy 5',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT,
				'template_file' => self::TEMPLATE_SUGGESTIONS_CONTENT_FANCY_5,
			],

			/**
			 * Grouped content
			 */
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_GROUPED_FANCY,
				'label'         => 'WPSOLR - Fancy',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'template_file' => self::TEMPLATE_SUGGESTIONS_CONTENT_GROUPED_FANCY,
			],
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_GROUPED_FANCY_3,
				'label'         => 'WPSOLR - Fancy 3',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'template_file' => self::TEMPLATE_SUGGESTIONS_CONTENT_GROUPED_FANCY_3,
			],
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_CONTENT_GROUPED_FANCY_4,
				'label'         => 'WPSOLR - Fancy 4',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'template_file' => self::TEMPLATE_SUGGESTIONS_CONTENT_GROUPED_FANCY_4,
			],

			/**
			 * Answers
			 */
			[
				'code'          => self::OPTION_SUGGESTION_LAYOUT_ID_QUESTIONS_ANSWERS_FANCY,
				'label'         => 'WPSOLR - Fancy',
				'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_QUESTIONS_ANSWERS,
				'template_file' => self::TEMPLATE_SUGGESTIONS_QUESTIONS_ANSWERS_FANCY,
			],
		];


		/**
		 * Add custom templates in child theme folder 'wpsolr-templates/twig/suggestions'
		 */
		$custom_templates_path    = sprintf( '%s/wpsolr-templates/twig/suggestions/', get_stylesheet_directory() );
		$custom_templates_folders = glob( $custom_templates_path . '*', GLOB_ONLYDIR );
		foreach ( $custom_templates_folders as $custom_template_folder ) {
			$custom_template_name = basename( $custom_template_folder );
			foreach ( self::SUGGESTION_TEMPLATE_TYPE_DEFINITIONS as $template_type => $template_type_def ) {
				$templates[] = [
					'code'          => sprintf( 'wpsolr_custom_%s_%s', $template_type, $custom_template_name ),
					'label'         => sprintf( '%s', $custom_template_folder ),
					'type'          => $template_type,
					'template_file' => sprintf( 'suggestions/%s/suggestions.twig', $custom_template_name ),
				];
			}
		}

		/**
		 * Here one can add his own template definition
		 */
		$definitions = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SUGGESTIONS_TEMPLATES,
			$templates,
			10, 1
		);

		/**
		 * Expand the template definitions with the template type properties
		 */
		foreach ( $definitions as &$definition ) {
			$definition['fields']        = self::SUGGESTION_TEMPLATE_TYPE_DEFINITIONS[ $definition['type'] ]['fields'];
			$definition['template_args'] = self::SUGGESTION_TEMPLATE_TYPE_DEFINITIONS[ $definition['type'] ]['template_args'];
		}


		return $definitions;
	}

	/**
	 * Return the order by in the options page select box
	 *
	 * @return array
	 */
	static function get_order_by_definitions() {

		return [
			[
				'code'     => WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY_GROUP_CONTENT_MAX_RELEVANCY,
				'label'    => 'Groups with the best suggestion',
				'type'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'disabled' => false,
			],
			[
				'code'     => WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY_GROUP_POSITION,
				'label'    => 'Groups with their position defined below by drag&drop',
				'type'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'disabled' => false,
			],
			[
				'code'     => WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY_GROUP_CONTENT_AVERAGE_RELEVANCY,
				'label'    => 'Groups with the best suggestions in average (not implemented)',
				'type'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'disabled' => true,
			],
		];
	}

	/**
	 * Get js options for each suggestion
	 * @return string[]
	 */
	public function get_active_suggestions_js_options() {
		global $wp_query;

		$default_selector = '.' . WPSOLR_Option::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT;
		$results          = [];
		$archive_filters  = ( $wp_query instanceof WPSOLR_Query ) ?
			array_map( function ( string $filter ) {
				return urlencode( $filter );
			}, $wp_query->get_archive_filter_query_fields() ) :
			[];
		WPSOLR_Option_View::backup_current_view_uuid();
		$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
		foreach ( $views as $view_uuid => $view ) {
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

			foreach ( WPSOLR_Service_Container::getOption()->get_option_suggestions_suggestions() as $suggestion_uuid => $suggestion ) {
				if ( isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IS_ACTIVE ] ) ) {

					$result = [
						'view_uuid'                                                   => WPSOLR_Option_View::get_current_view_uuid(),
						'suggestion_uuid'                                             => $suggestion_uuid,
						'suggestion_class'                                            => sprintf( self::SUGGESTION_CLASS_PATTERN, $suggestion_uuid ),
						'jquery_selector'                                             => empty( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_JQUERY_SELECTOR ] )
							? $default_selector
							: $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_JQUERY_SELECTOR ],
						WPSOLR_Query_Parameters::SEARCH_PARAMETER_AJAX_URL_PARAMETERS =>
							( ( $wp_query instanceof WPSOLR_Query ) && ( ( $wp_query instanceof WPSOLR_Query && $wp_query->wpsolr_get_is_admin() ) || isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IS_ARCHIVE ] ) ) && ! empty( $archive_filters ) ) ?
								build_query( [ WPSOLR_Query_Parameters::SEARCH_PARAMETER_FQ => $archive_filters ] )
								: '',
						'jquery_autocomplete_options'                                 => $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_JQUERY_AUTOCOMPLETE_OPTIONS ] ?? '',
					];

					$results[] = $result;
				}
			}

		}
		WPSOLR_Option_View::restore_current_view_uuid();

		return $results;
	}

	/**
	 * Migrate old jQuery selectors prior to version 21.5
	 */
	public static function migrate_data_from_v21_4() {

		$old_jquery_selectors = WPSOLR_Service_Container::getOption()->get_search_suggest_jquery_selector_before_version_21_5();
		if ( ! empty( $old_jquery_selectors ) ) {
			// Migrate to a new suggestions
			$suggestions_options = WPSOLR_Service_Container::getOption()->get_option_suggestions();

			if ( empty( $suggestions_options ) ) {
				$old_suggestions_content_type                                         = WPSOLR_Service_Container::getOption()->get_search_suggest_content_type_before_version_21_5();//update_option( WPSOLR_Option::OPTION_SUGGESTIONS, $suggestions_options );
				$suggestions_options[ WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ] = [
					WPSOLR_Option_Indexes::generate_uuid() => [
						WPSOLR_Option::OPTION_SUGGESTION_IS_ACTIVE       => 'is_active',
						WPSOLR_Option::OPTION_SUGGESTION_JQUERY_SELECTOR => $old_jquery_selectors,
						WPSOLR_Option::OPTION_SUGGESTION_TYPE            => $old_suggestions_content_type,
						WPSOLR_Option::OPTION_SUGGESTION_LAYOUT_ID       => self::get_type_default_layout( $old_suggestions_content_type ),
						WPSOLR_Option::OPTION_SUGGESTION_NB              => 10,
					]
				];
				update_option( WPSOLR_Option::OPTION_SUGGESTIONS, $suggestions_options );
			}


			// Erase old suggestions options
			$search_options = WPSOLR_Service_Container::getOption()->get_option_search();
			unset( $search_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR ] );
			unset( $search_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE ] );
			update_option( WPSOLR_Option::OPTION_SEARCH, $search_options );
		}

	}

}
