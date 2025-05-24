<?php

namespace wpsolr\core\classes\admin\ui\ajax;


use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient_Root;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

/**
 * Retrieve content of an index file content, on the server, by the index uuid
 *
 */
class WPSOLR_Admin_UI_Ajax_Index_Server_File_Get extends WPSOLR_Admin_UI_Ajax_Search {

	const PARAMETER_INDEX_UUID = 'index_uuid';
	const PARAMETER_FILE_NAME = 'file_name';
	protected static WPSOLR_AbstractIndexClient_Root $index_client;
	protected static string $file_name;


	/**
	 * @inheritDoc
	 */
	public static function extract_parameters() {

		check_ajax_referer( 'security', 'security' ); // Redundant to pass plugin-check

		$parameters = [
			self::PARAMETER_FILE_NAME  => WPSOLR_Sanitize::sanitize_text_field( $_POST, [ self::PARAMETER_FILE_NAME ] ) ?? '',
			self::PARAMETER_INDEX_UUID => WPSOLR_Sanitize::sanitize_text_field( $_POST, [ self::PARAMETER_INDEX_UUID ] ) ?? '',
		];

		return $parameters;
	}

	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		static::_prepare_data( $parameters );

		$file_content = static::$index_client->get_server_file_content( static::$file_name );

		return [
			[
				'id'    => static::$file_name,
				'label' => $file_content,
			],
		];
	}

	/**
	 * @param array $parameters
	 *
	 * @throws \Exception
	 */
	protected static function _prepare_data( array $parameters ) {
		$index_uuid     = $parameters[ self::PARAMETER_INDEX_UUID ] ?? '';
		$option_indexes = new WPSOLR_Option_Indexes();
		if ( empty( $index_uuid ) || empty( $index = $option_indexes->get_index( $index_uuid ) ) ) {
			throw new \Exception( 'This index settings do not seem to exist in WPSOLR admin.' );
		}

		if ( empty( static::$file_name = $parameters[ self::PARAMETER_FILE_NAME ] ?? '' ) ) {
			throw new \Exception( 'The file name is missing.' );
		}

		static::$index_client = WPSOLR_AbstractIndexClient::create( $index_uuid );
	}

}