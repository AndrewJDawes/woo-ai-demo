<?php

/**
 * Plugin Name: WPSOLR Fast Mode
 * Description: Fast Mode allows you to speed up Ajax suggestions, by unloading the theme or any other plugin.
 * Plugin URI: https://www.wpsolr.com
 * Author: wpsolr.com
 * Version: 1.0.0
 * Author URI: https://www.wpsolr.com
 *
 * Text Domain: wpsolr
 *
 * @package WPSOLR
 * @category Fast Mode
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPSOLR_Fast_Mode {

	public function add_hooks() {
		add_filter( 'pre_option_active_plugins', function () {
			return [
				'woocommerce/woocommerce.php',
				'wpsolr-free/wpsolr-free.php',
				'wpsolr-pro/wpsolr-pro.php',
				'wpsolr-enterprise/wpsolr-enterprise.php',

			];
		} );

	}

	public function __construct() {

		if (
			! (
				( defined( 'DOING_AJAX' ) && DOING_AJAX ) && // Ajax
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				( isset( $_POST['action'] ) && ( 'wdm_return_solr_rows' === $_POST['action'] ) )   // Not possible to sanitize: sanitize functions are not loaded yet
			)
		) {
			return;
		}

		$this->add_hooks();
	}
}

new WPSOLR_Fast_Mode();
