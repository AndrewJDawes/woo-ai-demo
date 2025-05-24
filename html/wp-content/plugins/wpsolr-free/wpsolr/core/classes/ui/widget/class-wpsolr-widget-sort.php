<?php

namespace wpsolr\core\classes\ui\widget;

use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

/**
 * WPSOLR Widget Sort.
 *
 * Class WPSOLR_Widget_Sort
 * @package wpsolr\core\classes\ui\widget
 */
class WPSOLR_Widget_Sort extends WPSOLR_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wpsolr_widget_sort', // Base ID
			__( 'WPSOLR Sort list', 'wpsolr-free' ), // Name
			[ 'description' => __( 'Display WPSOLR drop-down sort list', 'wpsolr-free' ), ] // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 *
	 * @see WP_Widget::widget()
	 *
	 */
	public function widget( $args, $instance ) {

		if ( $this->get_is_show() ) {

			WPSOLR_Escape::esc_html( $args['before_widget'] );;

			$results = WPSOLR_Service_Container::get_solr_client()->get_results_data( WPSOLR_Service_Container::get_query() );

			WPSOLR_Escape::echo_escaped( WPSOLR_Service_Container::get_template_builder()->load_template_sort( $results['sort'] ) );

			WPSOLR_Escape::esc_html( $args['after_widget'] );
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
		?>
        <p>
            Position this widget where you want your sort list to appear.
        </p>
        <p>
            Use the sort list to sort your results, with pretty much any field in your post types. Sort items must have
            been defined in WPSOLR admin pages.
        </p>
        <p>
            In next releases of WPSOLR, you will be able to configure your widget layout, to match your theme layout.
        </p>

		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 * @see WP_Widget::update()
	 *
	 */
	/*
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}*/

}