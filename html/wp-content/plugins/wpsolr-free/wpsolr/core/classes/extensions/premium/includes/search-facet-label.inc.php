<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

?>

<div class="wdm_row" style="top-margin:5px;">
    <div class='col_left'>
		<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Label', true ) ); ?>

		<?php WPSOLR_Escape::echo_escaped( apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_CUSTOM_DESCRIPTION, '', $selected_val ) ); ?>
    </div>
	<?php
	$facet_label = ! empty( $selected_facets_labels[ $selected_val ] ) ? $selected_facets_labels[ $selected_val ] : '';
	?>
    <div class='col_right'>
        <input type='text' class="wpsolr-remove-if-empty"
               name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_FACET_FACETS_LABEL ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $selected_val ); ?>]'
               value='<?php WPSOLR_Escape::echo_esc_attr( $facet_label ); ?>'
			<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
        />
        <p>
            Will be shown on the front-end (and
            translated in WPML/POLYLANG string modules).
            Leave empty if you wish to use the current
            facet
            name "<?php WPSOLR_Escape::echo_esc_html( $dis_text ); ?>".
        </p>

    </div>
    <div class="clear"></div>
</div>
