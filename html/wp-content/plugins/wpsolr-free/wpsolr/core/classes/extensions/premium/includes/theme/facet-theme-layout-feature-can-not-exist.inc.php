<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_IS_HIDE_IF_NO_CHOICE ) ); ?>">

	<?php
	$is_can_not_exist = isset( $selected_facets_is_can_not_exist[ $selected_val ] );
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Field not existing', true ) ); ?>
        </div>
        <div class='col_right'>
            <input type='checkbox'
                   name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_FACET_FACETS_IS_CAN_NOT_EXIST ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $selected_val ); ?>]'
                   value='1'
				<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
				<?php checked( $is_can_not_exist ); ?>
            />
            (Does not work with Algolia) Post types that do not index this field in screen 2.2 will be shown in results.

        </div>
        <div class="clear"></div>
    </div>

</div>