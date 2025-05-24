<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;

global $license_manager;

$wpsolr_upgrades = [
	'WPSOLR FREE' => 'WPSOLR PRO or WPSOLR ENTERPRISE',
	'WPSOLR PRO'  => 'WPSOLR ENTERPRISE',
];

?>
<div class="wdm-vertical-tabs-content">
    <div class='wrapper'>
        <h4 class='head_div'>This extension is not part
            of <?php WPSOLR_Escape::echo_esc_html( WPSOLR_PLUGIN_SHORT_NAME ); ?></h4>

        <div class="wdm_note">
            If your project requires more features, or need to integrate with more plugins, you can upgrade to
            <a href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) ); ?>"
               target="_blank"><?php WPSOLR_Escape::echo_esc_html( $wpsolr_upgrades[ WPSOLR_PLUGIN_SHORT_NAME ] ?? '' ); ?></a>
            <br/><br/>
        </div>
    </div>
    <div class="clear"></div>
</div>