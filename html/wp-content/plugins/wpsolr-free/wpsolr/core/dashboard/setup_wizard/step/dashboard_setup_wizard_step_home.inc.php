<?php

use wpsolr\core\classes\admin\setup_wizard\WPSOLR_Admin_Setup_Wizard_Factory;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class="wpsolr_wizard_main">
    <h2>Welcome to WPSolr!</h2>

    <div class="wpsolr_wizard_description">
        It's great to have you here with us! - we'll be guiding you through the setup
        process.<br><br>First, choose your autoconfigured search:
        <select class="wpsolr_wizard_form_element" id="wpsolr_wizard_form_hosting">
			<?php foreach (
				WPSOLR_Admin_Setup_Wizard_Factory::get_setup_wizard_settings() as $hosting => $hosting_def
			) { ?>
                <option value="<?php WPSOLR_Escape::echo_esc_attr( $hosting ); ?>"
					<?php WPSOLR_Escape::echo_esc_attr( $hosting_def['is_active'] ? '' : 'disabled' ); ?>>
					<?php WPSOLR_Escape::echo_esc_html( $hosting_def['search_type'] ); ?>
                </option>
			<?php } ?>
        </select>

    </div>

    <input type="submit"
           class="button-primary wpsolr_setup_wizard_btn" value="Set up my search"
           onclick="location.href=`?page=solr_settings&path=setup_wizard&hosting=${jQuery('#wpsolr_wizard_form_hosting').val()}&step=<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CREATE ); ?>`"
    />

</div>