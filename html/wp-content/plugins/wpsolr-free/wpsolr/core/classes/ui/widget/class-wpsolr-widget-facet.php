<?php

namespace wpsolr\core\classes\ui\widget;

use wpsolr\core\classes\exceptions\WPSOLR_Exception_Security;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\services\WPSOLR_Service_Container_Factory;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * WPSOLR Widget Facets.
 *
 * Class WPSOLR_Widget_Facet
 * @package wpsolr\core\classes\ui\widget
 */
class WPSOLR_Widget_Facet extends WPSOLR_Widget {
	use WPSOLR_Service_Container_Factory;

	// Field storing the facet skin
	const FIELD_SKIN_FACET = 'skin_%s';

	// Skin label in the drop-down lists
	const DROP_DOWN_SKIN_LABEL = '%s skin';

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wpsolr_widget_facets', // Base ID
			__( 'WPSOLR Facets', 'wpsolr-free' ), // Name
			[ 'description' => __( 'Display WPSOLR Facets', 'wpsolr-free' ), ] // Args
		);
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

		if ( $this->get_is_show() ) {

			WPSOLR_Escape::echo_escaped( $args['before_widget'] );

			try {

				$results = WPSOLR_Service_Container::get_solr_client()->get_results_data(
					WPSOLR_Service_Container::get_query(),
					[ 'facets_skins' => $this->get_instance_facets_skin( $instance ) ]
				);

				WPSOLR_Escape::echo_escaped( WPSOLR_Service_Container::get_template_builder()->load_template_facets( $results['facets'] ) );

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
		global $license_manager;

		$facets           = $this->get_container()->get_service_option()->get_facets_to_display();
		$facets_labels    = $this->get_container()->get_service_option()->get_facets_labels();
		$all_layout_skins = $this->get_all_layout_skins();
        $all_generic_scripts = $this->get_all_generic_scripts();
		?>

        <p>
            Facets are dynamic filters users can click on to filter search results, like categories, or tags. Facets
            must have been defined in WPSOLR admin pages.
        </p>

		<?php foreach ( $facets as $facet_name_with_str ) {
			$field_facet_skin   = $this->get_field_facet_skin( $facet_name_with_str );
			$facet_layout_id    = $this->get_container()->get_service_option()->get_facets_layout_id( $facet_name_with_str );
			$facet_layout_skins = ( ! empty( $all_layout_skins ) && ! empty( $all_layout_skins[ $facet_layout_id ] ) ) ? $all_layout_skins[ $facet_layout_id ] : [];
            $facet_generic_scripts = ( ! empty( $all_generic_scripts ) && ! empty( $all_generic_scripts[ $facet_layout_id ] ) ) ? $all_generic_scripts[ $facet_layout_id ] : [];
			?>
            <p>
                <label for="<?php WPSOLR_Escape::echo_esc_attr( $this->get_field_id( $field_facet_skin ) ); ?>">
					<?php WPSOLR_Escape::echo_esc_html( ! empty( $facets_labels[ $facet_name_with_str ] ) ? $facets_labels[ $facet_name_with_str ] : $facet_name_with_str ); ?>
                    :
                </label>
                <select id="<?php WPSOLR_Escape::echo_esc_attr( $this->get_field_id( $field_facet_skin ) ); ?>"
                        name="<?php WPSOLR_Escape::echo_esc_attr( $this->get_field_name( $field_facet_skin ) ); ?>">
					<?php
					$facet_layout_skins = [ '' => [ 'label' => 'Use the skin selected on the facet' ] ] + [ 'wpsolr_no_skin' => [ 'label' => 'Invisible' ] ] + $facet_layout_skins;
					foreach ( $facet_layout_skins as $skin_id => $skin ) { ?>
                        <option value="<?php WPSOLR_Escape::echo_esc_attr( $skin_id ); ?>" <?php selected( ! empty( $instance[ $field_facet_skin ] ) ? $instance[ $field_facet_skin ] : '', $skin_id ); ?>>
							<?php WPSOLR_Escape::echo_escaped( sprintf( ( empty( $skin_id ) || ( 'wpsolr_no_skin' === $skin_id ) ) ? '%s' : self::DROP_DOWN_SKIN_LABEL, WPSOLR_Escape::esc_html( $skin['label'] ) ) ); ?>
                        </option>
					<?php } ?>
                </select>
            </p>

		<?php } ?>
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

    /**
     * Retrieve all generic scripts
     *
     * @return array
     */
    protected function get_all_generic_scripts() {
        return apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_GENERIC_SCRIPTS, [] );
    }


}