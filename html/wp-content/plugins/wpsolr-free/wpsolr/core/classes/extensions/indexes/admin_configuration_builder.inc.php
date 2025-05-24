<?php


use wpsolr\core\classes\engines\configuration\builder\WPSOLR_Configuration_Builder_Factory;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

try {

	$test = WPSOLR_Configuration_Builder_Factory::build_form( $option_name, $option_data, $index_indice, '', '' );

	WPSOLR_Escape::echo_esc_html( $test );

} catch ( Exception $e ) {

	WPSOLR_Escape::echo_esc_html( $e->getMessage() );
}

