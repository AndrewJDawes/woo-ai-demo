<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class="wdm_row">
    <div class='col_left'>
        Stop real-time processing
    </div>
    <div class='col_right'>
        <input type='checkbox' name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[is_real_time]'
               class="wpsolr_collapser"
               value='1'
			<?php checked( '1', isset( $solr_options['is_real_time'] ) ? $solr_options['is_real_time'] : '' ); ?>
			<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
        >
        <span class="wpsolr_collapsed">The connector will no more be updated as soon as a post/comment/attachment
        is
        added/saved/deleted, but only when you launch the processing bach. Useful to load a large number of posts, for instance coupons/products from affiliate datafeeds.</span>

    </div>
    <div class="clear"></div>
</div>