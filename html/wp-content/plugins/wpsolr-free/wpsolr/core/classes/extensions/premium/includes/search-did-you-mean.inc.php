<?php

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
    <div class='col_left'>
		<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Display "Did you mean?" in search results header ?', true ) ); ?>
    </div>
    <div class='col_right'>
        <input type='checkbox'
               name='wdm_solr_res_data[<?php WPSOLR_Escape::echo_escaped( 'spellchecker' ); ?>]'
               value='spellchecker'
			<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
			<?php checked( 'spellchecker', isset( $solr_res_options['spellchecker'] ) ? $solr_res_options['spellchecker'] : '?' ); ?>>
    </div>
    <div class="clear"></div>
</div>
