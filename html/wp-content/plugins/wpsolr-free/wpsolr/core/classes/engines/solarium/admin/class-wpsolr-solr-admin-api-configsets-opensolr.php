<?php

namespace wpsolr\core\classes\engines\solarium\admin;

use wpsolr\core\classes\utilities\WPSOLR_Admin_Utilities;

/**
 * Class WPSOLR_Solr_Admin_Api_ConfigSets_OpenSolr
 * @package wpsolr\core\classes\engines\solarium\admin
 */
class WPSOLR_Solr_Admin_Api_ConfigSets_OpenSolr extends WPSOLR_Solr_Admin_Api_ConfigSets {
	use WPSOLR_Solr_Admin_Api_Opensolr_Utils;

	const GET_MULTIPART_BOUNDARY = 'wpsolr-multipart-boundary';

	/**
	 * @inheritDoc
	 */
	protected function get_endpoint_path() {
		return 'https://opensolr.com';
	}

	/**
	 * Returns the configuration file path (zip)
	 * @return string
	 * @throws \Exception
	 */
	static function get_config_file_path( $solr_version = '' ) {

		if ( empty( $solr_version ) || version_compare( $solr_version, '4', '<' ) ) {

			throw new \Exception( sprintf( 'The Solr version (%s) of this index is too old. Please select an environment with Solr version >= 4.0',
				esc_html( $solr_version ) ) );

		} elseif ( version_compare( $solr_version, '4', '>=' ) && version_compare( $solr_version, '5', '<' ) ) {

			$solr_files = 'https://www.dropbox.com/s/tjuz288mokvge94/wpsolr-v4.zip?dl=0'; // Custom _version_ "string" for opensolr 5.x and 6.x, else error on _version' when indexing

		} elseif ( version_compare( $solr_version, '5', '>=' ) && version_compare( $solr_version, '9', '<' ) ) {

			$solr_files = 'https://www.dropbox.com/s/7v34mxgjdbwi17o/wpsolr-v5.zip?dl=0'; // Custom _version_ "string" for opensolr 5.x and 6.x, else error on _version' when indexing

		} elseif ( version_compare( $solr_version, '9', '>=' ) ) {

			$solr_files = 'https://www.dropbox.com/scl/fi/8yl0jsadoz9qf9kpep29c/wpsolr-v9-0_v1.zip?rlkey=p56crfw58yd5jf26i5gt0h113&dl=0'; // same as standard Solr

		} else {
			// Same as parent

			return parent::get_config_file_path( $solr_version );
		}

		return WPSOLR_Admin_Utilities::convert_dropbox_url_from_html_to_zip( $solr_files );
	}

	/**
	 * Upload configset
	 *
	 * @param string $upload_url
	 *
	 * @param array $parameters
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 */
	public function upload_configset( $upload_url, $parameters = [] ) {

		try {

			/**
			 * This is a multi-part form with a file. Not supported by default by  WP http API.
			 * @link http://codechutney.com/posting-file-using-wp_remote_post/
			 */

			// Retrieve the configset file path
			$file = self::get_config_file_path( $parameters['solr_version'] );

			$file_data = '';
			foreach ( array_merge( $parameters, [ 'userfile' => file_get_contents( $file ) ] ) as $name => $value ) {

				if ( false === strpos( $name, 'file' ) ) {

					// standard form field
					$file_data .= '--' . self::GET_MULTIPART_BOUNDARY;
					$file_data .= "\r\n";
					$file_data .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
					$file_data .= $value;
					$file_data .= "\r\n";

				} else {

					// File field
					$file_data .= '--' . self::GET_MULTIPART_BOUNDARY;
					$file_data .= "\r\n";
					$file_data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . basename( $file ) . '"' . "\r\n";
					//        $payload .= 'Content-Type: image/jpeg' . "\r\n";
					$file_data .= "\r\n";
					$file_data .= file_get_contents( $file );
					$file_data .= "\r\n";
				}
			}

			// End of payload
			$file_data .= '--' . self::GET_MULTIPART_BOUNDARY . '--';

			// Upload the confisets files.
			$result = $this->call_rest_upload( $upload_url, $file_data, $parameters );

			return $result;

		} catch ( \Exception $e ) {

			throw $e;
		}

	}

	/**
	 * @param $path
	 * @param string|array $data
	 *
	 * @param array $parameters
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 */
	protected function call_rest_upload( $path, $data, $parameters = [] ) {

		// multi-part form to support standard fields and a file
		$args = [
			'method'  => 'POST',
			'headers' => [
				'content-type' => 'multipart/form-data; boundary=' . self::GET_MULTIPART_BOUNDARY
			],
			'body'    => $data,
		];

		return $this->call_rest_request( $path, $args );
	}

}
