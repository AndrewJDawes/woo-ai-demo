<?php

namespace wpsolr\core\classes\engines\weaviate\php_client;

use GraphQL\Query;

class WPSOLR_Php_Search_Index {

	protected string $index_label;
	protected WPSOLR_Php_Rest_Api $api;
	protected array $config;

	/**
	 * Constructor.
	 *
	 * @param string $index_label
	 * @param WPSOLR_Php_Rest_Api $api
	 * @param array $config
	 */
	public function __construct( string $index_label, WPSOLR_Php_Rest_Api $api, array $config ) {
		$this->index_label = $index_label;
		$this->api         = $api;
		$this->config      = $config;
	}

	/**
	 * @return string
	 */
	public function get_index_label() {
		return $this->index_label;
	}
	/**************************************************************************************************************
	 *
	 * Weaviate REST API calls
	 *
	 *************************************************************************************************************/

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function is_ready(): bool {
		return $this->api->get( '/v1/.well-known/ready', [], [] )->is_http_code_200();
	}

	/**
	 * @param array $settings
	 *
	 * @throws \Exception
	 */
	public function create_index( array $settings ) {
		return $this->api->post( '/v1/schema', [ $this->index_label ], $settings );
	}

	/**
	 * @param array $settings
	 *
	 * @throws \Exception
	 */
	public function update_index( array $settings ) {
		return $this->api->put( '/v1/schema', [ $this->index_label ], $settings );
	}

	public function get_index_fields_definitions() {
		$results = $this->api->get( '/v1/schema/%s', [ $this->index_label ], [] );

		return $results->get_fields();
	}

	/**
	 * @param array $field_definition
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function add_index_field_definition( array $field_definition ) {
		return $this->api->post( '/v1/schema/%s/properties', [ $this->index_label ], $field_definition );
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function has_index(): bool {
		return $this->api->get( '/v1/schema/%s', [ $this->index_label ], [] )->is_http_code_200();
	}

	/**
	 * @throws \Exception
	 */
	public function delete_index() {
		$this->api->delete( '/v1/schema/%s', [ $this->index_label ] );
	}

	/**
	 * @throws \Exception
	 */
	public function delete_object_uuid( $uuid ) {
		$this->api->delete( '/v1/objects/%s', [ $uuid ] );
	}

	/**
	 * @param Query $query
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function search( Query $query ): WPSOLR_Php_Rest_Api_Response {
		return $this->api->post( '/v1/graphql', [], [ 'query' => (string) $query ] );
	}

	/**
	 * @href https://www.semi.technology/developers/weaviate/current/restful-api-references/batch.html#method-and-url
	 *
	 * @param array $formatted_docs
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function index_objects( array $formatted_docs ): WPSOLR_Php_Rest_Api_Response {
		return $this->api->post( '/v1/batch/objects', [], $formatted_docs );
	}

}