<?php

namespace wpsolr\core\classes\engines\solarium;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Adapter\Http;
use Solarium\Core\Query\Helper;
use Solarium\Core\Query\QueryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use wpsolr\core\classes\engines\WPSOLR_Client;

/**
 * Some common methods of the Solr client.
 * @property Client $search_engine_client
 */
trait WPSOLR_SolariumClient {
	use WPSOLR_Client;

	/**
	 * Execute an update query with the client.
	 *
	 * @param Client $search_engine_client
	 * @param QueryInterface $update_query
	 *
	 * @return WPSOLR_ResultsSolariumClient
	 */
	public function search_engine_client_execute( $search_engine_client, $update_query ) {

		$this->search_engine_client_pre_execute();

		if ( $this->_get_is_more_like_this() ) {
			return new WPSOLR_ResultsSolariumClient( $search_engine_client->moreLikeThis( $update_query ) );
		}

		return new WPSOLR_ResultsSolariumClient( $search_engine_client->execute( $update_query ) );
	}

	/**
	 * Prepare query execute
	 */
	abstract public function search_engine_client_pre_execute();


	/**
	 * @param $config
	 *
	 * @return Client
	 */
	protected function create_search_engine_client( $config ) {

		// New Solarium version require '/solr/index1' => 'index1'
		$index = basename( $config['path'] );

		$solarium_config = [
			'endpoint' => [
				'localhost1' => [
					'scheme'     => $config['scheme'],
					'host'       => $config['host'],
					'port'       => $config['port'],
					'path'       => '/',
					'core'       => $index,
					'collection' => $index,
					'username'   => $config['username'],
					'password'   => $config['password'],
					'timeout'    => $config['timeout'],
				],
			],
		];

		switch ( $config['extra_parameters']['index_client_adapter'] ?? '' ) {
			case 'curl':
				$client_adapter = new Curl();
				break;

			default:
				$client_adapter = new Http();
				break;
		}

		return new Client( $client_adapter, new EventDispatcher(), $solarium_config );
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return string
	 */
	public function search_engine_client_format_date( $date_str ) {

		if ( ! isset( $this->helper ) ) {
			$this->helper = new Helper();
		}

		return $this->helper->formatDate( $date_str );
	}

	/**
	 * Get the analysers available
	 * @return array
	 */
	static public function get_analysers() {
		/*
		 * https://lucene.apache.org/solr/guide/8_0/language-analysis.html
		 */

		return [
			/*
			'Arabic'               => [],
			'Bengali'              => [],
			'Brazilian Portuguese' => [],
			'Bulgarian'            => [],
			'Catalan'              => [],
			'Traditional Chinese'  => [],
			'Simplified Chinese'   => [],
			'Czech'                => [],
			'Danish'               => [],
			'Dutch'                => [],
			*/
			'English' => [ 'is_default' => true, ],
			/*
			'Finnish'              => [],
			'French'               => [],
			'Galician'             => [],
			'German'               => [],
			'Greek'                => [],
			'Hebrew'               => [],
			'Lao'                  => [],
			'Myanmar'              => [],
			'Khmer'                => [],
			'Hindi'                => [],
			'Indonesian'           => [],
			'Italian'              => [],
			'Irish'                => [],
			'Japanese'             => [],
			'Latvian'              => [],
			'Norwegian'            => [],
			'Persian'              => [],
			'Polish'               => [],
			'Portuguese'           => [],
			'Romanian'             => [],
			'Russian'              => [],
			'Scandinavian'         => [],
			'Serbian'              => [],
			'Spanish'              => [],
			'Swedish'              => [],
			'Thai'                 => [],
			'Turkish'              => [],
			'Ukrainian'            => [],
			*/
		];
	}

}