<?php

namespace wpsolr\core\classes\engines\solarium\admin;


use wpsolr\core\classes\utilities\WPSOLR_Admin_Utilities;

/**
 * Class WPSOLR_Solr_Admin_Api_ConfigSets
 * @package wpsolr\core\classes\engines\solarium\admin
 */
class WPSOLR_Solr_Admin_Api_ConfigSets extends WPSOLR_Solr_Admin_Api_Abstract {

	/**
	 * Error messages returned by Solr. Do not change.
	 */
	const ERROR_MESSAGE_CORE_ALREADY_EXISTS = "Core with name '%s' already exists";
	const ERROR_MESSAGE_NOT_SOLRCLOUD_MODE = "Solr instance is not running in SolrCloud mode.";

	/**
	 * SolrCloud actions
	 */
	const API_CONFIGSETS_LIST = '/solr/admin/configs?action=LIST&wt=json';
	const API_CONFIGSETS_DELETE = '/solr/admin/configs?action=DELETE&name=%s&wt=json';


	/**
	 * Returns the configuration file path (zip)
	 *
	 * @param string $solr_version
	 *
	 * @return string
	 */
	static function get_config_file_path( $solr_version = '' ) {
		return WPSOLR_Admin_Utilities::convert_dropbox_url_from_html_to_zip( 'https://www.dropbox.com/scl/fi/8yl0jsadoz9qf9kpep29c/wpsolr-v9-0_v1.zip?rlkey=p56crfw58yd5jf26i5gt0h113&dl=0' );
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

			// Retrieve the configset file path
			$file = self::get_config_file_path();


			$file_data = file_get_contents( $file );
			//$file_data = curl_file_create ( $file );

			// Upload the confisets files.
			$result = $this->call_rest_upload( $upload_url, $file_data, $parameters );

			return $result;

		} catch ( \Exception $e ) {

			throw $e;
		}

	}

	/**
	 * Delete a configset
	 */
	public function delete_configset() {

		try {

			// Retrieve the configset file path
			$file = self::get_config_file_path();


			$file_data = file_get_contents( $file );

			// Delete the confisets files.
			$result = $this->call_rest_get( sprintf( self::API_CONFIGSETS_DELETE, $this->core ) );

		} catch ( \Exception $e ) {

			throw $e;
		}

	}

	/**
	 * Does a configset already exist ?
	 *
	 * @param string $configset
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function is_exists_configset() {

		// List of all configsets
		$result = $this->call_rest_get( self::API_CONFIGSETS_LIST );

		if ( isset( $result ) && ! empty( $result->configSets ) && in_array( $this->core, $result->configSets, true ) ) {
			return true;
		}

		return false;
	}

}