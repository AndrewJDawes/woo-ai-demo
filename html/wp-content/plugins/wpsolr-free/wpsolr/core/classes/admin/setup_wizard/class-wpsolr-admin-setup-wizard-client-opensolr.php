<?php

namespace wpsolr\core\classes\admin\setup_wizard;

use WP_Error;

class WPSOLR_Admin_Setup_Wizard_Client_Opensolr extends WPSOLR_Admin_Setup_Wizard_Client_Abstract {

	protected function _create_free_account(): void {
		$response = wp_remote_get(
			sprintf( 'https://opensolr.com/users/send_email_code/api?email=%s', $this->_get_ajax_email() ),
			[ 'timeout' => 60 ]
		);

		if ( $response instanceof WP_Error ) {
			$response_message = $response->get_error_message();
		} else {
			$response_message = $response['body'];
		}

		if ( ! str_contains( $response_message, sprintf( 'CODE_SENT_TO:%s', $this->_get_ajax_email() ) ) ) {
			$message = $response_message;
			if ( str_contains( $response_message, 'SYSTEM_ERROR:EMAIL_IS_TAKEN' ) ) {
				$message = 'Email already exists.';
			} else if ( str_contains( $response_message, 'SYSTEM_ERROR:CAN_NOT_VALIDATE_SO_MANY_EMAILS_SO_SOON_WAIT' ) ) {
				$message = 'Cannot validate so many emails so soon wait.';
			} else if ( str_contains( $response_message, 'SYSTEM_ERROR:CAN_NOT_RESEND_SO_FAST_WAIT' ) ) {
				$message = 'Cannot resend so fast. Please retry later.';
			}

			throw new \Exception( esc_html( $message ) );
		}

	}

	protected function _confirm_free_account(): void {
		$response = wp_remote_get(
			sprintf( 'https://opensolr.com/users/create_user/api?email=%s&code=%s', $this->_get_ajax_email(), $this->_get_ajax_account_confirmation() ),
			[ 'timeout' => 60 ]
		);

		if ( $response instanceof WP_Error ) {
			$response_message = $response->get_error_message();
		} else {
			$response_message = $response['body'];
		}

		if ( ! str_contains( $response_message, 'API_KEY:' ) ) {
			$message = $response_message;
			if ( str_contains( $response_message, 'SYSTEM_ERROR:INVALID_CODE_FOR_EMAIL' ) ) {
				$message = 'Invalid code or email.';
			} else if ( str_contains( $response_message, 'SYSTEM_ERROR:EMAIL_TOO_LONG' ) ) {
				$message = 'Email too long (> 220 chars).';
			} else if ( str_contains( $response_message, 'SYSTEM_ERROR:CODE_TOO_LONG' ) ) {
				$message = 'Code too long (> 220 chars).';
			} else if ( str_contains( $response_message, 'SYSTEM_ERROR:USER_ALREADY_EXISTS' ) ) {
				$message = 'User already exists.';
			}

			throw new \Exception( esc_html( $message ) );
		}

		if ( empty( $api_key = trim( explode( ':', $response_message )[1] ) ) ) {
			throw new \Exception( 'The api_key is empty.' );
		}

		$this->_set_ajax_data_api_key( $api_key );
	}

	protected function _create_index_config( array $index_data ): array {
		return [
			'index_engine'     => $index_data['index_engine'],
			'index_label'      => $index_data['index_label'],
			'scheme'           => $index_data['index_protocol'],
			'host'             => $index_data['index_host'],
			'port'             => $index_data['index_port'],
			'path'             => $index_data['index_path'],
			'username'         => $index_data['index_key'],
			'password'         => $index_data['index_secret'],
			'timeout'          => '30',
			'extra_parameters' => [
				'index_email'          => $index_data['index_email'],
				'index_api_key'        => $index_data['index_api_key'],
				'index_hosting_api_id' => $index_data['index_hosting_api_id'],
				'index_region_id'      => $index_data['index_region_id'],
				'index_client_adapter' => $index_data['index_client_adapter'],
				'index_analyser_id'    => 'English',
			],
		];
	}

	protected function _manage_index_ping_errors( \Exception $e ): void {
		if ( str_contains( $e->getMessage(), 'ERROR_CANNOT_ADD_MORE_THAN_1_CORES' ) ) {
			throw new \Exception( 'You can only create one free index. Please upgrade your Opensolr account to get more resources (indices, disk).' );
		}
		throw $e;
	}
}
