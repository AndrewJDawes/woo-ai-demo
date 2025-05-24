<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_EXCLUSION ) ); ?>">

	<?php
	$is_exclusion = isset( $selected_facets_is_exclusions[ $selected_val ] );
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Do not use other items selections to calculate the items count', true ) ); ?>
        </div>
        <div class='col_right'>
            <input type='checkbox'
                   class="wpsolr_collapser"
                   name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_FACET_FACETS_IS_EXCLUSION ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $selected_val ); ?>]'
                   value='1'
				<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
				<?php checked( $is_exclusion ); ?>
            />
            By default, the facet items count is updated when other facet items are selected. Use this option when you
            want
            to show facet items count as if no selections where made.
            <p class="wpsolr_err wpsolr_collapsed">
                Warning: With Algolia indexes, this will call an additional query for every query. So, if you select
                this feature on 3 filters, 3 extra queries will be called with every query. This will add-up to your
                Algolia usage and billing.
            </p>

        </div>
        <div class="clear"></div>
    </div>
</div>
