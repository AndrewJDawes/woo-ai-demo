<?php

namespace wpsolr\core\classes\engines\vespa\php_client;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use WP_Error;

class WPSOLR_Php_Rest_Api {

	const ERROR_MESSAGES_REQUIRE_NEW_TOKEN = [ 'expired', 'oidc: malformed' ];

	protected array $config;

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param array $args
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	public function get( string $path_pattern, array $path_pattern_values, array $args ): WPSOLR_Php_Rest_Api_Response {
		try {
			return $this->_get( $path_pattern, $path_pattern_values, $args, false );
		} catch ( Exception $e ) {
			if ( $this->_get_is_error_message_require_new_token( $e->getMessage() ) ) {
				return $this->_get( $path_pattern, $path_pattern_values, $args, true );
			}
			throw $e;
		}
	}

	/**
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param array $args
	 * @param bool $is_expired
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	protected function _get( string $path_pattern, array $path_pattern_values, array $args, $is_expired ): WPSOLR_Php_Rest_Api_Response {
		return $this->_convert_api_response(
			wp_remote_get(
				$this->generate_path( $path_pattern, $path_pattern_values ),
				array_merge( $this->_get_default_wp_remote_args( [ 'method' => 'GET', ], $is_expired ), $args )
			)
		);
	}

	/**
	 * @param array|WP_Error $wp_response
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	protected function _convert_api_response( $wp_response ): WPSOLR_Php_Rest_Api_Response {
		return new WPSOLR_Php_Rest_Api_Response( $wp_response );
	}

	/**
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param string|array $body
	 * @param bool $is_async
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	public function post( string $path_pattern, array $path_pattern_values, $body, bool $is_async = false ) {

		return $this->post_put( 'POST', $path_pattern, $path_pattern_values, $body, $is_async );
	}

	/**
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param string|array $body
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	public function post_binary_content( string $path_pattern, array $path_pattern_values, $body ) {

		return $this->_post_binary( 'POST', $path_pattern, $path_pattern_values, $body );
	}

	/**
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param string|array $body
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	public function put_binary_content( string $path_pattern, array $path_pattern_values, $body ) {

		return $this->_post_binary( 'PUT', $path_pattern, $path_pattern_values, $body );
	}

	/**
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param string|array $body
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	public function put( string $path_pattern, array $path_pattern_values, $body ) {

		return $this->post_put( 'PUT', $path_pattern, $path_pattern_values, $body );
	}

	/**
	 * @param string $method
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param string|array $body
	 * @param bool $is_async
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	protected function post_put( string $method, string $path_pattern, array $path_pattern_values, $body, bool $is_async = false ) {
		try {
			return $this->_post_put( $method, $path_pattern, $path_pattern_values, $body, false, $is_async );
		} catch ( Exception $e ) {
			if ( $this->_get_is_error_message_require_new_token( $e->getMessage() ) ) {
				return $this->_post_put( $method, $path_pattern, $path_pattern_values, $body, true );
			}
			throw $e;
		}
	}

	/**
	 * @param string $method
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param string|array $body
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	protected function _post_binary( string $method, string $path_pattern, array $path_pattern_values, $body ) {
		try {
			return $this->_post_file_upload( $method, $path_pattern, $path_pattern_values, $body, false );
		} catch ( Exception $e ) {
			if ( $this->_get_is_error_message_require_new_token( $e->getMessage() ) ) {
				return $this->_post_put( $method, $path_pattern, $path_pattern_values, $body, true );
			}
			throw $e;
		}
	}

	/**
	 * @param string $method
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param string|array $body
	 * @param bool $is_expired
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 */
	protected function _post_put( string $method, string $path_pattern, array $path_pattern_values, $body, $is_expired, bool $is_async = false ) {

		if ( $is_async ) {

			$result = $this->_wp_remote_post_async( $this->generate_path( $path_pattern, $path_pattern_values ), $method, $body, $is_expired );

		} else {

			$result = wp_remote_post(
				$this->generate_path( $path_pattern, $path_pattern_values ),
				$this->_get_default_wp_remote_args( [
					'method' => $method,
					'body'   => wp_json_encode( $body ),
					'Expect' => '',
					// https://wordpress.stackexchange.com/questions/301451/wp-remote-post-doesnt-work-with-more-than-1024-bytes-in-the-body
				], $is_expired )
			);

		}

		return $this->_convert_api_response( $result );
	}

	/**
	 * @param string $method
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param string|array $body
	 * @param bool $is_expired
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	protected function _post_file_upload( string $method, string $path_pattern, array $path_pattern_values, $body, $is_expired ) {

		return $this->_convert_api_response(
			wp_remote_post(
				$this->generate_path( $path_pattern, $path_pattern_values ),
				$this->_get_default_wp_remote_args( [
					'method'  => $method,
					'body'    => $body,
					'headers' => [ 'content-type' => ( 'POST' === $method ) ? 'application/zip' : 'application/binary', ],
					'Expect'  => '',
					// https://wordpress.stackexchange.com/questions/301451/wp-remote-post-doesnt-work-with-more-than-1024-bytes-in-the-body
				], $is_expired )
			)
		);
	}

	/**
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	public function delete( string $path_pattern, array $path_pattern_values ) {
		try {
			return $this->_delete( $path_pattern, $path_pattern_values, false );
		} catch ( Exception $e ) {
			if ( $this->_get_is_error_message_require_new_token( $e->getMessage() ) ) {
				return $this->_delete( $path_pattern, $path_pattern_values, true );
			}
			throw $e;
		}
	}

	/**
	 * @param string $path_pattern
	 * @param array $path_pattern_values
	 * @param bool $is_expired
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	public function _delete( string $path_pattern, array $path_pattern_values, $is_expired ) {

		return $this->_convert_api_response(
			wp_remote_post(
				$this->generate_path( $path_pattern, $path_pattern_values ),
				$this->_get_default_wp_remote_args( [
					'method' => 'DELETE',
				], $is_expired )
			)
		);
	}

	/**
	 * Replace url parameters
	 *
	 * @param $url
	 *
	 * @return string|string[]
	 */
	protected function _replace_url_parameters( $url ) {

		$url_params     = wp_parse_url( $url, PHP_URL_QUERY );
		$new_url_params = http_build_query( [ 'in' => 'text', 'out' => 'jsonfull' ] );
		$url            = empty( $url_params ) ? sprintf( '%s?%s', $url, $new_url_params ) : str_replace( $url_params, $new_url_params, $url );

		return $url;
	}

	public function generate_path( string $path_pattern = '', array $path_pattern_values = [] ) {

		$path = $path_pattern;
		if ( ! empty( $path_pattern_values ) ) {
			$path_pattern_values_url_encoded = [];
			foreach ( $path_pattern_values as $path_pattern_value ) {
				// encode even already encoded
				$path_pattern_values_url_encoded[] = str_contains( $path_pattern_value, '/' ) ? $path_pattern_value : urlencode( urldecode( $path_pattern_value ) );
			}
			$path = vsprintf( $path_pattern, $path_pattern_values_url_encoded );
		}

		$is_admin = ( 0 !== strpos( $path, '/document/' ) ) && ( 0 !== strpos( $path, '/search/' ) );
		$endpoint = $is_admin ? '' : '1';

		if ( isset( $this->config['index_endpoint'] ) ) {
			$endpoint = $is_admin ? '' : '_1';

			return sprintf( '%s%s', $this->config[ 'index_endpoint' . $endpoint ], $path );
		} else {
			$endpoint = $is_admin ? '' : '1';

			return sprintf( '%s://%s:%s%s', $this->config[ 'scheme' . $endpoint ], $this->config[ 'host' . $endpoint ], $this->config[ 'port' . $endpoint ], $path );
		}
	}

	/**
	 * @param array $args
	 * @param bool $is_expired
	 *
	 * @return array
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	protected function _get_default_wp_remote_args( array $args, $is_expired ): array {

		$headers = [
			'Content-Type' => 'application/json',
		];

		return array_merge( [
			'timeout' => 60,
			//'verify'  => true,
			'headers' => $headers,
		], $args );
	}

	/**
	 * @param string $error_msg
	 *
	 * @return bool
	 */
	protected function _get_is_error_message_require_new_token( $error_msg ) {
		foreach ( self::ERROR_MESSAGES_REQUIRE_NEW_TOKEN as $message ) {
			if ( false !== strpos( $error_msg, $message ) ) {
				return true;
			}
		}

		return false;
	}

	protected function _post_put_async( string $method, string $path_pattern, array $path_pattern_values, $body, $is_expired ) {

		return $this->_convert_api_response(
			$this->wp_remote_post(
				$this->generate_path( $path_pattern, $path_pattern_values ),
				$this->_get_default_wp_remote_args( [
					'method' => $method,
					'body'   => wp_json_encode( $body ),
					'Expect' => '',
					// https://wordpress.stackexchange.com/questions/301451/wp-remote-post-doesnt-work-with-more-than-1024-bytes-in-the-body
				], $is_expired )
			)
		);
	}

	/**
	 * @param string $path
	 * @param $method
	 * @param array $body
	 * @param bool $is_expired
	 *
	 * @return array|WP_Error
	 * @throws \Throwable
	 */
	private function _wp_remote_post_async( string $path, $method, array $body, bool $is_expired ) {

		// Vespa cannot index with batches: https://github.com/vespa-engine/vespa/issues/26579
		// Let's do it with async http2 pooling: https://docs.guzzlephp.org/en/stable/quickstart.html#concurrent-requests

		$client = new Client( [
			'version' => 2.0,
			#'debug'   => true
		] );

		$promises = [];
		$i        = 0;
		foreach ( $body as $body_el ) {
			$promises[ $i ++ ] = $client->postAsync( sprintf( $path, $body_el['fields']['wpsolr_id'] ), $this->_get_default_wp_remote_args( [
				'method' => $method,
				'body'   => wp_json_encode( $body_el ),
				'Expect' => '',
				// https://wordpress.stackexchange.com/questions/301451/wp-remote-post-doesnt-work-with-more-than-1024-bytes-in-the-body
			], $is_expired ) );
		}

		$result = Promise\Utils::unwrap( $promises );

		return [ 'response' => [ 'code' => 200, ] ];
	}

}