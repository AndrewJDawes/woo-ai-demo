<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class='col_left'>
	<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Display debug infos during processing', true ) ); ?>
</div>
<div class='col_right'>

    <input type='checkbox'
           id='is_debug_indexing'
           name='wdm_solr_operations_data[is_debug_indexing][<?php WPSOLR_Escape::echo_esc_attr( $current_index_indice ); ?>]'
           value='is_debug_indexing'
		<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
		<?php checked( 'is_debug_indexing', isset( $solr_operations_options['is_debug_indexing'][ $current_index_indice ] ) ? $solr_operations_options['is_debug_indexing'][ $current_index_indice ] : '' ); ?>>
    <span class='res_err'></span><br>
</div>

<div class="clear"></div>
