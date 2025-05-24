<?php

use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\recommendations\WPSOLR_Option_Recommendations;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\shortcode\WPSOLR_Shortcode_Recommendation;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

/**
 * Included file to display admin options
 */
global $license_manager;

WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_RECOMMENDATIONS, true );

$extension_options_name = WPSOLR_Option_View::get_view_uuid_options_name( WPSOLR_Option::OPTION_RECOMMENDATIONS );
$settings_fields_name   = 'extension_recommendations_opt';

$recommendations = WPSOLR_Service_Container::getOption()->get_option_recommendations_recommendations();
if ( isset( $_POST['wpsolr_new_recommendations'] ) && ! isset( $crons[ $_POST['wpsolr_new_recommendations'] ] ) ) {
	check_ajax_referer( 'security', 'security' );
	$recommendations = array_merge( [ WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'wpsolr_new_recommendations' ] ) => [ 'is_new' => true ] ], $recommendations );
}

WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );


$index_uuid    = WPSOLR_Service_Container::getOption()->get_view_index_uuid();
$current_index = ( new WPSOLR_Option_Indexes() )->get_index( $index_uuid );

$index_has_recommendation = false;
if ( ! empty( $current_index ) ) {
	$index_label              = $current_index[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_LABEL ];
	$index_hosting_api        = WPSOLR_Hosting_Api_Abstract::get_hosting_api_by_id( $current_index['index_hosting_api_id'] );
	$index_has_recommendation = $index_hosting_api->get_has_recommendation();
	$index_engine             = $index_hosting_api->get_search_engine();
	$index_engine_name        = ( new WPSOLR_Option_Indexes() )->get_search_engine_name( $index_engine );
	$engine                   = WPSOLR_SearchSolariumClient::create_from_index_indice( $index_uuid );
}


/* Include the current engine's recommendation admin js */
require_once( 'admin_options.inc.js.php' );
if ( $index_has_recommendation ) {
	//require_once( sprintf( '%s/admin_options.inc.js.php', $index_engine ) );
} ?>


<style>
    .wpsolr_recommendations_is_new {
        border: 1px solid gray;
        background-color: #e5e5e5;
    }

    .wpsolr-remove-if-hidden {
        display: none;
    }

    #extension_recommendations_settings_form .col_left {
        width: 10%;
    }

    #extension_recommendations_settings_form .col_right {
        width: 77%;
    }
</style>

<form id="wpsolr_form_new_recommendations" method="post">
    <input type="hidden" name="wpsolr_new_recommendations"
           value="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Indexes::generate_uuid() ); ?>"/>
    <input type="hidden" name="security"
           value="<?php WPSOLR_Escape::echo_esc_attr( wp_create_nonce( 'security' ) ); ?>"/>
</form>

<div wdm-vertical-tabs-contentid="extension_groups-options" class="wdm-vertical-tabs-content wpsolr-col-9">
    <form action="options.php" method="POST" id='extension_recommendations_settings_form'>
		<?php
		WPSOLR_Option_View::output_form_view_hidden_fields( $settings_fields_name );
		?>

        <div class='wrapper'>
            <h4 class='head_div'><?php WPSOLR_Escape::echo_escaped( WPSOLR_Option_View::get_views_html( 'Recommendations',
					[
						'is_show_default'              => true,
						'default_label'                => 'Choose a Recommendation index',
						'is_show_recommendations_only' => true,
					] ) ); ?> </h4>

			<?php if ( $index_has_recommendation ) { ?>
                <div class="wdm_note">
                    In this section, you will configure recommendations.
                    <ol>
                        <li>
                            Select the recommendations type.
                        </li>
                        <li>
                            Select the recommendations layout, or create your own to match your theme style.
                        </li>
                    </ol>

                    You can add recommendations to your pages with our Widget or the shortcode.
                </div>

                <div class="wdm_row">
                    <div class='col_left'>
                        <input type="button"
                               name="add_recommendation"
                               id="add_recommendation"
                               class="button-primary"
                               value="Configure new recommendations"
                               onclick="jQuery('#wpsolr_form_new_recommendations').submit();"
                        />
                    </div>
                    <div class='col_right'>
                    </div>
                    <div class="clear"></div>
                </div>

                <ul class="ui-sortable">
					<?php foreach (
						$recommendations

						as $recommendation_uuid => $recommendation
					) {
						$is_new                                    = isset( $recommendation['is_new'] );
						$recommendation_label                      = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_LABEL ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_LABEL ] : 'rename me';
						$recommendation_jquery_selector            = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_JQUERY_SELECTOR ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_JQUERY_SELECTOR ] : '';
						$recommendation_type                       = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ] : WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_NONE;
						$recommendation_layout                     = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_LAYOUT_ID ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_LAYOUT_ID ] : '';
						$recommendation_nb                         = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_NB ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_NB ] : '10';
						$recommendation_image_width_pct            = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_WIDTH_PCT ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_WIDTH_PCT ] : '10';
						$recommendation_image_size                 = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_SIZE ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_SIZE ] : '';
						$recommendation_custom_file                = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_TEMPLATE_FILE ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_TEMPLATE_FILE ] : '';
						$recommendation_is_active                  = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IS_ACTIVE ] );
						$recommendation_order_by                   = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_ORDER_BY ] ) ? $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_ORDER_BY ] : WPSOLR_Option::OPTION_RECOMMENDATION_ORDER_BY_CONTENT;
						$recommendation_is_archive                 = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IS_ARCHIVE ] );
						$recommendation_is_show_text               = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IS_SHOW_TEXT ] );
						$recommendation_custom_css                 = ! empty( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_CSS ] ) ?
							$recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_CSS ] :
							sprintf( '<!-- <style> .c%s li a {color: red;} </style> -->', $recommendation_uuid );
						$recommendation_is_not_ajax                = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IS_NOT_AJAX ] );
						$recommendation_filter_is_same_object_type = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_SAME_OBJECT_TYPE ] );
						$recommendation_filter_is_not_same_object  = isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_NOT_SAME_OBJECT ] );
						$recommendation_class                      = $is_new ? 'wpsolr_recommendations_is_new' : "wpsolr_$recommendation_uuid";
						?>
                        <li class="wpsolr_recommendations wpsolr-sorted ui-sortable-handle <?php WPSOLR_Escape::echo_escaped( $recommendation_class ); ?>">
							<?php if ( $is_new ) { ?>
                                <input type="hidden"
                                       id="wpsolr_recommendations_new_uuid"
                                       value="<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>"
                                />
							<?php } ?>

                            <div data-wpsolr-recommendations-label="<?php WPSOLR_Escape::echo_esc_attr( $recommendation_label ); ?>">
                                <input type="button"
                                       style="float:right;"
                                       name="delete_recommendations"
                                       class="c_<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?> wpsolr-recommendations-delete-button button-secondary"
                                       value="Delete"
                                       onclick="jQuery(this).closest('.wpsolr_recommendations').remove();"
                                />
                                <h4 class='head_div'>
                                    Recommendations: <?php WPSOLR_Escape::echo_esc_html( $recommendation_label ); ?> </h4>


								<?php if ( ! $is_new ) { ?>
                                    <div class="wdm_row">
                                        <div class='col_left'>
                                            Shortcode
                                        </div>
                                        <div class='col_right'>

                                            <input id="<?php WPSOLR_Escape::echo_esc_attr( sprintf( 'shortcode_%s', $recommendation_uuid ) ); ?>"
                                                   type='text' readonly
                                                   value="<?php WPSOLR_Escape::echo_escaped( WPSOLR_Shortcode_Recommendation::get_shortcode_html( $recommendation_uuid, $recommendation_label, true ) ); ?>"
                                            >

                                        </div>
                                        <div class="clear"></div>
                                    </div>
								<?php } ?>

                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Status
                                    </div>
                                    <div class='col_right'>
                                        <label>
                                            <input type='checkbox'
                                                   name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IS_ACTIVE ); ?>]'
                                                   value='is_active'
												<?php checked( $recommendation_is_active ); ?>>
                                            Is active
                                        </label>
                                    </div>
                                    <div class="clear"></div>
                                </div>

                                <div class="wdm_row wpsolr-remove-if-hidden">
                                    <div class='col_left'>
                                        No Ajax
                                    </div>
                                    <div class='col_right'>
                                        <label>
                                            <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IS_NOT_AJAX ); ?>"
                                                   type='checkbox'
                                                   name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IS_NOT_AJAX ); ?>]'
                                                   value='is_active'
												<?php checked( $recommendation_is_not_ajax ); ?>>
                                            Prevent calling Ajax. For instance, to allow caching recommendations with
                                            the whole HTML content.
                                        </label>
                                    </div>
                                    <div class="clear"></div>
                                </div>

                                <div class="wdm_row wpsolr-remove-if-hidden">
                                    <div class='col_left'>
                                        Exclude current post
                                    </div>
                                    <div class='col_right'>
                                        <label>
                                            <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_NOT_SAME_OBJECT ); ?>"
                                                   type='checkbox'
                                                   name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_NOT_SAME_OBJECT ); ?>]'
                                                   value='is_active'
												<?php checked( $recommendation_filter_is_not_same_object ); ?>>
                                            Do not select the current post in recommendations.
                                        </label>
                                    </div>
                                    <div class="clear"></div>
                                </div>

                                <div class="wdm_row wpsolr-remove-if-hidden">
                                    <div class='col_left'>
                                        Limit to same post type
                                    </div>
                                    <div class='col_right'>
                                        <label>
                                            <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_SAME_OBJECT_TYPE ); ?>"
                                                   type='checkbox'
                                                   name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_SAME_OBJECT_TYPE ); ?>]'
                                                   value='is_active'
												<?php checked( $recommendation_filter_is_same_object_type ); ?>>
                                            Posts will only be selected from the same post type as the current post.
                                        </label>
                                    </div>
                                    <div class="clear"></div>
                                </div>

                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Label
                                    </div>
                                    <div class='col_right'>
                                        <input type='text'
                                               name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_LABEL ); ?>]'
                                               placeholder="Enter a label"
                                               value="<?php WPSOLR_Escape::echo_esc_attr( $recommendation_label ); ?>"
                                        >

                                    </div>
                                    <div class="clear"></div>
                                </div>

                                <div class="wdm_row">
                                    <div class='col_left'>
                                        jQuery selectors
                                    </div>
                                    <div class='col_right'>
                                        <input type='text'
                                               name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_JQUERY_SELECTOR ); ?>]'
                                               placeholder="Enter a jQuery"
                                               value="<?php WPSOLR_Escape::echo_esc_attr( $recommendation_jquery_selector ); ?>">

                                    </div>
                                    <div class="clear"></div>
                                </div>


                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Type of recommendation
                                    </div>
                                    <div class='col_right'>
                                        <select class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Recommendations::CLASS_RECOMMENDATION_TYPE ); ?>"
                                                name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_TYPE ); ?>]'
                                        >
											<?php
											$options = WPSOLR_Option_Recommendations::get_type_definitions();

											foreach ( $options as $option ) {
												$selected = ( $option['code'] === $recommendation_type ) ? 'selected' : '';
												$disabled = $option['disabled'] ? 'disabled' : '';
												?>
                                                <option
                                                        value="<?php WPSOLR_Escape::echo_esc_attr( $option['code'] ); ?>"
													<?php WPSOLR_Escape::echo_esc_attr( $selected ); ?>
													<?php WPSOLR_Escape::echo_esc_attr( $disabled ); ?>
                                                >
													<?php WPSOLR_Escape::echo_esc_html( $option['label'] ); ?>
                                                </option>
											<?php } ?>

                                        </select>
                                    </div>
                                    <div class="clear"></div>
                                </div>

								<?php
								/* Include the current engine's recommendation admin template */
								$file_rel       = sprintf( '%s/admin_options.inc.php', $index_engine );
								$file           = sprintf( '%s/wpsolr/core/classes/extensions/recommendations/%s', WPSOLR_PLUGIN_DIR, $file_rel );
								$is_file_exists = file_exists( $file );
								if ( $is_file_exists ) {
									require( $file );
								}

								?>

                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Presentation
                                    </div>
                                    <div class='col_right'>

                                        <div class="wdm_row">
                                            <div class='col_left'>
                                                Template
                                            </div>
                                            <div class='col_right'>

                                                <label>
                                                    <select class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Recommendations::CLASS_RECOMMENDATION_LAYOUT ); ?>"
                                                            style="width:100%"
                                                            name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_LAYOUT_ID ); ?>]'
                                                    >
														<?php
														$options = array_merge( [
															[
																'code'  => '',
																'label' => 'Please select a template',
															]
														], WPSOLR_Option_Recommendations::get_template_definitions()
														);

														foreach ( $options as $option ) {
															$selected = ( $option['code'] === $recommendation_layout ) ? 'selected' : '';
															?>
                                                            <option value="<?php WPSOLR_Escape::echo_esc_attr( $option['code'] ); ?>"
																<?php WPSOLR_Escape::echo_esc_attr( $selected ); ?>
                                                            >
																<?php WPSOLR_Escape::echo_esc_html( ( $option['code'] === WPSOLR_Option::OPTION_RECOMMENDATION_LAYOUT_ID_CUSTOM_FILE ) ? $option['label'] : $option['label'] ); ?>
                                                            </option>
														<?php } ?>

                                                    </select>
                                                    <p>
                                                        <a href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/create-your-custom-templates/' ) ); ?>"
                                                           target="_new">
                                                            Consult our documentation
                                                        </a>
                                                        to create your own twig templates. More templates coming.
                                                    </p>
                                                </label>

                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <!-- Maximum -->
                                        <div class="wdm_row wpsolr-remove-if-hidden">
                                            <div class='col_left'>
                                                Maximum
                                            </div>
                                            <div class='col_right'>

                                                <label>
                                                    <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_NB ); ?>"
                                                           type='number' step="1" min="1"
                                                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_NB ); ?>]'
                                                           placeholder=""
                                                           value="<?php WPSOLR_Escape::echo_esc_attr( $recommendation_nb ); ?>">
                                                    Enter the maximum number of recommendations displayed.
                                                </label>


                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <!-- Show text -->
                                        <div class="wdm_row wpsolr-remove-if-hidden">
                                            <div class='col_left'>
                                                Filter
                                            </div>
                                            <div class='col_right'>
                                                <label>
                                                    <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IS_ARCHIVE ); ?>"
                                                           type='checkbox'
                                                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IS_ARCHIVE ); ?>]'
                                                           value='is_active'
														<?php checked( $recommendation_is_archive ); ?>>
                                                    Click to filter recommendations with the archive type of the page
                                                    containing
                                                    the current search box.
                                                    Leave uncheck to search globally (unfiltered). Admin archives are
                                                    automatically filtered by their post type.
                                                </label>
                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <!-- Show text -->
                                        <div class="wdm_row wpsolr-remove-if-hidden">
                                            <div class='col_left'>
                                                Description
                                            </div>
                                            <div class='col_right'>
                                                <label>
                                                    <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IS_SHOW_TEXT ); ?>"
                                                           type='checkbox'
                                                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IS_SHOW_TEXT ); ?>]'
                                                           value='is_active'
														<?php checked( $recommendation_is_show_text ); ?>>
                                                    Show description
                                                </label>
                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <!-- Image size -->
                                        <div class="wdm_row wpsolr-remove-if-hidden">
                                            <div class='col_left'>
                                                Image size
                                            </div>
                                            <div class='col_right'>

                                                <label>
                                                    <select class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_SIZE ); ?>"
                                                            style="width:100%"
                                                            name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_SIZE ); ?>]'
                                                    >
														<?php
														foreach ( get_intermediate_image_sizes() as $option ) {
															?>
                                                            <option value="<?php WPSOLR_Escape::echo_esc_attr( $option ); ?>"
																<?php WPSOLR_Escape::echo_esc_attr( selected( $recommendation_image_size, $option ) ); ?>
                                                            >
																<?php WPSOLR_Escape::echo_esc_html( $option ); ?>
                                                            </option>
														<?php } ?>

                                                    </select>
                                                </label>

                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <!-- Image width -->
                                        <div class="wdm_row wpsolr-remove-if-hidden">
                                            <div class='col_left'>
                                                Image width
                                            </div>
                                            <div class='col_right'>

                                                <label>
                                                    %<input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_WIDTH_PCT ); ?>"
                                                            type='number' step="1" min="0" max="100"
                                                            name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_WIDTH_PCT ); ?>]'
                                                            placeholder=""
                                                            value="<?php WPSOLR_Escape::echo_esc_attr( $recommendation_image_width_pct ); ?>">
                                                    Enter a % width for the thumbnail images: 0, 10, 20, ... 100. Leave
                                                    empty or use "0" to hide
                                                    images.
                                                </label>


                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <!-- Custom CSS -->
                                        <div class="wdm_row wpsolr-remove-if-hidden">
                                            <div class='col_left'>
                                                Custom css
                                            </div>
                                            <div class='col_right'>

                                                <label>
                                                <textarea
                                                        class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_CSS ); ?>"
                                                        name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_CSS ); ?>]'
                                                        placeholder=""
                                                        rows="4"
                                                ><?php WPSOLR_Escape::echo_esc_textarea( $recommendation_custom_css ); ?></textarea>
                                                </label>
                                                Enter your custom css code here. To keep isolation, prefix all your css
                                                selectors with
                                                .c<?php WPSOLR_Escape::echo_esc_html( $recommendation_uuid ); ?>
                                            </div>
                                            <div class="clear"></div>
                                        </div>


                                        <div class="wdm_row wpsolr-remove-if-hidden">
                                            <div class='col_left'>
                                                Order by
                                            </div>
                                            <div class='col_right'>

                                                <label>
                                                    <select class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_ORDER_BY ); ?>"
                                                            style="width:100%"
                                                            name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_ORDER_BY ); ?>]'
                                                    >
														<?php
														$options = WPSOLR_Option_Recommendations::get_order_by_definitions();

														foreach ( $options as $option ) {
															$selected = ( $option['code'] === $recommendation_order_by ) ? 'selected' : '';
															?>
                                                            <option value="<?php WPSOLR_Escape::echo_esc_attr( $option['code'] ); ?>"
																<?php WPSOLR_Escape::echo_esc_attr( $selected ); ?>
																<?php WPSOLR_Escape::echo_escaped( isset( $option['disabled'] ) && ( $option['disabled'] ) ? 'disabled' : '' ); ?>
                                                            >
																<?php WPSOLR_Escape::echo_esc_html( $option['label'] ); ?>
                                                            </option>
														<?php } ?>

                                                    </select>
                                                    Select how to sort the recommendations
                                                </label>

                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <div class="wdm_row wpsolr-remove-if-hidden <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_MODELS ); ?>">
                                            <div class='col_left'>
                                                Show
                                            </div>
                                            <div class='col_right'>
                                                <div style="float: right">
                                                    <a href="javascript:void();" class="wpsolr_checker">All</a> |
                                                    <a href="javascript:void();" class="wpsolr_unchecker">None</a>
                                                </div>
                                                <br>

                                                <ul class="ui-sortable">
													<?php
													$loop       = 0;
													$batch_size = 100;

													if ( isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_MODELS ] ) ) {
														foreach ( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_MODELS ] as $model_type => $dontcare ) {
															include( 'recommendation_models.inc.php' );
														}
													}

													$model_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();
													if ( ! empty( $model_types ) ) {
														foreach ( $model_types as $model_type ) {
															if ( ! isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_MODELS ] ) || ! isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_MODELS ][ $model_type ] ) ) { // Prevent duplicate
																include( 'recommendation_models.inc.php' );
															}
														}
													} else {
														?>
                                                        <span>First <a
                                                                    href="/wp-admin/admin.php?page=solr_settings&tab=solr_indexes">add an index</a>. Then configure it here.</span>
														<?php
													}
													?>
                                                </ul>
                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <!-- Custom Twig template file-->
                                        <div class="wdm_row wpsolr-remove-if-hidden">
                                            <div class='col_left'>
                                                Use my custom Twig file
                                            </div>
                                            <div class='col_right'>

                                                <label>
                                                    <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_TEMPLATE_FILE ); ?>"
                                                           type='text'
                                                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATIONS_RECOMMENDATIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_RECOMMENDATION_CUSTOM_TEMPLATE_FILE ); ?>]'
                                                           placeholder=""
                                                           value="<?php WPSOLR_Escape::echo_esc_attr( $recommendation_custom_file ); ?>">
                                                    Custom Twig file, relative to your folder
                                                    "child-theme/<?php WPSOLR_Escape::echo_esc_html( WPSOLR_Option_Recommendations::TEMPLATE_ROOT_DIR ); ?>
                                                    /twig".
                                                    Example: "my-recommendations.twig" will be transformed in
                                                    "child-theme/<?php WPSOLR_Escape::echo_esc_html( WPSOLR_Option_Recommendations::TEMPLATE_ROOT_DIR ); ?>
                                                    /twig/my-recommendations.twig"
                                                </label>


                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                    </div>
                                    <div class="clear"></div>
                                </div>

                            </div>
                        </li>
					<?php } ?>
                </ul>

                <div class='wdm_row'>
                    <div class="submit">
                        <input id="save_recommendations"
                               type="submit"
                               class="button-primary wdm-save"
                               value="Save Recommendations"/>
                    </div>
                </div>
			<?php } else { ?>
                <div class="wdm_note">
					<?php if ( ! empty( $current_index ) ) { ?>
                        <p><?php WPSOLR_Escape::echo_esc_html( $index_engine_name ); ?> indices does not support
                            recommendations.</p>
					<?php } ?>
                    <p>Please select a view with an index from a recommendation engine.</p>
                </div>
			<?php } ?>

        </div>

    </form>
</div>