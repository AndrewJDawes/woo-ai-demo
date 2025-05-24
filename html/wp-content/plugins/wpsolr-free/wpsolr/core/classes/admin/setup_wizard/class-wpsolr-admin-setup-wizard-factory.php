<?php

namespace wpsolr\core\classes\admin\setup_wizard;

use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

class WPSOLR_Admin_Setup_Wizard_Factory {

	const STEP_HOME = 'step_home';
	const STEP_ACCOUNT_CREATE = 'step_account_create';
	const STEP_ACCOUNT_CONFIRM = 'step_account_confirm';
	const STEP_END = 'step_end';

	public function __construct() {

		add_action( 'wp_ajax_' . WPSOLR_AJAX_ADMIN_SETUP_WIZARD, [ $this, 'call' ] );
	}

	public static function get_url_step_home(): string {
		return static::get_url_step( static::STEP_HOME );
	}

	public static function get_url_step( string $step ): string {
		return sprintf( '?page=solr_settings&path=setup_wizard&step=%s', $step );
	}

	public static function get_url_hosting_step( string $hosting, string $step ): string {
		return sprintf( '?page=solr_settings&path=setup_wizard&hosting=%s&step=%s', $hosting, $step );
	}

	function call(): void {

		$result = $this->_generate_ajax_result( 'OK', 'NO_ERROR', 'Step processed.' );

		try {
			$data = WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'data' ] ?? [] );
			if ( isset( $data['security'] ) && wp_verify_nonce( $data['security'], WPSOLR_NONCE_FOR_DASHBOARD ) ) {


				$hosting        = $data['hosting'] ?? '';
				$hosting_client = $this->_get_hosting_client( $hosting );
				$hosting_client->manage_step( $data );

			} else {
				// Nonce error
				$result = $this->_generate_ajax_result( 'ERROR', 'WRONG_NONCE', 'The nonce verification failed.' );
			}
		} catch ( \Exception $e ) {
			$result = $this->_generate_ajax_result( 'ERROR', 'ERROR', $e->getMessage() );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die( WPSOLR_Escape::esc_json( wp_json_encode( $result ) ) );
		// phpcs:enable
	}

	protected function _generate_ajax_result( string $state, string $code, string $message ): array {
		$result = [
			'status' => [
				'state'   => $state,
				'code'    => $code,
				'message' => $message,
			],
		];

		return $result;
	}

	public static function get_setup_wizard_settings(): array {
		return [
			'opensolr' => [
				'is_active'   => true,
				'name'        => 'Opensolr',
				'search_type' => 'Keyword search',
				'url'         => 'https://opensolr.com/pricing',
				'steps'       => [
					WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CREATE  => [
						'processing_label' => 'Your Opensolr free index creation',
						'step_next'        => WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CONFIRM,
					],
					WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CONFIRM => [
						'processing_label' => 'Confirming your Opensolr free index',
						'step_next'        => WPSOLR_Admin_Setup_Wizard_Factory::STEP_END,
					],
				],
			],
			'weaviate' => [
				'is_active'   => false,
				'name'        => 'Weaviate',
				'search_type' => 'AI search (soon)',
				'url'         => 'https://weaviate.io/services/serverless',
				'steps'       => [
					WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CREATE  => [
						'processing_label' => 'Your Weaviate free index creation',
						'step_next'        => WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CONFIRM,
					],
					WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CONFIRM => [
						'processing_label' => 'Confirming your Weaviate free index',
						'step_next'        => WPSOLR_Admin_Setup_Wizard_Factory::STEP_END,
					],
				],
			],
		];
	}

	/**
	 * @return WPSOLR_Admin_Setup_Wizard_Client_Abstract
	 * @throws \Exception
	 */
	protected function _get_hosting_client( $hosting ): WPSOLR_Admin_Setup_Wizard_Client_Abstract {
		switch ( $hosting ) {
			case 'opensolr':
				return new WPSOLR_Admin_Setup_Wizard_Client_Opensolr();

			default:
				throw new \Exception( sprintf( 'Hosting "%s" is not found.', esc_html( $data['hosting'] ) ) );
		}
	}
}

( new WPSOLR_Admin_Setup_Wizard_Factory() );