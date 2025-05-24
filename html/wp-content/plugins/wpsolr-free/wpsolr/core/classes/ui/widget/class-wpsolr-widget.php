<?php

namespace wpsolr\core\classes\ui\widget;

use wpsolr\core\classes\services\WPSOLR_Service_Container;

/**
 * Top level widget class from which all WPSOLR widgets inherit.
 * Class WPSOLR_Widget
 * @package wpsolr\core\classes\ui\widget
 */
class WPSOLR_Widget extends \WP_Widget {

	/**
	 * Load all widget classes in this very directory.
	 */
	public static function Autoload() {

		add_action( 'widgets_init', function () {

			// Loop on all widgets
			foreach (
				[
					WPSOLR_Widget_Facet::class,
					WPSOLR_Widget_Sort::class,
					WPSOLR_Widget_Recommendation::class,
				] as $widget_class_name
			) {

				if ( ! is_null( $widget_class_name ) ) { // Register widget
					register_widget( $widget_class_name );
				}
			}

		} );
	}

	/**
	 * Show ?
	 *
	 * @return bool
	 */
	public function get_is_show() {

		return WPSOLR_Service_Container::action_wp_loaded();
	}

}