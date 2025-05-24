<?php
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\extensions\recommendations\WPSOLR_Option_Recommendations;
?>

<script>

    jQuery(document).ready(function ($) {

    var type_definitions = <?php WPSOLR_Escape::echo_esc_json( wp_json_encode( WPSOLR_Option_Recommendations::get_type_definitions() ) ); ?>;
    var layout_definitions = <?php WPSOLR_Escape::echo_esc_json( wp_json_encode( WPSOLR_Option_Recommendations::get_template_definitions() ) ); ?>;

    /**
     * Refresh all layouts and fields of type(s) passed in argument
     **/
    function refresh_types(type_elements, is_type_changed) {

    $(type_elements).each(function (index) {

    var type_element = $(this);
    var current_recommendation_type_value = type_element.val();

    /**
     * Layouts shown/hidden in the select box for the current ftype
     **/
    var current_recommendation_layout_element = type_element.closest('.wpsolr_recommendations').find('.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Recommendations::CLASS_RECOMMENDATION_LAYOUT ); ?>');
    current_recommendation_layout_element.find('option').hide();
    $.each(layout_definitions, function (index, layout_definition) {
    if (('' === layout_definition['type']) || (current_recommendation_type_value === layout_definition['type'])) {
    current_recommendation_layout_element.find('option[value="' + layout_definition["code"] + '"]').show();
}
});

    // Type has changed: select its default layout
    if (is_type_changed) {
    $.each(type_definitions, function (index, type_definition) {
    if ((current_recommendation_type_value === type_definition['code'])) {
    current_recommendation_layout_element.val(type_definition["default_layout"]);
}
});
}

    /**
     * Refresh type layout fields
     **/
    refresh_layouts(current_recommendation_layout_element);
});
}

    /**
     * Refresh all fields of layout(s) passed in argument
     **/
    function refresh_layouts(layout_elements) {

    $(layout_elements).each(function (index) {

    var layout_element = $(this);
    var current_recommendation_layout_value = layout_element.val();

    /**
     * Hide all optional fields
     **/
    layout_element.closest('.wpsolr_recommendations').find('.wpsolr-remove-if-hidden').hide();
    layout_element.closest('.wpsolr_recommendations').find('.wpsolr_collapsed').removeClass('wpsolr_collapsed').addClass('wpsolr_collapsed_removed');

    /**
     * Show optional fields for the layout selected
     **/
    $.each(layout_definitions, function (index, layout_definition) {
    if ((current_recommendation_layout_value === layout_definition['code'])) {
    $.each(layout_definition['fields'], function (index, field_class) {

    // Pnly collapse elements authorized
    layout_element.closest('.wpsolr_recommendations').find('.' + field_class).closest('.wpsolr_collapsed_removed').removeClass('wpsolr_collapsed_removed').addClass('wpsolr_collapsed');

    // Show authorized elements
    layout_element.closest('.wpsolr_recommendations').find('.' + field_class).closest('.wpsolr-remove-if-hidden').not('.wpsolr-has-collapsed').show();
});
}
});
});
}

    /**
     * Refresh layout of type selected
     **/
    $(document).on('change', '.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Recommendations::CLASS_RECOMMENDATION_TYPE ); ?>', function (e) {
    refresh_types($(this), true);
});

    /**
     * Refresh layout of type selected
     **/
    $(document).on('change', '.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Recommendations::CLASS_RECOMMENDATION_LAYOUT ); ?>', function (e) {
    refresh_layouts($(this));
});

    /**
     * Refresh all the layouts of all types on page display
     */
    var all_type_elements = $('.<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option_Recommendations::CLASS_RECOMMENDATION_TYPE ); ?>');
    refresh_types(all_type_elements, false);

    /**
     * For change event on new recommendation type to display its layout and fields
     */
    $new_type_el = $('.wpsolr_recommendations_is_new .wpsolr_recommendation_type');
    $new_type_el.change();
})
    ;

</script>
