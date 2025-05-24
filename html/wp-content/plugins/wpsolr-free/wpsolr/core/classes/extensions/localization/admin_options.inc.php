<?php

use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Included file to display admin options
 */
global $license_manager;

WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_LOCALIZATION, true );

$extension_options_name = 'wdm_solr_localization_data';
$settings_fields_name   = 'solr_localization_options';

// Retrieve all options in database
$options = OptionLocalization::get_options( true );

?>
<div id="localization-options" class="wdm-vertical-tabs-content">
    <form action="options.php" method="POST" id='localization_settings_form'>

		<?php
		settings_fields( $settings_fields_name );
		$presentation = OptionLocalization::get_presentation_options();
		?>

        <div class='wrapper'>
            <h4 class='head_div'>Localization Options</h4>

            <div class="wdm_note">

                In this section, you will configure (localize) all the texts displayed on the front-end forms.<br/>
            </div>

			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_LOCALIZE ) ) ) {
				require $file_to_include;
			}
			?>

			<?php
			foreach ( $presentation as $section_name => $section ) {
				?>

                <div class='wdm_row'>

                    <div class='wdm_row'><h4
                                class='head_div'><?php WPSOLR_Escape::echo_esc_html( $section_name ); ?></h4></div>

					<?php
					foreach ( OptionLocalization::get_section_terms( $section ) as $term_code => $term_content ) {
						?>

                        <div class='wdm_row'>
                            <div class='col_left'>
								<?php WPSOLR_Escape::echo_esc_html( $term_content[0] ); ?>
                            </div>
                            <div class='col_right'>

								<?php
								$term_localized = OptionLocalization::get_term( $options, $term_code );
								WPSOLR_Escape::echo_escaped(
									sprintf(
										"<textarea id='message_user_without_capabilities_shown_no_results' name='wdm_solr_localization_data[terms][%s]'
						          rows='4' cols='100'>%s</textarea >",
										WPSOLR_Escape::esc_attr( $term_code ),
										WPSOLR_Escape::esc_textarea( $term_localized ) )
								);
								?>

                            </div>
                        </div>

						<?php
					} ?>
                </div>
                <div style="clear:both"></div>
				<?php
			}
			?>


            <div class='wdm_row'>
                <div class="submit">
                    <input name="save_selected_options_res_form"
                           id="save_selected_extension_groups_form" type="submit"
                           class="button - primary wdm - save" value="Save Options"/>

                </div>
            </div>

        </div>

    </form>
</div>