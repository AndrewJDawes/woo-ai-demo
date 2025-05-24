jQuery(document).ready(function ($) {

    $(document).on("click", ".wpsolr_setup_wizard_btn.wpsolr_is_ajax", function (event) {
        call_ajax_setup_wizard_on_step($(this));
    });

    function call_ajax_setup_wizard_on_step(jquery_this) {

        // Remember this for ajax
        var current = this;

        // Show progress
        var button_clicked = jquery_this;
        var buttonText = button_clicked.val(); // Remmember button text
        button_clicked.val(wpsolr_localize_script_dashboard_setup_wizard.settings.steps[wpsolr_localize_script_dashboard_setup_wizard.step].processing_label + ' is in progress. Please wait...');
        button_clicked.prop('disabled', true);
        var error_message_element = $('.wpsolr-error-message');
        error_message_element.css("display", "none");
        error_message_element.html("");

        data = {
            step: wpsolr_localize_script_dashboard_setup_wizard.step,
            hosting: wpsolr_localize_script_dashboard_setup_wizard.hosting,
            email: wpsolr_localize_script_dashboard_setup_wizard.email,
            security: $(wpsolr_localize_script_dashboard_setup_wizard.nonce_selector).val()
        };

        $('.wpsolr_setup_wizard_form_field').each(function () {
            data[$(this).attr('name')] = $(this).val();
        });

        var data = {
            action: 'wpsolr_setup_wizard',
            data: data
        };

        //alert(button_clicked.attr('id'));

        // Pass parameters to Ajax
        jQuery.ajax({
            url: get_admin_url() + 'admin-ajax.php',
            type: "post",
            data: data,
            success: function (data1) {

                data1 = JSON.parse(data1);


                if ("OK" === data1.status.state) {
                    location.href = `?page=solr_settings&path=setup_wizard&hosting=${wpsolr_localize_script_dashboard_setup_wizard.hosting}&step=${wpsolr_localize_script_dashboard_setup_wizard.settings.steps[wpsolr_localize_script_dashboard_setup_wizard.step].step_next}`;
                } else {
                    // End progress
                    button_clicked.val(buttonText);
                    button_clicked.prop('disabled', false);
                    error_message_element.css("display", "inline-block");
                    error_message_element.html(data1.status.message);
                }

            },
            error: function (data1) {

                // End progress
                button_clicked.val(buttonText);
                button_clicked.prop('disabled', false);
                error_message_element.css("display", "inline-block");
                error_message_element.html('Error: ' + data1.status + ' => ' + data1.responseText);
            },
            always: function () {
                // Not called.
            }
        });


        return false;
    }

    function get_admin_url() {
        return $('#adm_path').val();
    }

});