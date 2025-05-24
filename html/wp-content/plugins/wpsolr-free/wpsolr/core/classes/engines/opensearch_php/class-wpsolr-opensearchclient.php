<?php

namespace wpsolr\core\classes\engines\opensearch_php;


use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use OpenSearch\ClientBuilder;
use wpsolr\core\classes\engines\elasticsearch_php\WPSOLR_ElasticsearchClient;

/**
 * Some common methods of the OpenSearch client.
 */
trait WPSOLR_OpenSearchClient {
	use WPSOLR_ElasticsearchClient;

	protected $FILE_CONF_OS_INDEX_1_0 = 'wpsolr_index_1_0.json';

	/**
	 * @return string
	 */
	protected function _get_configuration_file_from_version(): string {
		try {

			$version = $this->search_engine_client->info()['version']['number'];

			$file = $this->FILE_CONF_OS_INDEX_1_0;
			if ( version_compare( $version, '1.0', '>=' ) ) {

				$file = $this->FILE_CONF_OS_INDEX_1_0;

			}

		} catch ( \Exception $e ) {
			// OpenSearch does not give access to cluster infos

			$file = $this->FILE_CONF_OS_INDEX_1_0;
		}

		return $file;
	}

	/**
	 * @return ClientBuilder
	 */
	protected function _get_client_builder( array $params = [] ) {
		return ClientBuilder::create();
	}

	/**
	 * @param $config
	 * @param ClientBuilder $client
	 *
	 * @return void
	 */
	protected function _before_client_build( $config, $client ): void {

		$hosts = empty( $config )
			? []
			: [
				[
					'scheme'                => $config['scheme'],
					'host'                  => $config['host'],
					'port'                  => $config['port'],
					'user'                  => $config['username'],
					'pass'                  => $config['password'],
					'aws_access_key_id'     => $config['aws_access_key_id'],
					'aws_secret_access_key' => $config['aws_secret_access_key'],
					'aws_region'            => $config['aws_region'],
					'timeout'               => $config['timeout'],
				]
			];
		$client->setHosts( $hosts );

		if ( ! empty( $config['aws_access_key_id'] ) && ! empty( $config['aws_secret_access_key'] ) && ! empty( $config['aws_region'] ) ) {

			$provider = CredentialProvider::fromCredentials(
				new Credentials( $config['aws_access_key_id'], $config['aws_secret_access_key'] )
			);
			#$handler  = new ElasticsearchPhpHandler( $config['aws_region'], $provider );
			#$client->setHandler( $handler );

			// Sign your request
			$client->setSigV4CredentialProvider( $provider );
			$client->setSigV4Region( $config['aws_region'] );
		}

	}

}
