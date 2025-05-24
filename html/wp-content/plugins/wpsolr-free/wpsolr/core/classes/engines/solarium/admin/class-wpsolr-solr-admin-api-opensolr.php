<?php

namespace wpsolr\core\classes\engines\solarium\admin;

/**
 * Class WPSOLR_Solr_Admin_Api_Opensolr
 * @package wpsolr\core\classes\engines\solarium\admin
 */
class WPSOLR_Solr_Admin_Api_Opensolr extends WPSOLR_Solr_Admin_Api_Core {
	const THE_INDEX_CONFIGURATION_FILES_HAVE_BEEN_UPDATED = "The index configuration files have been updated with WPSOLR's. You should reindex everything.";
	use WPSOLR_Solr_Admin_Api_Opensolr_Utils;

	const API_UPDATE_HTTP_AUTH = '/solr_manager/api/update_http_auth?email=%s&api_key=%s&core_name=%s&username=%s&password=%s';
	const API_REMOVE_HTTP_AUTH = '/solr_manager/api/remove_http_auth?email=%s&api_key=%s&core_name=%s';
	const API_GET_CORE_SCHEMA = 'https://%s%s/solr/%s/schema?wt=json';
	const API_GET_CORE = '/solr_manager/api/get_core_info?email=%s&api_key=%s&core_name=%s';
	const API_CREATE_CORE = '/solr_manager/api/create_core?email=%s&api_key=%s&core_name=%s&server_country=%s&core_type=generic';
	const API_DELETE_CORE = '/solr_manager/api/delete_core?email=%s&api_key=%s&core_name=%s';
	const API_CONFIGSETS_UPLOAD = '/solr_manager/api/upload_zip_config_files';
	const API_GET_ENVS = '/solr_manager/api/get_env?email=%s&api_key=%s';

	const ERROR_MESSAGE_CORE_ALREADY_EXISTS = 'ERROR_CORE_NAME_TAKEN_CHOOSE_ANOTHER_CORE_NAME';

	/**
	 * @return object
	 * @throws \Exception
	 */
	public function ping() {

		// Exception is the core dos not exist
		return $this->call_rest_get( sprintf( static::API_GET_CORE, $this->email, $this->api_key, $this->core ) );
	}

	/**
	 * @param $index_parameters
	 *
	 * @throws \Exception
	 */
	public function create_solr_index_existing() {

		// Create a core on existing index. No upload.
		return $this->call_rest_get( sprintf( static::API_CREATE_CORE, $this->email, $this->api_key, $this->core, $this->region_id ) );
	}

	/**
	 * @param $index_parameters
	 *
	 * @throws \Exception
	 */
	public function create_solr_index( &$index_parameters ) {

		// Create a core.
		$this->create_core_or_collection( sprintf( static::API_CREATE_CORE, $this->email, $this->api_key, $this->core, $this->region_id ), $index_parameters );
	}

	/**
	 * @param string $term_in_label
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 */
	public function get_environments( $term_in_label = '', $is_only_recent = false ) {

		$results = [];

		// Get all envs
		$result = $this->call_rest_get( sprintf( static::API_GET_ENVS, $this->email, $this->api_key ) );

		if ( $result->msg ) {

			foreach (
				[
					'simple_owned_environments'  => 'Simple owned',
					'cluster_owned_environments' => 'Cluster owned',
					'simple_shared_environments' => 'Simple shared',
				] as $env_type => $env_label
			) {
				if ( property_exists( $result->msg, $env_type ) ) {
					foreach ( $result->msg->$env_type as $env ) {

						$is_not_too_old = $this->check_solr_version( $env->solr_version );

						$label = sprintf(
							'%s%s - Solr %s - %s - %s',
							! $is_not_too_old ? 'Solr version not supported by WPSOLR - ' : '', $env_label, $env->solr_version, $env->server_identifier, $env->region, $env->provider
						);

						// Add env if it's label contains $term_in_label, and env is not too old
						if ( ! $is_only_recent || $is_not_too_old ) {
							if ( empty( $term_in_label ) || ( false !== strpos( strtolower( $label ), strtolower( $term_in_label ) ) ) ) {

								$results[] = [
									'id'           => $env->server_identifier,
									'label'        => $label,
									'solr_version' => $env->solr_version
								];
							}
						}
					}
				}
			}

		}

		return $results;
	}


	/**
	 * @param string $solr_version
	 *
	 * @return bool
	 */
	protected function check_solr_version( $solr_version ) {
		return ! ( empty( $solr_version ) || version_compare( $solr_version, '4', '<' ) );
	}

	/**
	 * Do something a before call_rest_get()
	 *
	 * @throws \Exception
	 */
	protected function before_call_rest_create_core_or_collection() {
		$environments = $this->get_environments( $this->region_id, false );

		if ( ( 1 !== count( $environments ) || ( ! $this->check_solr_version( $environments[0]['solr_version'] ) ) ) ) {
			throw new \Exception( sprintf( 'The Solr version "%s" of environment "%s" is too old. Please select an environment with Solr version >= 4.10',
				esc_html( $environments[0]['solr_version'] ), esc_html( $environments[0]['id'] ) ) );
		}

	}

	/**
	 * @throws \Exception
	 */
	public function admin_index_update( &$index_parameters ) {

		$endpoint = $this->client->getEndpoint( 'localhost1' );;

		if ( empty( $endpoint->getHost() ) ) {
			// New index, but existing already on the server.
			$index_parameters['message'] = self::THE_INDEX_CONFIGURATION_FILES_HAVE_BEEN_UPDATED;
			$this->after_call_rest_create_core_or_collection( $index_parameters );
		}

		$authentication = $endpoint->getAuthentication();
		if ( ! empty( $authentication ) && ! empty( $authentication['username'] ) && ! empty( $authentication['password'] ) ) {
			$this->call_rest_update_authentication( $authentication['username'], $authentication['password'] );
		} else {
			$this->call_rest_delete_authentication();
		}
	}

	/**
	 *
	 * @param string $user_name
	 * @param string $password
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 */
	protected function call_rest_update_authentication( $user_name, $password ) {

		$path = sprintf( static::API_UPDATE_HTTP_AUTH, $this->email, $this->api_key, $this->core, $user_name, $password );

		return $this->call_rest_request( $path, [] );
	}

	/**
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 */
	protected function call_rest_delete_authentication() {

		$path = sprintf( static::API_REMOVE_HTTP_AUTH, $this->email, $this->api_key, $this->core );

		return $this->call_rest_request( $path, [] );
	}

	/**
	 * @param $result
	 * @param $index_parameters
	 *
	 * @throws \Exception
	 */
	protected function after_call_rest_create_core_or_collection( &$index_parameters ) {

		// Retrieve the index details
		$result        = $this->ping();
		$solr_version  = $result->msg->info->solr_version;
		$core_hostname = wp_parse_url( $result->msg->info->connection_url, PHP_URL_HOST );

		$this->upload_files( $solr_version );

		// Return parameters to the Ajax call, to update the index form fields before saving the form.
		$endpoint = $this->client->getEndpoint( 'localhost1' );;
		if ( ! empty( $core_hostname ) ) {
			$index_parameters['index_host'] = $core_hostname;
			$endpoint->setHost( $index_parameters['index_host'] );
		}

		$index_parameters['index_protocol'] = 'https';
		$endpoint->setScheme( $index_parameters['index_protocol'] );
		$index_parameters['index_port'] = '443';
		$endpoint->setPort( $index_parameters['index_port'] );
		$index_parameters['index_path'] = sprintf( '/solr/%s', $this->core );
		$endpoint->setPath( $index_parameters['index_path'] );
		$index_parameters['index_region_id'] = $result->msg->info->environment_identifier;
	}

	protected function get_endpoint_path() {

		return 'https://opensolr.com';
	}

	/**
	 * @throws \Exception
	 */
	public function delete_solr_index() {

		// Delete the core.
		$this->delete_core_or_collection( sprintf( static::API_DELETE_CORE, $this->email, $this->api_key, '%s' ) );
	}

	protected function get_error_message_core_already_exists() {
		return static::ERROR_MESSAGE_CORE_ALREADY_EXISTS;
	}

	/**
	 * @param $solr_version
	 *
	 * @throws \Exception
	 */
	protected function upload_files( $solr_version ) {
// Upload the config files
		$solr_admin_api_configsets = new WPSOLR_Solr_Admin_Api_ConfigSets_OpenSolr( [], $this->client );
		$result1                   = $solr_admin_api_configsets->upload_configset( static::API_CONFIGSETS_UPLOAD,
			[
				'email'        => $this->email,
				'api_key'      => $this->api_key,
				'core_name'    => $this->core,
				'solr_version' => $solr_version,
			] );
	}
}
