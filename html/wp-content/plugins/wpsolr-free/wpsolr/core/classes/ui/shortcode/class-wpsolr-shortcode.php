<?php

namespace wpsolr\core\classes\ui\shortcode;

use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

/**
 * Class WPSOLR_Shortcode
 */
class WPSOLR_Shortcode {

	const SHORTCODE_NAME = 'to be defined in children';

	/**
	 * Load all shorcode classes in this very directory.
	 */
	public static function Autoload() {


		// Loop on all widgets
		foreach (
			[
				WPSOLR_Shortcode_Facet::class,
				//WPSOLR_Shortcode_Sort::class,
				WPSOLR_Shortcode_Recommendation::class,
			] as $shortcode_class_name
		) {

			add_shortcode( $shortcode_class_name::SHORTCODE_NAME, array(
				$shortcode_class_name,
				'get_html'
			) );
		}

		add_action( 'manage_posts_extra_tablenav', [ static::class, 'manage_posts_extra_tablenav' ], 10, 1 );
		add_action( 'woocommerce_order_list_table_extra_tablenav', [
			static::class,
			'woocommerce_order_list_table_extra_tablenav'
		], 10, 2 );

	}


	/**
	 * Add facets shortcode to admin post type pages
	 *
	 * @param string $which
	 *
	 * @return void
	 */
	static function manage_posts_extra_tablenav( $which ): void {
		global $pagenow, $wp_query;

		if ( ( 'top' === $which ) && is_admin() && ( $pagenow == 'edit.php' ) &&
		     ( WPSOLR_Query::class === get_class( $wp_query ) )
		) {
			static::_show_shortcode_html();
		}
	}

	/**
	 * Add facets shortcode to admin HPOS order pages
	 *
	 * @param string $order_type
	 * @param string $which
	 *
	 * @return void
	 */
	static function woocommerce_order_list_table_extra_tablenav( $order_type, $which ) {
		global $pagenow, $wp_query;

		if ( ( 'top' === $which ) && is_admin() && ( $pagenow == 'admin.php' ) &&
		     ( WPSOLR_Query::class === get_class( $wp_query ) )
		) {
			static::_show_shortcode_html();
		}
	}

	protected static function _show_shortcode_html() {
		?>
        <style>
            .wpsolr_group_facets {
                width: 100%;
                float: left;
                margin: 10px 0 10px 0;
            }

            <!--
            .Xwpsolr_group_facets ul {
                width: 20%;
            }

            -->
        </style>
		<?php
		WPSOLR_Escape::echo_escaped( do_shortcode( '[wpsolr_facet]' ) );
	}
}