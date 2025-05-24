<?php

namespace wpsolr\core\classes\utilities;

use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

trait WPSOLR_Post_Type_Image {

	/**
	 * Send files as url or content
	 */
	static protected $FILE_SEND_AS_URL = 'source';
	static protected $FILE_SEND_AS_CONTENT = 'content';

	/**
	 * @param string $image_src
	 *
	 * @return bool
	 */
	abstract protected function _get_is_image_format_supported( $image_src );

	/**
	 * @param string $send_file_as
	 * @param string $url
	 *
	 * @return array
	 */
	abstract protected function _get_file_content_or_source( $send_file_as, $url );

	/**
	 * Extract all images from a document before an update: featured, embedded ...
	 *
	 * @param array $document_for_update
	 *
	 * @return array
	 */
	protected function _extract_images_from_document( $document_for_update, $params ) {

		$images = [];

		if ( $params['is_attachment_image'] &&
		     ( $image_src = wp_get_attachment_image_src( $document_for_update[ WpSolrSchema::_FIELD_NAME_PID ], 'full' ) ) ) {
			// Attachment
			if ( $this->_get_is_image_format_supported( $image_src[0] ) ) {
				try {
					$images[] = $this->_get_file_content_or_source( $params['internal_image_mode'], $image_src[0] );
				} catch ( \Exception $e ) {
					// Skip error, continue
				}
			}

		} else {
			// Extract images from the post type

			// Featured image
			if ( $params['is_featured_image'] &&
			     ( $image_src = wp_get_attachment_image_src( get_post_thumbnail_id( $document_for_update[ WpSolrSchema::_FIELD_NAME_PID ] ), 'full' ) ) ) {
				if ( $this->_get_is_image_format_supported( $image_src[0] ) ) {
					try {
						$images[] = $this->_get_file_content_or_source( $params['internal_image_mode'], $image_src[0] );
					} catch ( \Exception $e ) {
						// Skip error, continue
					}
				}
			}

			// Images embedded in HTML
			// Use post content, because it is not stripped
			if ( ( $params['is_embedded_image'] &&
			       ( $post = get_post( $document_for_update[ WpSolrSchema::_FIELD_NAME_PID ] ) ) &&
			       preg_match_all( '/src="([^"]*)"/', $post->post_content, $matches ) ) ) {
				foreach ( $matches[1] as $image_src ) {
					if ( $this->_get_is_image_format_supported( $image_src ) ) {
						$is_internal_url = ( false !== strpos( $image_src, home_url() ) );
						try {
							$images[] = $this->_get_file_content_or_source( $is_internal_url ? $params['internal_image_mode'] : $params['external_image_mode'], $image_src );
						} catch ( \Exception $e ) {
							// Skip error, continue
						}
					}
				}
			}

		}

		/**
		 * Let other plugins add extra images.
		 * For instance, WooCommerce images gallery.
		 */
		$extra_images = apply_filters( WPSOLR_Events::WPSOLR_FILTER_AI_POST_TYPE_IMAGES_URLS, [], $document_for_update[ WpSolrSchema::_FIELD_NAME_PID ] );
		foreach ( $extra_images as $image_src ) {
			if ( $this->_get_is_image_format_supported( $image_src ) ) {
				$is_internal_url = ( false !== strpos( $image_src, home_url() ) );
				try {
					$images[] = $this->_get_file_content_or_source( $is_internal_url ? $params['internal_image_mode'] : $params['external_image_mode'], $image_src );
				} catch ( \Exception $e ) {
					// Skip error, continue
				}
			}
		}

		return $images;
	}

}