<?php
/**
 * Managed Solr server Signin form
 */

use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class="wdm-vertical-tabs-content">

    <div class="wrapper">

        <form method="POST">
            <h4 class='head_div'>Signin</h4>

            <div class="wdm_row">
                <div class='col_left'>Email</div>

                <div class='col_right'>
                    <input type="text" placeholder="Your Login Email" name="email"
                           value="<?php WPSOLR_Escape::echo_esc_attr( $form_data['email']['value'] ); ?>">

                    <div class="clear"></div>
                    <span class='name_err'><?php WPSOLR_Escape::echo_esc_html( $form_data['email']['error'] ); ?></span>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row">
                <div class='col_left'>Password</div>

                <div class='col_right'>
                    <input type="text" placeholder="Your Login Password" name="password"
                           value="<?php WPSOLR_Escape::echo_esc_attr( $form_data['password']['value'] ); ?>">

                    <div class="clear"></div>
                    <span class='name_err'><?php WPSOLR_Escape::echo_esc_html( $form_data['password']['error'] ); ?></span>
                </div>
                <div class="clear"></div>
            </div>

            <div class='wdm_row'>
                <div class="submit">
                    <input name="submit-form-signin" type="submit"
                           class="button-primary wdm-save"
                           value="Show my Solr indexes at <?php WPSOLR_Escape::echo_esc_html( $managed_solr_server->get_label() ); ?>"/>
                    <input type="hidden" name="security"
                           value="<?php WPSOLR_Escape::echo_esc_attr( wp_create_nonce( 'security' ) ); ?>"/>
                </div>
            </div>

        </form>
    </div>

    <div class="numberCircle">or</div>
    <div style="clear: both; margin-bottom: 15px;"></div>

    <div class="wrapper">

        <form method="POST">
            <h4 class='head_div'>Buy a subscription</h4>

            <div class="wdm_row">
                <div class='col_left'>Email<br/></br>
                    If you want to quickly test WPSOLR, without the burden of your own Solr server.</br><br/>
                    Valid during 2 hours. After that, the index will be deleted automatically.<br/><br/>
                </div>

                <div class='col_right'>
                    <input type="text" placeholder="Your Login Email" name="email"
                           value="<?php WPSOLR_Escape::echo_esc_attr( $form_data['email']['value'] ); ?>">

                    <div class="clear"></div>
                    <span class='name_err'><?php WPSOLR_Escape::echo_esc_html( $form_data['email']['error'] ); ?></span>
                </div>
                <div class="clear"></div>
            </div>

            <div class='wdm_row'>
                <div class="submit">
                    <input name="submit-form-signup" type="submit"
                           class="button-primary wdm-save"
                           value="Get my instant free Solr index at <?php WPSOLR_Escape::echo_esc_html( $managed_solr_server->get_label() ); ?>"/>
                    <input type="hidden" name="security"
                           value="<?php WPSOLR_Escape::echo_esc_attr( wp_create_nonce( 'security' ) ); ?>"/>
                </div>
            </div>

        </form>
    </div>

</div>