<?php

use wpsolr\core\classes\extensions\premium\WPSOLR_Option_Premium;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;

class WPSOLR_Autocomplete {
	public function __construct() {

		add_action( 'wp_ajax_' . WPSOLR_AJAX_AUTO_COMPLETE_ACTION, [ $this, 'call' ] );
		add_action( 'wp_ajax_nopriv_' . WPSOLR_AJAX_AUTO_COMPLETE_ACTION, [ $this, 'call' ] );
	}

	function call() {

		if (
			( isset( $_POST['security'] ) && wp_verify_nonce(
					WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'security' ] ), 'nonce_for_autocomplete' ) )
			|| WPSOLR_Service_Container::getOption()->get_search_is_no_ajax_nonce_verification_front_end()
		) {

			$input           = isset( $_POST['word'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'word' ] ) : '';
			$suggestion_uuid = isset( $_POST['suggestion_uuid'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'suggestion_uuid' ] ) : '';
			$is_search_admin = WPSOLR_Option_Premium::get_is_search_admin();
			$lang            = isset( $_POST['lang'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'lang' ] ) : '';

			$view_uuid = isset( $_POST['view_uuid'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'view_uuid' ] ) : '';
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

			if ( '' != $input ) {

				if ( ! empty( $lang ) ) {
					do_action( WPSOLR_Events::WPSOLR_ACTION_SET_CURRENT_LANGUAGE, $lang );
				}

				// No  lower case anymore, because it breaks Solr Edismax 'NOT' syntax
				// $input = strtolower( $input );

				$nb_max_retries = 1;
				for ( $i = 0; $i <= $nb_max_retries; $i ++ ) {

					try {

						//$suggestion_uuid = '???';
						$html = $this->wpsolr_json_encode_suggestions( WPSOLR_Service_Container::get_solr_client()->get_suggestions_html( $suggestion_uuid, $input, $is_search_admin ) );
						WPSOLR_Escape::echo_escaped( $html );
						break;

					} catch ( Exception $e ) {

						if ( $i >= $nb_max_retries ) {
							// No more retries

							WPSOLR_Escape::echo_escaped(
								$this->wpsolr_json_encode_suggestions( sprintf( '<li>Error while searching for "%s": %s</li>',
										WPSOLR_Escape::esc_html( $input ), WPSOLR_Escape::esc_html( $e->getMessage() ) )
								)
							);
							break;

						} else {
							// Retry.
							$t = 1;
						}

					}
				}
			}

		}

		die();
	}


	/**
	 * Encode $html for ahead js library
	 *
	 * @param $html
	 *
	 * @return string
	 */
	function wpsolr_json_encode_suggestions( $html ) {

		return json_encode(
			[
				[
					'url'  => '',
					'html' => $html,
				]
			]
		);
	}

}

( new WPSOLR_Autocomplete() );
