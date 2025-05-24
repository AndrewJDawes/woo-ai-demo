<?php

use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax;
use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax_Search;
use wpsolr\core\classes\admin\ui\WPSOLR_Admin_UI_Select2;
use wpsolr\core\classes\engines\weaviate\WPSOLR_Weaviate_Constants;
use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Elasticsearch_None;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Vespa_None;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Included file to display admin options
 */
global $license_manager;

WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );

// Options name
$option_name = WPSOLR_Option_Indexes::get_option_name( WPSOLR_Extension::OPTION_INDEXES );

// Options object
$option_object = new WPSOLR_Option_Indexes();

// Options data. Loaded after the POST, to be sure it contains the posted data.
$option_data = WPSOLR_Option_Indexes::get_option_data( WPSOLR_Extension::OPTION_INDEXES );

$subtabs = array();

// Create the tabs from the Solr indexes already configured
foreach ( $option_object->get_indexes() as $index_indice => $index ) {
	$subtabs[ $index_indice ] = isset( $index['index_name'] ) ? $index['index_name'] : 'Connector with no name';
}

if ( count( $option_object->get_indexes() ) <= 0 ) {
	$subtabs[ $option_object->generate_uuid() ] = 'Configure your first connector';
}
if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_MULTI_INDEX ) ) ) {
	require $file_to_include;
}

// Create subtabs on the left side
$subtab = wpsolr_admin_sub_tabs( $subtabs );

$is_clone_index = isset( $_REQUEST['clonetab'] ) && ! isset( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $subtab ] );
$is_new_index   = ! $is_clone_index && ! $option_object->has_index( $subtab );

?>

<?php
global $response_object1, $response_object, $google_recaptcha_site_key, $google_recaptcha_token;
$is_submit_button_form_temporary_index = isset( $_POST['submit_button_form_temporary_index'] ) && check_ajax_referer( 'security', 'security' );
$form_data                             = WPSOLR_Extension::extract_form_data( $is_submit_button_form_temporary_index, array(
		'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
	)
);

/**
 * @param $is_clone_index
 * @param $is_remove_if_empty
 * @param string $field_name
 * @param bool $is_index_readonly
 * @param bool $is_new_index
 * @param string $option_name
 * @param array $option_data
 * @param string $index_indice
 * @param string $subtab
 * @param bool $is_password
 * @param bool $is_blurr
 */
function include_edit_field( $field_type, $field_name, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, $is_password, $is_blurr, $is_remove_if_empty, $is_clone_index ) {
	$value = empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ $field_name ] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ $field_name ];
	if ( ! $is_new_index && ! $is_clone_index && $is_remove_if_empty && empty( $value ) ) {
		return;
	}
	?>
    <div class="wdm_row wpsolr_hide <?php WPSOLR_Escape::echo_esc_attr( ( $subtab === $index_indice ) ? $field_name : '' ); ?>">
        <div class='col_left'>
            <span class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_LABEL ); ?>"></span>
        </div>
        <div class='col_right'>

			<?php switch ( $field_type ) {
				case 'textedit':
				case 'number':
					?>
                    <input class="wpsolr-remove-if-empty <?php WPSOLR_Escape::echo_esc_attr( $is_blurr ? 'wpsolr_blur' : '' ); ?> <?php WPSOLR_Escape::echo_esc_attr( $is_password ? 'wpsolr_password' : '' ); ?>"
                           type="<?php WPSOLR_Escape::echo_esc_attr( $is_password ? 'password' : ( ( 'number' === $field_type ) ? 'number' : 'text' ) ); ?>" <?php WPSOLR_Escape::echo_esc_attr( ( $is_index_readonly || ! $is_new_index ) ? 'readonly' : '' ); ?>
                           name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $field_name ); ?>]"
						<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? sprintf( "id='%s'", WPSOLR_Escape::esc_attr( $field_name ) ) : "" ); ?>
                           value="<?php WPSOLR_Escape::echo_esc_attr( $value ); ?>"
                    >
					<?php break;
				case 'textarea': ?>
                    <textarea
                            class="wpsolr-remove-if-empty <?php WPSOLR_Escape::echo_esc_attr( $is_blurr ? 'wpsolr_blur' : '' ); ?> <?php WPSOLR_Escape::echo_esc_attr( $is_password ? 'wpsolr_password' : '' ); ?>"
                            name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $field_name ); ?>]"
                        <?php WPSOLR_Escape::echo_escaped( ( $subtab === $index_indice ) ? sprintf( "id='%s'", WPSOLR_Escape::esc_attr( $field_name ) ) : "" ); ?>
                              rows="10"
                    ><?php WPSOLR_Escape::echo_esc_textarea( $value ); ?></textarea>

					<?php break;
			} // switch ?>

			<?php if ( $is_password ) { ?>
                <input type="checkbox" class="wpsolr_password_toggle"/>Show
			<?php } ?>

            <div class="clear"></div>
			<?php if ( $subtab === $index_indice ) { ?>
                <span class='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>'></span>
			<?php } ?>
        </div>
        <div class="clear"></div>
    </div>
	<?php
}

?>

<style>
    .wpsolr_hide {
        display: none;
    }
</style>
<script>

    jQuery(document).ready(function ($) {

        // Remove clonetab url parameter
        window.history.replaceState({}, '', wpsolr_remove_query_parameter_from_url(window.location.href, 'clonetab'));

        // Remove form settings clonetab url parameter
        const form_settings_referrer = $('#settings_conf_form').find('[name="_wp_http_referer"]');
        form_settings_referrer.val(wpsolr_remove_query_parameter_from_url(window.location.origin + form_settings_referrer.val(), 'clonetab'));

        var g_field_name_hosting_apis = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_HOSTING_APIS ); ?>';
        var g_field_name_engines = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_ENGINES ); ?>';
        var g_field_name_default_api = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_DEFAULT_API ); ?>';
        var g_field_name_engine = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_ENGINE ); ?>';
        var g_field_name_fields = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS ); ?>';
        var g_field_name_label = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_LABEL ); ?>';
        var g_field_name_default_value = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_DEFAULT_VALUE ); ?>';
        var g_field_name_hosting_api_url = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_URL ); ?>';
        var g_field_name_hosting_api_documentation_url = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_DOCUMENTATION_URL ); ?>';
        var g_field_name_placeholder = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_PLACEHOLDER ); ?>';
        var g_field_name_is_create_only = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_IS_CREATE_ONLY ); ?>';
        var g_field_name_is_update_only = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_IS_UPDATE_ONLY ); ?>';
        var g_field_name_field_format = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT ); ?>';
        var g_field_name_field_format_type = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_TYPE ); ?>';
        var g_field_name_field_format_type_mandatory = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_TYPE_MANDATORY ); ?>';
        var g_field_name_field_format_type_integer_2_digits = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_2_DIGITS ); ?>';
        var g_field_name_field_format_type_integer_positive = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_POSITIVE ); ?>';
        var g_field_name_field_format_error_label = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>';
        var g_field_name_engine_analysers = '<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_ENGINE_ANALYSERS ); ?>';

        var g_ui_api_fields = <?php WPSOLR_Escape::echo_esc_json( wp_json_encode( WPSOLR_Hosting_Api_Abstract::get_all_ui_fields() ) ); ?>;
        var g_current_engine = $('#index_engine').val();
        var g_current_hosting_api_id = $('#index_hosting_api_id').val();
        var g_current_analyser_id = $('#<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ); ?>').val();
        var openai_fields_el = $('.<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_API_KEY ); ?>,.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL ); ?>,.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL_VERSION ); ?>,.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_TYPE ); ?>');
        var huggingface_fields_el = $('.<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_API_KEY ); ?>,.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_HUGGINGFACE_CONFIG_MODEL ); ?>,.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_HUGGINGFACE_CONFIG_MODEL_QUERY ); ?>');
        var cohere_fields_el = $('.<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_API_KEY ); ?>,.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_COHERE_CONFIG_MODEL ); ?>');
        var google_palm_fields_el = $('.<?php WPSOLR_Escape::echo_esc_js( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_KEY_JSON ); ?>,.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_GOOGLE_PALM_CONFIG_MODEL ); ?>,.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_TOKEN ); ?>');

        //console.log(g_current_engine, g_current_hosting_api_id);
        //console.log(JSON.stringify(g_ui_api_fields, null, 2));

        function show_hide_fields(engine, hosting_api_id, current_analyser_id, is_form_validation) {

            //console.log(engine, hosting_api_id, current_analyser_id, is_form_validation);

            // Hide all fields by default
            $(".wpsolr_hide").hide();

            var engine = ((typeof engine === 'undefined')) ? $('#index_engine').val() : engine;
            var engine_default_hosting_api = g_ui_api_fields[g_field_name_engines][engine][g_field_name_default_api];
            //alert(engine_default_hosting_api);

            /* Show/hide fields depending on search engines */
            $('.wpsolr_engine').hide();
            $('.wpsolr_engine_not').show();
            $('.wpsolr_engine.' + engine).show();
            $('.wpsolr_engine_not.' + engine).hide();

            //alert(hosting_api_id);
            const reset_analyser_id = (typeof hosting_api_id === 'undefined');
            var hosting_api_id = ((typeof hosting_api_id === 'undefined')) ? engine_default_hosting_api : hosting_api_id;
            //alert(hosting_api_id);

            // Show the hosting api information
            var url = g_ui_api_fields[g_field_name_hosting_apis][hosting_api_id][g_field_name_hosting_api_url];
            var label = g_ui_api_fields[g_field_name_hosting_apis][hosting_api_id][g_field_name_label];
            if ('' !== url) {
                $('.wpsolr_hide.' + g_field_name_hosting_api_url).show().html('<a href=' + "'" + url + "'" + " target='_new'>" + 'Visit ' + label + "</a>   |   ");
            }

            // Show the documentation hosting api information
            var documentation_url = g_ui_api_fields[g_field_name_hosting_apis][hosting_api_id][g_field_name_hosting_api_documentation_url];
            $('.wpsolr_hide.' + g_field_name_hosting_api_documentation_url).show().html("<a href=" + "'" + documentation_url + "'" + " target='_new'>See the tutorial</a>");

            // Manage the hosting api list content based on engine selection
            //console.log("hui", engine, JSON.stringify(g_ui_api_fields[g_field_name_engines][engine], null, 2));
            $('#index_hosting_api_id').val(hosting_api_id);
            jQuery.each(g_ui_api_fields[g_field_name_hosting_apis], function (hosting_api_id, fields) {

                if (engine === fields[g_field_name_engine]) {

                    $('#index_hosting_api_id option[value="' + hosting_api_id + '"]').show();

                } else {
                    $('#index_hosting_api_id option[value="' + hosting_api_id + '"]').hide();
                }
            });

            // Manage the analyser list content based on engine selection
            //console.log("analysers", engine, JSON.stringify(g_ui_api_fields['engines'][engine][g_field_name_engine_analysers], null, 2));
            element_analysers = $('select#<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ); ?>');
            if (element_analysers) {
                if (reset_analyser_id) {
                    jQuery.each(g_ui_api_fields[g_field_name_engines][engine][g_field_name_engine_analysers], function (field_name, field_properties) {
                        if (field_properties['is_default']) {
                            element_analysers.val(field_name);
                            //console.log(engine, field_name, field_properties);
                        }
                    });
                }
                element_analysers.find('option').hide();
                engine_analysers = (engine === '<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractSearchClient::ENGINE_SOLR_CLOUD ); ?>') ? '<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractSearchClient::ENGINE_SOLR ); ?>' : engine;
                element_analysers.find('option.' + engine_analysers).show(); // solr and solrcloud have same analysers
            }

            //console.log("hey", g_field_name_hosting_apis, hosting_api_id, g_field_name_fields, JSON.stringify(g_ui_api_fields[g_field_name_hosting_apis][g_current_hosting_api_id][g_field_name_fields], null, 2));
            // Manage the fields
            var has_errors = false;
            jQuery.each(g_ui_api_fields[g_field_name_hosting_apis][hosting_api_id][g_field_name_fields], function (field_name, field_properties) {

                // Show the field
                var section_field_selector = '.wpsolr_hide.' + field_name;
                $(section_field_selector).show();
                $('#' + field_name).prop({readOnly: false});


                // Set the field properties
                jQuery.each(field_properties, function (field_property_name, field_property_value) {

                    var current_field_name_in_form_selector = '#current_index_configuration_edited_id #' + field_name;
                    var current_field_name_update_selector = '.wpsolr_is_index_readonly #' + field_name;

                    var current_object = $(section_field_selector + ' .' + field_property_name);

                    switch (field_property_name) {
                        case g_field_name_default_value:
                            current_object = $(current_field_name_in_form_selector);
                            if (!current_object.val()) {
                                current_object.val(field_property_value);
                            }
                            //console.log(field_name, field_property_value);
                            break;

                        case g_field_name_is_create_only:
                            current_object = $(current_field_name_update_selector);
                            current_object.prop({readOnly: field_property_value});
                            //console.log(field_name, field_property_value, current_object.prop('readOnly'));
                            break;

                        case g_field_name_is_update_only:
                            current_object = $(current_field_name_update_selector);
                            if (current_object.length === 0) {
                                // Hide on creation
                                $(section_field_selector).hide();
                            }
                            break;

                        case g_field_name_placeholder:
                            current_object = $(current_field_name_in_form_selector);
                            current_object.prop(g_field_name_placeholder, field_property_value);
                            //console.log(field_name, field_property_value);
                            break;

                        case g_field_name_label:
                            current_object.html(field_property_value);
                            break;

                        case g_field_name_field_format:
                            //console.log('error', g_field_name_field_format_error_label, field_property_value);

                            var current_object_error = $(section_field_selector + ' .' + g_field_name_field_format_error_label);

                            var is_error = false;
                            if ($(section_field_selector).is(":visible") && is_form_validation) {
                                //console.log('validation', section_field_selector, $(section_field_selector).css("display"), g_field_name_field_format_type, g_field_name_field_format_type_mandatory, field_property_value[g_field_name_field_format_type]);

                                var current_object = $('#' + field_name);

                                var field_format_type = ((typeof field_property_value[g_field_name_field_format_type] === 'undefined')) ? '' : field_property_value[g_field_name_field_format_type];

                                switch (field_format_type) {
                                    case g_field_name_field_format_type_mandatory:
                                        is_error = (0 === current_object.val().trim().length);
                                        break;

                                    case g_field_name_field_format_type_integer_2_digits:
                                        is_error = (isNaN(parseInt(current_object.val())) || (parseInt(current_object.val()) <= 9));
                                        break;

                                    case g_field_name_field_format_type_integer_positive:
                                        is_error = (isNaN(parseInt(current_object.val())) || (parseInt(current_object.val()) <= 0));
                                        break;

                                }
                            }

                            current_object_error.html(is_error ? field_property_value[g_field_name_field_format_error_label] : '');
                            has_errors = has_errors || is_error;
                            break;
                    }

                });

            });

            if (is_form_validation && !has_errors) {
                // remove parameter from url before submitting the options
                $(document).trigger('wpsolr_event_save_index');
            } else if (is_form_validation) {
                // Show loaded message
                $('.wpsolr_loading_index').html('(Connector loaded)');
            }

        }

        // Refresh fields on engine selection
        $(document).on('change', '#index_engine', function (e) {

            show_hide_fields(this.value, undefined, undefined, false);
        });

        // Refresh fields on hosting api selection
        $(document).on('change', '#index_hosting_api_id', function (e) {

            var value = (0 === this.value.length) ? 'no_hosting_api' : this.value;

            $('.index_hosting_api_id_label').html($(this).find("option:selected").text());

            show_hide_fields(undefined, value, $('#index_analyser_id').val(), false);
        });


        // Refresh fields on analysis api selection
        $(document).on('change', '#index_analyser_id', function (e) {
            show_hide_fields(undefined, $('#index_hosting_api_id').val(), $(this).val(), false);
        });

        // Verify the fields on save
        $(document).on("click", ".check_index_status", function () {

            $('.wpsolr_loading_index').html('(Loading the index ...)');

            show_hide_fields(undefined, $('#index_hosting_api_id').val(), $('#index_analyser_id').val(), true);
        });

        // remove query parameter from url
        function wpsolr_remove_query_parameter_from_url($url, $parameter) {
            var url_obj = new URL($url);
            url_obj.searchParams.delete($parameter);
            return url_obj.toString();
        }


        const is_new_index = <?php WPSOLR_Escape::echo_esc_js( $is_new_index ? "true" : "false" ); ?>;
        if (is_new_index) {
            // So default analyser of default engine is set on new index
            show_hide_fields(undefined, undefined, undefined, false);
        } else {
            // Init the fields if this is a not a new index
            show_hide_fields(undefined, $('#index_hosting_api_id').val(), $('#index_analyser_id').val(), false);
        }
    });

</script>

<div id="solr-hosting-tab">

	<?php
	if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_MULTI_INDEX ) ) ) {
		require $file_to_include;
	}
	?>

    <div id="solr-results-options" class="wdm-vertical-tabs-content">

		<?php
		$class_collapsed = '';

		if ( $is_clone_index ) {
			$clone_index_indice                                                                                                            = WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ 'clonetab' ] );
			$option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $subtab ]                                                               = $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $clone_index_indice ];
			$option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $subtab ][ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_NAME ]  .= sprintf( ' (clone %s)', $subtab );
			$option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $subtab ][ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_LABEL ] .= $subtab;
		}

		if ( $is_new_index ) {

			// Prevent rare zombie deleted indexes error
			if ( empty( $option_data ) || ! is_array( $option_data ) ) {
				$option_data = [];
			}
			if ( empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] ) || ! is_array( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] ) ) {
				$option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] = [];
			}

			$option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $subtab ] = [];
			$class_collapsed                                                 = $option_object->has_index_type_temporary() ? '' : 'wpsolr_collapsed';

			if ( ! $option_object->has_index_type_temporary() ) {
				// No temporary index yet: display the form to create one.
				WPSOLR_Extension::load_file(
					'managed_solr_servers/templates/template-temporary-account-form.php',
					false,
					array(
						'managed_solr_service_id'   => $form_data['managed_solr_service_id']['value'],
						'response_error'            => ( isset( $response_object1 ) && ! OptionManagedSolrServer::is_response_ok( $response_object1 ) ) ? OptionManagedSolrServer::get_response_error_message( $response_object1 ) : '',
						'google_recaptcha_site_key' => isset( $google_recaptcha_site_key ) ? $google_recaptcha_site_key : '',
						'google_recaptcha_token'    => isset( $google_recaptcha_token ) ? $google_recaptcha_token : '',
						'total_nb_indexes'          => $option_object->get_nb_indexes(),
					)
				);
			}
		} else {
			// Verify that current subtab is a Solr index indice.
			if ( ! $is_clone_index && ! $option_object->has_index( $subtab ) ) {
				// Use the first subtab element
				$subtab = key( $subtabs );
			}

		}

		?>

		<?php if ( $is_new_index && ! $option_object->has_index_type_temporary() ) {
		$btn_title = "Connect to your self-hosted search server";
		?>
        <input id="wpsolr_connect_search_btn_id" type="button" class="button-secondary wpsolr_collapser"
               value="<?php WPSOLR_Escape::echo_esc_attr( $btn_title ); ?>"/>
		<?php } ?>

        <div class="<?php WPSOLR_Escape::echo_esc_attr( $class_collapsed ); ?>">
            <form action="options.php" method="POST" id='settings_conf_form'>

				<?php
				settings_fields( $option_name );
				$hosting_apis = WPSOLR_Hosting_Api_Abstract::get_hosting_apis();
				?>

				<?php
				foreach ( ( isset( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] ) ? $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] : [] ) as $index_indice => $index ) {

				if ( ! empty( $index ) ) {
					try {
						$index_hosting_api = WPSOLR_Hosting_Api_Abstract::get_hosting_api_by_id( $index['index_hosting_api_id'], $index['index_engine'] );
					} catch ( Exception $e ) {
						// Hosting API does not exist, due to downgrade?
						continue;
					}
				}

				$is_index_type_temporary = false;
				$is_index_type_managed   = false;
				$is_index_readonly       = false;
				$is_index_in_creation    = false;
				$search_engine_name      = WPSOLR_AbstractSearchClient::ENGINE_SOLR;

				if ( $subtab === $index_indice ) {
					$is_index_in_creation    = $is_clone_index || $is_new_index;
					$is_index_type_temporary = $option_object->is_index_type_temporary( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ] );
					$is_index_type_managed   = $option_object->is_index_type_managed( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ] );
					$is_index_readonly       = $is_index_type_temporary;
					$search_engine           = $option_object->get_index_search_engine_name( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ] );
					$search_engine_name      = $option_object->get_index_search_engine_name( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ] );

					if ( $is_index_type_temporary ) {
						// Check that the temporary index is still temporary on the server.
						$managed_solr_server = new OptionManagedSolrServer( $option_object->get_index_managed_solr_service_id( $index ) );
						$response_object     = $managed_solr_server->call_rest_get_temporary_solr_index_status( $index_indice );

						if ( OptionManagedSolrServer::is_response_ok( $response_object ) ) {

							$is_index_unknown_on_server = OptionManagedSolrServer::get_response_result( $response_object, 'isUnknown' );

							if ( $is_index_unknown_on_server ) {

								// Change the solr index type to managed
								$option_object->update_index_property( $index_indice, WPSOLR_Option_Indexes::INDEX_TYPE, WPSOLR_Option_Indexes::STORED_INDEX_TYPE_UNMANAGED );

								// Display message
								$response_error = 'This test index has expired and was therefore deleted. You can delete this configuration.';

								// No more readonly therefore
								$is_index_type_temporary = false;
								$is_index_readonly       = false;

							} else {

								$is_index_type_temporary_on_server = OptionManagedSolrServer::get_response_result( $response_object, 'isTemporary' );
								if ( ! $is_index_type_temporary_on_server ) {

									// Change the solr index type to managed
									$option_object->update_index_property( $index_indice, WPSOLR_Option_Indexes::INDEX_TYPE, WPSOLR_Option_Indexes::STORED_INDEX_TYPE_MANAGED );

									// No more readonly therefore
									$is_index_type_temporary = false;
									$is_index_readonly       = false;
								}
							}

						} else {

							$response_error = ( isset( $response_object ) && ! OptionManagedSolrServer::is_response_ok( $response_object ) ) ? OptionManagedSolrServer::get_response_error_message( $response_object ) : '';
						}
					}

					?>

				<?php }

				?>

                <div
                        id="<?php WPSOLR_Escape::echo_esc_attr( $subtab != $index_indice ? $index_indice : "current_index_configuration_edited_id" ); ?>"
                        class="wrapper <?php WPSOLR_Escape::echo_esc_attr( ! $is_index_in_creation ? "wpsolr_is_index_readonly" : "" ); ?>" <?php WPSOLR_Escape::echo_escaped( ( $subtab != $index_indice ) ? "style='display:none'" : "" ); ?> >

                    <input type='hidden'
                           name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_doc_type]"
						<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_doc_type'" : "" ); ?>
                           value="<?php WPSOLR_Escape::echo_esc_attr( empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_doc_type'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_doc_type'] ); ?>">

                    <input type='hidden'
                           name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][managed_solr_service_id]"
						<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='managed_solr_service_id'" : "" ); ?>
                           value="<?php WPSOLR_Escape::echo_esc_attr( empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['managed_solr_service_id'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['managed_solr_service_id'] ); ?>">

                    <input type='hidden'
                           name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_type]"
						<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_type'" : "" ); ?>
                           value="<?php WPSOLR_Escape::echo_esc_attr( empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_type'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_type'] ); ?>">

                    <h4 class='head_div'>
						<?php WPSOLR_Escape::echo_esc_html( $is_index_type_temporary
							? 'This is your temporary (2 hours) index configuration for testing'
							: ( $is_index_type_managed
								? sprintf( 'This is your index configuration managed by %s', $option_object->get_index_managed_solr_service_id( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ] ) )
								: ( $is_new_index ? 'Configure your connector' : sprintf( 'Configure your %s connector', $search_engine_name ) )
							)
						);
						?>
                        <span class="wpsolr_loading_index"></span>
                    </h4>

					<?php
					if ( $is_new_index ) {
						?>
                        <div class="wdm_note hide_engine_solr hide_engine_solr_cloud show_engine_elasticsearch"
                             style="display:none">

                            Important ! You must first have:
                            <ol>
                                <li>
                                    <a
                                            href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/install-elasticsearch/' ) ); ?>"
                                            target="__wpsolr">Installed</a> your Elasticsearch server,
                                    or get one <a
                                            href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/elasticsearch-hosting/' ) ); ?>"
                                            target="__wpsolr">hosted</a>
                                </li>
                            </ol>

                            WPSOLR is compatible with Elasticsearch 5.0 and above.

                            Set your Elasticsearch index properties here, then save it.

                            <ol>
                                <li>If the index name does not exist on this Elasticsearch server, it will be
                                    created
                                    with WPSOLR's mappings "wpsolr_types" (dynamic templates and fields).
                                </li>
                                <li>If the index name exists on this Elasticsearch server, but has no mappings
                                    "wpsolr_types", his mappings will be updated with WPSOLR's mappings
                                    "wpsolr_types"
                                    (dynamic templates and fields).
                                </li>
                                <li>Else, the index is not updated.</li>
                            </ol>

                            In all cases, the index connectivity will be tested: a green icon displayed with
                            success, a
                            red error message else.

                        </div>
						<?php
					}
					?>

                    <div class="wdm_row">
                        <h4 class="solr_error" <?php WPSOLR_Escape::echo_escaped( $subtab != $index_indice ? "style='display:none'" : "" ); ?> >
							<?php
							if ( ! empty( $response_error ) ) {
								WPSOLR_Escape::echo_esc_html( $response_error );
							}
							?>
                        </h4>
						<?php
						if ( ( $subtab === $index_indice ) && ! empty( $wpsolr_index_settings_warning = get_transient( get_current_user_id() . 'wpsolr_index_settings_warning' ) ) ) {
							?>
                            <a class="wpsolr_collapser solr_success" href="javascript:void();" ;>Succes with
                                warnings. Click to display the warnings</a>
                            <h4 class="wpsolr_collapsed solr_success" <?php WPSOLR_Escape::echo_escaped( $subtab != $index_indice ? "style='display:none'" : "" ); ?> >
								<?php
								if ( ( $subtab === $index_indice ) && ! empty( $wpsolr_index_settings_warning = get_transient( get_current_user_id() . 'wpsolr_index_settings_warning' ) ) ) {
									delete_transient( get_current_user_id() . 'wpsolr_index_settings_warning' );
									WPSOLR_Escape::echo_esc_html( $wpsolr_index_settings_warning );
								}
								?>
                            </h4>
						<?php } ?>
                    </div>

					<?php if ( ( $subtab === $index_indice ) && $is_index_in_creation ) { ?>
                        <input type='hidden' id="is_index_creation" value="1">
					<?php } ?>

                    <div class="wdm_row">
                        <div class='col_left'>Connector</div>

                        <div class='col_right'>
							<?php
							$is_engine_solr          = empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ] )
							                           || ( WPSOLR_AbstractEngineClient::ENGINE_SOLR === $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ] )
							                           || ( WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD === $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ] );
							$search_engine           = empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ] ) ? WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ];
							$is_engine_solr_cloud    = ( $search_engine === WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD );
							$is_engine_elasticsearch = ( $search_engine === WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH );
							$is_engine_opensearch    = ( $search_engine === WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH );
							$is_engine_vespa         = ( $search_engine === WPSOLR_AbstractEngineClient::ENGINE_VESPA );

							$raw_hosting_api_id     = ! empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_hosting_api_id'] )
								? $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_hosting_api_id']
								: ( ( ( $subtab === $index_indice ) && $is_index_in_creation ) ? WPSOLR_Hosting_Api_Elasticsearch_None::HOSTING_API_ID : '' );
							$index_hosting_api_id   = WPSOLR_Hosting_Api_Abstract::get_hosting_api_by_id( $raw_hosting_api_id, $search_engine )->get_id();
							$index_region_id        = empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_region_id'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_region_id'];
							$index_configuration_id = empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_ID ] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_ID ];
							$index_analyser_id      = empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ][ WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ];
							$text_vectorizer_id     = empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['text_vectorizer_id'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['text_vectorizer_id'];
							$option_indexes         = new WPSOLR_Option_Indexes();


							?>
							<?php if ( ! $is_index_readonly && $is_index_in_creation ) { ?>
                                <select id="index_engine"
                                        name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE ); ?>]"
                                >
									<?php foreach ( WPSOLR_AbstractEngineClient::get_engines_definitions() as $engine_def_id => $engine_def ) { ?>
                                        <option value="<?php WPSOLR_Escape::echo_esc_attr( $engine_def_id ); ?>" <?php selected( $search_engine, $engine_def_id ); ?> <?php disabled( ! $engine_def['is_active'] ); ?> >
											<?php WPSOLR_Escape::echo_esc_html( $option_indexes->get_search_engine_name( $engine_def_id ) ); ?>
                                        </option>
									<?php } ?>


                                </select>

							<?php } else { ?>
                                <input type='hidden'
                                       name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_engine]"
									<?php WPSOLR_Escape::echo_escaped( ( $subtab === $index_indice ) ? "id='index_engine'" : "" ); ?>
                                       value="<?php WPSOLR_Escape::echo_esc_attr( $search_engine ); ?>"
                                >
								<?php WPSOLR_Escape::echo_esc_html( $search_engine_name ); ?>
							<?php } ?>

                            <div class="clear"></div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="wdm_row">
                        <div class='col_left'>Hosting service</div>
                        <div class='col_right'>
							<?php if ( ! $is_index_readonly && $is_index_in_creation ) { ?>
                                <select
                                        name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_hosting_api_id]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_hosting_api_id'" : "" ); ?>
                                >
									<?php
									foreach ( $hosting_apis as $hosting_api ) {
										if ( $is_index_in_creation && ! $hosting_api->is_show_create() ) {
											continue;
										}
										?>
                                        <option class="<?php WPSOLR_Escape::echo_esc_attr( $hosting_api->get_id() ); ?>"
                                                value="<?php WPSOLR_Escape::echo_esc_attr( $hosting_api->get_id() ); ?>"
											<?php disabled( $hosting_api->get_is_disabled() ); ?>
											<?php selected( $hosting_api->get_id(), $index_hosting_api_id ) ?>
                                        >
											<?php
											if ( $hosting_api->get_is_no_hosting() ) {
												WPSOLR_Escape::echo_esc_html( $hosting_api->get_label() );
											} else {
												WPSOLR_Escape::echo_esc_html(
													sprintf(
														'%s - %s', $hosting_api->get_label(),
														sprintf( $hosting_api->get_is_disabled() ?
															( empty( $hosting_api->get_incompatibility_reason() ) ? 'Coming soon' : $hosting_api->get_incompatibility_reason() )
															: (
															empty( $hosting_api->get_latest_version() ) ? 'Tested on current version'
																: sprintf( 'Tested on WPSOLR %s', $hosting_api->get_latest_version() ) )
														)
													)
												);
											}
											?>
                                        </option>
									<?php } ?>

                                </select>
							<?php } else { ?>
                                <input type='hidden'
                                       name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_hosting_api_id]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_hosting_api_id'" : "" ); ?>
                                       value="<?php WPSOLR_Escape::echo_esc_attr( $index_hosting_api_id ); ?>">
								<?php try {
									WPSOLR_Escape::echo_esc_html( WPSOLR_Hosting_Api_Abstract::get_hosting_api_by_id( $index_hosting_api_id, $search_engine )->get_label() );
								} catch ( Exception $e ) {
									WPSOLR_Escape::echo_esc_html( $e->getMessage() );
								} ?>
							<?php } ?>

                            <p>
                                <span class="wpsolr_hide <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_URL ); ?>"></span>
                                <span class="wpsolr_hide <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_DOCUMENTATION_URL ); ?>"></span>
                            </p>

                            <div class="clear"></div>
							<?php if ( $subtab === $index_indice ) { ?>
                                <span class='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>'></span>
							<?php } ?>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="wdm_row wpsolr_engine_not
                        <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_VESPA ); ?>
                        ">
                        <div class='col_left'>Module</div>
                        <div class='col_right'>
							<?php if ( ! $is_index_readonly && $is_index_in_creation ) { ?>
                                <select
                                        name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ); ?>]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? sprintf( "id='%s'", WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ) ) : "" ); ?>
                                >
									<?php

									foreach ( WPSOLR_AbstractEngineClient::get_search_engines_type_analysers() as $s_search_engine => $search_engine_analysers ) {
										foreach ( $search_engine_analysers as $search_engine_analyser => $search_engine_analyser_def ) { ?>
                                            <option class="<?php WPSOLR_Escape::echo_esc_attr( $s_search_engine ); ?>"
                                                    value="<?php WPSOLR_Escape::echo_esc_attr( $search_engine_analyser ); ?>"
												<?php ( empty( $index_analyser_id ) && isset( $search_engine_analyser_def['is_default'] )
												        && ( WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH === $s_search_engine ) ) ?
													selected( true ) : selected( $index_analyser_id, $search_engine_analyser ); ?>
                                            >
												<?php
												WPSOLR_Escape::echo_esc_html( $search_engine_analyser_def['label'] ?? ucfirst( $search_engine_analyser ) );
												?>
                                            </option>
										<?php } ?>
									<?php } ?>

                                </select>
							<?php } else { ?>
                                <input type='hidden'
                                       name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ); ?>]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? sprintf( "id='%s'", WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ) ) : "" ); ?>
                                       value="<?php WPSOLR_Escape::echo_esc_attr( $index_analyser_id ); ?>"
                                >
								<?php
								if (
									! empty( $engine_analysers = WPSOLR_AbstractEngineClient::get_search_engine_type_analysers( $search_engine ) )
									&& isset( $engine_analysers[ $index_analyser_id ] )
									&& isset( $engine_analysers[ $index_analyser_id ]['label'] )
								) {
									WPSOLR_Escape::echo_esc_html( WPSOLR_AbstractEngineClient::get_search_engine_type_analysers( $search_engine )[ $index_analyser_id ]['label'] );
								} else {
									WPSOLR_Escape::echo_esc_html( $index_analyser_id );
								}
								?>
							<?php } ?>

                            <div class="clear"></div>
							<?php if ( $subtab === $index_indice ) { ?>
                                <span class='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>'></span>
							<?php } ?>
                        </div>
                        <div class="clear"></div>
                    </div>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_EMAIL, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_API_KEY, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, true, false, false, $is_clone_index ); ?>

                    <div class="wdm_row wpsolr_hide <?php WPSOLR_Escape::echo_esc_attr( ( $subtab === $index_indice ) ? WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_REGION_ID : '' ); ?>">
                        <div class='col_left'>
                            <span class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_LABEL ); ?>"></span>
                        </div>
                        <div class='col_right'>
							<?php if ( ! $is_index_readonly && $is_index_in_creation ) { ?>

								<?php
								WPSOLR_Admin_UI_Select2::dropdown_select2( [
									WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_IS_MULTISELECT       => false,
									WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_CLASS                => 'index_region_id',
									WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_SELECTED_IDS         => [],
									WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_AJAX_EVENT           => WPSOLR_Admin_UI_Ajax::AJAX_ENVIRONMENTS_SEARCH,
									WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_PLACEHOLDER_TEXT     => 'Choose an environment&hellip;',
									WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_OPTION_ABSOLUTE_NAME => sprintf( '%s[%s][%s][%s]', $option_name, WPSOLR_Option::OPTION_INDEXES_INDEXES, $index_indice, 'index_region_id' ),
									WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_OPTION_RELATIVE_NAME => WPSOLR_Admin_UI_Ajax_Search::FORM_FIELD_FILTER_QUERY_CONTENT,
									WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS                   => [],
									WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_SELECTORS         => [
										'email'   => '#index_email',
										'api_key' => '#index_api_key'
									],
								] );

								?>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <input type='hidden' id='index_region_id' value="">
								<?php } ?>

							<?php } else { ?>
                                <input type='hidden'
                                       name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_region_id]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_region_id'" : "" ); ?>
                                       value="<?php WPSOLR_Escape::echo_esc_attr( $index_region_id ); ?>">
								<?php if ( $subtab === $index_indice ) {
									WPSOLR_Escape::echo_esc_html( $index_region_id );
								}
								?>
							<?php } ?>

                            <div class="clear"></div>
							<?php if ( $subtab === $index_indice ) { ?>
                                <span class='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>'></span>
							<?php } ?>
                        </div>
                        <div class="clear"></div>
                    </div>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_NAME, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_LABEL, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

                    <div class="wdm_row wpsolr_hide <?php WPSOLR_Escape::echo_esc_attr( ( $subtab === $index_indice ) ? WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_CLIENT_ADAPTER : '' ); ?>">
                        <div class='col_left'>
                            <span class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_LABEL ); ?>"></span>
                        </div>
                        <div class='col_right'>
							<?php if ( ! $is_index_readonly ) { ?>
                                <select
                                        name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_client_adapter]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_client_adapter'" : "" ); ?>
                                >
                                    <option
                                            value='http' <?php selected( '', empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_client_adapter'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_client_adapter'] ); ?>>
                                        http
                                    </option>
                                    <option
                                            value='curl' <?php selected( 'curl', empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_client_adapter'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_client_adapter'] ); ?>>
                                        curl
                                    </option>
                                </select>
							<?php } else { ?>
                                <input type='text' readonly
                                       name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_client_adapter]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_client_adapter'" : "" ); ?>
                                       value="<?php WPSOLR_Escape::echo_esc_attr( empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_client_adapter'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_client_adapter'] ); ?>">
							<?php } ?>

                            <div class="clear"></div>
							<?php if ( $subtab === $index_indice ) { ?>
                                <span class='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>'></span>
							<?php } ?>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="wdm_row wpsolr_hide <?php WPSOLR_Escape::echo_esc_attr( ( $subtab === $index_indice ) ? WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_PROTOCOL : '' ); ?>">
                        <div class='col_left'>
                            <span class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_LABEL ); ?>"></span>
                        </div>
                        <div class='col_right'>
							<?php if ( ! $is_index_readonly ) { ?>
                                <select
                                        name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_protocol]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_protocol'" : "" ); ?>
                                >
                                    <option
                                            value='http' <?php selected( 'http', empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_protocol'] ) ? 'http' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_protocol'] ) ?>>
                                        http
                                    </option>
                                    <option
                                            value='https' <?php selected( 'https', empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_protocol'] ) ? 'http' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_protocol'] ) ?>>
                                        https
                                    </option>
                                </select>
							<?php } else { ?>
                                <input type='text' readonly
                                       name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][index_protocol]"
									<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='index_protocol'" : "" ); ?>
                                       value="<?php WPSOLR_Escape::echo_esc_attr( empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_protocol'] ) ? '' : $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ]['index_protocol'] ); ?>">
							<?php } ?>

                            <div class="clear"></div>
							<?php if ( $subtab === $index_indice ) { ?>
                                <span class='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>'></span>
							<?php } ?>
                        </div>
                        <div class="clear"></div>
                    </div>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_HOST, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, true, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_VESPA_SERVICE_CONTAINER_ID, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>
					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_VESPA_SERVICE_CONTENT_ID, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ENDPOINT, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, true, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ENDPOINT_1, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, true, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_API_KEY_1, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, true, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_PORT, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_PATH, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'number', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_SHARDS, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'number', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_REPLICAS, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'number', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'number', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'number', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_SHARDS, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'number', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_REPLICATION_FACTOR, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'number', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_MAX_SHARDS_NODE, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textarea', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_CERT_1, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, true, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_KEY, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textarea', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_KEY_JSON, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, true, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textarea', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_KEY_JSON_1, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, true, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_SECRET, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, true, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_PUBLIC_KEY, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_TOKEN, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, true, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_AWS_REGION, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_DATASET_GROUP_ARN, $is_new_index, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_DATASET_ITEMS_ARN, $is_new_index, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, true, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_DATASET_ITEM_INTERACTION_EVENTS_ARN, $is_new_index, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, true, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_DATASET_USERS_ARN, $is_new_index, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, true, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_DATASET_ACTIONS_ARN, $is_new_index, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, true, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_DATASET_USER_ACTION_INTERACTION_EVENTS_ARN, $is_new_index, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, true, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_LANGUAGE_CODE, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_TYPE, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL_VERSION, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_HUGGINGFACE_CONFIG_MODEL, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_HUGGINGFACE_CONFIG_MODEL_QUERY, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_COHERE_CONFIG_MODEL, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_GOOGLE_PALM_CONFIG_MODEL, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_TYPE_QNA, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL_QNA, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL_VERSION_QNA, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>

					<?php include_edit_field( 'textedit', WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_CATALOG_BRANCH, $is_index_readonly, $is_new_index, $option_name, $option_data, $index_indice, $subtab, false, false, false, $is_clone_index ); ?>


					<?php
					/*
					if ( ! $is_engine_elasticsearch ) { ?>
						<div class="wdm_row">
							<div class='col_left'>Advanced search engine configuration</div>
							<div class='col_right'>
								<?php include "admin_configuration.inc.php"; ?>
							</div>
							<div class="clear"></div>
						</div>
					<?php } */
					?>

					<?php
					// Display managed offers links
					if ( $is_index_type_temporary ) {
						?>

                        <div class='col_right' style='width:90%'>

							<?php
							$managed_solr_service_id = $option_object->get_index_managed_solr_service_id( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_indice ] );

							$OptionManagedSolrServer = new OptionManagedSolrServer( $managed_solr_service_id );
							foreach ( $OptionManagedSolrServer->generate_convert_orders_urls( $index_indice ) as $managed_solr_service_orders_url ) {
								?>

                                <input name="gotosolr_plan_yearly_trial"
                                       type="button" class="button-primary"
                                       value="<?php WPSOLR_Escape::echo_esc_attr( $managed_solr_service_orders_url[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL ] ); ?>"
                                       onclick="window.open('<?php WPSOLR_Escape::echo_esc_url( $managed_solr_service_orders_url[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_ORDER_URL_LINK ] ); ?>', '__blank');"
                                />

								<?php

							}
							?>

                        </div>
                        <div class="clear"></div>

						<?php
					}
					?>

                    <div class="wdm_row wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_VESPA ); ?>
                        "
                    ">
                    <div class='col_left'>Text vectorizer</div>
                    <div class='col_right'>
						<?php
						$components_vectorizer_text = WPSOLR_Hosting_Api_Vespa_None::get_components_vectorizer_text();
						$components_vectorizer_text = array_merge( [ '' => [ 'label' => 'No text vectorizer', ] ], $components_vectorizer_text );
						if ( ! $is_index_readonly && $is_index_in_creation ) { ?>
                            <select
                                    name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][text_vectorizer_id]"
								<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='text_vectorizer_id'" : "" ); ?>
                            >
								<?php
								foreach ( $components_vectorizer_text as $model_id => $model_def ) { ?>
                                    <option class="<?php WPSOLR_Escape::echo_esc_attr( $model_id ); ?>"
                                            value="<?php WPSOLR_Escape::echo_esc_attr( $model_id ); ?>"
										<?php selected( $model_id, $text_vectorizer_id ) ?>
                                    >
										<?php WPSOLR_Escape::echo_esc_html( $model_def['label'] ); ?>
                                    </option>
								<?php } ?>

                            </select>
						<?php } else { ?>
                            <input type='hidden'
                                   name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[solr_indexes][<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>][text_vectorizer_id]"
								<?php WPSOLR_Escape::echo_escaped( $subtab === $index_indice ? "id='text_vectorizer_id'" : "" ); ?>
                                   value="<?php WPSOLR_Escape::echo_esc_attr( $text_vectorizer_id ); ?>">
							<?php WPSOLR_Escape::echo_esc_html( $components_vectorizer_text[ $text_vectorizer_id ]['label'] ?? $text_vectorizer_id ); ?>
						<?php } ?>

                        <div class="clear"></div>
						<?php if ( $subtab === $index_indice ) { ?>
                            <span class='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>'></span>
						<?php } ?>
                    </div>
                    <div class="clear"></div>
                </div>

				<?php
				if ( ! $is_new_index && ( $subtab === $index_indice ) ) {
					include_once "admin_configuration_files.inc.php";
				}
				?>

        </div>
		<?php } // end foreach ?>


		<?php
		foreach(WPSOLR_AbstractEngineClient::get_engines_definitions() as $engine_def_id => $engine_def) {
		?>
        <div style="display:none"
             class='wdm_row wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( $engine_def_id ); ?>'>
            <div class="submit">
				<?php if ( empty( $license ) || ! $license_manager->is_installed || $license_manager->get_license_is_activated( $license ) ) { ?>
					<?php if ( ! empty( $license ) ) { ?>
                        <div class="wpsolr_premium_block_class">
							<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, $license, OptionLicenses::TEXT_LICENSE_ACTIVATED, true ) ); ?>
                        </div>
					<?php } ?>

                    <div class="wdm_row">
                        <div class="submit">
                            <input name="check_solr_status" type="button"
                                   class="check_index_status button-primary wdm-save"
                                   value="Save this configuration"/>
                            <span>
                                <div class='img-load'></div>

                                                 <img
                                                         src='<?php WPSOLR_Escape::echo_esc_url( WPSOLR_DEFINE_PLUGIN_DIR_URL . '/images/success.png' ); ?>'
                                                         style='height:18px;width:18px;margin-top: 10px;display: none'
                                                         class='img-succ'/>
                                                    <img
                                                            src='<?php WPSOLR_Escape::echo_esc_url( WPSOLR_DEFINE_PLUGIN_DIR_URL . '/images/warning.png' ); ?>'
                                                            style='height:18px;width:18px;margin-top: 10px;display: none'
                                                            class='img-err'/>
                        </span>
                        </div>

						<?php if ( ! $is_new_index ) { ?>
                            <input name="delete_index_configuration" type="button"
                                   class="delete_index_configuration button-secondary wdm-delete"
                                   value="Delete this configuration"/>
                            <input name="delete_index" type="checkbox" class="delete_index wpsolr_collapser"
                                   style="margin-left:20px"/>
                            Also delete the connector and its content
                            <span class="wpsolr_collapsed" style="color: red">
                                => Warning!! Selecting this checkbox will also delete the connector and all its content. No way to get the content back after you click on the delete button.
                            </span>
						<?php } // end if ?>

                    </div>

				<?php } else { ?>
					<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, $license, 'Save Options', true ) ); ?>
                    <br/>
				<?php } ?>
            </div>
        </div>
		<?php } ?>


        <div class="clear"></div>

        </form>

		<?php if ( ! $is_clone_index ) { ?>
        <form action="" method="GET">
            <input type="hidden" name="page" value="solr_settings"/>
            <input type="hidden" name="tab" value="solr_indexes"/>
            <input type="hidden" name="subtab"
                   value="<?php WPSOLR_Escape::echo_esc_attr( $option_object->generate_uuid() ); ?>"/>
            <input type="hidden" name="clonetab" value="<?php WPSOLR_Escape::echo_esc_attr( $subtab ); ?>"/>
            <input type="submit"
                   class="button-primary wdm-clone"
                   value="Clone this configuration"
                   style="float:right"/>
        </form>
		<?php } ?>

    </div>
</div>

</div>
