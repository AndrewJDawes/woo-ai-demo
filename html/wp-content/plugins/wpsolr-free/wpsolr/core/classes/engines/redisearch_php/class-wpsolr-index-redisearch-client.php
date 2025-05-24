<?php

namespace wpsolr\core\classes\engines\redisearch_php;

use Ehann\RediSearch\Index;
use Solarium\Core\Query\Helper;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_Index_RediSearch_Client extends WPSOLR_AbstractIndexClient {
	use WPSOLR_RediSearch_Client;

	const PATTERN_CONTROL_CHARACTERS = '@[\x00-\x08\x0B\x0C\x0E-\x1F]@';

	/** @var string[] */
	protected static $index_schema = [];

	/* @var Helper $helper */
	protected $helper;

	/**
	 * @inheritDoc
	 */
	public function __construct( $config, $solr_index_indice = null, $language_code = null ) {
		parent::__construct( $config, $solr_index_indice, $language_code );

		add_filter( WPSOLR_Events::WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE, [
			$this,
			'strip_control_characters',
		], 10, 5 );

	}

	/**
	 * Remove control characters that provoke indexing Solr errors
	 *
	 * @param array $document_for_update
	 * @param $solr_indexing_options
	 * @param $post
	 * @param $attachment_body
	 * @param WPSOLR_AbstractIndexClient $search_engine_client
	 *
	 * @return array Document updated with fields
	 */
	function strip_control_characters( $document_for_update, $solr_indexing_options, $post, $attachment_body, WPSOLR_AbstractIndexClient $search_engine_client ) {

		WPSOLR_Regexp::replace_recursive( $document_for_update, self::PATTERN_CONTROL_CHARACTERS, '' );

		return $document_for_update;
	}

	/**
	 * http://www.ethanhann.com/redisearch-php/indexing/
	 *
	 * @param \Ehann\RediSearch\Index $index
	 * @param array $documents
	 *
	 * @return array
	 * @throws \Ehann\RediSearch\Exceptions\FieldNotInSchemaException
	 */
	protected function search_engine_client_prepare_documents_for_update( Index $index, array $documents ) {

		$formatted_document = [];

		try {

			// Create schema first
			foreach ( $documents as $document ) {
				foreach ( $document as $field_name => $field_value ) {
					if ( 'id' !== $field_name ) {
						$index->addTextField( $field_name );


						if ( ! in_array( $field_name, static::$index_schema ) && ( 'title' === $field_name ) ) {
							// https://oss.redislabs.com/redisearch/Commands.html#ftalter_schema_add

							$arguments = [
								$index->getIndexName(),
								'SCHEMA',
								'ADD',
								$field_name,
							];

							//$field_def = WpSolrSchema::get_custom_field_dynamic_type( $field_name );

							$arguments[] = 'TEXT';
							//$arguments[] = 'SORTABLE';

							$index->getRedisClient()->rawCommand( 'FT.ALTER', $arguments );
							static::$index_schema[] = $field_name;
						}
					}
				}
			}

		} catch ( \Exception $e ) {

			throw new \Exception( sprintf( '(RediSearch) %s', esc_html( $this->_get_exception_message( $e ) ) ) );
		}

		foreach ( $documents as $document ) {
			$doc = $index->makeDocument( $document['id'] );
			$doc->setReplace( true ); // upsert

			foreach ( $document as $field_name => $field_value ) {
				if ( 'id' !== $field_name ) {

					if ( ! is_array( $field_value ) ) {
						// CAnnot index arrays?
						$doc->$field_name->setValue( $field_value );
					}
				}
			}

			$formatted_document[] = $doc;
		}

		return $formatted_document;
	}

	/**
	 * @inheritDoc
	 * @throws \Ehann\RediSearch\Exceptions\FieldNotInSchemaException
	 */
	public function send_posts_or_attachments_to_solr_index( $documents, $is_error = false ) {

		$index = $this->get_search_index();

		$formatted_docs = $this->search_engine_client_prepare_documents_for_update( $index, $documents );

		try {

			$index->addMany( $formatted_docs );

		} catch ( \Exception $e ) {

			throw new \Exception( sprintf( "(RediSearch) \"%s\"\n", esc_html( $this->_get_exception_message( $e ) ) ) );
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	protected function search_engine_client_get_count_document( $site_id = '' ) {

		try {

			if ( ! empty( $site_id ) ) {

				return $this->get_search_index()->count( sprintf( '@%s:("%s")', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $site_id ) );

			} else {

				return $this->get_search_index()->count( '*' );
			}

		} catch ( \Exception $e ) {

			throw new \Exception( esc_html( $this->_get_exception_message( $e ) ) );
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function search_engine_client_delete_all_documents( $post_types = null, $site_id = '' ) {

		// RedisSearch does not support delete by query. Only single document delete with ->delete($id, true)
		// https://oss.redislabs.com/redisearch/Commands/#ftdel

		if ( ( is_null( $post_types ) || empty( $post_types ) ) && ( empty( $site_id ) ) ) {

			// Drop and recreate !
			try {

				$this->get_search_index()->drop();

			} catch ( \Exception $e ) {
				// Nothing, in case the index was already dropped. Continue
			}

			$this->admin_create_index( $this->config );

		} else {

			throw new \Exception( 'Sorry, but RediSearch cannot erase an index content partially. All you can do is select all the post types, but only if you have not selected the cross-domain search; then the index will be utterly destroyed and replaced with a brand new index.' );
		}

	}

	/**
	 * Use Tika to extract a file content.
	 *
	 * @param $file
	 *
	 * @return string
	 */
	protected function search_engine_client_extract_document_content( $file ) {

		$solarium_extract_query = $this->search_engine_client->createExtract();

		// Set URL to attachment
		$solarium_extract_query->setFile( $file );
		$doc1 = $solarium_extract_query->createDocument();
		$solarium_extract_query->setDocument( $doc1 );
		// We don't want to add the document to the solr index now
		$solarium_extract_query->addParam( 'extractOnly', 'true' );
		// Try to extract the document body
		$client   = $this->search_engine_client;
		$results  = $this->execute( $client, $solarium_extract_query );
		$response = $results->get_results()->getResponse()->getBody();

		return $response;
	}

	/**
	 * @inerhitDoc
	 */
	protected function search_engine_client_delete_document( $document_id, $model = null ) {

		$this->get_search_index()->delete( $document_id );
	}

	/**
	 * Prepare query execute
	 */
	public function search_engine_client_pre_execute() {
		// TODO: Implement search_engine_client_pre_execute() method.
	}

}
