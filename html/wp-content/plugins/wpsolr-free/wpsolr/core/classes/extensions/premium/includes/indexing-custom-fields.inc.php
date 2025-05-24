<?php

use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax;
use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax_Search;
use wpsolr\core\classes\admin\ui\WPSOLR_Admin_UI_Select2;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;


$disabled = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM );

// Count nb of fields selected
$nb_fields_selected = 0;
foreach ( $model_type_fields as $model_type_field ) {
	if ( isset( $field_types_opt[ $model_type ] ) && ( false !== array_search( $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING, $field_types_opt[ $model_type ], true ) ) ) {
		$nb_fields_selected ++;
	}
}
?>

<div class="wdm_row">
    <div>
        <a href="javascript:void(0);"
           class="cust_fields wpsolr_collapser <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>"
           style="margin: 0px;">

			<?php WPSOLR_Escape::echo_escaped( sprintf( ( count( $model_type_fields ) > 1 ) ? '%s Fields - %s selected' : '%s Field - %s selected',
				WPSOLR_Escape::esc_html( count( $model_type_fields ) ),
				empty( $nb_fields_selected ) ? 'none' : WPSOLR_Escape::esc_html( $nb_fields_selected ) ) ); ?></a>


        <div class='cust_fields wpsolr_collapsed <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>'>
            <br>
			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_CHECKER ) ) ) {
				require $file_to_include;
			}
			?>

			<?php
			if ( ! empty( $custom_fields_error_message ) ) {
				WPSOLR_Escape::echo_escaped( sprintf( '<div class="error-message">%s</div>', WPSOLR_Escape::esc_html( $custom_fields_error_message ) ) );
			}
			?>

			<?php
			if ( count( $model_type_fields ) > 0 ) {
				// sort custom fields
				uasort( $model_type_fields, function ( $a, $b ) {
					return strcmp( str_replace( '_', 'zzzzzz', $a ), str_replace( '_', 'zzzzzz', $b ) ); // fields '_xxx' at the end
				} );

				// Show selected first
				foreach ( [ true, false ] as $is_show_selected ) {

					foreach (
						$model_type_fields

						as $model_type_field
					) {
						$is_selected = ( isset( $field_types_opt[ $model_type ] ) && ( false !== array_search( $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING, $field_types_opt[ $model_type ], true ) ) );

						if ( $is_show_selected ? $is_selected : ! $is_selected ) {
							$is_indexed_custom_field = true;
							?>

                            <div class="wpsolr_custom_field_selected">
                                <input type='checkbox'
                                       name="<?php WPSOLR_Escape::echo_escaped( sprintf( '%s[%s][%s][%s]',
									       WPSOLR_Escape::esc_attr( $index_options_name ), WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELDS ),
									       WPSOLR_Escape::esc_attr( $model_type ), WPSOLR_Escape::esc_attr( $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) ) ); ?>"
                                       class="wpsolr-remove-if-empty  wpsolr_collapser wpsolr_checked"
                                       value='1'
									<?php WPSOLR_Escape::echo_esc_attr( $disabled ); ?>
									<?php checked( $is_show_selected ); ?>>

                                <b><?php WPSOLR_Escape::echo_esc_html( $model_type_field ) ?></b>

                                <br/>

                                <div class="wpsolr_collapsed" style="margin-left:30px;">

                                    <div class="wdm_row">
                                        <div class="col_left">Field type:</div>
                                        <div class="col_right">
                                            <select
                                                    class="wpsolr_same_name_same_value"
												<?php
												$solr_dynamic_types = WpSolrSchema::get_solr_dynamic_entensions();
												$field_solr_type    = ! empty( $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ] ) && ! empty( $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
													? $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ]
													: WpSolrSchema::get_solr_dynamic_entension_id_by_default();
												if ( $disabled ) {
													WPSOLR_Escape::echo_escaped( ' disabled ' );
												}
												?>
                                                    name="<?php WPSOLR_Escape::echo_escaped( sprintf( '%s[%s][%s][%s]',
														WPSOLR_Escape::esc_attr( $index_options_name ),
														WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES ),
														WPSOLR_Escape::esc_attr( $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
														WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ) ) ); ?>">
												<?php
												foreach ( $solr_dynamic_types as $solr_dynamic_type_id => $solr_dynamic_type_array ) {
													WPSOLR_Escape::echo_escaped( sprintf( '<option value="%s" %s %s>%s</option>',
														WPSOLR_Escape::esc_attr( $solr_dynamic_type_id ),
														selected( $field_solr_type, $solr_dynamic_type_id, false ),
														WPSOLR_Escape::esc_attr( $solr_dynamic_type_array['disabled'] ),
														WPSOLR_Escape::esc_html( WpSolrSchema::get_solr_dynamic_entension_label( $solr_dynamic_type_array ) )
													) );
												}
												?>
                                            </select>
                                        </div>
                                        <div class="clear"></div>
                                    </div>

                                    <div class="wdm_row">
                                        <div class="col_left">Error management:</div>
                                        <div class="col_right">
                                            <select
                                                    class="wpsolr_same_name_same_value"
                                                    style="float:left; width:70%"
												<?php
												$field_action_id = ! empty( $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ] ) && ! empty( $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ] )
													? $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ]
													: WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_IGNORE_FIELD;
												if ( $disabled ) {
													WPSOLR_Escape::echo_escaped( ' disabled ' );
												}
												?>
                                                    name="<?php WPSOLR_Escape::echo_escaped( sprintf( '%s[%s][%s][%s]',
														WPSOLR_Escape::esc_attr( $index_options_name ),
														WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES ),
														WPSOLR_Escape::esc_attr( $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
														WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ) ) ); ?>">
												<?php
												foreach (
													[
														WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_IGNORE_FIELD => 'Use empty value if conversion error',
														WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_THROW_ERROR  => 'Stop indexing at first conversion error',
													] as $action_id => $action_text
												) {
													WPSOLR_Escape::echo_escaped( sprintf( '<option value="%s" %s>%s</option>',
														WPSOLR_Escape::esc_attr( $action_id ),
														WPSOLR_Escape::esc_attr( selected( $field_action_id, $action_id, false ) ),
														WPSOLR_Escape::esc_html( $action_text ) ) );
												}
												?>
                                            </select>
                                        </div>
                                        <div class="clear"></div>
                                    </div>

                                    <div class="wdm_row">
                                        <div class="col_left">Carried by relation to:</div>
                                        <div class="col_right">

											<?php
											$field_relation_id = ! empty( $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ] ) && ! empty( $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION ] )
												? $custom_field_properties[ $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION ]
												: WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION_NONE;

											WPSOLR_Admin_UI_Select2::dropdown_select2( [
												// Do not activate on page load to prevent crash when many custom fields
												WPSOLR_Admin_UI_Select2::PARAM_SELECT2_DO_NOT_ACTIVATE          => empty( $field_relation_id ),
												WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_IS_MULTISELECT       => false,
												WPSOLR_Admin_UI_Select2::PARAM_SELECT2_CLASS                    => sprintf( 'wpsolr_same_name_same_value %s', $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
												WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_SELECTED_IDS         =>
													[
														'no_matter' =>
															[
																$field_relation_id => $field_relation_id
															],
													],
												WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_AJAX_EVENT           => WPSOLR_Admin_UI_Ajax::AJAX_RELATIONS_SEARCH,
												WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_PLACEHOLDER_TEXT     => 'Choose a relation&hellip;',
												WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_OPTION_ABSOLUTE_NAME => sprintf( '%s[%s][%s][%s]', $index_options_name, WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES, $model_type_field . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING, WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_RELATION ),
												WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_OPTION_RELATIVE_NAME => 'no_matter',
												WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS                   => [ WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_EXTRAS => [ 'object_type' => $model_type ] ],
												WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_SELECTORS         => [],
											] );

											?>

                                        </div>
                                        <div class="clear"></div>
                                    </div>

                                </div>

                            </div>

							<?php
						}
					}
				}

			} else {
				WPSOLR_Escape::echo_escaped( 'None' );
			}
			?>
        </div>
    </div>
    <div class="clear"></div>
</div>
