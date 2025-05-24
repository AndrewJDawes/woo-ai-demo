<?php

// Definitions
use wpsolr\core\classes\ui\shortcode\WPSOLR_Shortcode_Recommendation;

define( 'WPSOLR_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'WPSOLR_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSOLR_PLUGIN_DIR_IMAGE_URL', WPSOLR_PLUGIN_DIR_URL . 'wpsolr/core/images/' );
define( 'WPSOLR_PLUGIN_DIR_IMAGE_CSS', WPSOLR_PLUGIN_DIR_URL . 'wpsolr/core/css/' );


require_once( 'wpsolr/core/wpsolr_include.inc.php' );

function wpsolr_add_js_global_error() {
// Store js errors in a global used by Selenium tests
	if ( true ) {
		?>
        <script>
            wpsolr_globalError = [];
            window.onerror = function (msg, url, line, col, error) {
                wpsolr_globalError.push({msg: msg, url: url, line: line, error: error});
            };
        </script>
		<?php
	}
}

add_action( 'admin_head', 'wpsolr_add_js_global_error' );
add_action( 'wp_head', 'wpsolr_add_js_global_error' );

add_action( 'init', function () {
	WPSOLR_Shortcode_Recommendation::init_ajax();
} );
