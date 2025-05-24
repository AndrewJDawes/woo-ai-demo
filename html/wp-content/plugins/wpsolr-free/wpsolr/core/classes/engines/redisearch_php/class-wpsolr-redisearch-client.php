<?php

namespace wpsolr\core\classes\engines\redisearch_php;

use Ehann\RediSearch\Index;
use Ehann\RedisRaw\RedisClientAdapter;
use Solarium\Core\Query\Helper;

trait WPSOLR_RediSearch_Client {


	/**
	 * Execute an update query with the client.
	 *
	 * @param \RediSearch_\Client $search_engine_client
	 * @param \RediSearch_\Core\Query\QueryInterface $update_query
	 *
	 * @return WPSOLR_Results_RediSearch_Client
	 */
	public function search_engine_client_execute( $search_engine_client, $update_query ) {

		$this->search_engine_client_pre_execute();

		try {

			return new WPSOLR_Results_Redisearch_Client( $this->get_search_index()->highlight( [] )->return( [ 'title' ] )->search( '*' ) );

		} catch ( \Exception $e ) {

			throw new \Exception( esc_html( $this->_get_exception_message( $e ) ) );
		}
	}

	/**
	 * Prepare query execute
	 */
	abstract public function search_engine_client_pre_execute();


	/**
	 * @param array $config
	 *
	 * @return \Ehann\RedisRaw\RedisRawClientInterface
	 */
	protected function create_search_engine_client( $config ) {

		if ( ! empty( trim( $config['password'] ) ) ) {

			return ( new RedisClientAdapter() )->connect( $config['host'], $config['port'], 0, $config['password'] );

		} else {

			return ( new RedisClientAdapter() )->connect( $config['host'], $config['port'] );
		}

	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return string
	 */
	public function search_engine_client_format_date( $date_str ) {

		if ( null === $this->helper ) {
			$this->helper = new Helper( $this );
		}

		return $this->helper->formatDate( $date_str );
	}

	/**
	 * @return Index
	 */
	public function get_search_index( $index_name = '' ): Index {
		return ( new Index( $this->search_engine_client, empty( $index_name ) ? $this->config['index_label'] : $index_name ) );
	}

	/**
	 *
	 * Get exception message
	 *
	 * @param \Exception $e
	 *
	 * @return string
	 */
	protected function _get_exception_message( \Exception $e ) {

		if ( false !== strpos( $e->getMessage(), 'The Redis client threw an exception. See the inner exception for details.' ) ) {

			return $e->getPrevious()->getMessage();

		} else {

			return $e->getMessage();
		}
	}

	/**
	 * Create the index
	 *
	 * @param array $index_parameters
	 *
	 * @throws \Exception
	 */
	protected function admin_create_index( &$index_parameters ) {

		try {

			$this->get_search_index()
			     ->addTextField( 'wpsolr_not_used' )
			     ->create();

		} catch ( \Exception $e ) {

			throw new \Exception( esc_html( $this->_get_exception_message( $e ) ) );
		}

	}

	/**
	 * Does index exists ?
	 *
	 * @param $is_throw_error
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function admin_is_index_exists( $is_throw_error = false ) {

		try {

			$info = $this->get_search_index()->info();

			return true;

		} catch ( \Exception $e ) {

			return false;
		}
	}

}
