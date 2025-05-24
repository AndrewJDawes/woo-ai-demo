<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\models\post\WPSOLR_Model_Meta_Type_Post;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\WPSOLR_Events;

/** @var WPSOLR_Model_Meta_Type_Post $model_type_object */
$allowed_attachments_types = $model_type_object->get_allowed_mime_types();

$attachment_types_str = WPSOLR_Service_Container::getOption()->get_option_index_attachment_types_str();
$attachment_types     = WPSOLR_Service_Container::getOption()->get_option_index_attachment_types();

$nb_fields_selected = 0;
foreach ( $allowed_attachments_types as $mime_type ) {
	if ( false !== array_search( $mime_type, $attachment_types, true ) ) {
		$nb_fields_selected ++;
	}
}
?>

<div class="wdm_row">
    <div>

        <a href="javascript:void(0);"
           class="wpsolr_attachments wpsolr_collapser <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>"
           style="margin: 0px;">

			<?php WPSOLR_Escape::echo_escaped( sprintf( ( count( $allowed_attachments_types ) > 1 ) ? '%s Media types - %s selected' : '%s Media type - %s selected',
				WPSOLR_Escape::esc_html( count( $allowed_attachments_types ) ),
				empty( $nb_fields_selected ) ? 'none' : WPSOLR_Escape::esc_html( $nb_fields_selected ) ) ); ?></a>


        <div class='wpsolr_attachments wpsolr_collapsed <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>'>
            <br>

			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_CHECKER ) ) ) {
				require $file_to_include;
			}
			?>

            <input type='hidden' name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[attachment_types]'
                   id='attachment_types'>
			<?php
			$disabled = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM );

			// sort attachments
			asort( $allowed_attachments_types );

			// Selected first
			foreach ( $allowed_attachments_types as $type ) {
				if ( strpos( $attachment_types_str, $type ) !== false ) {
					?>
                    <input type='checkbox' name='attachment_types' class="wpsolr_checked"
                           value='<?php WPSOLR_Escape::echo_esc_attr( $type ); ?>'
						<?php WPSOLR_Escape::echo_esc_attr( $disabled ); ?>
                           checked> <?php WPSOLR_Escape::echo_esc_html( $type ); ?>
                    <br>
					<?php
				}
			}

			// Unselected 2nd
			foreach ( $allowed_attachments_types as $type ) {
				if ( strpos( $attachment_types_str, $type ) === false ) {
					?>
                    <input type='checkbox' name='attachment_types' class="wpsolr_checked"
                           value='<?php WPSOLR_Escape::echo_esc_attr( $type ); ?>'
						<?php WPSOLR_Escape::echo_esc_attr( $disabled ); ?>
                    > <?php WPSOLR_Escape::echo_esc_html( $type ) ?>
                    <br>
					<?php
				}
			}

			?>
        </div>
    </div>
    <div class="clear"></div>
</div>
