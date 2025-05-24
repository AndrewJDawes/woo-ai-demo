<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

/**
 * Included file to display admin options
 */
global $license_manager;

WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, true );

$extension_options_name = WPSOLR_Option::OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE;
$settings_fields_name   = 'extension_yith_woocommerce_ajax_search_free_opt';

$extension_options = WPSOLR_Service_Container::getOption()->get_option_yith_woocommerce_ajax_search_free();
$is_plugin_active  = WPSOLR_Extension::is_plugin_active( WPSOLR_Extension::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE );

$plugin_name    = "YITH WooCommerce Ajax Search (Free)";
$plugin_link    = "https://wordpress.org/plugins/yith-woocommerce-ajax-search/";
$plugin_version = "(Last tested with version 1.5.3)";

?>

<div wdm-vertical-tabs-contentid="extension_groups-options" class="wdm-vertical-tabs-content wpsolr-col-9">
    <form action="options.php" method="POST" id='extension_groups_settings_form'>
		<?php
		settings_fields( $settings_fields_name );
		?>

        <div class='wrapper'>
            <h4 class='head_div'><?php WPSOLR_Escape::echo_esc_html( $plugin_name ); ?> plugin Options</h4>

            <div class="wdm_note">

                In this section, you will configure WPSOLR to work
                with <?php WPSOLR_Escape::echo_esc_html( $plugin_name ); ?>.<br/>

				<?php if ( ! $is_plugin_active ): ?>
                    <p>
                        Status: <a href="<?php WPSOLR_Escape::echo_esc_url( $plugin_link ); ?>"
                                   target="_blank"><?php WPSOLR_Escape::echo_esc_html( $plugin_name ); ?>
                            plugin</a> is not activated. First, you need to install and
                        activate it to configure WPSOLR.
                    </p>
				<?php else : ?>
                    <p>
                        Status: <a href="<?php WPSOLR_Escape::echo_esc_url( $plugin_link ); ?>"
                                   target="_blank"><?php WPSOLR_Escape::echo_esc_html( $plugin_name ); ?>
                            plugin</a>
                        is activated. You can now configure WPSOLR to use it.
                    </p>
				<?php endif; ?>
            </div>

            <div class="wdm_row">
                <div class='col_left'>Use the <a
                            href="<?php WPSOLR_Escape::echo_esc_url( $plugin_link ); ?>"
                            target="_blank"><?php WPSOLR_Escape::echo_esc_html( $plugin_name ); ?> <?php WPSOLR_Escape::echo_esc_html( $plugin_version ); ?>
                        plugin</a>.
                </div>
                <div class='col_right'>
                    <input type='checkbox' <?php WPSOLR_Escape::echo_escaped( $is_plugin_active ? '' : 'readonly' ); ?>
                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[is_extension_active]'
                           value='is_extension_active'
						<?php checked( 'is_extension_active', isset( $extension_options['is_extension_active'] ) ? $extension_options['is_extension_active'] : '' ); ?>>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row">
                <div class='col_left'>Replace search in the YITH search form widget
                </div>
                <div class='col_right'>
                    <input type='checkbox' class="wpsolr_collapser"
                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE_IS_REPLACE_PRODUCT_SUGGESTIONS ); ?>]'
                           value='y'
						<?php checked( WPSOLR_Service_Container::getOption()->get_yith_woocommerce_ajax_search_free_is_replace_product_suggestions() ); ?>>
                    <span class="wpsolr_collapsed">
                        Add the YITH WooCommerce Ajax search form widget to your sidebar, and let the magic happens.<br/>The look & feel is the same, but in the background WPSOLR has replaced the WordPress search.
                    </span>
                </div>
                <div class="clear"></div>
            </div>


            <div class='wdm_row'>
                <div class="submit">
					<?php if ( $license_manager->get_license_is_activated( OptionLicenses::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE ) ) { ?>
                        <div class="wpsolr_premium_block_class">
							<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, OptionLicenses::TEXT_LICENSE_ACTIVATED, true, true ) ); ?>
                        </div>
                        <input
                                name="save_selected_options_res_form"
                                id="save_selected_extension_groups_form" type="submit"
                                class="button-primary wdm-save"
                                value="Save Options"/>
					<?php } else { ?>
						<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, 'Save Options', true, true ) ); ?>
                        <br/>
					<?php } ?>
                </div>
            </div>
        </div>

    </form>
</div>