<?php

namespace wpsolr\core\classes\admin\ui\ajax;


use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

/**
 * Retrieve content of an index file content, on the server, by the index uuid
 *
 */
class WPSOLR_Admin_UI_Ajax_Index_Server_File_Deploy extends WPSOLR_Admin_UI_Ajax_Index_Server_File_Get {

	const PARAMETER_FILE_CONTENT = 'file_content';

	/**
	 * @inheritDoc
	 */
	public static function extract_parameters() {

		check_ajax_referer( 'security', 'security' ); // Redundant to pass plugin-check

		$parameters = parent::extract_parameters();

		$parameters[ self::PARAMETER_FILE_CONTENT ] = str_replace( '\"', '"',
			WPSOLR_Sanitize::sanitized( $_POST, [ self::PARAMETER_FILE_CONTENT ], '' ) ); // XML cannot be sanitized!?

		return $parameters;
	}

	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		static::_prepare_data( $parameters );

		if ( empty( $file_content = $parameters[ self::PARAMETER_FILE_CONTENT ] ?? '' ) ) {
			throw new \Exception( 'The file is empty, and cannot be deployed.' );
		}

		static::$index_client->deploy_server_file_content( static::$file_name, $file_content );

		return [
			[
				'id'    => static::$file_name,
				'label' => 'Success: file was deployed.',
			],
		];

	}

}