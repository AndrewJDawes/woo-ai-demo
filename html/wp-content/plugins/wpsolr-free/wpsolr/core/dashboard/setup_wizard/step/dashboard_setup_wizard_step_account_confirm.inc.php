<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class="wpsolr_wizard_main">
    <h2>Step 2/2 - Confirm</h2>

    <p class="wpsolr_wizard_description">
        Please copy the confirmation code you should have already received to
        <b><?php WPSOLR_Escape::echo_esc_html( $admin_email ); ?></b>:

        <br>
        <input type="text"
               class="wpsolr_setup_wizard_form_field"
               name="account_confirmation"
        />

    </p>

	<?php if ( $is_wpsolr_already_configured ) { ?>
        <input type="checkbox"
               class="wpsolr_collapser" value="wizard_accept_conditions"
        />
        I agree to have my current WPSOLR settings erased and replaced with new ones
        <br>
	<?php } ?>
    <input type="submit"
           class="button-primary wpsolr_setup_wizard_btn wpsolr_is_ajax <?php WPSOLR_Escape::echo_esc_attr( $is_wpsolr_already_configured ? 'wpsolr_collapsed' : '' ); ?>"
           value="Finish the setup"
    />

</div>