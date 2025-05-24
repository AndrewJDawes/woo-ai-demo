<?php

use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\models\WPSOLR_Model_Meta_Type_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

/** @var WPSOLR_Model_Meta_Type_Abstract $model_type_object */
try {
	$model_type_object = WPSOLR_Model_Builder::get_model_type_object( $model_type );
} catch ( Exception $e ) {
	$t = 1;
}

$post_type  = $model_type_object->get_type();
$post_label = $model_type_object->get_label();

$is_group_exists        = isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] ) && isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $post_type ] );
$suggestion_model_label = $is_group_exists && isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $post_type ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $post_type ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL ] : '';
$suggestion_model_nb    = $is_group_exists && isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $post_type ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_NB ] ) ? $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $post_type ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_NB ] : '10';
?>

<li class="wpsolr-sorted">

    <div class="wdm_row">
        <div class='col_left'>
			<?php WPSOLR_Escape::echo_esc_html( $post_label ); ?>
        </div>
        <div class='col_right'>
            <input type='checkbox'
                   data-wpsolr-index-post-type="<?php WPSOLR_Escape::echo_esc_attr( $post_type ); ?>"
                   class="wpsolr_collapser wpsolr_index_post_types wpsolr_checked"
                   name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ) ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODELS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $post_type ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_ID ); ?>]'
                   value='y'
				<?php
				checked( isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model_type ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_ID ] ) );
				?>
            >

            <div class="wdm_row wpsolr_collapsed  wpsolr-remove-if-hidden">
                <div class='col_left'>
                    Label
                </div>
                <div class='col_right'>
                    <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL ); ?>"
                           type='text'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODELS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $post_type ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL ); ?>]'
                           placeholder="Replace '<?php WPSOLR_Escape::echo_esc_attr( $post_label ); ?>' with your label here"
                           value="<?php WPSOLR_Escape::echo_esc_attr( $suggestion_model_label ); ?>">
                    Will be translated in WPML/POLYLANG string modules if not empty.
                </div>
                <div class="clear"></div>
            </div>


            <div class="wdm_row wpsolr_collapsed  wpsolr-remove-if-hidden">
                <div class='col_left'>
                    Maximum
                </div>
                <div class='col_right'>
                    <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_NB ); ?>"
                           type='text'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODELS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $post_type ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_NB ); ?>]'
                           placeholder="Enter the maximum number of suggestions displayed in this group."
                           value="<?php WPSOLR_Escape::echo_esc_attr( $suggestion_model_nb ); ?>">
                    Max number of <?php WPSOLR_Escape::echo_esc_html( $post_label ); ?> in suggestions
                </div>
                <div class="clear"></div>
            </div>

			<?php if ( in_array( $post_type, [ 'product', 'product_variation' ] ) ) { ?>
                <div class="wdm_row wpsolr_collapsed  wpsolr-remove-if-hidden">
                    <div class='col_left'>
                        Add rating
                    </div>
                    <div class='col_right'>
                        <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_RATING ); ?>"
                               type='checkbox'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODELS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $post_type ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_RATING ); ?>]'
                               value='y'
							<?php
							checked( isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model_type ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_RATING ] ) );
							?>
                        >
                        Show <?php WPSOLR_Escape::echo_esc_html( $post_label ); ?> rating
                    </div>
                    <div class="clear"></div>
                </div>
                <div class="wdm_row wpsolr_collapsed  wpsolr-remove-if-hidden">
                    <div class='col_left'>
                        Price
                    </div>
                    <div class='col_right'>
                        <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_PRICE  ); ?>"
                               type='checkbox'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODELS  ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $post_type ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_PRICE); ?>]'
                               value='y'
							<?php
							checked( isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model_type ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_PRICE ] ) );
							?>
                        >
                        Show <?php WPSOLR_Escape::echo_esc_html( $post_label ); ?> price
                    </div>
                    <div class="clear"></div>
                </div>
                <div class="wdm_row wpsolr_collapsed  wpsolr-remove-if-hidden">
                    <div class='col_left'>
                        Add to cart button
                    </div>
                    <div class='col_right'>
                        <input class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_ADD_TO_CART  ); ?>"
                               type='checkbox'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTIONS_SUGGESTIONS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $suggestion_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODELS  ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $post_type ); ?>][<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_ADD_TO_CART); ?>]'
                               value='y'
							<?php
							checked( isset( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ][ $model_type ][ WPSOLR_Option::OPTION_SUGGESTION_MODEL_PRODUCT_IS_SHOW_ADD_TO_CART ] ) );
							?>
                        >
                        Show <?php WPSOLR_Escape::echo_esc_html( $post_label ); ?> "Add to cart" button
                    </div>
                    <div class="clear"></div>
                </div>
			<?php } ?>

        </div>
        <div class="clear"></div>
    </div>

</li>
