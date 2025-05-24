<?php

namespace wpsolr\core\classes\engines\solarium\admin;

use wpsolr\core\classes\utilities\WPSOLR_Regexp;


/**
 * Class WPSOLR_Solr_Admin_Api_Abstract
 * @package wpsolr\core\classes\engines\solarium\admin
 */
abstract class WPSOLR_Solr_Admin_Api_Abstract {

	/**
	 * @var \Solarium\Client
	 */
	protected $client;

	/** @var string */
	protected $core;

	/** @var string */
	protected $email;

	/** @var string */
	protected $api_key;

	/** @var string */
	protected $region_id;

	/**
	 * WPSOLR_Solr_Admin_Api_Abstract constructor.
	 *
	 * @param array $config
	 * @param \Solarium\Client $client
	 */
	public function __construct( $config, $client ) {

		if ( ! empty( $client ) ) {

			$this->client = $client;
			$this->core   = empty( $config['index_label'] ) ? $this->client->getEndpoint()->getCore() : $config['index_label'];
		}

		if ( ! empty( $config ) && ! empty( $config['extra_parameters'] ) ) {

			$this->email     = ! empty( $config['extra_parameters']['index_email'] ) ? $config['extra_parameters']['index_email'] : '';
			$this->api_key   = ! empty( $config['extra_parameters']['index_api_key'] ) ? $config['extra_parameters']['index_api_key'] : '';
			$this->region_id = ! empty( $config['extra_parameters']['index_region_id'] ) ? $config['extra_parameters']['index_region_id'] : '';
		}
	}

	/**
	 * @param string $path_core '/solr/core'
	 *
	 * @return string 'core'
	 */
	protected function extract_core_from_path( $path_core ) {

		$result = WPSOLR_Regexp::extract_last_separator( $path_core, '/' );

		return $result;
	}

	/**
	 * @return string
	 */
	protected function get_endpoint_path() {
		$endpoint = $this->client->getEndpoint();

		$result = sprintf( '%s://%s:%s', $endpoint->getScheme(), $endpoint->getHost(), $endpoint->getPort() );

		return $result;
	}

	/**
	 * @param $path
	 * @param $args
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 * @args array $args
	 *
	 */
	protected function call_rest_request( $path, $args ) {

		$full_path = ( false !== strpos( $path, '://' ) ) ? $path : sprintf( '%s%s', $this->get_endpoint_path(), $path );

		$default_args = [
			'timeout' => 60 * 5,  // opensolr parallel tests need more time to create an index
			'verify'  => true,
			'headers' => [ 'Content-Type' => 'application/json' ],
		];

		$response = wp_remote_request(
			$full_path,
			array_merge( $default_args, $args )
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( esc_html( $response->get_error_message() ) );
		}

		if ( 200 !== $response['response']['code'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( $response['body'], esc_html( $response['response']['code'] ) ); # Safe. Comes from Solr. Required to interpret and display error.
		}

		$json = json_decode( $response['body'] ); // can be null if not a json format.

		return $this->manage_response_result( ! is_object( $json ) ? $response['body'] : $json, $response['body'] );
	}

	/**
	 * @param $path
	 * @param array $data
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 */
	protected function call_rest_post( $path, $data = [] ) {

		$args = [
			'method' => 'POST',
			'body'   => wp_json_encode( $data ),
		];

		return $this->call_rest_request( $path, $args );
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

		$args = [
			'method'  => 'POST',
			'headers' => [
				'accept'       => 'application/json', // The API returns JSON
				'content-type' => 'application/binary', // Set content type to binary
			],
			'body'    => $data,
		];

		return $this->call_rest_request( $path, $args );
	}

	/**
	 * Generic REST calls
	 *
	 * @param $path
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 */
	protected function call_rest_get( $path ) {

		$args = [
			'method' => 'GET',
		];

		return $this->call_rest_request( $path, $args );
	}

	/**
	 * @param $path
	 *
	 * @return array|mixed|object
	 * @throws \Exception
	 */
	protected function call_rest_delete( $path ) {

		$args = [
			'method' => 'DELETE',
		];

		return $this->call_rest_request( $path, $args );
	}

	/**
	 * @param object $response_json
	 * @param string $response_body
	 *
	 * @return object
	 */
	protected function manage_response_result( $response_json, $response_body ) {
		return $response_json;
	}

	/**
	 * @param string $core
	 */
	public function set_core( $core ) {
		$this->core = $core;
	}

}