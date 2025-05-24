<?php

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;

?>

<div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
">
    <div class='col_left'>
		<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Search template', true ) ); ?>
		<?php WPSOLR_Escape::echo_escaped( WPSOLR_Help::get_help( WPSOLR_Help::HELP_SEARCH_TEMPLATE ) ); ?>
    </div>
    <div class='col_right'>
        <select name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[search_method]">
			<?php
			$options = [
				[
					'code'     => 'use_current_theme_search_template',
					'label'    => 'Use my current theme search template without ajax (with widget Facets and widget Sort)',
					'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ),
				],
				[
					'code'     => 'use_current_theme_search_template_with_ajax',
					'label'    => 'Use my current theme search template with Ajax (with widget Facets and widget Sort)',
					'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ),
				],
				[
					'code'  => 'ajax',
					'label' => '(Deprecated) Use WPSOLR custom Ajax search templates',
				],
				[
					'code'  => 'ajax_with_parameters',
					'label' => '(Deprecated) Use WPSOLR custom Ajax search templates and show parameters in url',
				],
			];

			$search_method = WPSOLR_Service_Container::getOption()->get_search_method();
			foreach ( $options as $option ) {
				$selected = $option['code'] === $search_method ? 'selected' : '';
				$disabled = isset( $option['disabled'] ) ? $option['disabled'] : '';
				?>
                <option
                        value="<?php WPSOLR_Escape::echo_esc_attr( $option['code'] ) ?>"
					<?php WPSOLR_Escape::echo_esc_attr( $selected ); ?>
					<?php WPSOLR_Escape::echo_esc_attr( $disabled ); ?>>
					<?php WPSOLR_Escape::echo_esc_attr( $option['label'] ); ?>
                </option>
			<?php } ?>
        </select>

        <p>Select a tempate to show your search results. Eventually use the Theme extension options to choose more
            presentation options.</p>

    </div>
    <div class="clear"></div>
</div>

