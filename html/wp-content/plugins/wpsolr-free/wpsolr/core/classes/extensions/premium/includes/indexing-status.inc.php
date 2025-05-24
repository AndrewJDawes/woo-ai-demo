<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

$nb_status_selected = 0;
asort( $model_type_all_statuses ); // Sort by code aplhabetical
foreach ( $model_type_all_statuses as $status => $status_label ) {
	if ( isset( $solr_options[ WPSOLR_Option::OPTION_INDEX_STATUSES ][ $model_type ][ $status ] ) ) {
		$nb_status_selected ++;
	}
}

?>

<div class="wdm_row" style="margin-top:20px;">
    <a href="javascript:void(0);"
       class="wpsolr_event_statuses wpsolr_collapser <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>"
       style="margin: 0px;">
		<?php WPSOLR_Escape::echo_escaped( sprintf( ( count( $model_type_all_statuses ) > 1 ) ? "%s Statuses - %s selected" : "%s Status - %s selected",
			WPSOLR_Escape::esc_html( count( $model_type_all_statuses ) ), empty( $nb_status_selected ) ? 'none' : WPSOLR_Escape::esc_html( $nb_status_selected ) ) ); ?></a>
    </a>

    <div class='wpsolr_event_statuses wpsolr_collapsed <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>'>
        <br>
		<?php
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_CHECKER ) ) ) {
			require $file_to_include;
		}
		?>

		<?php foreach ( [ true, false, ] as $is_selected ) { ?>
			<?php foreach ( $model_type_all_statuses as $status => $status_label ) { ?>
				<?php if ( $is_selected === isset( $solr_options[ WPSOLR_Option::OPTION_INDEX_STATUSES ][ $model_type ][ $status ] ) ) { ?>
                    <input type='checkbox'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEX_STATUSES ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $status ); ?>]'
                           class="wpsolr_checked <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?> <?php WPSOLR_Escape::echo_esc_html( $status ); ?>"
                           value='<?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>'
						<?php checked( $is_selected ) ?>>
					<?php WPSOLR_Escape::echo_esc_html( $status_label ); ?><br>
				<?php } ?>
			<?php } ?>
		<?php } ?>

    </div>
    <div class="clear"></div>
</div>
<br>