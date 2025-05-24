<?php

use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\suggestions\WPSOLR_Option_Suggestions;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

/**
 * Included file to display admin options
 */
global $license_manager;

WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_SUGGESTIONS, true );

$extension_options_name = WPSOLR_Option_View::get_view_uuid_options_name( WPSOLR_Option::OPTION_SUGGESTIONS );
$settings_fields_name   = 'extension_suggestions_opt';

$suggestions = WPSOLR_Service_Container::getOption()->get_option_suggestions_suggestions();
if ( isset( $_POST['wpsolr_new_suggestions'] ) && ! isset( $crons[ $_POST['wpsolr_new_suggestions'] ] ) ) {
	check_ajax_referer( 'security', 'security' ); // Redundant to pass plugin-check
	$suggestions = array_merge( [ WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'wpsolr_new_suggestions' ] ) => [ 'is_new' => true ] ], $suggestions );
}

?>


<style>
    .wpsolr_suggestions_is_new {
        border: 1px solid gray;
        background-color: #e5e5e5;
    }

    .wpsolr-remove-if-hidden {
        display: none;
    }

    #extension_suggestions_settings_form .col_left {
        width: 10%;
    }

    #extension_suggestions_settings_form .col_right {
        width: 77%;
    }
</style>

<script>

    jQuery(document).ready(function ($) {

        var type_definitions = <?php WPSOLR_Escape::echo_esc_json( wp_json_encode( WPSOLR_Option_Suggestions::get_type_definitions() ) ); ?>;
        var layout_definitions = <?php WPSOLR_Escape::echo_esc_json( wp_json_encode( WPSOLR_Option_Suggestions::get_template_definitions() ) ); ?>;

        /**
         * Refresh all layouts and fields of type(s) passed in argument
         **/
        function refresh_types(type_elements, is_type_changed) {

            $(type_elements).each(function (index) {

                var type_element = $(this);
                var current_suggestion_type_value = type_element.val();

                /**
                 * Layouts shown/hidden in the select box for the current ftype
                 **/
                var current_suggestion_layout_element = type_element.closest('.wpsolr_suggestions').find('.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Suggestions::CLASS_SUGGESTION_LAYOUT ); ?>');
                current_suggestion_layout_element.find('option').hide();
                $.each(layout_definitions, function (index, layout_definition) {

                    if (('' === layout_definition['type']) || (current_suggestion_type_value === layout_definition['type'])) {
                        current_suggestion_layout_element.find('option[value="' + layout_definition["code"] + '"]').show();
                    }
                });

                // Type has changed: select its default layout
                if (is_type_changed) {
                    $.each(type_definitions, function (index, type_definition) {
                        if ((current_suggestion_type_value === type_definition['code'])) {
                            current_suggestion_layout_element.val(type_definition["default_layout"]);
                        }
                    });
                }

                /**
                 * Refresh type layout fields
                 **/
                refresh_layouts(current_suggestion_layout_element);
            });
        }

        /**
         * Refresh all fields of layout(s) passed in argument
         **/
        function refresh_layouts(layout_elements) {

            $(layout_elements).each(function (index) {

                var layout_element = $(this);
                var current_suggestion_layout_value = layout_element.val();

                /**
                 * Hide all optional fields
                 **/
                layout_element.closest('.wpsolr_suggestions').find('.wpsolr-remove-if-hidden').hide();
                layout_element.closest('.wpsolr_suggestions').find('.wpsolr_collapsed').removeClass('wpsolr_collapsed').addClass('wpsolr_collapsed_removed');

                /**
                 * Show optional fields for the layout selected
                 **/
                $.each(layout_definitions, function (index, layout_definition) {
                    if ((current_suggestion_layout_value === layout_definition['code'])) {
                        $.each(layout_definition['fields'], function (index, field_class) {

                            // Pnly collapse elements authorized
                            layout_element.closest('.wpsolr_suggestions').find('.' + field_class).closest('.wpsolr_collapsed_removed').removeClass('wpsolr_collapsed_removed').addClass('wpsolr_collapsed');

                            // Show authorized elements
                            layout_element.closest('.wpsolr_suggestions').find('.' + field_class).closest('.wpsolr-remove-if-hidden').not('.wpsolr-has-collapsed').show();
                            console.log('show', field_class);
                        });
                    }
                });
            });
        }

        /**
         * Refresh layout of type selected
         **/
        $(document).on('change', '.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Suggestions::CLASS_SUGGESTION_TYPE ); ?>', function (e) {
            refresh_types($(this), true);
        });

        /**
         * Refresh layout of type selected
         **/
        $(document).on('change', '.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Suggestions::CLASS_SUGGESTION_LAYOUT ); ?>', function (e) {
            refresh_layouts($(this));
        });

        /**
         * Refresh all the layouts of all types on page display
         */
        var all_type_elements = $('.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Suggestions::CLASS_SUGGESTION_TYPE ); ?>');
        refresh_types(all_type_elements, false);

        /**
         * For change event on new suggestion type to display its layout and fields
         */
        $new_type_el = $('.wpsolr_suggestions_is_new .wpsolr_suggestion_type');
        $new_type_el.change();
    });

</script>

<form id="wpsolr_form_new_suggestions" method="post">
    <input type="hidden" name="wpsolr_new_suggestions"
           value="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Indexes::generate_uuid() ); ?>"/>
    <input type="hidden" name="security"
           value="<?php WPSOLR_Escape::echo_esc_attr( wp_create_nonce( 'security' ) ); ?>"/>
</form>

<div wdm-vertical-tabs-contentid="extension_groups-options" class="wdm-vertical-tabs-content wpsolr-col-9">
    <form action="options.php" method="POST" id='extension_suggestions_settings_form'>
		<?php
		WPSOLR_Option_View::output_form_view_hidden_fields( $settings_fields_name );
		?>

        <div class='wrapper'>
            <h4 class='head_div'><?php WPSOLR_Escape::echo_escaped( WPSOLR_Option_View::get_views_html( 'Suggestions' ) ); ?> </h4>

            <div class="wdm_note">
                In this section, you will configure suggestions. Also named autocompletion.
                <ol>
                    <li>
                        Define on which search box(es) suggestions will appear, with jQuery selectors.
                    </li>
                    <li>
                        Select the suggestions type: keywords, flat results, grouped results.
                    </li>
                    <li>
                        Select the suggestions layout, or create your own to match your theme style.
                    </li>
                </ol>

                You can define several suggestion definitions, and order them by drag&drop. The first definition that
                matches your search box(es) jQuery selector will be activated.
            </div>

            <div class="wdm_row">
                <div class='col_left'>
                    <input type="button"
                           name="add_suggestion"
                           id="add_suggestion"
                           class="button-primary"
                           value="Configure new suggestions"
                           onclick="jQuery('#wpsolr_form_new_suggestions').submit();"
                    />
                </div>
                <div class='col_right'>
                </div>
                <div class="clear"></div>
            </div>

            <ul class="ui-sortable">
				<?php foreach (
					$suggestions

					as $suggestion_uuid => $suggestion
				) {
					$is_new                                 = isset( $suggestion['is_new'] );
					$suggestion_label                       = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_LABEL ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_LABEL ] : 'rename me';
					$suggestion_jquery_selector             = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_JQUERY_SELECTOR ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_JQUERY_SELECTOR ] : '';
					$suggestion_redirection_pattern         = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_REDIRECTION_PATTERN ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_REDIRECTION_PATTERN ] : '';
					$suggestion_type                        = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_TYPE ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_TYPE ] : WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_NONE;
					$suggestion_layout                      = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_LAYOUT_ID ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_LAYOUT_ID ] : '';
					$suggestion_nb                          = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_NB ] : '10';
					$suggestion_image_width_pct             = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IMAGE_WIDTH_PCT ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IMAGE_WIDTH_PCT ] : '10';
					$suggestion_image_size                  = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IMAGE_SIZE ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IMAGE_SIZE ] : '';
					$suggestion_custom_file                 = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_TEMPLATE_FILE ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_TEMPLATE_FILE ] : '';
					$suggestion_is_active                   = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IS_ACTIVE ] );
					$suggestion_order_by                    = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY ] : WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY_CONTENT;
					$suggestion_is_archive                  = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IS_ARCHIVE ] );
					$suggestion_is_show_text                = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_IS_SHOW_TEXT ] );
					$suggestion_custom_css                  = ! empty( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_CSS ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_CSS ] :
						sprintf( '<!-- <style> .c%s li a {color: red;} </style> -->', $suggestion_uuid );
					$suggestion_jquery_autocomplete_options = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_JQUERY_AUTOCOMPLETE_OPTIONS ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_JQUERY_AUTOCOMPLETE_OPTIONS ] : '';
					?>
                    <li class="wpsolr_suggestions wpsolr-sorted ui-sortable-handle <?php WPSOLR_Escape::echo_escaped( $is_new ? 'wpsolr_suggestions_is_new' : '' ); ?>">
						<?php if ( $is_new ) { ?>
                            <input type="hidden"
                                   id="wpsolr_suggestions_new_uuid"
                                   value="<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>"
                            />
						<?php } ?>

                        <div data-wpsolr-suggestions-label="<?php WPSOLR_Escape::echo_esc_attr( $suggestion_label ); ?>">
                            <input type="button"
                                   style="float:right;"
                                   name="delete_suggestions"
                                   class="c_<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?> wpsolr-suggestions-delete-button button-secondary"
                                   value="Delete"
                                   onclick="jQuery(this).closest('.wpsolr_suggestions').remove();"
                            />
                            <h4 class='head_div'>
                                Suggestions: <?php WPSOLR_Escape::echo_esc_html( $suggestion_label ); ?> </h4>


                            <div class="wdm_row">
                                <div class='col_left'>
                                    Status
                                </div>
                                <div class='col_right'>
                                    <label>
                                        <input type='checkbox'
                                               name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IS_ACTIVE ); ?>]'
                                               value='is_active'
											<?php checked( $suggestion_is_active ); ?>>
                                        Is active
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
                                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_LABEL ); ?>]'
                                           placeholder="Enter a Number"
                                           value="<?php WPSOLR_Escape::echo_esc_attr( $suggestion_label ); ?>"
                                    >

                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="wdm_row">
                                <div class='col_left'>
                                    CSS selector
                                </div>
                                <div class='col_right'>
                                    <input type='text'
                                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_JQUERY_SELECTOR ); ?>]'
                                           placeholder="Specify the CSS selector for the search box where you want to display the suggestions."
                                           value="<?php WPSOLR_Escape::echo_esc_attr_jquery( $suggestion_jquery_selector ); ?>">

                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="wdm_row">
                                <div class='col_left'>
                                    Javascript options
                                </div>
                                <div class='col_right'>
                                    <textarea
                                            rows="2"
                                            class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_JQUERY_AUTOCOMPLETE_OPTIONS ); ?>"
                                            name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_JQUERY_AUTOCOMPLETE_OPTIONS ); ?>]'
                                            class="wpsolr-remove-if-empty"
                                            data-wpsolr-empty-value=""
                                    ><?php WPSOLR_Escape::echo_esc_textarea( $suggestion_jquery_autocomplete_options ); ?></textarea>

                                    <p>
                                        Use any option available to
                                        <a href="<?php WPSOLR_Escape::echo_esc_url( 'https://github.com/devbridge/jQuery-Autocomplete?tab=readme-ov-file#general-settings-local-and-ajax' ); ?>"
                                           target="_new">jQuery-Autocomplete</a>.
                                    <p>
                                        For instance, copy and paste the following options:
                                        <input type="text"
                                               value='{"minChars": 3,"deferRequestBy": 1000}'
                                               style="width:50%"
                                               disabled
                                        />
                                    <p>
                                    </p>

                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="wdm_row">
                                <div class='col_left'>
                                    Query
                                </div>
                                <div class='col_right'>
                                    <select class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Suggestions::CLASS_SUGGESTION_TYPE ); ?>"
                                            name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_TYPE ); ?>]'
                                    >
										<?php
										$options = WPSOLR_Option_Suggestions::get_type_definitions();

										foreach ( $options as $option ) {
											$selected = ( $option['code'] === $suggestion_type ) ? 'selected' : '';
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

                            <!-- Refirection pattern -->
                            <div class="wdm_row wpsolr-remove-if-hidden">
                                <div class='col_left'>
                                    Redirect to
                                </div>
                                <div class='col_right'>
                                    <input type='text'
                                           class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_REDIRECTION_PATTERN ); ?>"
                                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_REDIRECTION_PATTERN ); ?>]'
                                           placeholder="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Suggestions::SUGGESTION_REDIRECTION_PATTERN_DEFAULT ); ?>"
                                           value="<?php WPSOLR_Escape::echo_esc_attr( $suggestion_redirection_pattern ); ?>">
                                    <p>By default, clicking on a suggestion keyword will redirect to the standard WP
                                        search url <span style="color: blueviolet">/?s=%s</span> (%s being replaced with
                                        the keyword).
                                        You can use any other redirection url pattern.
                                        For instance <span style="color: blueviolet">/?s=%s&post_type=product</span> to
                                        redirect to the standard WoooCommerce
                                        products search.</p>

                                </div>
                                <div class="clear"></div>
                            </div>

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
                                                <select class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Suggestions::CLASS_SUGGESTION_LAYOUT ); ?>"
                                                        style="width:100%"
                                                        name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_LAYOUT_ID ); ?>]'
                                                >
													<?php
													$options = WPSOLR_Option_Suggestions::get_template_definitions();

													foreach ( $options as $option ) {
														$selected = ( $option['code'] === $suggestion_layout ) ? 'selected' : '';
														?>
                                                        <option value="<?php WPSOLR_Escape::echo_esc_attr( $option['code'] ); ?>"
															<?php WPSOLR_Escape::echo_esc_attr( $selected ); ?>
                                                        >
															<?php WPSOLR_Escape::echo_esc_html( ( $option['code'] === WPSOLR_Option::OPTION_SUGGESTION_LAYOUT_ID_CUSTOM_FILE ) ? $option['label'] : $option['label'] ); ?>
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
                                                <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_NB ); ?>"
                                                       type='number' step="1" min="1"
                                                       name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_NB ); ?>]'
                                                       placeholder=""
                                                       value="<?php WPSOLR_Escape::echo_esc_html( $suggestion_nb ); ?>">
                                                Enter the maximum number of suggestions displayed.
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
                                                <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IS_ARCHIVE ); ?>"
                                                       type='checkbox'
                                                       name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IS_ARCHIVE ); ?>]'
                                                       value='is_active'
													<?php checked( $suggestion_is_archive ); ?>>
                                                Click to filter suggestions with the archive type of the page containing
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
                                                <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IS_SHOW_TEXT ); ?>"
                                                       type='checkbox'
                                                       name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IS_SHOW_TEXT ); ?>]'
                                                       value='is_active'
													<?php checked( $suggestion_is_show_text ); ?>>
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
                                                <select class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IMAGE_SIZE ); ?>"
                                                        style="width:100%"
                                                        name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IMAGE_SIZE ); ?>]'
                                                >
													<?php
													foreach ( get_intermediate_image_sizes() as $option ) {
														?>
                                                        <option value="<?php WPSOLR_Escape::echo_esc_attr( $option ); ?>"
															<?php WPSOLR_Escape::echo_esc_attr( selected( $suggestion_image_size, $option ) ); ?>
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
                                                %<input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IMAGE_WIDTH_PCT ); ?>"
                                                        type='number' step="1" min="0" max="100"
                                                        name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_IMAGE_WIDTH_PCT ); ?>]'
                                                        placeholder=""
                                                        value="<?php WPSOLR_Escape::echo_esc_attr( $suggestion_image_width_pct ); ?>">
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
                                                        class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_CSS ); ?>"
                                                        name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_CSS ); ?>]'
                                                        placeholder=""
                                                        rows="4"
                                                ><?php WPSOLR_Escape::echo_esc_textarea( $suggestion_custom_css ); ?></textarea>
                                            </label>
                                            Enter your custom css code here. To keep isolation, prefix all your css
                                            selectors with .c<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>
                                        </div>
                                        <div class="clear"></div>
                                    </div>


                                    <div class="wdm_row wpsolr-remove-if-hidden">
                                        <div class='col_left'>
                                            Order by
                                        </div>
                                        <div class='col_right'>

                                            <label>
                                                <select class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY ); ?>"
                                                        style="width:100%"
                                                        name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_ORDER_BY ); ?>]'
                                                >
													<?php
													$options = WPSOLR_Option_Suggestions::get_order_by_definitions();

													foreach ( $options as $option ) {
														$selected = ( $option['code'] === $suggestion_order_by ) ? 'selected' : '';
														?>
                                                        <option value="<?php WPSOLR_Escape::echo_esc_attr( $option['code'] ); ?>"
															<?php WPSOLR_Escape::echo_esc_attr( $selected ); ?>
															<?php WPSOLR_Escape::echo_escaped( isset( $option['disabled'] ) && ( $option['disabled'] ) ? 'disabled' : '' ); ?>
                                                        >
															<?php WPSOLR_Escape::echo_esc_html( $option['label'] ); ?>
                                                        </option>
													<?php } ?>

                                                </select>
                                                Select how to sort the suggestions
                                            </label>

                                        </div>
                                        <div class="clear"></div>
                                    </div>

                                    <div class="wdm_row wpsolr-remove-if-hidden <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODELS ); ?>">
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

												if ( isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] ) ) {
													foreach ( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] as $model_type => $dontcare ) {
														include( 'suggestion_models.inc.php' );
													}
												}

												$model_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();
												if ( ! empty( $model_types ) ) {
													foreach ( $model_types as $model_type ) {
														if ( ! isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] ) || ! isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model_type ] ) ) { // Prevent duplicate
															include( 'suggestion_models.inc.php' );
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
                                                <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_TEMPLATE_FILE ); ?>"
                                                       type='text'
                                                       name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_CUSTOM_TEMPLATE_FILE ); ?>]'
                                                       placeholder=""
                                                       value="<?php WPSOLR_Escape::echo_esc_attr( $suggestion_custom_file ); ?>">
                                                Custom Twig file, relative to your folder
                                                "child-theme/<?php WPSOLR_Escape::echo_esc_html( WPSOLR_Option_Suggestions::TEMPLATE_ROOT_DIR ); ?>
                                                /twig".
                                                Example: "my-suggestions.twig" will be transformed in
                                                "child-theme/<?php WPSOLR_Escape::echo_esc_html( WPSOLR_Option_Suggestions::TEMPLATE_ROOT_DIR ); ?>
                                                /twig/my-suggestions.twig"
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
                    <input id="save_suggestions"
                           type="submit"
                           class="button-primary wdm-save"
                           value="Save Suggestions"/>
                </div>
            </div>
        </div>

    </form>
</div>