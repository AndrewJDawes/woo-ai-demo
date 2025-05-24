<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_MULTIPLE ) ); ?>">

	<?php
	$facet_is_multiple = WPSOLR_Service_Container::getOption()->get_facets_is_multiple_value( $selected_val );
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Multiple', true ) ); ?>
        </div>
        <div class='col_right'>
            <input type='checkbox'
                   name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_FACET_FACETS_IS_MULTIPLE ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $selected_val ); ?>]'
                   value='1'
				<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
				<?php checked( $facet_is_multiple ); ?>
            />
            Select multiple values.

        </div>
        <div class="clear"></div>
    </div>
</div>
