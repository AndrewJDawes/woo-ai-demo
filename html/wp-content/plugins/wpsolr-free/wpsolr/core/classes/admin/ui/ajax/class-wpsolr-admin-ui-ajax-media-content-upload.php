<?php

namespace wpsolr\core\classes\admin\ui\ajax;


use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

/**
 * Upload a file content to the media library
 *
 * Class WPSOLR_Admin_UI_Ajax_Media_Content_Upload
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Media_Content_Upload extends WPSOLR_Admin_UI_Ajax_Search {

	const PARAMETER_POST_ID = 'post_id';
	const PARAMETER_POST_TITLE = 'post_title';
	const PARAMETER_POST_CONTENT = 'post_content';

	/**
	 * @inheritDoc
	 */
	public static function extract_parameters() {

		check_ajax_referer( 'security', 'security' ); // Redundant to pass plugin-check

		$parameters = array(
			self::PARAMETER_POST_ID      => WPSOLR_Sanitize::sanitize_text_field( $_GET, [ self::PARAMETER_POST_ID ], '' ),
			self::PARAMETER_POST_TITLE   => WPSOLR_Sanitize::sanitize_text_field( $_GET, [ self::PARAMETER_POST_TITLE ], '' ),
			self::PARAMETER_POST_CONTENT => WPSOLR_Sanitize::sanitize_text_field( $_GET, [ self::PARAMETER_POST_CONTENT ], '' ),
		);

		return $parameters;
	}

	/**
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		$results = [];

		$file = '';
		if ( ! empty( $parameters[ self::PARAMETER_POST_ID ] ) ) {

			$current_file = get_attached_file( $parameters[ self::PARAMETER_POST_ID ] );

			if ( basename( $current_file ) === $parameters[ self::PARAMETER_POST_TITLE ] ) {
				// New file name is current file name: use current file name and replace it's content
				$file = $current_file;
			}

		}


		if ( empty( $file ) ) {
			// Create a unique file name

			$temp_dir = wp_upload_dir();
			$file     = $temp_dir['path'] . '/' . wp_unique_filename( $temp_dir['path'], $parameters[ self::PARAMETER_POST_TITLE ] ); // Build unique name


			$new_post_id = wp_insert_attachment( [
				'guid'           => $file,
				'post_mime_type' => 'text/plain',
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $parameters[ self::PARAMETER_POST_TITLE ] ),
				'post_content'   => $parameters[ self::PARAMETER_POST_CONTENT ],
				'post_status'    => 'inherit'
			], $file );

			$res = wp_update_attachment_metadata( $new_post_id, wp_generate_attachment_metadata( $new_post_id, $file ) );

		}

		// Replace or create file content
		$nb_bytes_written = file_put_contents( $file, $parameters[ self::PARAMETER_POST_CONTENT ] );

		if ( false === $nb_bytes_written ) {

			$results[] = [
				'id'    => self::ID_ERROR,
				'label' => "Could not write in file '{$$file}'.",
			];

		} else {

			// Just to be sure content has been really updated :  reload it from the file itself
			$file_contents = file_get_contents( $file );

			$results[] = [
				'id'      => basename( $file ),
				'label'   => $file_contents,
				'post_id' => empty( $new_post_id ) ? $parameters[ self::PARAMETER_POST_ID ] : $new_post_id,
			];

		}

		return $results;
	}

}