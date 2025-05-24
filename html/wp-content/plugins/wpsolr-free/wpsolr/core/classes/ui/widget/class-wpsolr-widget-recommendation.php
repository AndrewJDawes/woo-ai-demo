<?php

namespace wpsolr\core\classes\ui\widget;

use wpsolr\core\classes\exceptions\WPSOLR_Exception_Security;
use wpsolr\core\classes\services\WPSOLR_Service_Container_Factory;
use wpsolr\core\classes\ui\shortcode\WPSOLR_Shortcode_Recommendation;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\extensions\recommendations\WPSOLR_Option_Recommendations;

/**
 * WPSOLR Widget Recommendations.
 *
 * Class WPSOLR_Widget_Recommendation
 * @package wpsolr\core\classes\ui\widget
 */
class WPSOLR_Widget_Recommendation extends WPSOLR_Widget {
	use WPSOLR_Service_Container_Factory;

	// Field storing the recommendation skin
	const FIELD_SKIN_FACET = 'skin_%s';

	// Skin label in the drop-down lists
	const DROP_DOWN_SKIN_LABEL = '%s skin';

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wpsolr_widget_recommendations', // Base ID
			__( 'WPSOLR Recommendations', 'wpsolr-free' ), // Name
			[ 'description' => __( 'Display WPSOLR Recommendations', 'wpsolr-free' ), ] // Args
		);
	}

	/**
	 * Always show
	 *
	 * @return bool
	 */
	public function get_is_show() {
		return true;
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 *
	 * @throws \Exception
	 * @see WP_Widget::widget()
	 *
	 */
	public function widget( $args, $instance ) {

		if ( $this->get_is_show() && ! empty( $instance[ WPSOLR_Option::OPTION_RECOMMENDATION_UUID ] ) ) {

			WPSOLR_Escape::echo_escaped( $args['before_widget'] );;

			try {

				WPSOLR_Escape::echo_escaped( do_shortcode( WPSOLR_Shortcode_Recommendation::get_shortcode_html( $instance[ WPSOLR_Option::OPTION_RECOMMENDATION_UUID ] ?? '', '' ) ) );

			} catch ( WPSOLR_Exception_Security $e ) {

				WPSOLR_Escape::echo_esc_html( $e->getMessage() );
			}

			WPSOLR_Escape::echo_escaped( $args['after_widget'] );
		}

	}

	/**
	 * Back-end widget form.
	 *
	 * @param array $instance Previously saved values from database.
	 *
	 * @see WP_Widget::form()
	 *
	 */
	public function form( $instance ) {
		$current_recommendation_uuid = $instance[ WPSOLR_Option::OPTION_RECOMMENDATION_UUID ] ?? '';
		?>

        <p>
            Choose a Recommendation in the list. Recommendations are created on screen
            <a target="_new"
               href="/wp-admin/admin.php?page=solr_settings&tab=solr_option&subtab=recommendation_opt">2.3
                Recommendations</a>
        </p>

        <p>
            <label for="<?php WPSOLR_Escape::echo_esc_attr( $this->get_field_id( WPSOLR_Option::OPTION_RECOMMENDATION_UUID ) ); ?>">
                Recommendations:
            </label>
            <select id="<?php WPSOLR_Escape::echo_esc_attr( $this->get_field_id( WPSOLR_Option::OPTION_RECOMMENDATION_UUID ) ); ?>"
                    name="<?php WPSOLR_Escape::echo_esc_attr( $this->get_field_name( WPSOLR_Option::OPTION_RECOMMENDATION_UUID ) ); ?>"
            >
				<?php
				foreach ( WPSOLR_Option_Recommendations::get_recommendations( true ) as $recommendation_uuid => $recommendation ) { ?>
                    <option value="<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>"
						<?php selected( $current_recommendation_uuid, $recommendation_uuid ); ?>
                    ><?php WPSOLR_Escape::echo_esc_html( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_LABEL ] ); ?></option>
				<?php } ?>
            </select>
        </p>

		<?php
	}


	/**
	 * Return the instance field name for the facet skin
	 *
	 * @param string $facet_name
	 *
	 * @return string
	 */
	public function get_field_facet_skin( $facet_name ) {
		return sprintf( self::FIELD_SKIN_FACET, $facet_name );
	}

	/**
	 * Retrieve all layout skins already saved
	 *
	 * @param array $instance
	 * @param array $all_layout_skins
	 *
	 * @return array
	 */
	protected function get_instance_layout_skins( $instance, $all_layout_skins = [] ) {

		$current_layout_skins = [];

		foreach ( ( empty( $all_layout_skins ) ? $this->get_all_layout_skins() : $all_layout_skins ) as $layout_id => $layout_skin ) {

			foreach ( $instance as $field_id => $field_value ) {

				if ( isset( $layout_skin[ $field_value ] ) ) {

					if ( empty( $current_layout_skins[ $layout_id ] ) ) {
						$current_layout_skins[ $layout_id ] = [];
					}

					$current_layout_skins[ $layout_id ][] = $field_value;
				}
			}
		}

		return $current_layout_skins;
	}


	/**
	 * Retrieve all facets skin already saved. Use default
	 *
	 * @param array $instance
	 *
	 * @return array
	 */
	protected function get_instance_facets_skin( $instance ) {

		$current_facets_skin = [];

		foreach ( $this->get_container()->get_service_option()->get_facets_to_display() as $facet_name ) {

			$field_facet_skin = $this->get_field_facet_skin( $facet_name );

			if ( ! empty( $instance[ $field_facet_skin ] ) ) {
				$current_facets_skin[ $facet_name ] = $instance[ $field_facet_skin ];
			}
		}

		return $current_facets_skin;
	}

	/**
	 * Retrieve all skin layouts
	 *
	 * @return array
	 */
	protected function get_all_layout_skins() {
		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_LAYOUT_SKINS, [] );
	}


}