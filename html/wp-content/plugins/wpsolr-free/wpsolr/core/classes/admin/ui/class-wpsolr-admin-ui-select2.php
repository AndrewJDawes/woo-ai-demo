<?php

namespace wpsolr\core\classes\admin\ui;

use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax_Search;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

/**
 * Class WPSOLR_Admin_UI_Select2
 * @package wpsolr\core\classes\admin\ui
 */
class WPSOLR_Admin_UI_Select2 {

	const PARAM_MULTISELECT_AJAX_EVENT = 'PARAM_MULTISELECT_AJAX_EVENT';
	const PARAM_MULTISELECT_PLACEHOLDER_TEXT = 'PARAM_MULTISELECT_PLACEHOLDER_TEXT';
	const PARAM_MULTISELECT_HELP_TIP = 'PARAM_MULTISELECT_HELP_TIP';
	const PARAM_MULTISELECT_OPTION_ABSOLUTE_NAME = 'PARAM_MULTISELECT_OPTION_ABSOLUTE_NAME';
	const PARAM_MULTISELECT_OPTION_RELATIVE_NAME = 'PARAM_MULTISELECT_OPTION_RELATIVE_NAME';
	const PARAM_MULTISELECT_SELECTED_IDS = 'PARAM_MULTISELECT_SELECTED_IDS';
	const PARAM_MULTISELECT_IS_MULTISELECT = 'PARAM_MULTISELECT_IS_MULTISELECT';
	const PARAM_MULTISELECT_CLASS = 'PARAM_MULTISELECT_CLASS';
	const PARAM_SELECT2_CLASS = 'PARAM_SELECT2_CLASS';
	const PARAM_SELECT2_DO_NOT_ACTIVATE = 'PARAM_SELECT2_DO_NOT_ACTIVATE';

	/**
	 * Display a multiselect drop down list with search box
	 *
	 * @param $args
	 */
	public static function dropdown_select2( $args ) {

		$is_multiple = ! empty( $args[ self::PARAM_MULTISELECT_IS_MULTISELECT ] ) ? $args[ self::PARAM_MULTISELECT_IS_MULTISELECT ] : false;

		$json_ids = [];
		$values   = empty( $args[ self::PARAM_MULTISELECT_SELECTED_IDS ] ) || empty( $args[ self::PARAM_MULTISELECT_SELECTED_IDS ][ $args[ self::PARAM_MULTISELECT_OPTION_RELATIVE_NAME ] ] )
			? []
			: $args[ self::PARAM_MULTISELECT_SELECTED_IDS ][ $args[ self::PARAM_MULTISELECT_OPTION_RELATIVE_NAME ] ];

		foreach ( $values as $key => $value ) {
			$json_ids[ trim( $key ) ] = 'test';
		}
		$selected = implode( ',', array_keys( $json_ids ) );

		// Generate a unique class name for the options
		$options_class = $args[ self::PARAM_MULTISELECT_CLASS ] ?? '';

		// params
		$params           = ! empty( $args[ WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS ] ) ? $args[ WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS ] : [];
		$params_selectors = ! empty( $args[ WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_SELECTORS ] ) ? $args[ WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_SELECTORS ] : [];
		?>

        <input type="hidden"
               class="wpsolrc-multiselect-search<?php WPSOLR_Escape::echo_escaped( ( $args[ self::PARAM_SELECT2_DO_NOT_ACTIVATE ] ?? false ) ? '-inactive' : '' ); ?> <?php WPSOLR_Escape::echo_esc_attr( $args[ self::PARAM_SELECT2_CLASS ] ?? '' ); ?>"
               style="width: 80%;"
               name="post_types_ids"
               data-option-name="<?php WPSOLR_Escape::echo_esc_attr( $args[ self::PARAM_MULTISELECT_OPTION_ABSOLUTE_NAME ] ); ?>"
               data-placeholder="<?php WPSOLR_Escape::echo_esc_attr( $args[ self::PARAM_MULTISELECT_PLACEHOLDER_TEXT ] ); ?>"
               data-action="<?php WPSOLR_Escape::echo_esc_attr( $args[ self::PARAM_MULTISELECT_AJAX_EVENT ] ); ?>"
               data-option-class="<?php WPSOLR_Escape::echo_esc_attr( $options_class ); ?>"
               data-multiple="<?php WPSOLR_Escape::echo_esc_attr( $is_multiple ? 'true' : 'false' ); ?>"
               data-exclude=""
               data-limit="15"
               data-selected="<?php WPSOLR_Escape::echo_escaped( $is_multiple ? WPSOLR_Escape::esc_json( wp_json_encode( $json_ids ) ) : ( empty( $value ) ? '' : WPSOLR_Escape::esc_attr( $value ) ) ); ?>"
               data-params=<?php WPSOLR_Escape::echo_esc_json( wp_json_encode( $params ) ); ?>
               data-params_selectors=<?php WPSOLR_Escape::echo_esc_json( wp_json_encode( $params_selectors ) ); ?>
               value="<?php WPSOLR_Escape::echo_esc_attr( $selected ); ?>"
        />

        <div class="wpsolrc-multiselect-search-values">
			<?php
			// Add the option values to the form
			foreach ( $values as $key => $value ) {
				?>

                <input type="hidden"
                       name="<?php WPSOLR_Escape::echo_esc_attr( sprintf( '%s', $args[ self::PARAM_MULTISELECT_OPTION_ABSOLUTE_NAME ] ) ); ?>"
                       class="<?php WPSOLR_Escape::echo_esc_attr( $options_class ); ?>"
                       value="<?php WPSOLR_Escape::echo_esc_attr( $key ); ?>"
                />

				<?php
			}
			?>
        </div>
		<?php

	}

	/**
	 * Display a simple select drop down list
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public static function dropdown_simpleselect( $args ) {

		$values = empty( $args['options'] ) ? [] : $args['options'];

		$html = '';
		$html .= sprintf( '<select name="%s">', esc_attr( $args['name'] ) );
		foreach ( $values as $option_value => $option_label ) {
			$html .= sprintf( '<option value="%s" %s>%s</option>option>', esc_attr( $option_value ), selected( $args['selected'], $option_value, false ), $option_label );
		}
		$html .= '</select>';

		return $html;
	}

}