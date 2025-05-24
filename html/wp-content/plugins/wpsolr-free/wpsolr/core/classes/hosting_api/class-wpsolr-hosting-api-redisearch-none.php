<?php

namespace wpsolr\core\classes\hosting_api;

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;

class WPSOLR_Hosting_Api_RediSearch_None extends WPSOLR_Hosting_Api_Abstract {

	const HOSTING_API_ID = 'none_redisearch';

	const FIELD_NAME_FIELDS_INDEX_PORT_DEFAULT_REDISEARCH = [
		self::FIELD_NAME_FIELDS_INDEX_PORT => [
			self::FIELD_NAME_LABEL                 => 'Port',
			self::FIELD_NAME_PLACEHOLDER           => '6379 is the default port',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_2_DIGITS,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a valid port',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_INDEX_HOST_DEFAULT_REDISEARCH = [
		self::FIELD_NAME_FIELDS_INDEX_HOST => [
			self::FIELD_NAME_LABEL                 => 'Host',
			self::FIELD_NAME_PLACEHOLDER           => "127.0.0.1 (default), localhost or ip adress or hostname. No 'http', no '/', no ':'",
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a Redis host',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_LABEL_DEFAULT_REDISEARCH = [
		self::FIELD_NAME_FIELDS_INDEX_LABEL => [
			self::FIELD_NAME_LABEL                 => 'RediSearch index name',
			self::FIELD_NAME_PLACEHOLDER           => 'Index name in the RediSearch server, like "my_index".',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a RediSearch index label',
			],
		]
	];

	const FIELD_NAME_FIELDS_INDEX_SECRET_DEFAULT_REDISEARCH = [
		self::FIELD_NAME_FIELDS_INDEX_SECRET => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_LABEL                 => 'Password',
			self::FIELD_NAME_PLACEHOLDER           => 'Optional security if the RediSearch server is password protected',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	/**
	 * @inheritdoc
	 */
	public function get_is_no_hosting() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return sprintf( self::NONE_LABEL, 'RediSearch' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_search_engine() {
		return WPSOLR_AbstractEngineClient::ENGINE_REDISEARCH;
	}

	/**
	 * @inheritdoc
	 */
	public function XXget_url() {
		return 'https://oss.redislabs.com/redisearch/index.html';
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		$result = [
			static::FIELD_NAME_FIELDS_INDEX_LABEL_DEFAULT_REDISEARCH,
			static::FIELD_NAME_FIELDS_INDEX_HOST_DEFAULT_REDISEARCH,
			static::FIELD_NAME_FIELDS_INDEX_PORT_DEFAULT_REDISEARCH,
			//static::FIELD_NAME_FIELDS_INDEX_KEY_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_SECRET_DEFAULT_REDISEARCH,
		];

		return $result;
	}
}