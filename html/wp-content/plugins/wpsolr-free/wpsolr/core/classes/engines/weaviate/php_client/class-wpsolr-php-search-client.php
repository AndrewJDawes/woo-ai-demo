<?php

namespace wpsolr\core\classes\engines\weaviate\php_client;

class WPSOLR_Php_Search_Client {
	protected string $username;
	protected string $password;
	protected array $config;
	protected WPSOLR_Php_Rest_Api $api;

	/**
	 * @param array $config
	 *
	 * @return WPSOLR_Php_Search_Client
	 */
	public static function create( array $config ): WPSOLR_Php_Search_Client {
		$client = new static();

		$client->config = $config;
		$client->api    = new WPSOLR_Php_Rest_Api( $config );

		return $client;
	}

	/**
	 * @param string $index_label
	 *
	 * @return WPSOLR_Php_Search_Index
	 */
	public function init_index( string $index_label ): WPSOLR_Php_Search_Index {
		return new WPSOLR_Php_Search_Index( $index_label, $this->api, $this->config );
	}

}