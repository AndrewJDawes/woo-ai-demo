<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class='col_left'>
	<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Full re-processing', true ) ); ?>
</div>
<div class='col_right'>

    <input type='checkbox'
           id='is_reindexing_all_posts'
           name='is_reindexing_all_posts'
           value='is_reindexing_all_posts'
		<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
		<?php checked( true, false ); ?>>

    Process all data again, without deleting data already present in the connector.
    <span class='res_err'></span><br>
</div>
<div class="clear"></div>