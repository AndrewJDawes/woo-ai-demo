<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_LOCALIZATION ) ); ?>">

	<?php
	$max_facet_items = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_ITEMS_MAX_LABELS_SHOWN, 50 );

	$facet_name_standard = ( 'categories' === $dis_text ) ? 'category' : ( 'tags' === $dis_text ? 'post_tag' : $dis_text );

	// Let others a chance to tell us what facet items are.
	$facet_items = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_ITEMS, [], $facet_name_standard, $selected_val );

	// Well, it's up to us then.
	if ( empty( $facet_items ) ) {
		if ( taxonomy_exists( $facet_name_standard ) ) {
			$terms_args = [
				'taxonomy' => $facet_name_standard,
				'fields'   => 'names',
			];

			if ( ! empty( $max_facet_items ) ) {
				$terms_args['number'] = $max_facet_items;
			}

			$facet_items = get_terms( $terms_args );

		} elseif ( WpSolrSchema::_FIELD_NAME_TYPE === $selected_val ) {

			$post_types  = get_post_types();
			$facet_items = [ 'attachment' ];
			foreach ( $post_types as $post_type ) {
				if ( 'attachment' !== $post_type && 'revision' !== $post_type && 'nav_menu_item' !== $post_type ) {
					array_push( $facet_items, $post_type );
				}
			}
		} elseif ( WpSolrSchema::_FIELD_NAME_META_TYPE_S === $selected_val ) {

			$facet_items = WPSOLR_Model_Builder::get_all_meta_types();

		} else {
			// Custom fields
			global $wpdb;

			$query = "            SELECT distinct meta_value
                                  FROM {$wpdb->prefix}postmeta
                                  WHERE meta_key = %s
                                  AND char_length(meta_value) < 100 /* Prevent overflow with huge custom field values */
                                  ORDER BY meta_value ASC";

			if ( ! empty( $max_facet_items ) ) {
				$query          = $query . ' LIMIT %d';
				$query_prepared = $wpdb->prepare( "{$query}", $dis_text, $max_facet_items );
			} else {
				$query_prepared = $wpdb->prepare( "{$query}", $dis_text );
			}

			$facet_items = $wpdb->get_col( "{$query_prepared}" );
		}
	}

	?>

	<?php

	$facet_items = array_slice( $facet_items, 0, $max_facet_items );

	if ( ! empty( $facet_items ) && ! empty( $facets_layout_available[ $current_layout_id ] ) ) {

		/** @var WPSOLR_UI_Layout_Abstract $layout_object */
		$layout_object = $facets_layout_available[ $current_layout_id ];

		$button_open_localizations = $layout_object->get_button_localize_label();
		?>

        <input name="collapser" type="button"
               class="button-primary wpsolr_collapser <?php WPSOLR_Escape::echo_esc_attr( $selected_val ); ?>"
               value="<?php WPSOLR_Escape::echo_esc_attr( $button_open_localizations ); ?>">

        <div class="wpsolr_collapsed">

			<?php
			/**
			 * Put first the selected values
			 */
			$facets_groups = [];
			foreach (
				[
					$selected_facets_item_labels,
					$selected_facets_item_is_default,
					$selected_facets_seo_items_templates,
					$selected_facets_item_is_hidden,
				] as $group_selected_values
			) {
				if ( ! empty( $group_selected_values[ $selected_val ] ) ) {
					$facets_groups = array_merge( array_keys( $group_selected_values[ $selected_val ] ), $facets_groups );
				}
			}

			sort( $facets_groups );
			sort( $facet_items );
			$facets_groups = array_unique( array_merge( $facets_groups, $facet_items ) );

			foreach ( $facets_groups as $facet_item_label ) {
				if ( ! empty( $facet_item_label ) ) {
					?>

                    <div class="wdm_row wpsolr-facet-custom" style="top-margin:5px;">
                        <div class='col_left'>
							<?php WPSOLR_Escape::echo_escaped( $license_manager->show_premium_link( true, OptionLicenses::LICENSE_PACKAGE_PREMIUM, sprintf( '%s', ucfirst( $facet_item_label ) ), true ) ); ?>
                        </div>
						<?php
						$facet_label = ( ! empty( $selected_facets_item_labels[ $selected_val ] ) && ! empty( $selected_facets_item_labels[ $selected_val ][ $facet_item_label ] ) )
							? $selected_facets_item_labels[ $selected_val ][ $facet_item_label ] : '';
						?>
                        <div class='col_right'>
							<?php
							include 'facet-theme-layout-localizations-field.inc.php';
							if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_THEME_COLOR_PICKER_TEMPLATE_LOCALIZATION ) ) ) {
								require $file_to_include;
							}
							if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_SEO_TEMPLATE_LOCALIZATION ) ) ) {
								require $file_to_include;
							}
							?>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="wdm_row" style="top-margin:5px;">
                        <div class='col_left'>
                        </div>
						<?php
						$is_default = ( ! empty( $selected_facets_item_is_default[ $selected_val ] ) && ! empty( $selected_facets_item_is_default[ $selected_val ][ $facet_item_label ] )
						                && ! empty( $selected_facets_item_is_default[ $selected_val ][ $facet_item_label ] ) );
						?>
                        <div class='col_right'>
                            <input type='checkbox' class="wpsolr-remove-if-empty"
                                   name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_FACET_FACETS_ITEMS_IS_DEFAULT ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $selected_val ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $facet_item_label ); ?>]'
                                   value='1'
								<?php checked( $is_default ); ?>
								<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
                            />
                            Pre-select "<?php WPSOLR_Escape::echo_esc_html( $facet_item_label ); ?>".

                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="wdm_row" style="top-margin:5px;">
                        <div class='col_left'>
                        </div>
						<?php
						$is_hidden = ( ! empty( $selected_facets_item_is_hidden[ $selected_val ] ) && ! empty( $selected_facets_item_is_hidden[ $selected_val ][ $facet_item_label ] )
						               && ! empty( $selected_facets_item_is_hidden[ $selected_val ][ $facet_item_label ] ) );
						?>
                        <div class='col_right'>
                            <input type='checkbox'
                                   class="wpsolr-remove-if-empty wpsolr_collapser"
                                   name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_FACET_FACETS_ITEMS_IS_HIDDEN ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $selected_val ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $facet_item_label ); ?>]'
                                   value='1'
								<?php checked( $is_hidden ); ?>
								<?php WPSOLR_Escape::echo_esc_attr( $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ); ?>
                            />
                            Hide "<?php WPSOLR_Escape::echo_esc_html( $facet_item_label ); ?>".
                            <span class="wpsolr_collapsed">If you change this option, a Full re-processing of your data is required.</span>

                        </div>
                        <div class="clear"></div>
                    </div>

				<?php }
			} ?>
        </div>
	<?php } ?>

</div>
