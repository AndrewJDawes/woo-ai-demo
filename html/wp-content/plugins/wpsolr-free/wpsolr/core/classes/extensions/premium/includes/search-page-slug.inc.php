<?php

use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
    <div
            class='col_left'><?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Ajax search page slug', true, true ) ); ?></div>
    <div class='col_right'>
        <input type='text'
               name='wdm_solr_res_data[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG ); ?>]'
               placeholder="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_SearchSolariumClient::_SEARCH_PAGE_SLUG ); ?>"
			<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ) ); ?>
               value="<?php WPSOLR_Escape::echo_esc_attr( ! empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG ] ) ? $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG ] : '' ); ?>">
        <br/>Enter a slug for the search page containing the shortcode for the Ajax
        search results, [solr_search_shortcode].
        <br/>By default, if empty,
        '<?php WPSOLR_Escape::echo_esc_html( WPSOLR_SearchSolariumClient::_SEARCH_PAGE_SLUG ); ?>' will be used.
        <br/>This slug will be used as the target url in the WPSOLR Ajax search box
        form.
    </div>
    <div class="clear"></div>
</div>
