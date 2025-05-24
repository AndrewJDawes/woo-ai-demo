<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class="wpsolr_wizard_main">
    <h2>Step 1/2 - Search index</h2>

    <p class="wpsolr_wizard_description">
        To make your setup seamless, we will create <a
                href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( $hosting_url ) ); ?>"
                target="_new">a free index
            with <?php WPSOLR_Escape::echo_esc_html( $hosting_name ); ?></a> on your behalf.<br><br>
		<?php WPSOLR_Escape::echo_esc_html( $hosting_name ); ?> is the renowned search hosting company that will
        take care of your search backend.<br><br>
        Security, backups and availability are all managed for you.
    </p>


    <br><br>
    <input type="checkbox"
           class="wpsolr_collapser wpsolr_setup_wizard_accept_conditions" value="wizard_accept_conditions"
    />
    I have read and accept the general terms and conditions of WPSolr and <?php WPSOLR_Escape::echo_esc_html( $hosting_name ); ?>
    <br>
    <div class="wpsolr_collapsed">
        <div class="wpsolr_wizard_description">
			<?php include( sprintf( '%s/general_terms_and_conditions.inc.php', $hosting ) ) ?>
        </div>
        <input type="submit"
               class="button-primary wpsolr_setup_wizard_btn wpsolr_is_ajax"
               value="Create my free search index"
        />

    </div>

</div>