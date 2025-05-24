<?php

namespace wpsolr\core\classes\admin\ui\ajax;


use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

/**
 * Retrieve content of a media by the post id
 *
 * Class WPSOLR_Admin_UI_Ajax_Media_PostId_Content_Get
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Media_PostId_Content_Get extends WPSOLR_Admin_UI_Ajax_Search {

	const PARAMETER_POST_ID = 'post_id';

	/**
	 * @inheritDoc
	 */
	public static function extract_parameters() {

		check_ajax_referer( 'security', 'security' ); // Redundant to pass plugin-check

		$parameters = array(
			self::PARAMETER_POST_ID => WPSOLR_Sanitize::sanitize_text_field( $_GET, [ self::PARAMETER_POST_ID ], '' ),
		);

		return $parameters;
	}

	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		$results = [];

		if ( is_numeric( $parameters[ self::PARAMETER_POST_ID ] ) ) {
			// A media post id
			$file = get_attached_file( $parameters[ self::PARAMETER_POST_ID ] );

		} else {
			// A file full path
			$file = $parameters[ self::PARAMETER_POST_ID ];
		}


		$file_contents = file_get_contents( $file );
		if ( false === $file_contents ) {

			$results[] = [
				'id'    => self::ID_ERROR,
				'label' => "No file '{$parameters[ self::PARAMETER_POST_ID ]}' in media library or in predefined files.",
			];

		} else {

			$results[] = [
				'id'    => basename( $file ),
				'label' => $file_contents,
			];
		}

		return $results;
	}

}