<?php

namespace wpsolr\core\classes\ui;

use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\layout\checkboxes\WPSOLR_UI_Layout_Check_Box;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Translate;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Facets data
 * Class WPSOLR_Data_Facets
 * @package wpsolr\core\classes\ui
 *
 */
class WPSOLR_Data_Facets {

	// Labels for field values
	protected static $fields_items_labels;

	/**
	 * @param array $facets_selected
	 * @param array $facets_to_display
	 * @param array $facets_in_results
	 * @param $localization_options
	 * @param array $options
	 * @param bool $is_engine_indexing_force_html_encoding
	 *
	 * @return array [
	 *                  {"items":[{"name":"post","count":5,"selected":true}],"id":"type","name":"Type"},
	 * {"items":[{"name":"admin","count":6,"selected":false}],"id":"author","name":"Author"},
	 * {"items":[{"name":"Blog","count":13,"selected":true}],"id":"categories","name":"Categories"}
	 * ]
	 */
	public static function get_data(
		$facets_selected, $facets_to_display, $facets_in_results,
		$localization_options, $options = [], $is_engine_indexing_force_html_encoding = false,
		$facet_hierarchy_separator = WpSolrSchema::FACET_HIERARCHY_SEPARATOR
	) {

		$results                                = [];
		$results['facets']                      = [];
		$results['labels']                      = [
			'facets_element'             => OptionLocalization::get_term( $localization_options, 'facets_element' ),
			'facets_title'               => OptionLocalization::get_term( $localization_options, 'facets_title' ),
			'facets_element_all_results' => OptionLocalization::get_term( $localization_options, 'facets_element_all_results' ),
			'facets_header'              => OptionLocalization::get_term( $localization_options, 'facets_header' ),
			'facets_show_more'           => OptionLocalization::get_term( $localization_options, 'facets_show_more' ),
			'facets_show_less'           => OptionLocalization::get_term( $localization_options, 'facets_show_less' ),
		];
		$results['default_layout_id']           = WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID;
		$results['redirect_home_href']          = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_PERMALINK_HOME, '' );
		$results['redirect_home_href']          = ! empty( $results['redirect_home_href'] ) ? ( '/' . $results['redirect_home_href'] ) : './';
		$results['class_prefix']                = WPSOLR_UI_Layout_Abstract::CLASS_PREFIX;
		$results['is_generate_facet_permalink'] = apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_GENERATE_FACET_PERMALINK, false );
		$results['facet_type_field']            = WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD;
		$results['is_rtl']                      = is_rtl();
		$results['facets_orientation']          = WPSOLR_Service_Container::getOption()->get_facets_orientation();

		if ( count( $facets_in_results ) && count( $facets_to_display ) ) {

			$facets_labels = WPSOLR_Service_Container::getOption()->get_facets_labels();
			$facets_grids  = WPSOLR_Service_Container::getOption()->get_facets_grid();

			$facets_skins             = WPSOLR_Service_Container::getOption()->get_facets_skin();
            $facets_generic_script    = WPSOLR_Service_Container::getOption()->get_facets_generic_script();
			$facets_js                = WPSOLR_Service_Container::getOption()->get_facets_js();
			$facets_is_multiple       = WPSOLR_Service_Container::getOption()->get_facets_is_multiple();
			$facet_placeholder        = WPSOLR_Service_Container::getOption()->get_facets_placeholder();
			$facets_size              = WPSOLR_Service_Container::getOption()->get_facets_size();
			$facets_size_shown        = WPSOLR_Service_Container::getOption()->get_facets_size_shown();
			$facets_hide_if_no_choice = WPSOLR_Service_Container::getOption()->get_facets_is_hide_if_no_choice();

			if ( ! empty( $options['facets_skins'] ) ) {
				// Use specific facet skins (widget selection, shortcode selection) instead of skins stored on facets.
				$facets_skins = array_merge( $facets_skins, $options['facets_skins'] );
			}

            if ( ! empty( $options['facets_generic_script'] ) ) {
                // Use specific facet skins (widget selection, shortcode selection) instead of skins stored on facets.
                $facets_generic_script = array_merge( $facets_generic_script, $options['facets_generic_script'] );
            }

			$facets_in_results = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACETS_CONTENT_TO_DISPLAY, $facets_in_results, $is_engine_indexing_force_html_encoding, $facet_hierarchy_separator );

			foreach ( $facets_to_display as $facet_to_display_id ) {

				if ( isset( $facets_in_results[ $facet_to_display_id ] ) && ! empty( $facets_in_results[ $facet_to_display_id ]['values'] ) ) {

					$facet_with_no_blank_id = strtolower( str_replace( ' ', '_', $facet_to_display_id ) );

					$facet_to_display_name = WPSOLR_Translate::translate_field_custom_field(
						WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL,
						$facet_to_display_id,
						! empty( $facets_labels[ $facet_to_display_id ] ) ? $facets_labels[ $facet_to_display_id ] : ''
					);

					$facet                         = [];
					$facet['items']                = [];
					$facet['id']                   = $facet_to_display_id;
					$facet['name']                 = $facet_to_display_name;
					$facet['facet_type']           = $facets_in_results[ $facet_to_display_id ]['facet_type'];
					$facet['facet_layout_id']      = ! empty( $facets_in_results[ $facet_to_display_id ]['facet_layout_id'] ) ? $facets_in_results[ $facet_to_display_id ]['facet_layout_id'] : $results['default_layout_id'];
					$facet['facet_layout_skin_id'] = ! empty( $facets_skins[ $facet_to_display_id ] ) ? $facets_skins[ $facet_to_display_id ] : '';
                    $facet['facet_layout_generic_script_id'] = ! empty( $facets_generic_script[ $facet_to_display_id ] ) ? $facets_generic_script[ $facet_to_display_id ] : '';
					$facet['facet_grid']           = ! empty( $facets_grids[ $facet_to_display_id ] ) ? $facets_grids[ $facet_to_display_id ] : '';
					$facet['facet_size']           = ! empty( $facets_size[ $facet_to_display_id ] ) ? $facets_size[ $facet_to_display_id ] : '';
					$facet['facet_class_id']       = sprintf( '%s_%s', $results['class_prefix'], strtolower( str_replace( ' ', '_', $facet['id'] ) ) );
					$facet['facet_size_shown']     = ! empty( $facets_size_shown[ $facet_to_display_id ] ) ? $facets_size_shown[ $facet_to_display_id ] : '';

					/**
					 * Template for the layout: just remove 'id_' to layout id. Let the template add the 'twig' or 'php' extension
					 */
					$facet['facet_template_file'] = str_replace( '_', '-', sprintf( 'facet/facet-%s.%%s', str_replace( 'id_', '', $facet['facet_layout_id'] ) ) );

					/**
					 * Add layout javascript/css code and files
					 **/
					/** @var WPSOLR_UI_Layout_Abstract $layout_object */
					$layout_object = apply_filters( WPSOLR_Events::WPSOLR_FILTER_LAYOUT_OBJECT, null, $facet['facet_layout_id'] );
					if ( is_null( $layout_object ) ) {
						// Back to default layout
						$layout_object = new WPSOLR_UI_Layout_Check_Box();
					}
					// Unique uuid for each facet. used to inject specific css/js to each facet.
					$facet_class_uuid                 = $layout_object->get_class_uuid();
					$facet_layout_skin_id             = $layout_object->get_skin_id( $facet );
                    $facet_layout_generic_script_id   = $layout_object->get_generic_script_id( $facet );
					$facet['facet_class_uuid']        = $facet_class_uuid;
					$facet['facet_layout_class']      = $layout_object->get_css_class_name();
					$facet['facet_layout_skin_class'] = $layout_object->get_css_skin_class_name( $facet['facet_layout_skin_id'] );

					/**
					 * Facet grid class
					 */
					$facet_grid = ! empty( $facet['facet_grid'] ) ? $facet['facet_grid'] : '';
					switch ( $facet_grid ) {
						case WPSOLR_Option::OPTION_FACET_GRID_HORIZONTAL:
							$facet_grid_class = 'wpsolr_facet_column_horizontal';
							break;

						case WPSOLR_Option::OPTION_FACET_GRID_1_COLUMN:
							$facet_grid_class = 'wpsolr_facet_columns wpsolr_facet_column_1';
							break;

						case WPSOLR_Option::OPTION_FACET_GRID_2_COLUMNS:
							$facet_grid_class = 'wpsolr_facet_columns wpsolr_facet_column_2';
							break;

						case WPSOLR_Option::OPTION_FACET_GRID_3_COLUMNS:
							$facet_grid_class = 'wpsolr_facet_columns wpsolr_facet_column_3';
							break;

						default;
							$facet_grid_class = ''; //'wpsolr_facet_columns wpsolr_facet_column_1';
							break;
					}
					$facet['facet_grid_class'] = $facet_grid_class . ' wpsolr_facet_scroll';


					$facet_custom_js = ! empty( $facets_js[ $facet_to_display_id ] ) ? $facets_js[ $facet_to_display_id ] : '';
					if ( ! empty( trim( $facet_custom_js ) ) ) {
						// Give plugins a chance to change the facet name (WPML, POLYLANG).
						$facet_custom_js = apply_filters(
							WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_STRING,
							$facet_custom_js,
							[
								'domain' => WPSOLR_Option::TRANSLATION_DOMAIN_FACET_JS,
								'name'   => $facet_to_display_id,
								'text'   => $facet_custom_js,
							]
						);

					}
					$facet['facet_layout_skin_js'] = $facet_custom_js;
					$facet['facet_layout_skin_js'] = ( 'wpsolr_no_skin' === $facet_layout_skin_id ) ? '' : $layout_object->generate_skin_js( $facet, $facet_class_uuid );

					if ( ! empty( $facets_is_multiple[ $facet_to_display_id ] ) ) {
						$facet['facet_is_multiple'] = true;
					}
					$facet['facet_placeholder'] =
						! empty( $facet_placeholder[ $facet_to_display_id ] )
							? apply_filters( WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_STRING, $facet_placeholder[ $facet_to_display_id ],
							[
								'domain' => WPSOLR_Option::TRANSLATION_DOMAIN_FACET_PLACEHOLDER,
								'name'   => $facet_to_display_id,
								'text'   => $facet_placeholder[ $facet_to_display_id ],
							]
						)
							: '';

					switch ( $facet['facet_type'] ) {
						case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:
							$facet['facet_template'] = $facets_in_results[ $facet_to_display_id ]['facet_template'];
							break;

					}

					$items_hierarchy = [];
					self::build_hierarchies(
						$facet,
						$items_hierarchy,
						$facet_to_display_id,
						$facets_in_results[ $facet_to_display_id ]['values'],
						! empty( $facets_selected[ $facet_with_no_blank_id ] ) ? $facets_selected[ $facet_with_no_blank_id ] : [],
						$facet_hierarchy_separator
					);

					$i = 0;
					foreach ( $items_hierarchy as $facet_in_results ) {

						$i ++;

						$facet_item               = [
							'value'           => $facet_in_results['value'],
							'escaped_value'   => htmlentities( $facet_in_results['value'] ),
							// '&' is transformed in '&amp;' to match the values in the index
							'count'           => $facet_in_results['count'],
							'items'           => $facet_in_results['items'],
							'selected'        => $facet_in_results['selected'],
							'value_localized' => ! empty( $facet_in_results['value_localized'] ) ? $facet_in_results['value_localized'] : $facet_in_results['value'],
							'shown'           => empty( $facet['facet_size_shown'] ) || ( $i <= $facet['facet_size_shown'] ),
							'id'              => $facet_to_display_id,
						];
						$facet_item['html_class'] = static::_generate_facet_html_classes( $facet_to_display_id, $facet_in_results['value'], $facet_item['value_localized'] );;


						switch ( $facet['facet_type'] ) {
							case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:

								$facet_item['value_localized'] = ( ! empty( $facet_in_results['value_localized'] ) && ( $facet_in_results['value_localized'] !== $facet_in_results['value'] ) ) ? $facet_in_results['value_localized'] : $facet['facet_template'];

								// Generate the end value for regular ranges, from the value and the gap
								$range                     = explode( '-', $facet_item['value'] );
								$facet_item['range_start'] = $range[0];
								$facet_item['range_end']   = $range[1];
								break;

							case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX:

								// Generate the min/max values
								$min_max           = explode( '-', $facet_item['value'] );
								$facet_item['min'] = $min_max[0];
								$facet_item['max'] = $min_max[1];

								$from_top = explode( '-', ! empty( $facets_selected[ $facet_with_no_blank_id ] ) ? $facets_selected[ $facet_with_no_blank_id ][0] : $facet_item['value'] );

								if ( ! empty( $facets_selected[ $facet_with_no_blank_id ] ) ) {
									$facet_item['value']           = $facets_selected[ $facet_with_no_blank_id ][0];
									$facet_item['value_localized'] = $facet_item['value'];
									$facet_item['escaped_value']   = $facet_item['value'];
									$facet_item['selected']        = true;
								}

								$facet_item['from'] = isset( $from_top[0] ) ? $from_top[0] : $facet_item['min'];
								$facet_item['to']   = isset( $from_top[1] ) ? $from_top[1] : $facet_item['max'];

								break;

							default:
								break;

						}

						array_push(
							$facet['items'],
							$facet_item
						);

					}

					/**
					 * Add UI related data
					 */
					$facet_template = ! empty( $facet['facet_template'] ) ? $facet['facet_template'] : $results['labels']['facets_element'];
					$layout_object->displayFacetHierarchy( $facet['facet_class_uuid'], $facet_template, $facet['facet_grid_class'], $html, $facet, $facet['items'] );

					/**
					 * Add current facet to results if not to be hidden
					 */
					$facet_hide_if_no_choice = ! empty( $facets_hide_if_no_choice[ $facet_to_display_id ] );
					switch ( $facet['facet_type'] ) {
						case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX:
							// No choice
							$facet_hidden = $facet_hide_if_no_choice && ( count( $facet['items'] ) === 1 ) && ( $facet['items'][0]['from'] === $facet['items'][0]['to'] );
							break;

						default:
							$facet_hidden = $facet_hide_if_no_choice && ( count( $facet['items'] ) < 2 );
							break;
					}
					if ( ! $facet_hidden ) {
						array_push( $results['facets'], $facet );
					}

				}
			}
		}

		// Update the facets data if necessary
		$results = apply_filters( WPSOLR_Events::WPSOLR_FILTER_UPDATE_FACETS_DATA, $results );

		// Custom HTML
		$html = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACETS_REPLACE_HTML, null, $results, $localization_options );
		if ( null !== $html ) {
			$results['html'] = $html;
		}

		// Custom CSS
		$results['css'] = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACETS_CSS, '' );


		return $results;
	}


	/**
	 * Build a hierachy of facets when facet name contains $facet_hierarchy_separator
	 * Recursive
	 *
	 * @param array $facet
	 * @param array $results
	 * @param string $facet_to_display_id
	 * @param array $items
	 * @param array $facets_selected
	 * @param string $facet_hierarchy_separator
	 */
	public
	static function build_hierarchies(
		$facet, &$results, $facet_to_display_id, $items, $facets_selected, $facet_hierarchy_separator
	) {

		$result = [];
		foreach ( $items as $item ) {

			$item_hierarchy_root                = $item['hierarchy_root'] ?? '';
			$item_hierarcy_item_names_hierarchy = $item['value_hierarchy'] ?? ( $item_hierarchy_root . $item['value'] );
			$item_hierarcy_item_names           = explode( $facet_hierarchy_separator, $item['value'] );
			$item_top_level_name                = trim( array_shift( $item_hierarcy_item_names ) );

			if ( empty( $result[ $item_top_level_name ] ) ) {

				$result[ $item_top_level_name ] = [
					'value'           => $item_hierarchy_root . $item_top_level_name,
					'value_localized' => self::get_field_item_value_localization( $facet_to_display_id, $item_top_level_name, null ),
					'count'           => $item['count'],
					'selected'        => isset( $facets_selected ) &&
					                     ( in_array( $item_hierarcy_item_names_hierarchy, $facets_selected, true ) ||
					                       in_array( "$item_hierarcy_item_names_hierarchy", $facets_selected, true ) ),
				];

				$result[ $item_top_level_name ]['items'] = [];
			}

			if ( ! $result[ $item_top_level_name ]['selected'] ) {
				$result[ $item_top_level_name ]['selected'] = isset( $facets_selected ) && ( in_array( $item_hierarcy_item_names_hierarchy, $facets_selected, true ) );
			}

			if ( ! empty( $item_hierarcy_item_names ) ) {

				array_push( $result[ $item_top_level_name ]['items'],
					[
						'value'           => trim( implode( $facet_hierarchy_separator, $item_hierarcy_item_names ) ),
						'value_hierarchy' => $item_hierarcy_item_names_hierarchy,
						'count'           => $item['count'],
						'selected'        => isset( $facets_selected ) && ( in_array( $item['value'], $facets_selected, true ) ),
					]
				);
			}
		}

		$i = 0;
		foreach ( $result as $top_name => $sub_items ) {

			$i ++;

			$level               = [
				'value'           => $sub_items['value'],
				'value_localized' => ! empty( $sub_items['value_localized'] ) ? $sub_items['value_localized'] : $sub_items['value'],
				'count'           => $sub_items['count'],
				'selected'        => $sub_items['selected'],
				'items'           => [],
				'shown'           => empty( $facet['facet_size_shown'] ) || ( $i <= $facet['facet_size_shown'] ),
				'escaped_value'   => htmlentities( $sub_items['value'] ),
				'id'              => $facet_to_display_id,
			];
			$level['html_class'] = static::_generate_facet_html_classes( $facet_to_display_id, $sub_items['value'], $level['value_localized'] );;

			if ( ! empty( $sub_items['items'] ) ) {

				self::build_hierarchies( $facet, $level['items'], $facet_to_display_id, $sub_items['items'], $facets_selected, $facet_hierarchy_separator );
			}

			// Calculate the count by summing children count
			if ( ! empty( $level['items'] ) ) {

				$count = 0;
				foreach ( $level['items'] as $item ) {

					$count += $item['count'];
				}
				$level['count'] = $count;
			}

			array_push( $results, $level );
		}

	}

	/**
	 * Replace a field item value by it's localization.
	 * Example: on field 'color': '#81d742' => 'green', '#81d742' => 'vert'
	 *
	 * @param $field_name
	 * @param $field_value
	 * @param $language
	 *
	 * @return mixed
	 */
	public
	static function get_field_item_value_localization(
		$field_name, $field_value, $language
	) {

		$value = $field_value;

		if ( null === self::$fields_items_labels ) {

			// Init the items labels once, only for field WpSolrSchema::_FIELD_NAME_TYPE
			self::$fields_items_labels = WPSOLR_Service_Container::getOption()->get_facets_items_labels();
		}

		if ( ( ! empty( self::$fields_items_labels[ $field_name ] ) ) ) {

			if ( ! empty( self::$fields_items_labels[ $field_name ][ $field_value ] ) ) {

				$value = apply_filters( WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_STRING, $field_value,
					[
						'domain'   => WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL,
						'name'     => $field_value,
						'text'     => self::$fields_items_labels[ $field_name ][ $field_value ],
						'language' => $language,
					]
				);

				if ( $value === $field_value ) {
					// No translation for this value, try to get the localization instead.

					$value = ! empty( self::$fields_items_labels[ $field_name ][ $field_value ] ) ? self::$fields_items_labels[ $field_name ][ $field_value ] : $field_value;
				}
			}
		}

		return $value;
	}

	/**
	 * @param string $facet_to_display_id
	 * @param string $facet_value
	 * @param string $facet_value_localized
	 *
	 * @return string
	 */
	protected static function _generate_facet_html_classes( string $facet_to_display_id, string $facet_value, string $facet_value_localized ) {

		$class_value     = sanitize_title( sprintf( '%s:%s', $facet_to_display_id, $facet_value ) );
		$class_localized = sanitize_title( sprintf( '%s:%s', $facet_to_display_id, $facet_value_localized ) );
		if ( $class_value !== $class_localized ) {
			return sprintf( '%s %s', $class_value, $class_localized );
		}

		return $class_value;
	}

}
