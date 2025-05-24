<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\WPSOLR_Events;

?>

<div class="wpsolr_wizard_header">
    <input type="hidden"
           id="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_DASHBOARD_NONCE_SELECTOR ); ?>"
           value="<?php WPSOLR_Escape::echo_esc_attr( wp_create_nonce( WPSOLR_NONCE_FOR_DASHBOARD ) ); ?>"
    >
    <input type='hidden'
           id='adm_path'
           value='<?php WPSOLR_Escape::echo_escaped( apply_filters( WPSOLR_Events::WPSOLR_FILTER_UAT_TEST_ADMIN_URL, admin_url() ) ); ?>'
    >

    <a href="?page=solr_settings&path=setup_wizard&step=step_skip">Skip my guided setup</a>
</div>