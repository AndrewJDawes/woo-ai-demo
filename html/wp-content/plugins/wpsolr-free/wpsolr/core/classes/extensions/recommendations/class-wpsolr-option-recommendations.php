<?php

namespace wpsolr\core\classes\extensions\recommendations;

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

class WPSOLR_Option_Recommendations extends WPSOLR_Extension {


	const IS_RECOMMENDATIONS_IMPLEMENTED = true;

	// Default keyword redirection pattern (can be updated in recommendation option for WooCommerce, or bbPress ...)
	const RECOMMENDATION_REDIRECTION_PATTERN_DEFAULT = '/?s=%s';


	const CLASS_RECOMMENDATION_TYPE = 'wpsolr_recommendation_type';
	const CLASS_RECOMMENDATION_LAYOUT = 'wpsolr_recommendation_layout';
	const CLASS_RECOMMENDATION_GROUPS = 'wpsolr_recommendation_groups';

	const RECOMMENDATION_LAYOUTS = [];

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
	const TEMPLATE_RECOMMENDATIONS_ARGS_NAME = 'recommendations';

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
	const RECOMMENDATION_CLASS_PATTERN = 'c%s';

	/**
	 * Template type definitions
	 */
	const RECOMMENDATION_MODEL_TYPE_DEFINITIONS = [

		WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_MORE_LIKE_THIS                          =>
			[
				'settings'      => [ 'is_to_item' => true, 'twig_prefix' => [ self::TWIG_PREFIX_CONTENT ], ],
				'fields'        => [
					WPSOLR_Option::OPTION_RECOMMENDATION_IS_NOT_AJAX,
					WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_NOT_SAME_OBJECT,
					WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_SAME_OBJECT_TYPE,
					WPSOLR_Option::OPTION_RECOMMENDATION_NB,
					WPSOLR_Option::OPTION_RECOMMENDATION_IS_SHOW_TEXT,
					WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_WIDTH_PCT,
					WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_SIZE,
					WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_CSS,
					WPSOLR_Option::OPTION_RECOMMENDATION_MODELS,
					WPSOLR_Option::OPTION_RECOMMENDATION_MODEL_SUBTYPES,
				],
				'template_args' => self::TEMPLATE_RECOMMENDATIONS_ARGS_NAME,
			],
		WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_WEAVIATE_NEAR_OBJECT                    =>
			[
				'settings'      => [ 'is_to_item' => true, 'twig_prefix' => [ self::TWIG_PREFIX_CONTENT ] ],
				'fields'        => [
					WPSOLR_Option::OPTION_RECOMMENDATION_IS_NOT_AJAX,
					WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_NOT_SAME_OBJECT,
					WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_SAME_OBJECT_TYPE,
					WPSOLR_Option::OPTION_RECOMMENDATION_NB,
					WPSOLR_Option::OPTION_RECOMMENDATION_IS_SHOW_TEXT,
					WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_WIDTH_PCT,
					WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_SIZE,
					WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_CSS,
					WPSOLR_Option::OPTION_RECOMMENDATION_MODELS,
					WPSOLR_Option::OPTION_RECOMMENDATION_MODEL_SUBTYPES,
				],
				'template_args' => self::TEMPLATE_RECOMMENDATIONS_ARGS_NAME,
			],
		WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_WEAVIATE_NEAR_IMAGE                     =>
			[
				'settings'      => [ 'is_to_item' => true, 'twig_prefix' => [ self::TWIG_PREFIX_CONTENT ] ],
				'fields'        => [
					WPSOLR_Option::OPTION_RECOMMENDATION_IS_NOT_AJAX,
					WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_SAME_OBJECT_TYPE,
					WPSOLR_Option::OPTION_RECOMMENDATION_NB,
					WPSOLR_Option::OPTION_RECOMMENDATION_IS_SHOW_TEXT,
					WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_WIDTH_PCT,
					WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_SIZE,
					WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_CSS,
					WPSOLR_Option::OPTION_RECOMMENDATION_MODELS,
					WPSOLR_Option::OPTION_RECOMMENDATION_MODEL_SUBTYPES,
				],
				'template_args' => self::TEMPLATE_RECOMMENDATIONS_ARGS_NAME,
			],
	];
	const TWIG_PREFIX_CONTENT = 'content_';
	const TWIG_PREFIX_EVENT = 'event_';
	const TWIG_PREFIXES = [ self::TWIG_PREFIX_CONTENT, self::TWIG_PREFIX_EVENT, ];

	/**
	 * @var array
	 */
	private static $cached_recommendations = [];
	private static $_template_definitions_cache;


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
	 * Add recommendations options
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function wpsolr_filter_javascript_front_localized_parameters( $parameters ) {
		global $wp_query;

		$parameters['data']['wpsolr_recommendation_selector']       = $this->get_active_recommendations_js_options();
		$parameters['data']['wpsolr_recommendation_action']         = WPSOLR_AJAX_RECOMMENDATION_ACTION;
		$parameters['data']['wpsolr_recommendation_nonce_selector'] = ( '#' . WPSOLR_AJAX_RECOMMENDATION_NONCE_SELECTOR );

		return $parameters;
	}

	/**
	 * Filter post types according to the recommendation
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

		$recommendation = $wpsolr_query->wpsolr_get_recommendation();
		if ( ! empty( $recommendation ) ) {
			switch ( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] ) {
				default:

					/**
					 * Filter by types selected on the recommendation.
					 * If none selected, then use all indexed types.
					 */
					if ( empty( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_MODELS ] ) ) {

						$post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();

					} else {

						$post_types = [];
						foreach ( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_MODELS ] as $post_type => $model_def ) {
							if ( isset( $model_def[ WPSOLR_Option::OPTION_RECOMMENDATION_MODEL_ID ] ) ) {
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
	 * Get the default layout of a recommendation type
	 *
	 * @param $recommendation_type
	 *
	 * @return string
	 */
	public static function get_type_default_layout( $recommendation_type ) {

		$result = self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY;

		foreach ( self::get_type_definitions() as $type_definition ) {
			if ( $recommendation_type === $type_definition['code'] ) {
				$result = $type_definition['default_layout'];
				break;
			}
		}

		return $result;
	}

	/**
	 * Get the file template of a recommendation  by uuid
	 *
	 * @param string $recommendation_uuid
	 *
	 * @return string[]
	 * @throws \Exception
	 */
	public static function get_recommendation_layout_file( $recommendation_uuid ) {

		$result = [];

		$recommendation = self::get_recommendation( $recommendation_uuid );

		$layout_definitions = self::get_template_definitions( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_LAYOUT_ID ] );
		if ( 1 !== count( $layout_definitions ) ) {
			throw new \Exception(
				sprintf( sprintf( "The recommendation '%s' has %%s selected template(s) in WPSOLR settings 2.3.", esc_html( $recommendation_uuid ) ),
					count( $layout_definitions ) ) );
		}

		return [
			'template_file' => $layout_definitions[0]['template_file'],
			'template_args' => $layout_definitions[0]['template_args'],
		];
	}

	/**
	 * Get the type of recommendation by uuid
	 *
	 * @param string $recommendation_uuid
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function get_recommendation_type( $recommendation_uuid ) {

		$recommendation = self::get_recommendation( $recommendation_uuid );

		return $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ];
	}

	/**
	 * Get the redirection pattern of recommendation
	 *
	 * @param array $recommendation
	 *
	 * @return string
	 */
	public static function get_recommendation_redirection_pattern( $recommendation ) {

		return empty( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_RECOMBEE_SCENARIO ] ) ? self::RECOMMENDATION_REDIRECTION_PATTERN_DEFAULT : $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_RECOMBEE_SCENARIO ];
	}

	/**
	 * Get the type of recommendation by uuid
	 *
	 * @param string $recommendation_uuid
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function get_recommendation( $recommendation_uuid ) {

		if ( ! empty( $recommendation_uuid ) ) {

			if ( ! empty( static::$cached_recommendations[ $recommendation_uuid ] ) ) {
				// Use cache
				return static::$cached_recommendations[ $recommendation_uuid ];
			}

			if ( ! empty( $recommendation = static::get_recommendations( true )[ $recommendation_uuid ] ?? [] ) ) {
				if ( empty( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IS_ACTIVE ] ) ) {
					throw new \Exception( sprintf( "The recommendation '%s' is not active in WPSOLR settings 2.3.",
						esc_html( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_LABEL ] ) ) );
				}

				// Set the cache
				static::$cached_recommendations[ $recommendation_uuid ] = $recommendation;

				return static::$cached_recommendations[ $recommendation_uuid ];
			}
		}

		throw new \Exception( sprintf( "The recommendation '%s' is missing in WPSOLR settings 2.3.", esc_html( $recommendation_uuid ) ) );
	}

	/**
	 * Return the recommendations types in the options page select box
	 *
	 *
	 * @return array
	 */
	static function get_type_definitions() {

		$index_uuid         = WPSOLR_Option_View::get_current_index_uuid();
		$option_indexes     = WPSOLR_Service_Container::getOption()->get_option_indexes();
		$search_engine      = '';
		$search_engine_name = '';
		if ( isset( $option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_uuid ] ) ) {
			$search_engine      = $option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_uuid ][ WPSOLR_AbstractEngineClient::ENGINE ];
			$search_engine_name = ( new WPSOLR_Option_Indexes() )->get_search_engine_name( $search_engine );
		}

		$definitions = [];
		switch ( $search_engine ) {


			/**
			 * https://weaviate.io/developers/weaviate/current/graphql-references/get.html#vector-search-operators
			 */
			case WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE:
				$definitions = [
					[
						'code'    => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_WEAVIATE_NEAR_OBJECT,
						'label'   => 'Related posts based on vector semantic similarity (NearObject)',
						// 'default_layout' => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY,
						'engines' => [
							WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE,
						]
					],
					/*[
						'code'    => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_WEAVIATE_NEAR_IMAGE,
						'label'   => 'Similar images',
						// 'default_layout' => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY,
						'engines' => [
							WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE,
						]
					],*/
				];
				break;
			case WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH:
			case WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH:
			case WPSOLR_AbstractEngineClient::ENGINE_SOLR:
			case WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD:
				$definitions = [
					[
						'code'    => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_MORE_LIKE_THIS,
						'label'   => 'Related posts based on term frequency (More Like This)',
						'engines' => [
							WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH,
							WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH,
							WPSOLR_AbstractEngineClient::ENGINE_SOLR,
							WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD,
						]
					],
				];
				break;
		}


		foreach ( $definitions as &$definition ) {
			if ( isset( $definition['not_engines'] ) && in_array( $search_engine, $definition['not_engines'] ) ) {
				// Disable this definition
				//$definition['label']    = sprintf( '%s - Not available with %s.', $definition['label'], $search_engine_name );
				$definition['label']    = sprintf( '%s (soon)', $definition['label'] );
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
				//$definition['label']    = sprintf( '%s - Not available with %s.', $definition['label'], $search_engine_name );
				$definition['label']    = sprintf( '%s (soon)', $definition['label'] );
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
	static function get_template_definitions( string $layout_id = '' ) {

		if ( isset( static::$_template_definitions_cache ) ) {
			return static::$_template_definitions_cache;
		}

		/*
		$templates = [
			[
				'code'          => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY,
				'label'         => 'WPSOLR - Default - Recommended Items',
				'type'          => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_RECOMBEE_ITEMS_TO_USER,
				'template_file' => self::TEMPLATE_RECOMMENDATIONS_CONTENT_FANCY,
			],
			[
				'code'          => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY1,
				'label'         => 'Amazon-like Slider',
				'type'          => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_RECOMBEE_ITEMS_TO_USER,
				'template_file' => self::TEMPLATE_RECOMMENDATIONS_CONTENT_FANCY1,
			],
			[
				'code'          => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY2,
				'label'         => 'Pinterest-like Gallery',
				'type'          => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_RECOMBEE_ITEMS_TO_USER,
				'template_file' => self::TEMPLATE_RECOMMENDATIONS_CONTENT_FANCY2,
			],
			[
				'code'          => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY3,
				'label'         => 'Ebay-like List',
				'type'          => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_RECOMBEE_ITEMS_TO_USER,
				'template_file' => self::TEMPLATE_RECOMMENDATIONS_CONTENT_FANCY3,
			],
			[
				'code'          => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY4,
				'label'         => 'Walmart-like Slider (Price first)',
				'type'          => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_RECOMBEE_ITEMS_TO_USER,
				'template_file' => self::TEMPLATE_RECOMMENDATIONS_CONTENT_FANCY4,
			],
			[
				'code'          => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY5,
				'label'         => 'CDiscount-like Slider (Price last)',
				'type'          => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_RECOMBEE_ITEMS_TO_USER,
				'template_file' => self::TEMPLATE_RECOMMENDATIONS_CONTENT_FANCY5,
			],
			[
				'code'          => self::OPTION_RECOMMENDATION_LAYOUT_ID_KEYWORDS_FANCY6,
				'label'         => '9Gag-like Media single-column list',
				'type'          => WPSOLR_Option::OPTION_RECOMMENDATION_TYPE_RECOMBEE_ITEMS_TO_USER,
				'template_file' => self::TEMPLATE_RECOMMENDATIONS_CONTENT_FANCY6,
			]
		];
		*/

		/**
		 * Add default templates in plugin folder 'wpsolr-templates/twig/recommendations'
		 */
		$templates         = [];
		$templates_folders = [];
		foreach ( [ WPSOLR_PLUGIN_DIR, get_stylesheet_directory() ] as $path ) {
			$templates_path             = sprintf( '%s/wpsolr-templates/twig/recommendations/', $path );
			$templates_folders[ $path ] = glob( sprintf( '%s%s', $templates_path, '*' ), GLOB_ONLYDIR );
		}

		foreach (
			[
				[
					'path'  => WPSOLR_PLUGIN_DIR,
					'code'  => 'wpsolr_%s_%s',
					'label' => '%s - WPSolr template in %s'
				],
				[
					'path'  => get_stylesheet_directory(),
					'code'  => 'wpsolr_custom_%s_%s',
					'label' => '%s - Your theme template in %s'
				]
			] as $folder_def
		) {
			foreach ( static::RECOMMENDATION_MODEL_TYPE_DEFINITIONS as $model_type => $model_type_def ) {
				foreach ( $templates_folders[ $folder_def['path'] ] as $template_file ) {

					$template_name = basename( $template_file );
					$code          = sprintf( $folder_def['code'], $model_type, $template_name );

					if ( ! empty( $layout_id ) && ( $layout_id != $code ) ) {
						continue;
					}

					if ( WPSOLR_PLUGIN_DIR === $folder_def['path'] ) {
						// Filter official templates per prefix
						$has_prefix = false;
						foreach ( $model_type_def['settings']['twig_prefix'] as $twig_prefix ) {
							if ( str_contains( $template_file, sprintf( '%s%s', '/wpsolr-templates/twig/recommendations/', $twig_prefix ) ) ) {
								$has_prefix = true;
								break;
							}
						}

						if ( ! $has_prefix ) {
							// File does not contains prefix: not valid for this model type
							continue;
						}
					}

					$label = 'Unnamed template';
					if ( empty( $layout_id ) ) {
						if ( file_exists( $file_settings_json = sprintf( '%s/%s', $template_file, 'settings.json' ) ) ) {
							$template_file_settings_json = file_get_contents( $file_settings_json );
						}
						if ( ! empty( $template_file_settings_json ) && ! empty( $template_file_settings = json_decode( $template_file_settings_json, true ) ) ) {
							$label = $template_file_settings['label'] ?? '';
						}
					}
					$templates[] = [
						'code'          => $code,
						'label'         => sprintf( $folder_def['label'], $label, $template_file ),
						'type'          => $model_type,
						'template_file' => sprintf( 'recommendations/%s/recommendations.twig', $template_name ),
					];

					if ( ! empty( $layout_id ) ) {
						break;
					}

				}
			}
		}

		/**
		 * Here one can add his own template definition
		 */
		$definitions = apply_filters( WPSOLR_Events::WPSOLR_FILTER_RECOMMENDATIONS_TEMPLATES,
			$templates,
			10, 1
		);

		/**
		 * Expand the template definitions with the template type properties
		 */
		foreach ( $definitions as &$definition ) {
			$definition['fields']        = self::RECOMMENDATION_MODEL_TYPE_DEFINITIONS[ $definition['type'] ]['fields'];
			$definition['template_args'] = self::RECOMMENDATION_MODEL_TYPE_DEFINITIONS[ $definition['type'] ]['template_args'];
		}

		return ( static::$_template_definitions_cache = $definitions );
	}

	/**
	 * Return the order by in the options page select box
	 *
	 * @return array
	 */
	static function get_order_by_definitions() {

		return [
			[
				'code'     => WPSOLR_Option::OPTION_RECOMMENDATION_ORDER_BY_GROUP_CONTENT_MAX_RELEVANCY,
				'label'    => 'Groups with the best recommendation',
				'type'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'disabled' => false,
			],
			[
				'code'     => WPSOLR_Option::OPTION_RECOMMENDATION_ORDER_BY_GROUP_POSITION,
				'label'    => 'Groups with their position defined below by drag&drop',
				'type'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'disabled' => false,
			],
			[
				'code'     => WPSOLR_Option::OPTION_RECOMMENDATION_ORDER_BY_GROUP_CONTENT_AVERAGE_RELEVANCY,
				'label'    => 'Groups with the best recommendations in average (not implemented)',
				'type'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
				'disabled' => true,
			],
		];
	}

	/**
	 * Get js options for each recommendation
	 * @return string[]
	 */
	public function get_active_recommendations_js_options() {
		global $wp_query;

		$default_selector = '.' . WPSOLR_Option::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT;
		$results          = [];
		$archive_filters  = $wp_query->get_archive_filter_query_fields();
		foreach ( WPSOLR_Service_Container::getOption()->get_option_recommendations_recommendations() as $recommendation_uuid => $recommendation ) {

			if ( isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IS_ACTIVE ] ) ) {

				$result = [
					'view_uuid'                                                   => WPSOLR_Option_View::get_current_view_uuid(),
					'recommendation_uuid'                                         => $recommendation_uuid,
					'recommendation_class'                                        => sprintf( self::RECOMMENDATION_CLASS_PATTERN, $recommendation_uuid ),
					'jquery_selector'                                             => empty( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_JQUERY_SELECTOR ] )
						? $default_selector
						: $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_JQUERY_SELECTOR ],
					WPSOLR_Query_Parameters::SEARCH_PARAMETER_AJAX_URL_PARAMETERS =>
						( ( $wp_query instanceof WPSOLR_Query ) && ( ( $wp_query instanceof WPSOLR_Query && $wp_query->wpsolr_get_is_admin() ) || isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IS_ARCHIVE ] ) ) && ! empty( $archive_filters ) ) ?
							build_query( [ WPSOLR_Query_Parameters::SEARCH_PARAMETER_FQ => $archive_filters ] )
							: '',
				];

				$results[] = $result;
			}
		}

		return $results;
	}

	/**
	 * @return array
	 */
	public static function get_recommendations( $is_add_empty = false ): array {
		$results[''] = $is_add_empty ? [ WPSOLR_Option::OPTION_RECOMMENDATION_LABEL => 'No recommendation', ] : [];

		WPSOLR_Option_View::backup_current_view_uuid();
		$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
		foreach ( $views as $view_uuid => $view ) {
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );
			$recommendations = WPSOLR_Service_Container::getOption()->get_option_recommendations_recommendations();
			foreach ( $recommendations as $recommendation_uuid => $recommendation ) {
				/**
				 * Add uuid to $recommendation. Easier to manipulate later.
				 */
				$recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_UUID ] = $recommendation_uuid;
				$recommendation[ WPSOLR_Option_View::INDEX_UUID ]            = WPSOLR_Service_Container::getOption()->get_view_index_uuid();
				$recommendation[ WPSOLR_Option_View::VIEW_UUID ]             = $view_uuid;

				$results[ $recommendation_uuid ] = $recommendation;
			}
			WPSOLR_Option_View::restore_current_view_uuid();
		}

		return $results;
	}


	/**
	 * @param $values
	 *
	 * @return string
	 */
	protected static function warning_message( $message ): string {
		return '[WPSOLR warning] ' . $message;
	}


	/**
	 * @param array $recommendation
	 * @param array $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function check_recommendation( array $recommendation, array $context ) {

		if ( ( static::RECOMMENDATION_MODEL_TYPE_DEFINITIONS[ $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] ]['settings']['is_to_item'] ?? false ) &&
		     ( empty( $context['object_id'] ?? '' ) ) ) {
			throw new \Exception( sprintf( "[WPSOLR warning] Model '%s' must be invoked on an item.",
					esc_html( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] ) )
			);
		}

	}

}
