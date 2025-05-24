<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

$image_types        = [
	WPSOLR_Option::OPTION_INDEX_POST_TYPES_IS_IMAGE_FEATURED => 'Index the featured image.',
	WPSOLR_Option::OPTION_INDEX_POST_TYPES_IS_IMAGE_EMBEDDED => 'Index the images embedded in the description.',
];
$nb_images_selected = 0;
foreach ( $image_types as $image_type => $image_type_label ) {
	if ( isset( $solr_options[ $image_type ][ $model_type ] ) ) {
		$nb_images_selected ++;
	}
}

?>

<div class="wdm_row">
    <a href="javascript:void(0);"
       class="cust_is_image wpsolr_collapser <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>"
       style="margin: 0px;">
		<?php WPSOLR_Escape::echo_escaped( sprintf( ( count( $image_types ) > 1 ) ? "%s Image types - %s selected" : "%s Image type - %s selected",
			WPSOLR_Escape::esc_html( count( $image_types ) ), empty( $nb_images_selected ) ? 'none' : WPSOLR_Escape::esc_html( $nb_images_selected ) ) ); ?></a>
    </a>

    <div class='cust_is_image wpsolr_collapsed <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>'>
        <br>
		<?php
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_CHECKER ) ) ) {
			require $file_to_include;
		}
		?>

		<?php foreach ( $image_types as $image_type => $image_type_label ) { ?>
            <input type='checkbox'
                   name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( $image_type ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>]'
                   class="wpsolr_checked <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?> <?php WPSOLR_Escape::echo_esc_html( $image_type ); ?>"
                   style="float:left"
                   value='<?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>'
				<?php checked( isset( $solr_options[ $image_type ][ $model_type ] ) ) ?>>
            <span style="float:left"><?php WPSOLR_Escape::echo_esc_html( $image_type_label ); ?></span><br>
		<?php } ?>

    </div>
    <div class="clear"></div>
</div>
<br>