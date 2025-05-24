<?php

namespace wpsolr\core\classes\hosting_api;

use wpsolr\core\classes\engines\solarium\admin\WPSOLR_Solr_Admin_Api_Opensolr;
use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;

class WPSOLR_Hosting_Api_Solr_Opensolr_Free extends WPSOLR_Hosting_Api_Abstract {

	const HOSTING_API_ID = 'opensolr_free';

	public function is_show_create(): bool {
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function get_data_by_id( $label, $id, $default, $parameters = [] ) {

		switch ( $label ) {
			case self::DATA_HOST_BY_REGION_ID:

				$result = '';
				break;

			case self::DATA_PORT:

				$result = '443';
				break;

			case self::DATA_PATH:

				$result = sprintf( '/solr/%s', $id );
				break;

			case self::DATA_SCHEME:

				$result = 'https';
				break;

			case self::DATA_REGION_LABEL_BY_REGION_ID:

				$hosting_admin_api = new WPSOLR_Solr_Admin_Api_Opensolr( [
					'extra_parameters' => [
						'index_email'   => $parameters['email'],
						'index_api_key' => $parameters['api_key']
					]
				], null );

				$regions = $hosting_admin_api->get_environments();

				if ( ! ( $key = array_search( $id, array_column( $regions, 'id' ) ) ) ) {
					throw new \Exception( sprintf( 'Unknown region %s', esc_html( $id ) ) );
				}

				$result = $regions[ $key ]['label'];
				break;

			default:
				$result = $default;
				break;
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return 'My opensolr.com free account';
	}

	/**
	 * @inheritdoc
	 */
	public function get_url() {
		return 'https://opensolr.com';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/configure-your-indexes/create-an-opensolr-solr-index/';
	}

	/**
	 * @inheritdoc
	 */
	public function get_credentials() {
		return [
			[ 'id' => 'email', 'label' => 'E-mail', 'type' => 'edit' ],
			[ 'id' => 'api_key', 'label' => 'API key', 'type' => 'password' ],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function get_search_engine() {
		return WPSOLR_AbstractEngineClient::ENGINE_SOLR;
	}

	/**
	 * @inheritdoc
	 */
	protected function new_solr_admin_api( $extra_parameters, $search_engine_client ) {

		return new WPSOLR_Solr_Admin_Api_Opensolr( $extra_parameters, $search_engine_client );
	}

	/**
	 * @inheritdoc
	 */
	public function get_host( $host ) {
		return '443';
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		$result = [
			static::FIELD_NAME_FIELDS_INDEX_LABEL_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_CLIENT_ADAPTER_DEFAULT,
			[
				self::FIELD_NAME_FIELDS_INDEX_EMAIL => [
					self::FIELD_NAME_LABEL                 => 'E-mail',
					self::FIELD_NAME_PLACEHOLDER           => 'Your account E-mail',
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_READONLY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your E-mail',
					],
					self::FIELD_NAME_FORMAT_IS_UPDATE_ONLY => true,
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
				]
			],
			[
				self::FIELD_NAME_FIELDS_INDEX_API_KEY => [
					self::FIELD_NAME_LABEL                 => 'API key',
					self::FIELD_NAME_PLACEHOLDER           => 'Your account API key',
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_READONLY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your API key',
					],
					self::FIELD_NAME_FORMAT_IS_UPDATE_ONLY => true,
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
				]
			],
			static::FIELD_NAME_FIELDS_INDEX_REGION_ID_DEFAULT,
			[
				self::FIELD_NAME_FIELDS_INDEX_KEY => [
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => 'Set a security user to protect the index with Http Basic Authentication',
					],
					self::FIELD_NAME_LABEL                 => 'Key',
					self::FIELD_NAME_PLACEHOLDER           => 'Security user to protect the index with Http Basic Authentication',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
				],
			],
			[
				self::FIELD_NAME_FIELDS_INDEX_SECRET => [
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => 'Set a security password to protect the index with Http Basic Authentication',
					],
					self::FIELD_NAME_LABEL                 => 'Secret/Password',
					self::FIELD_NAME_PLACEHOLDER           => 'Security password to protect the index with Http Basic Authentication',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
				],
			],
		];

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function get_is_host_contains_user_password() {
		return false; // Opensolr does not require it anymore (it used too)!
	}
}
