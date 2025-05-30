<?php

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
    <div class='col_left'>
		<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Activate the Infinite scroll pagination', true ) ); ?>
		<?php WPSOLR_Escape::echo_escaped( WPSOLR_Help::get_help( WPSOLR_Help::HELP_SEARCH_INFINITE_SCROLL ) ); ?>
    </div>
    <div class='col_right'>
        <input type='checkbox'
               name='wdm_solr_res_data[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL ); ?>]'
               value='infinitescroll'
			<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
			<?php checked( WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL,
				isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL ] ) ? $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL ] : '?' ); ?>>

        This feature loads the next page of results automatically when visitors
        approach
        the bottom of search page.
    </div>
    <div class="clear"></div>
</div>

<div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
    <div class='col_left'>
		<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Replace the Infinite scroll javascript with my own', true ) ); ?>
		<?php WPSOLR_Escape::echo_escaped( WPSOLR_Help::get_help( WPSOLR_Help::HELP_SEARCH_INFINITE_SCROLL ) ); ?>
    </div>
    <div class='col_right'>
        <input type='checkbox'
               name='wdm_solr_res_data[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS ); ?>]'
               value='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS ); ?>'
			<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
			<?php checked( WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS,
				isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS ] ) ? $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS ] : '?' ); ?>>

        If you need to load your own javascript infinitescroll file in your theme or plugin, check this option to
        prevent WPSOLR loading it's default javascript.
    </div>
    <div class="clear"></div>
</div>

