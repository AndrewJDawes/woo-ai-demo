<?php

use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax;
use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax_Index_Configuration_Default_builder;
use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax_Search;
use wpsolr\core\classes\admin\ui\WPSOLR_Admin_UI_Select2;
use wpsolr\core\classes\engines\configuration\WPSOLR_Configurations_Builder_Factory;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<?php

$use_configuration = isset( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION ] )
	? $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION ]
	: '';

$index_configuration_code = isset( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_CODE ] )
	? $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_CODE ]
	: '';

?>

<?php if ( $subtab === $index_indice ) {
	wp_enqueue_media();
	?>
    <script>
        jQuery(document).ready(function () {

            // Selection of a configuration checkbox parameter
            jQuery(document).on('click', 'input[type="checkbox"].wpsolr_builder_type_checkbox_mandatory', function (event) {

                var me = jQuery(this);
                var selected_el = me.parent().find('input[type="checkbox"].wpsolr_builder_type_checkbox_mandatory:checked');
                var all_el = me.parent().find('input[type="checkbox"].wpsolr_builder_type_checkbox_mandatory');

                if (!selected_el.length) {
                    // Cancel checked
                    me.prop('checked', !me.prop('checked'));
                    alert('You must select a value');

                } else {

                    // All elements selected. Use ''.
                    var new_selected_values = '';
                    if (all_el.length !== selected_el.length) {

                        // Array of values to string with comma separator
                        var new_selected_values = selected_el.map(function () {
                            return jQuery(this).val();
                        }).get().join(',');
                    }

                    me.closest('div.wpsolr_builder_type_checkbox_mandatory').find('input[type="hidden"].wpsolr_builder_type_checkbox_mandatory').val(new_selected_values);
                }
            });

            // Selection of a predefined file
            jQuery(document).on('change', '.wpsolr_attachment_file_predefined_list', function (event) {

                me = jQuery(this);
                var selected_option = me.val();
                var selected_option_label = me.find('option:selected').text().trim();

                if (selected_option) {
                    me.parent().find('.wpsolr_attachment_file_selected_id').val(selected_option_label); // basename
                    me.parent().find('.wpsolr_attachment_file_selected_id_path').val(selected_option); // full path file name
                    me.parent().find('.wpsolr_attachment_file_selected_filename_button').val('Edit ' + selected_option_label);
                }

            });

            // Upload file content in the media library.
            jQuery(document).on('click', '.wpsolr_attachment_file_selected_upload_to_media_library_button', function (event) {

                var current_button_el = jQuery(this);
                var current_button_label = current_button_el.val();

                var attachment_infos_el = current_button_el.closest('.wpsolr_attachment, #TB_window').find('.wpsolr_attachment_infos, #TB_ajaxContent');
                var attachment_content_el = attachment_infos_el.find('.wpsolr_attachment_content');
                var filecontent_el = attachment_infos_el.find('.wpsolr_attachment_content_textarea');
                var textarea_el = attachment_infos_el.find('.wpsolr_attachment_content_textarea');
                var file_basename_el = attachment_infos_el.find('.wpsolr_attachment_content_file_basename');
                var error_el = attachment_infos_el.find('.wpsolr_attachment_err');
                var popup_id = current_button_el.parent().find('input[name="wpsolr_popup_id"]').val();

                var attachment_file_selected_button_el = jQuery('.' + popup_id + '.wpsolr_attachment_file_selected_filename_button');

                var post_id_el = current_button_el.parent().find('.wpsolr_attachment_file_selected_id');
                if (!post_id_el.val()) {
                    alert('Please select a file first.');
                    return;
                }

                // Set button label
                current_button_el.val('Saving ' + file_basename_el.val() + ' content ...');

                return jQuery.ajax({
                    url: wpsolrc_enhanced_select_params.ajax_url,
                    dataType: 'json',
                    quietMillis: 250,
                    data: {
                        action: '<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax::AJAX_MEDIA_CONTENT_UPLOAD ); ?>',
                        security: wpsolrc_enhanced_select_params.security,
                        post_id: post_id_el.val(),
                        post_title: file_basename_el.val(),
                        post_content: filecontent_el.val()
                    },
                    success: function (data) {

                        var file_name = data[0]['id'];
                        var file_content = data[0]['label'];
                        var post_id = data[0]['post_id'];

                        if ('error' === file_name) {

                            error_el.html(file_content);

                        } else {

                            post_id_el.val(post_id);
                            file_basename_el.val(file_name);
                            filecontent_el.val(file_content);

                            attachment_file_selected_button_el.val('Edit ' + file_name).show();
                            attachment_infos_el.find('.wpsolr_attachment_file_selected_filename_button.wpsolr_err').hide();
                            //attachment_content_el.show();
                        }

                        // Set button label back
                        current_button_el.val(current_button_label);
                    }

                    ,
                    error: function (data) {
                    }
                });

            });

            // Fill file content textarea with file content.
            jQuery(document).on('click', 'input.wpsolr_attachment_file_selected_filename_button,.wpsolr_attachment_file_selected_filename_reload_button', function (event) {


                var current_button_el = jQuery(this);
                var current_button_label = current_button_el.val();

                var attachment_infos_el = current_button_el.closest('.wpsolr_attachment, #TB_window').find('.wpsolr_attachment_infos, #TB_ajaxContent');
                var attachment_content_el = attachment_infos_el.find('.wpsolr_attachment_content');
                var textarea_el = attachment_infos_el.find('.wpsolr_attachment_content_textarea');
                var file_basename_el = attachment_infos_el.find('.wpsolr_attachment_content_file_basename');
                var error_el = attachment_infos_el.find('.wpsolr_attachment_err');
                var popup_id = attachment_content_el.attr('id');

                var post_id = current_button_el.parent().find('.wpsolr_attachment_file_selected_id').val();
                if (isNaN(parseInt(post_id))) {
                    // Get full path of file instead of media post id
                    post_id = attachment_infos_el.find('.wpsolr_attachment_file_selected_id_path').val();
                }
                if (!post_id) {
                    alert('Please select a file first.');
                    return;
                }


                // Set button label
                current_button_el.val('Loading file content ...');

                return jQuery.ajax({
                    url: wpsolrc_enhanced_select_params.ajax_url,
                    dataType: 'json',
                    quietMillis: 250,
                    data: {
                        action: '<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax::AJAX_MEDIA_POST_ID_CONTENT_GET ); ?>',
                        security:
                        wpsolrc_enhanced_select_params.security,
                        post_id: post_id
                    },
                    success: function (data) {

                        var file_name = data[0]['id'];
                        var file_content = data[0]['label'];

                        if ('error' === file_name) {

                            error_el.html(file_content);

                        } else {

                            file_basename_el.val(file_name);
                            textarea_el.val(file_content);

                            // Open popup
                            tb_show("File content", "#TB_inline?width=800&amp;height=700&amp;inlineId=" + popup_id);
                        }

                        // Set button label back
                        current_button_el.val(current_button_label);

                    }

                    ,
                    error: function (data) {
                    }
                });

            });

            var i = 0;

            var wpsolr_file_frame;
            jQuery(document).on('click', '.wpsolr_attachment_button', function (event) {

                event.preventDefault();

                var me = jQuery(this);
                var attachment_file_selected_id_el = me.parent().find('.wpsolr_attachment_file_selected_id');

                // Create the media frame.
                wpsolr_file_frame = wp.media.frames.file_frame = wp.media({
                    title: 'Select a file',
                    button: {
                        text: 'Use this file',
                    },
                    library: {
                        type: 'text' // limits the frame to show only txt files
                    },
                    multiple: false	// Set to true to allow multiple files to be selected
                });

                // When an image is selected, run a callback.
                wpsolr_file_frame.on('select', function () {
                    // We set multiple to false so only get one image from the uploader
                    attachment = wpsolr_file_frame.state().get('selection').first().toJSON();

                    //console.log(attachment);

                    // Do something with attachment.id and/or attachment.url here
                    attachment_file_selected_id_el.val(attachment.id);
                    attachment_file_selected_id_el.closest('.wpsolr_attachment').find('.wpsolr_attachment_file_selected_filename_button.button').val('Edit ' + attachment.filename).show();
                    attachment_file_selected_id_el.closest('.wpsolr_attachment').find('.wpsolr_attachment_file_selected_filename_button.wpsolr_err').hide();
                });

                // Open the frame
                wpsolr_file_frame.open();
            });

            // Button to remove builder from configuration
            jQuery(document).on('click', '.wpsolr_remove_builder', function (e) {
                // Fade then remove
                jQuery(this).closest('li.ui-sortable-handle').fadeOut(300, function () {
                    jQuery(this).remove();
                });
            });

            // Button to clone the builder
            var wpsolr_clone_counter = 0;
            jQuery(document).on('click', '.wpsolr_clone_builder', function (e) {
                var target_el = jQuery(this).closest('li.ui-sortable-handle');
                var clone_el = target_el.clone(true);

                clone_el.css('border-color', 'blue');

                /**
                 * Change names of cloned stored elements
                 **/
                wpsolr_clone_counter++;
                var clone_html = clone_el.html();

                // Add a counter at the end of builder uuid.
                // [configuration_builder][xyz] => [configuration_builder][xyz1], then [configuration_builder][xyz2], ...
                var new_clone_html = clone_html.replace(/\[configuration_builder]\[(\w+)]/g, '[configuration_builder][$1' + wpsolr_clone_counter + ']');

                // New popup id
                var current_popup_id = clone_el.find('.wpsolr_attachment_content').attr('id');
                if (current_popup_id) {
                    var new_popup_id = current_popup_id + '_' + wpsolr_clone_counter;
                    new_clone_html = new_clone_html.replace(new RegExp(current_popup_id, 'g'), new_popup_id);
                }

                // Set element new name
                clone_el.html(new_clone_html);


                // Destroy select2 js elements inserted by Ajax, or else it remains inactive
                clone_el.find(':input.wpsolrc-multiselect-search').removeClass('enhanced');
                clone_el.find('.select2-container').first().remove();

                clone_el.fadeIn(500).insertAfter(target_el);

                // Reinit select2 js elements inserted by Ajax
                jQuery(document.body).trigger('wpsolrc-enhanced-select-init');

            });

            // Button click to reload the whole configuration
            jQuery(document).on('click', '#wpsolr_configuration_load_builder_button', function (event) {

                var configuration_id = get_configuration_id();
                var builder_form = jQuery('.wpsolr_configuration_form').first();
                show_configuration_builder_form(builder_form, configuration_id, '');
            });

            // Selection of a configuration/tokenizer/filter in the list will reload the configuration/tokenizer/filter
            jQuery(document).on('wpsolr_select2_after_change', function (event, object, value) {

                var configuration_id = get_configuration_id();
                if (configuration_id === value) {
                    value = '';
                }

                var builder_form = !value
                    ? jQuery('.wpsolr_configuration_form').first()
                    : jQuery(object).closest('.wpsolr_configuration_builder_form').first();

                show_configuration_builder_form(builder_form, configuration_id, value);
            });

            // Click on button "Add a char filter"
            jQuery(document).on('click', '.btn_add_char_filter', function (event) {

                var configuration_id = get_configuration_id();
                builder_form = jQuery('.wpsolr_configuration_form .wpsolr_section_char_filters').first();
                builder_form.append('<li class="wpsolr_builder_id_to_add">Please wait ....</li>');
                builder_form = builder_form.find('.wpsolr_builder_id_to_add');
                show_configuration_builder_form(builder_form, configuration_id, 'solr.HTMLStripCharFilterFactory');
            });

            // Click on button "Add a tokenizer filter"
            jQuery(document).on('click', '.btn_add_tokenizer_filter', function (event) {

                var configuration_id = get_configuration_id();
                builder_form = jQuery('.wpsolr_configuration_form .wpsolr_section_tokenizer_filters').first();
                builder_form.append('<li class="wpsolr_builder_id_to_add">Please wait ....</li>');
                builder_form = builder_form.find('.wpsolr_builder_id_to_add');
                show_configuration_builder_form(builder_form, configuration_id, 'solr.LowerCaseFilterFactory');
            });

            function get_configuration_id() {
                return jQuery('[name="<?php WPSOLR_Escape::echo_esc_attr( $option_name );?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_INDEXES );?>][<?php WPSOLR_Escape::echo_esc_attr( $index_indice );?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_ID );?>]"').val();
            }

            // Reload the whole configuration with Ajax
            function show_configuration_builder_form(builder_form, configuration_id, builder_id) {

                jQuery('.configuration_id_err').html('');

                // Show the results in the next following form
                var use_builder_radio = jQuery('[name="<?php WPSOLR_Escape::echo_esc_attr( $option_name );?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_INDEXES );?>][<?php WPSOLR_Escape::echo_esc_attr( $index_indice );?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION );?>]"][value="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION_BUILDER ); ?>"]');

                if (!use_builder_radio.is(':checked')) {
                    return;
                }

                builder_form.fadeTo(1000, .4);

                return jQuery.ajax({
                    url: wpsolrc_enhanced_select_params.ajax_url,
                    dataType: 'json',
                    quietMillis: 250,
                    data: {
                        action: '<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax::AJAX_INDEX_CONFIGURATION_DEFAULT_BUILDER ); ?>',
                        security:
                        wpsolrc_enhanced_select_params.security,
				<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax_Index_Configuration_Default_builder::PARAMETER_OPTION_NAME ); ?>:
                '<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>',
				<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax_Index_Configuration_Default_builder::PARAMETER_INDEX_UUID ); ?>:
                '<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>',
				<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax_Index_Configuration_Default_builder::PARAMETER_CONFIGURATION_ID ); ?>:
                configuration_id,
				<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax_Index_Configuration_Default_builder::PARAMETER_BUILDER_ID ); ?>:
                builder_id
            },
                success: function (data) {

                    jQuery('.configuration_id_err').html('');

                    var form_content = data[0]['label'];
                    if (builder_id) {
                        // Replace form content and outer
                        builder_form.replaceWith(form_content);

                    } else {
                        // Replace form content but not outer
                        builder_form.html(form_content);
                    }

                    // Reinit select2 js elements inserted by Ajax
                    jQuery(document.body).trigger('wpsolrc-enhanced-select-init');

                    builder_form.fadeTo(1000, 1);


                    // Re-init sortables
                    jQuery('.wpsolr_section_char_filters,.wpsolr_section_tokenizer_filters').sortable({
                        //cursor: 'move'
                    });

                }

            ,
                error: function (data) {

                    jQuery('.configuration_id_err').html(JSON.stringify(data));
                    builder_form.fadeTo(1000, 1)
                }
            })
                ;
            }

        })
        ;
    </script>
	<?php

	WPSOLR_Admin_UI_Select2::dropdown_select2( [
		WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_IS_MULTISELECT       => false,
		WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_CLASS                => WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_ID,
		WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_SELECTED_IDS         => [
			WPSOLR_Admin_UI_Ajax_Search::FORM_FIELD_FILTER_QUERY_CONTENT =>
				[
					$index_configuration_id => WPSOLR_Configurations_Builder_Factory::get_configuration_label_by_id( $index_configuration_id )
				]
		],
		WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_AJAX_EVENT           => WPSOLR_Admin_UI_Ajax::AJAX_INDEX_CONFIGURATIONS_SEARCH,
		WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_PLACEHOLDER_TEXT     => 'Choose a configuration template&hellip;',
		WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_OPTION_ABSOLUTE_NAME => sprintf( '%s[%s][%s][%s]', $option_name, WPSOLR_Option::OPTION_INDEXES_INDEXES, $index_indice, WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_ID ),
		WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_OPTION_RELATIVE_NAME => WPSOLR_Admin_UI_Ajax_Search::FORM_FIELD_FILTER_QUERY_CONTENT,
		WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS                   => [ WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_EXTRAS => [ 'index_uuid' => $index_indice ] ],
		WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_SELECTORS         => [],
	] );


} else { ?>

    <input type='hidden'
           name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_INDEXES ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_ID ); ?>]"
           value="<?php WPSOLR_Escape::echo_esc_attr( $index_configuration_id ); ?>">

<?php } ?>

<?php if ( $subtab === $index_indice ) { ?>
    <div class="wpsolr_err configuration_id_err"></div>

    <div class="wdm_row" style="margin-top: 10px;">
        <div>
            <input class="wpsolr_collapser"
                   type='radio'
                   name='<?php WPSOLR_Escape::echo_escaped( sprintf( '%s[%s][%s][%s]', WPSOLR_Escape::esc_attr( $option_name ), WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_INDEXES ), WPSOLR_Escape::esc_attr( $index_indice ), WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION ) ) ); ?>'
                   value=''
				<?php checked( empty( $use_configuration ) ); ?>>
            Use
            the default selected configuration

            <div class="wpsolr_collapsed">
                <br>
                Use the default selected configuration
            </div>
        </div>

        <br>
        <div>
            <input class="wpsolr_collapser"
                   type='radio'
                   name='<?php WPSOLR_Escape::echo_escaped( sprintf( '%s[%s][%s][%s]', WPSOLR_Escape::esc_attr( $option_name ), WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_INDEXES ), WPSOLR_Escape::esc_attr( $index_indice ), WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION ) ) ); ?>'
                   value='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION_BUILDER ); ?>'
				<?php checked( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION_BUILDER, $use_configuration ); ?>>
            Use the
            builder to customize the configuration

            <div class="wpsolr_collapsed wpsolr-remove-if-hidden">
                <br><input id="wpsolr_configuration_load_builder_button" type="button"
                           value="Show the configuration's default builder settings"/>

                <div class="wpsolr_configuration_form">
					<?php
					if ( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION_BUILDER === $use_configuration ) {
						require "admin_configuration_builder.inc.php";
					}
					?>
                </div>
            </div>
        </div>


        <br>
        <div>
            <input class="wpsolr_collapser"
                   type='radio'
                   name='<?php WPSOLR_Escape::echo_escaped( sprintf( '%s[%s][%s][%s]', WPSOLR_Escape::esc_attr( $option_name ), WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_INDEXES ), WPSOLR_Escape::esc_attr( $index_indice ), WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION ) ) ); ?>'
                   value='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION_CODE ); ?>'
				<?php checked( WPSOLR_Option::OPTION_INDEXES_USE_CONFIGURATION_CODE, $use_configuration ); ?>>
            Use code
            to customize the configuration

            <div class="wpsolr_collapsed wpsolr-remove-if-hidden">
                <br>
                <textarea rows="50"
                          name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_INDEXES ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_CODE ); ?>]"
                ><?php WPSOLR_Escape::echo_esc_textarea( $index_configuration_code ); ?></textarea>

            </div>
        </div>

    </div>
<?php } ?>



