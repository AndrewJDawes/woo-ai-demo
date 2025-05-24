// Timout handler on the indexing timeoutHandlerIsCleared;
var wpsolr_indexing_handler;
var wpsolr_indexing_is_stopping = false;

jQuery(document).ready(function ($) {

    /*
     // Instance the tour
     var tour = new Tour({
     name: 'tour5',
     steps: [
     {
     element: "#1wpbody",
     title: "Quick Tour of WPSOLR",
     content: "WPSOLR is so powerfull that it can be overwhelming, compared to classic search solutions. <br/><br/>This tour will show you it's most important concepts and features.",
     orphan: true
     },
     {
     element: "#wpsolr_tour_button_start",
     title: "Stop and resume the Tour",
     content: "Stop the Tour, and resume anytime with this button.",
     backdrop: false,
     backdropPadding: 0,
     },
     {
     element: ".wpsolr-tour-navigation-tabs",
     title: "Navigation tabs",
     content: "Four tabs, it's just what you need to control wpsolr.",
     backdrop: true,
     backdropPadding: 10,
     },
     {
     element: ".wpsolr-tour-navigation-tabs",
     title: "Navigation tabs",
     content: "Four tabs, it's just what you need to control wpsolr.",
     backdrop: true,
     backdropPadding: 10,
     }
     ]
     });

     // Initialize the tour
     tour.init();

     // Start the tour
     tour.start();

     // Restart the tour
     $('#wpsolr_tour_button_start').click(function (e) {
     tour.start(true);
     });
     */

    /**
     * Unprotected code below
     */
    // Toggle a password text from clear to hidden
    $(document).on("click", ".wpsolr_password_toggle", function (e) {

        var password_box = $(this).parent().find(".wpsolr_password");

        if ($(this).prop('checked')) {
            password_box.prop("type", "text");
        } else {
            password_box.prop("type", "password");
        }
    });

    $(document).on("click", "input[name='ajax_verify_licence'], input[name='ajax_deactivate_licence'], input[name='ajax_activate_licence']", function (e) {
        ajax_manage_license($(this));
    });

    /**
     * Control protected license
     */
    if (deactivate_after_check_license()) {
        // License invalid. Stop now.
        return;
    }

    function deactivate_after_check_license() {
        if (!check_license()) {
            $('.wdm_row .submit .wdm-save').css('display', 'none');
            return true;
        } else {
            $('.wdm_row .submit .wdm-save').css('display', 'block');
            return false;
        }
    }

    function display_license_status($message, $is_error) {
        $('.wpsolr-page-title').prepend('<span ' + ((null === $is_error) ? 'style="color:orange;font-size:16px;font-weight:bolder;"' : $is_error ? 'class="wpsolr_err" style="font-size:16px;font-weight:bolder;"' : 'class="wpsolr_message" style="color:green"') + '>' + $message + '</span>');
    }

    function clear_license_status() {
        $('.wpsolr-page-title span.wpsolr_message, .wpsolr-page-title span.wpsolr_err').remove();
    }

    function get_admin_url() {
        return $('#adm_path').val();
    }

    // Refresh fields on engine selection
    $(document).on('change', '#index_engine', function (e) {
        clear_license_status();
        deactivate_after_check_license();
    });

    function check_license() {




        return true;
    }

    function ajax_manage_license(jquery_this) {

        // Remember this for ajax
        var current = this;

        // Show progress
        var button_clicked = jquery_this;
        var button_form = button_clicked.parents('.wpsolr_form_license');
        var buttonText = button_clicked.val(); // Remmember button text
        button_clicked.val('Operation in progress ... Please wait.');
        button_clicked.prop('disabled', true);
        var error_message_element = $('.wpsolr_form_license').find(".error-message");
        error_message_element.css("display", "none");
        error_message_element.html("");


        // Extract form data
        var subscription_number = button_form.find("input[name=license_subscription_number]").val()

        if (subscription_number.length === 0) {
            // End progress
            button_clicked.val(buttonText);
            button_clicked.prop('disabled', false);

            error_message_element.css("display", "inline-block");
            error_message_element.html('Please enter a license#.');
            return;
        }

        var license_package = button_form.find("input[name=license_package]").val();
        var license_matching_reference = button_form.find("input[name=matching_license_reference]").val();
        var action = button_clicked.attr('name');
        var data = {
            action: action,
            data: {
                license_package: license_package,
                matching_license_reference: license_matching_reference,
                license_subscription_number: subscription_number,
                security: $(wpsolr_localize_script_dashboard.wpsolr_dashboard_nonce_selector).val()
            }
        };

        //alert(button_clicked.attr('id'));

        // Pass parameters to Ajax
        jQuery.ajax({
            url: get_admin_url() + 'admin-ajax.php',
            type: "post",
            data: data,
            success: function (data1) {

                data1 = JSON.parse(data1);

                if (("OK" !== data1.status.state) && ('ajax_deactivate_licence' !== action)) {


                    if ("ERR_LABEL_ALREADY_EXIST" === data1.status.code) {

                        var button_deactivate = button_form.find("input[name=ajax_deactivate_licence]");
                        button_deactivate.show();
                    }

                    // End progress
                    button_clicked.val(buttonText);
                    button_clicked.prop('disabled', false);

                    error_message_element.css("display", "inline-block");
                    error_message_element.html(data1.status.message);

                } else {

                    // Continue the submit if not error, or if error is during deactivation
                    button_form.submit();
                }

            },
            error: function () {

                // End progress
                $(current).val(buttonText);
                $(current).prop('disabled', false);

                /*
                 // Post Ajax UI display
                 $('.loading_res').css('display', 'none');
                 $('.results-by-facets').css('display', 'block');
                 */

            },
            always: function () {
                // Not called.
            }
        });


        return false;
    }

    /**
     * Other stuff
     */

    $('.ui-sortable').sortable({
        //cursor: 'move'
    });

    $(document).on('click', '.wpsolr_checker', function () {
        $(this).closest('.wdm_row').find(".wpsolr_checked").prop('checked', true).change();
    });

    $(document).on('click', '.wpsolr_unchecker', function () {
        $(this).closest('.wdm_row').find(".wpsolr_checked").prop('checked', false).change();
    });

    function wpsolr_change_button_label(button) {
        button.attr('value', 'Saving ...');
    }

    function wpsolr_is_empty(element) {
        return ((element.value === "") || (('checkbox' === element.type) && !element.checked) || (element.value === $(element).data('wpsolr-empty-value')));
    }

    function wpsolr_remove_empty_values() {
        $(".wpsolr-remove-if-empty").filter(function () {
            return wpsolr_is_empty(this);
        }).remove();

    }

    function wpsolr_remove_hidden_values() {
        $(".wpsolr-remove-if-hidden").filter(":hidden").remove();
    }

    // Force same value to all select with same name
    $(document).on('change', 'select.wpsolr_same_name_same_value', function (e) {
        // Force
        $('select.wpsolr_same_name_same_value[name="' + $(this).attr('name') + '"]').val($(this).val());
    });

    // Collapse/uncollapse in checkbox
    $('.wpsolr_collapser:checkbox').each(function (e) {
        if (undefined !== $(this).filter(':checked').val()) {
            $(this).siblings('.wpsolr_collapsed').removeClass('wpsolr-has-collapsed').show();
        }
    });

    $(document).on('change', '.wpsolr_collapser:checkbox', function (e) {
        if ($(this).filter(':checked').val()) {
            $(this).siblings('.wpsolr_collapsed').removeClass('wpsolr-has-collapsed').show();
        } else {
            $(this).siblings('.wpsolr_collapsed').addClass('wpsolr-has-collapsed').hide();
        }
    });

    $(document).on('change', '.col_left .wpsolr_column_collapser:checkbox', function (e) {
        if ($(this).filter(':checked').val()) {
            $(this).closest('.wdm_row').find('.col_right .wpsolr_column_collapsed').removeClass('wpsolr-has-collapsed').show();
        } else {
            $(this).closest('.wdm_row').find('.col_right .wpsolr_column_collapsed').addClass('wpsolr-has-collapsed').hide();
        }
    });

    // Collapse/uncollapse in all radios/checkboxes
    $('.wpsolr_collapser:radio,.wpsolr_collapser:checkbox').each(function (e) {
        if (undefined !== $(this).filter(':checked').val()) {
            $(this).siblings('.wpsolr_collapsed').removeClass('wpsolr-has-collapsed').show();
        } else {
            $(this).siblings('.wpsolr_collapsed').addClass('wpsolr-has-collapsed').hide();
        }
    });

    $(document).on('click', '.wpsolr_collapser:radio', function (e) {
        $('input[name="' + $(this).attr('name') + '"]').siblings('.wpsolr_collapsed').addClass('wpsolr-has-collapsed').hide();
        $(this).siblings('.wpsolr_collapsed').removeClass('wpsolr-has-collapsed').show();
    });

    $(document).on('click', 'div.wpsolr_collapser,.wpsolr_collapser:button, a.wpsolr_collapser', function (e) {
        $(this).next('.wpsolr_collapsed').toggleClass('wpsolr-has-collapsed').toggle();
    });

    // Simulate a combobox with checkboxes, when it's class is like 'wpsolr_checkbox_mono_someidhere'
    $('[class^="wpsolr_checkbox_mono"]').change(function () {

        if ($(this).prop("checked")) {
            var classes = $(this).prop("class");
            var matches = classes.match(/wpsolr_checkbox_(\w+)/g);

            // Deactivate all checks with same class
            $('.' + matches[0]).not(this).filter(":checked").prop("checked", false).css('background-color', 'yellow');
        }

    });

    $(".radio_type").change(function () {

        if ($("#self_host").attr("checked")) {
            $('#div_self_hosted').slideDown("slow");
            $('#hosted_on_other').css('display', 'none');
        } else if ($("#other_host").attr("checked")) {
            $('#hosted_on_other').slideDown("slow");
            $('#div_self_hosted').css('display', 'none');


        }
    });

    // Stop the current Solr index process
    $('#solr_stop_delete_data').click(function () {

        $('#solr_stop_delete_data').attr('value', 'Stopping ... please wait');
        $('#solr_actions').submit();
    });

    // Clean the Solr index
    $('#solr_delete_index').click(function (e) {

        me = $(this);

        if (me.data('wpsolr-confirmation') && !confirm(me.data('wpsolr-confirmation'))) {
            return false;
        }

        // Block submit while Ajax is running
        e.preventDefault();

        err = 1;
        me.siblings('.img-load').first().css('display', 'inline-block');
        $('#solr_stop_delete_data').css('visibility', 'visible');
        $('#solr_delete_index').hide();
        $('#solr_start_index_data').attr("disabled", true);
        var all_post_types = $('.wpsolr_index_post_types');
        var post_types_selected = $('.wpsolr_index_post_types:checked').map(function () {
            return $(this).data('wpsolr-index-post-type');
        }).get();

        if (0 === post_types_selected.length) {
            $('.wpsolr_post_types_err').text("Please select the post types you want to remove from the index");
            err = 0;
        } else {
            $('.wpsolr_post_types_err').text();
        }

        if (all_post_types.length === post_types_selected.length) {
            // Way to know that all post types where selected
            post_types_selected = [];
        }

        if (0 === err) {

            return false;
        }

        var path = get_admin_url();
        var solr_index_indice = $('#solr_index_indice').val();

        var request = jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            dataType: "json",
            timeout: 1000 * 60 * 5, // 5 minutes
            data: {
                action: 'return_solr_delete_index',
                solr_index_indice: solr_index_indice,
                post_types: post_types_selected,
                security: $(wpsolr_localize_script_dashboard.wpsolr_dashboard_nonce_selector).val()
            }
        });

        request.done(function (data) {

            // Errors
            if (data && (data.status !== 0 || data.message)) {
                me.siblings('.img-load').first().css('display', 'none');

                $('.status_index_message').html('<br><br>An error occured: <br><br>' + data.message);

                // Block submit
                return false;
            }

            $('#solr_actions').submit();
        });

        request.fail(function (req, status, error) {

            if (error) {
                me.siblings('.img-load').first().css('display', 'none');

                $('.status_index_message').html('<br><br>An error or timeout occured. <br><br>' + '<b>Error code:</b> ' + status + '<br><br>' + '<b>Error message:</b> ' + escapeHtml(error) + '<br><br>' + escapeHtml(req.responseText) + '<br><br>');

            }

        });

    });

    // Stop the current Solr index process
    $('#solr_stop_index_data').click(function () {

        $('#solr_stop_index_data').attr('value', 'Stopping ... please wait');

        wpsolr_indexing_is_stopping = true;
        clearTimeout(wpsolr_indexing_handler);

    });

    // Fill the Solr index
    $('#solr_start_index_data').click(function () {

        //$('.solr_error').text('Contacting your search server ... Please wait ...');

        $(this).siblings('.img-load').first().css('display', 'inline-block');

        $('#solr_stop_index_data').css('visibility', 'visible');
        $('#solr_start_index_data').hide();
        $('#solr_delete_index').hide();

        var solr_index_indice = $('#solr_index_indice').val();
        var batch_size = $('#batch_size').val();
        var is_debug_indexing = $('#is_debug_indexing').prop('checked');
        var is_reindexing_all_posts = $('#is_reindexing_all_posts').prop('checked');
        var post_types = $('.wpsolr_index_post_types:checked').map(function () {
            return $(this).data('wpsolr-index-post-type');
        }).get();

        var err = 1;

        if (isNaN(batch_size) || (batch_size < 1)) {
            $('.res_err').text("Please enter a number > 0");
            err = 0;
        } else {
            $('.res_err').text();
        }

        if (0 === post_types.length) {
            $('.wpsolr_post_types_err').text("Please select the post types you want to add/update to the index");
            err = 0;
        } else {
            $('.wpsolr_post_types_err').text();
        }

        if (0 === err) {

            return false;

        } else {

            wpsolr_indexing_is_stopping = false;
            call_solr_index_data(solr_index_indice, batch_size, 0, is_debug_indexing,
                is_reindexing_all_posts, post_types
            );

            // Block submit
            return false;
        }

    });


    // Promise to the Ajax call
    function call_solr_index_data(solr_index_indice, batch_size, nb_results, is_debug_indexing, is_reindexing_all_posts, post_types
    ) {

        var nb_results_message = nb_results + ' documents processed so far'

        $('.status_index_message').html(nb_results_message);

        var path = get_admin_url();

        var request = jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            data: {
                action: 'return_solr_index_data',
                solr_index_indice: solr_index_indice,
                batch_size: batch_size,
                nb_results: nb_results,
                is_debug_indexing: is_debug_indexing,
                is_reindexing_all_posts: is_reindexing_all_posts,
                post_types: post_types,
                is_stopping: wpsolr_indexing_is_stopping,
                security: $(wpsolr_localize_script_dashboard.wpsolr_dashboard_nonce_selector).val()
            },
            dataType: "json",
            timeout: 1000 * 3600 * 24
        });

        request.done(function (data) {


            if (data.debug_text) {
                // Debug

                $('.status_debug_message').append('<br><br>' + data.debug_text);

                if (data.indexing_complete) {
                    // Freeze the screen to have time to read debug infos
                    return false;
                }

            }

            if (data.message) {
                // Errors

                $('.status_index_message').html('<br><br>An error occured: <br><br>' + data.message);
            } else if (!data.indexing_complete) {

                // If indexing completed, stop. Else, call once more.
                // Do not re-index all, again !
                is_reindexing_all_posts = false;
                wpsolr_indexing_handler = setTimeout(call_solr_index_data(solr_index_indice, batch_size, data.nb_results, is_debug_indexing, is_reindexing_all_posts, post_types
                    )
                    , 100);

            } else {

                $('#solr_stop_index_data').click();
            }
        });


        request.fail(function (req, status, error) {

            if (error) {

                var message = '';
                if (batch_size > 100) {
                    message = '<br> You could try to decrease your batch size to prevent errors or timeouts.';
                }
                $('.status_index_message').html('<br><br>An error or timeout occured. <br><br>' + '<b>Error code:</b> ' + status + '<br><br>' + '<b>Error message:</b> ' + escapeHtml(error) + '<br><br>' + escapeHtml(req.responseText) + '<br><br>' + message);
            }

        });

    }

    /*
     Escape html for javascript error messages to be displayed correctly.
     */
    var entityMap = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': '&quot;',
        "'": '&#39;',
        "/": '&#x2F;'
    };

    function escapeHtml(string) {
        return String(string).replace(/[&<>"'\/]/g, function (s) {
            return entityMap[s];
        });
    }

    $('.wpsolr_custom_field_selected').click(function () {
        // Activate current custom field relation select2
        $(this).find('.wpsolrc-multiselect-search-inactive').addClass('wpsolrc-multiselect-search').trigger('wpsolrc-enhanced-select-init');
    });

    $('#save_selected_index_options_form').click(function () {
        var ps_types = '';
        var tax = '';
        var fields = '';
        var attachment_types = '';

        $(".wpsolr-remove-if-empty").filter(function (index) {
            return wpsolr_is_empty(this);
        }).parent('.wpsolr_custom_field_selected').find('.wpsolr_same_name_same_value, .wpsolrc-multiselect-search-values').remove();

        $("input:checkbox[name=post_tys]:checked").each(function () {
            ps_types += $(this).val() + ',';
        });
        var pt_tp = ps_types.substring(0, ps_types.length - 1);
        $('#p_types').val(pt_tp);

        $("input:checkbox[name=attachment_types]:checked").each(function () {
            attachment_types += $(this).val() + ',';
        });
        attachment_types = attachment_types.substring(0, attachment_types.length - 1);
        $('#attachment_types').val(attachment_types);

        $("input:checkbox[name=taxon]:checked").each(function () {
            tax += $(this).val() + ',';
        });
        var tx = tax.substring(0, tax.length - 1);
        $('#tax_types').val(tx);

    });

    $('#save_facets_options_form, #save_fields_options_form, #save_scoring').click(function () {

        var result = '';
        $(".facet_selected").each(function () {
            result += $(this).attr('id') + ",";
        });
        result = result.substring(0, result.length - 1);

        $("#select_fac").val(result);

    });

    $('#save_sort_options_form').click(function () {

        var result = '';
        $(".sort_selected").each(function () {
            result += $(this).attr('id') + ",";
        });
        result = result.substring(0, result.length - 1);

        $("#select_sort").val(result);

    });

    $('#save_selected_res_options_form').click(function () {

        wpsolr_remove_hidden_values();

        var num_of_res = $('#number_of_res').val();
        var num_of_fac = $('#number_of_fac').val();
        var highlighting_fragsize = $('#highlighting_fragsize').val();
        var err = 1;
        if ($('#number_of_res:visible').length > 0) {
            if (isNaN(num_of_res)) {
                $('.res_err').text("Please enter valid number of results");
                err = 0;
            } else if (num_of_res < 1 || num_of_res > 100) {
                $('.res_err').text("Number of results must be between 1 and 100");
                err = 0;
            } else {
                $('.res_err').text();
            }
        }

        if ($('#number_of_fac:visible').length > 0) {
            if (isNaN(num_of_fac)) {
                $('.fac_err').text("Please enter valid number of facets");
                err = 0;
            } else if (num_of_fac < 0) {
                $('.fac_err').text("Number of facets must be >= 0");
                err = 0;
            } else {
                $('.fac_err').text();

            }
        }

        if ($('#highlighting_fragsize:visible').length > 0) {
            if (isNaN(highlighting_fragsize)) {
                $('.highlighting_fragsize_err').text("Please enter a valid Highlighting fragment size");
                err = 0;
            } else if (highlighting_fragsize < 1) {
                $('.highlighting_fragsize_err').text("Highlighting fragment size must be > 0");
                err = 0;
            } else {
                $('.highlighting_fragsize_err').text();

            }
        }

        if (err == 0)
            return false;
    });

    $('#save_selected_extension_groups_form').click(function () {
        var err = 1;
        if (err == 0)
            return false;
    });

    $('#save_fields_options_form').click(function () {

        var err = 1;

        // Change button label
        $(this).val('Saving ...');

        // Clear errors
        $('.res_err').empty();

        // Verify each boost factor is a numbers > 0
        $(".wpsolr_field_boost_factor_class").each(function () {

            if ($(this).data('wpsolr_is_error')) {
                $(this).css('border-color', 'green');
                $(this).data('wpsolr_is_error', false);
            }

            var boost_factor = $(this).val();
            if (isNaN(boost_factor) || (boost_factor <= 0)) {

                $(this).data('wpsolr_is_error', true);
                $(this).css('border-color', 'red');
                $(this).after("<span class='res_err'>Please enter a number > 0. Examples: '0.5', '2', '3.1'</span>");
                err = 0;
            }

            if ('none' == $(this).css('display')) {
                $(this).remove();
            }

        });

        // Verify each boost term factor
        $(".wpsolr_field_boost_term_factor_class").each(function () {

            if ('none' == $(this).css('display')) {
                $(this).remove();
            }

        });

        if (err == 0) {
            return false;
        }

    });

    /*
     Create a temporary managed index
     */
    $("input[name='submit_button_form_temporary_index']").click(function () {

        // Display the loading icon
        $(this).hide();
        $('.solr_error').hide();
        $('.wdm_note').hide();
        $(this).after("<h2>Please wait a few seconds. We are configuring your test index ...</h2>");
        $(this).after("<div class='loading'>");

        // Let the submit execute by doing nothing
        return true;
    });

    /*
     Remove an index configuration
     */
    $('.delete_index_configuration').click(function () {

        $(this).hide();
        $('.solr_error').hide();
        $('.wdm_note').hide();

        if ($('.delete_index').is(':checked')) {
            // Delete the index before deleting the configuration

            $(this).after("<h2>Please wait a few seconds. We are deleting your index ...</h2>");

            var me = $(this);

            var path = get_admin_url();
            var index_engine = $('#index_engine').val();
            var index_uuid = $('#index_uuid').val();
            var index_label = $('#index_label').val();
            var host = $('#index_host').val();
            var port = $('#index_port').val();
            var spath = $('#index_path').val();
            var pwd = $('#index_secret').val();
            var user = $('#index_key').val();
            var protocol = $('#index_protocol').val();
            var endpoint = $('#index_endpoint').val();
            var endpoint1 = $('#index_endpoint_1').val();

            var index_hosting_api_id = $('#index_hosting_api_id').val();
            var index_email = $('#index_email').val();
            var index_api_key = $('#index_api_key').val();
            var index_api_key_1 = $('#index_api_key_1').val();
            var index_region_id = $('#index_region_id').val(); // select2
            var index_aws_region = $('#index_aws_region').val();
            var index_language_code = $('#index_language_code').val();
            var index_analyser_id = $('#index_analyser_id').val();

            var index_weaviate_openai_config_type = $('#index_weaviate_openai_config_type').val();
            var index_weaviate_openai_config_model = $('#index_weaviate_openai_config_model').val();
            var index_weaviate_openai_config_model_version = $('#index_weaviate_openai_config_model_version').val();
            var index_weaviate_openai_config_type_qna = $('#index_weaviate_openai_config_type_qna').val();
            var index_weaviate_openai_config_model_qna = $('#index_weaviate_openai_config_model_qna').val();
            var index_weaviate_openai_config_model_version_qna = $('#index_weaviate_openai_config_model_version_qna').val();
            var index_weaviate_huggingface_config_model = $('#index_weaviate_huggingface_config_model').val();
            var index_weaviate_huggingface_config_model_query = $('#index_weaviate_huggingface_config_model_query').val();
            var index_key_json = $('#index_key_json').val();
            var index_key_json_1 = $('#index_key_json_1').val();
            var index_catalog_branch = $('#index_catalog_branch').val();
            var index_weaviate_cohere_config_model = $('#index_weaviate_cohere_config_model').val();
            var index_weaviate_palm_config_model = $('#index_weaviate_palm_config_model').val();
            var dataset_group_arn = $('#dataset_group_arn').val();
            var dataset_items_arn = $('#dataset_items_arn').val();
            var dataset_users_arn = $('#dataset_users_arn').val();
            var dataset_item_interaction_events_arn = $('#dataset_item_interaction_events_arn').val();
            var dataset_actions_arn = $('#dataset_actions_arn').val();
            var dataset_user_action_interaction_events_arn = $('#dataset_user_action_interaction_events_arn').val();
            var vespa_service_content_id = $('#vespa_service_content_id').val();
            var vespa_service_container_id = $('#vespa_service_container_id').val();
            var index_client_adapter = $('#index_client_adapter').val();
            var text_vectorizer_id = $('#text_vectorizer_id').val();
            var index_cert_1 = $('#index_cert_1').val();

            jQuery.ajax({
                url: path + 'admin-ajax.php',
                type: "post",
                data: {
                    action: 'return_solr_instance',
                    'wpsolr_index_action': 'wpsolr_index_action_delete',
                    'sindex_uuid': index_uuid,
                    'sindex_engine': index_engine,
                    'sproto': protocol,
                    'shost': host,
                    'sport': port,
                    'spath': spath,
                    'slabel': index_label,
                    'spwd': pwd,
                    'skey': user,
                    'sindex_hosting_api_id': index_hosting_api_id,
                    'sindex_email': index_email,
                    'sindex_api_key': index_api_key,
                    'sindex_api_key_1': index_api_key_1,
                    'sindex_region_id': index_region_id,
                    'sindex_aws_region': index_aws_region,
                    'sendpoint': endpoint,
                    'sendpoint1': endpoint1,
                    'sindex_language_code': index_language_code,
                    'sindex_analyser_id': index_analyser_id,
                    'sindex_weaviate_openai_config_type': index_weaviate_openai_config_type,
                    'sindex_weaviate_openai_config_model': index_weaviate_openai_config_model,
                    'sindex_weaviate_openai_config_model_version': index_weaviate_openai_config_model_version,
                    'sindex_weaviate_openai_config_type_qna': index_weaviate_openai_config_type_qna,
                    'sindex_weaviate_openai_config_model_qna': index_weaviate_openai_config_model_qna,
                    'sindex_weaviate_openai_config_model_version_qna': index_weaviate_openai_config_model_version_qna,
                    'sindex_weaviate_huggingface_config_model': index_weaviate_huggingface_config_model,
                    'sindex_weaviate_huggingface_config_model_query': index_weaviate_huggingface_config_model_query,
                    'sindex_key_json': index_key_json,
                    'sindex_key_json_1': index_key_json_1,
                    'sindex_catalog_branch': index_catalog_branch,
                    'sindex_weaviate_cohere_config_model': index_weaviate_cohere_config_model,
                    'sindex_weaviate_palm_config_model': index_weaviate_palm_config_model,
                    'sdataset_group_arn': dataset_group_arn,
                    'sdataset_items_arn': dataset_items_arn,
                    'sdataset_users_arn': dataset_users_arn,
                    'sdataset_item_interaction_events_arn': dataset_item_interaction_events_arn,
                    'sdataset_actions_arn': dataset_actions_arn,
                    'sdataset_user_action_interaction_events_arn': dataset_user_action_interaction_events_arn,
                    'svespa_service_content_id': vespa_service_content_id,
                    'svespa_service_container_id': vespa_service_container_id,
                    'sindex_client_adapter': index_client_adapter,
                    'stext_vectorizer_id': text_vectorizer_id,
                    'sindex_cert_1': index_cert_1,

                    security: $(wpsolr_localize_script_dashboard.wpsolr_dashboard_nonce_selector).val()
                },
                timeout: 1000 * 3600 * 24,
                success: function (data_string) {

                    var data = JSON.parse(data_string);

                    $('.img-load').css('display', 'none');
                    if ('0' === data.status) {
                        // Remove the current configuration to delete from the DOM
                        $('#current_index_configuration_edited_id').remove();

                        // Autosubmit
                        $('#settings_conf_form').submit();
                    } else if ('1' === data.status) {
                        me.prop("disabled", false);
                        $('.solr_error').css('display', 'block').text('Error in detecting engine instance');
                    } else {

                        me.prop("disabled", false);
                        me.prop("style", 'background-color: red !important;border-color:white');
                        me.val('Index deletion failed. Check the error message, then try again ...');
                        $('.solr_error').css('display', 'block').html(data.message);
                    }

                },
                error: function (req, status, error) {

                    me.prop("disabled", false);
                    $('.img-load').css('display', 'none');

                    $('.solr_error').css('display', 'block').text('Timeout: we had no response from your engine server in less than 10 seconds. It\'s probably because port ' + port + ' is blocked. Please try another port, for instance 443, or contact your hosting provider to unblock port ' + port + '.');
                }
            });

        } else {
            $(this).after("<h2>Please wait a few seconds. We are deleting your configuration ...</h2>");

            // Remove the current configuration to delete from the DOM
            $('#current_index_configuration_edited_id').remove();

            // Autosubmit
            $('#settings_conf_form').submit();
        }

    });


    $(document).on("wpsolr_event_save_index", function () {

        var me = $(this);

        var path = get_admin_url();

        var is_index_creation = $('#is_index_creation').val();
        var index_engine = $('#index_engine').val();
        var index_uuid = $('#index_uuid').val();
        var name = $('#index_name').filter(':visible').val();
        var index_label = $('#index_label').val();
        var host = $('#index_host').val();
        var port = $('#index_port').val();
        var spath = $('#index_path').val();
        var pwd = $('#index_secret').val();
        var user = $('#index_key').val();
        var protocol = $('#index_protocol').val();
        var endpoint = $('#index_endpoint').val();
        var endpoint1 = $('#index_endpoint_1').val();

        var index_hosting_api_id = $('#index_hosting_api_id').val();
        var index_email = $('#index_email').val();
        var index_api_key = $('#index_api_key').val();
        var index_api_key_1 = $('#index_api_key_1').val();
        var index_region_id = $('#index_region_id').val(); // select2
        var index_aws_region = $('#index_aws_region').val();
        var index_language_code = $('#index_language_code').val();
        var index_analyser_id = $('#index_analyser_id').val();

        var index_weaviate_openai_config_type = $('#index_weaviate_openai_config_type').val();
        var index_weaviate_openai_config_model = $('#index_weaviate_openai_config_model').val();
        var index_weaviate_openai_config_model_version = $('#index_weaviate_openai_config_model_version').val();
        var index_weaviate_openai_config_type_qna = $('#index_weaviate_openai_config_type_qna').val();
        var index_weaviate_openai_config_model_qna = $('#index_weaviate_openai_config_model_qna').val();
        var index_weaviate_openai_config_model_version_qna = $('#index_weaviate_openai_config_model_version_qna').val();
        var index_weaviate_huggingface_config_model = $('#index_weaviate_huggingface_config_model').val();
        var index_weaviate_huggingface_config_model_query = $('#index_weaviate_huggingface_config_model_query').val();
        var index_key_json = $('#index_key_json').val();
        var index_key_json_1 = $('#index_key_json_1').val();
        var index_catalog_branch = $('#index_catalog_branch').val();
        var index_weaviate_cohere_config_model = $('#index_weaviate_cohere_config_model').val();
        var index_weaviate_palm_config_model = $('#index_weaviate_palm_config_model').val();

        var dataset_group_arn = $('#dataset_group_arn').val();
        var dataset_items_arn = $('#dataset_items_arn').val();
        var dataset_users_arn = $('#dataset_users_arn').val();
        var dataset_item_interaction_events_arn = $('#dataset_item_interaction_events_arn').val();
        var dataset_actions_arn = $('#dataset_actions_arn').val();
        var dataset_user_action_interaction_events_arn = $('#dataset_user_action_interaction_events_arn').val();
        var vespa_service_content_id = $('#vespa_service_content_id').val();
        var vespa_service_container_id = $('#vespa_service_container_id').val();
        var index_client_adapter = $('#index_client_adapter').val();
        var text_vectorizer_id = $('#text_vectorizer_id').val();
        var index_cert_1 = $('#index_cert_1').val();


        var ajax_data = {
            action: 'return_solr_instance',
            'wpsolr_index_action': 'wpsolr_index_action_ping',
            'sis_index_creation': is_index_creation,
            'sindex_engine': index_engine,
            'sindex_uuid': index_uuid,
            'sproto': protocol,
            'shost': host,
            'sport': port,
            'spath': spath,
            'slabel': index_label,
            'spwd': pwd,
            'skey': user,
            'sindex_hosting_api_id': index_hosting_api_id,
            'sindex_email': index_email,
            'sindex_api_key': index_api_key,
            'sindex_api_key_1': index_api_key_1,
            'sindex_region_id': index_region_id,
            'sindex_aws_region': index_aws_region,
            'sendpoint': endpoint,
            'sendpoint1': endpoint1,
            'sindex_language_code': index_language_code,
            'sindex_analyser_id': index_analyser_id,
            'sindex_weaviate_openai_config_type': index_weaviate_openai_config_type,
            'sindex_weaviate_openai_config_model': index_weaviate_openai_config_model,
            'sindex_weaviate_openai_config_model_version': index_weaviate_openai_config_model_version,
            'sindex_weaviate_openai_config_type_qna': index_weaviate_openai_config_type_qna,
            'sindex_weaviate_openai_config_model_qna': index_weaviate_openai_config_model_qna,
            'sindex_weaviate_openai_config_model_version_qna': index_weaviate_openai_config_model_version_qna,
            'sindex_weaviate_huggingface_config_model': index_weaviate_huggingface_config_model,
            'sindex_weaviate_huggingface_config_model_query': index_weaviate_huggingface_config_model_query,
            'sindex_key_json': index_key_json,
            'sindex_key_json_1': index_key_json_1,
            'sindex_catalog_branch': index_catalog_branch,
            'sindex_weaviate_cohere_config_model': index_weaviate_cohere_config_model,
            'sindex_weaviate_palm_config_model': index_weaviate_palm_config_model,
            'sdataset_group_arn': dataset_group_arn,
            'sdataset_items_arn': dataset_items_arn,
            'sdataset_users_arn': dataset_users_arn,
            'sdataset_item_interaction_events_arn': dataset_item_interaction_events_arn,
            'sdataset_actions_arn': dataset_actions_arn,
            'sdataset_user_action_interaction_events_arn': dataset_user_action_interaction_events_arn,
            'svespa_service_content_id': vespa_service_content_id,
            'svespa_service_container_id': vespa_service_container_id,
            'sindex_client_adapter': index_client_adapter,
            'stext_vectorizer_id': text_vectorizer_id,
            'sindex_cert_1': index_cert_1,
            security: $(wpsolr_localize_script_dashboard.wpsolr_dashboard_nonce_selector).val()
        };


        if ('engine_solr_cloud' === index_engine) {
            var index_solr_cloud_shards = $('#index_solr_cloud_shards').val();
            var index_solr_cloud_replication_factor = $('#index_solr_cloud_replication_factor').val();
            var index_solr_cloud_max_shards_node = $('#index_solr_cloud_max_shards_node').val();

            ajax_data.index_solr_cloud_shards = index_solr_cloud_shards;
            ajax_data.index_solr_cloud_replication_factor = index_solr_cloud_replication_factor;
            ajax_data.index_solr_cloud_max_shards_node = index_solr_cloud_max_shards_node;
        }

        if ('engine_elasticsearch' === index_engine) {
            var index_elasticsearch_shards = $('#index_elasticsearch_shards').val();
            var index_elasticsearch_replicas = $('#index_elasticsearch_replicas').val();

            ajax_data.index_elasticsearch_shards = index_elasticsearch_shards;
            ajax_data.index_elasticsearch_replicas = index_elasticsearch_replicas;
        }

        if ('engine_opensearch' === index_engine) {
            var index_opensearch_shards = $('#index_opensearch_shards').val();
            var index_opensearch_replicas = $('#index_opensearch_replicas').val();

            ajax_data.index_opensearch_shards = index_opensearch_shards;
            ajax_data.index_opensearch_replicas = index_opensearch_replicas;
        }

        $('.solr_error').text('Contacting your search server ... Please wait ...');
        $('.img-succ').css('display', 'none');
        $('.img-err').css('display', 'none');
        $('.img-load').css('display', 'inline-block');

        me.prop("disabled", true);

        jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            data: ajax_data,
            timeout: 1000 * 60 * 5, // 5 minutes
            success: function (data_string) {

                var data = JSON.parse(data_string);

                $('.img-load').css('display', 'none');
                if ('0' === data.status) {
                    $('.solr_error').html('');
                    $('.img-succ').css('display', 'inline');
                    me.prop("style", 'background-color: rgba(46, 222, 121, 1) !important;border-color:white;color:white !important;');
                    me.val('Index configuration verified. Saving now ...');

                    // Set output data value to the form before submission
                    if (undefined !== data.return) {
                        jQuery.each(data.return, function (key, value) {
                            if ('message' === key) {
                                // Display the message
                                $('.solr_error').html(value);
                            } else {
                                $('#' + key).val(value);
                            }
                        });
                    }

                    wpsolr_remove_hidden_values();

                    //console.log($('#settings_conf_form').serialize());

                    $('#settings_conf_form').submit();
                } else if ('1' === data.status) {
                    me.prop("disabled", false);
                    $('.solr_error').text('Error in detecting search engine instance');
                } else {
                    me.prop("disabled", false);
                    me.prop("style", 'background-color: red !important;border-color:white');
                    me.val('Index configuration failed. Check the error message, then try again ...');
                    $('.solr_error').html(data.message);
                }

            },
            error: function (req, status, error) {

                me.prop("disabled", false);
                $('.img-load').css('display', 'none');

                if (error) {
                    $('.solr_error').html('<br><br>An error or timeout occured. <br><br>' + '<b>Error code:</b> ' + status + '<br><br>' + '<b>Error message:</b> ' + escapeHtml(error) + '<br><br>' + escapeHtml(req.responseText) + '<br><br>');

                }
            }
        });
    });

    $('.plus_icon_all').click(function () {
        $(this).closest('.wdm_row').find('.plus_icon').click()
    });

    $('.minus_icon_all').click(function () {
        $(this).closest('.wdm_row').find('.minus_icon').click()
    });

    $('.plus_icon').click(function () {
        $(this).parent().addClass('facet_selected');
        $(this).hide();
        $(this).parent().find('.wdm_row').find('*').css('display', 'block');
        $(this).siblings('img').css('display', 'inline');
    });

    $('.minus_icon').click(function () {
        $(this).parent().removeClass('facet_selected');
        $(this).hide();
        $(this).parent().find('.wdm_row').find('*').css('display', 'none');
        $(this).siblings('img').css('display', 'inline');
    });

    $("#sortable1").sortable(
        {
            connectWith: ".connectedSortable",
            stop: function (event, ui) {
                $('.connectedSortable').each(function () {
                    var result = "";
                    $(this).find(".facet_selected").each(function () {
                        result += $(this).attr('id') + ",";
                    });
                    result = result.substring(0, result.length - 1);

                    $("#select_fac").val(result);
                });
            }
        });


    $('.plus_icon_sort').click(function () {
        $(this).parent().addClass('sort_selected');
        $(this).hide();
        $(this).parent().find('.wdm_row').find('*').css('display', 'block');
        $(this).siblings('img').css('display', 'inline');
    });

    $('.minus_icon_sort').click(function () {
        $(this).parent().removeClass('sort_selected');
        $(this).hide();
        $(this).parent().find('.wdm_row').find('*').css('display', 'none');
        $(this).siblings('img').css('display', 'inline');
    });

    $("#sortable_sort").sortable(
        {
            connectWith: ".connectedSortable_sort",
            stop: function (event, ui) {
                $('.connectedSortable_sort').each(function () {
                    var result = "";
                    $(this).find(".sort_selected").each(function () {
                        result += $(this).attr('id') + ",";
                    });
                    result = result.substring(0, result.length - 1);

                    $("#select_sort").val(result);

                });
            }
        });

    // Clean the Solr index
    $('.wpsolr_unlock_process').click(function (e) {

        // Block submit while Ajax is running
        e.preventDefault();

        var me = $(this);
        var message_text = me.parent().children('.solr_error');
        var button_label = me.val();

        message_text.html('');
        me.attr("disabled", true);
        me.val('Stopping ... Please wait ...');
        var process_id = me.data('wpsolr-process-id');

        var path = get_admin_url();

        var request = jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            dataType: "json",
            timeout: 1000 * 3600 * 24,
            data: {
                action: 'wpsolr_ajax_remove_process_lock',
                process_id: process_id,
                security: $(wpsolr_localize_script_dashboard.wpsolr_dashboard_nonce_selector).val()
            }
        });

        request.done(function (data) {

            // Errors
            if (0 !== data.status) {
                message = '<br/><br/>An error occured: <br/>' + data.message;

                me.attr("disabled", false);
                me.val(button_label);

            } else {
                message = '<br/><br/>Success: <br/>' + data.message;

                location.reload(true);
            }

            message_text.html(message);
        });

    });

    // Change settings url when selecting a view
    $(document).on('change', '#wpsolr_views_id', function (e) {

        var value = (0 === this.value.length) ? '' : this.value;

        // Change url and redirect
        var url = new URL(window.location.href);
        if (0 === value.length) {
            url.searchParams.delete('view_uuid');
        } else {
            url.searchParams.set('view_uuid', value);
        }
        window.location.href = url.href;

    });

    // Change settings url when selecting an index
    $(document).on('change', '#wpsolr_indexes_id', function (e) {

        var value = (0 === this.value.length) ? '' : this.value;

        // Change url and redirect
        var url = new URL(window.location.href);
        if (0 === value.length) {
            url.searchParams.delete('index_uuid');
        } else {
            url.searchParams.set('index_uuid', value);
        }
        window.location.href = url.href;

    });

    // Remove empty fields on saved form. Last position to let other click events be triggered first
    $('#save_facets_options_form,#save_scoring,#save_selected_index_options_form,#save_cron,#save_suggestions').click(function (e) {
        wpsolr_change_button_label($(this));
        wpsolr_remove_empty_values();
        wpsolr_remove_hidden_values();
    });

});
