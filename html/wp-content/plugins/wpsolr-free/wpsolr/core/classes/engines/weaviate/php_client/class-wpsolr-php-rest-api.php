<?php

namespace wpsolr\core\classes\engines\weaviate\php_client;

use Exception;
use Google\Client;
use Jumbojett\OpenIDConnectClient;
use WP_Error;
use wpsolr\core\classes\engines\weaviate\WPSOLR_Weaviate_Constants;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;

class WPSOLR_Php_Rest_Api {

	const ERROR_MESSAGES_REQUIRE_NEW_TOKEN = [ 'expired', 'oidc: malformed', 'credentials' ];

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
				$this->_generate_path( $path_pattern, $path_pattern_values ),
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
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	public function post( string $path_pattern, array $path_pattern_values, $body ) {

		return $this->post_put( 'POST', $path_pattern, $path_pattern_values, $body );
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
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws Exception
	 */
	protected function post_put( string $method, string $path_pattern, array $path_pattern_values, $body ) {
		try {
			return $this->_post_put( $method, $path_pattern, $path_pattern_values, $body, false );
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
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	protected function _post_put( string $method, string $path_pattern, array $path_pattern_values, $body, $is_expired ) {

		return $this->_convert_api_response(
			wp_remote_post(
				$this->_generate_path( $path_pattern, $path_pattern_values ),
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
				$this->_generate_path( $path_pattern, $path_pattern_values ),
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

	protected function _generate_path( string $path_pattern, array $path_pattern_values ) {

		$path = $path_pattern;
		if ( ! empty( $path_pattern_values ) ) {
			$path_pattern_values_url_encoded = [];
			foreach ( $path_pattern_values as $path_pattern_value ) {
				// encode even already encoded
				$path_pattern_values_url_encoded[] = urlencode( urldecode( $path_pattern_value ) );
			}
			$path = vsprintf( $path_pattern, $path_pattern_values_url_encoded );
		}

		return sprintf( '%s://%s:%s%s', $this->config['scheme'], $this->config['host'], $this->config['port'], $path );
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



		// https://weaviate.io/developers/weaviate/current/configuration/authentication.html#add-a-bearer-to-a-request
		if ( ! empty( $api_token = $this->config['extra_parameters'][ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_API_KEY_1 ] ?? '' ) ) {
			$headers['Authorization'] = sprintf( 'Bearer %s', $api_token );
		}

		$headers = $this->_manage_openid_connect( $is_expired, $headers );

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

	/**
	 * @param $token
	 *
	 * @return void
	 */
	protected function _save_token_in_cache( $token ): void {
		if ( ! empty( $this->config['index_uuid'] ) ) {
			// Store the token for next call
			$indexes = new WPSOLR_Option_Indexes();
			$indexes->update_index_property( $this->config['index_uuid'], WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_TOKEN, $token );
		}
	}

	/**
	 * @return string
	 */
	protected function _get_token_from_cache(): string {
		if ( ! empty( $this->config['index_uuid'] ) ) {
			// Get the token from a previous call
			$indexes = new WPSOLR_Option_Indexes();
			$token   = $indexes->get_index_property( $indexes->get_index( $this->config['index_uuid'] ), WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_TOKEN );
		}

		return $token ?? '';
	}

	/**
	 * @param bool $is_expired
	 * @param array $headers
	 *
	 * @return array
	 * @throws \Jumbojett\OpenIDConnectClientException
	 */
	protected function _manage_openid_connect( bool $is_expired, array $headers ): array {
// Init
		$client_id      = '';
		$client_secret  = '';
		$token_endpoint = '';
		$provider_url   = '';
		$token          = '';


		/* TODO: Let's manage OIDC for local instances in the future
		// auth0.com
		$client_id      = 'Uj4JM3lMTdNMfU49NjItNVttvemPuuV0';
		$client_secret  = 'zKP010OiJ7tDDXNWL1NPn1Cp2iRBv6pNemfKp22mrsMEHUKlHB0MW7sWPrv7gg3u';
		$token_endpoint = 'https://wpsolr.eu.auth0.com/oauth/token';
		$provider_url   = 'https://wpsolr.eu.auth0.com/.well-known/openid-configuration';
		*/

		if ( ! empty( $client_id ) ) {
			/**
			 * Generate an OpenID token when an OpenId clientId is set
			 */


			if ( empty( $token ) || $is_expired ) {

				$oidc = new OpenIDConnectClient(
					$provider_url,
					$client_id,
					$client_secret,
				);

				$oidc->providerConfigParam( [ 'token_endpoint' => $token_endpoint ] );

				if ( ! empty( $this->config['username'] ) && ! empty( $this->config['password'] ) ) {
					$oidc->addAuthParam( [ 'username' => $this->config['username'] ] );
					$oidc->addAuthParam( [ 'password' => $this->config['password'] ] );

					$clientCredentialsToken = $oidc->requestResourceOwnerToken( true );
				} else {
					$clientCredentialsToken = $oidc->requestClientCredentialsToken();
				}

				if ( empty( $clientCredentialsToken ) ) {
					throw new \Exception( 'Credential error. No explanation available.' );
				} elseif ( ! empty( $clientCredentialsToken->error ) ) {
					throw new \Exception( esc_html( $clientCredentialsToken->error_description ) );
				}

				$token = $clientCredentialsToken->access_token;

				$this->_save_token_in_cache( $token );
			}

			// https://www.semi.technology/developers/weaviate/current/configuration/authentication.html#add-a-bearer-to-a-request
			$headers['Authorization'] = sprintf( 'Bearer %s', $token );
		}

		return $headers;
	}

}