<?php

namespace wpsolr\core\classes\engines\solarium;

use Solarium\Client;
use Solarium\Core\Query\Helper;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_IndexSolariumClient
 * @package wpsolr\core\classes\engines\solarium
 *
 * @property Client $search_engine_client
 */
class WPSOLR_IndexSolariumClient extends WPSOLR_AbstractIndexClient {
	use WPSOLR_SolariumClient;

	const PATTERN_CONTROL_CHARACTERS = '@[\x00-\x08\x0B\x0C\x0E-\x1F]@';

	protected Helper $helper;

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
	 * @param \Solarium\QueryType\Update\Query\Query $solarium_update_query
	 * @param array $documents
	 */
	protected function search_engine_client_prepare_documents_for_update( $solarium_update_query, array $documents ) {

		$formatted_document = [];

		foreach ( $documents as $document ) {
			$formatted_document[] = $solarium_update_query->createDocument( $document );
		}

		return $formatted_document;
	}

	/**
	 * @param array $documents
	 *
	 * @return mixed
	 */
	public function send_posts_or_attachments_to_solr_index( $documents, $is_error = false ) {

		$solarium_update_query = $this->search_engine_client->createUpdate();

		$formatted_docs = $this->search_engine_client_prepare_documents_for_update( $solarium_update_query, $documents );

		$solarium_update_query->addDocuments( $formatted_docs );
		$solarium_update_query->addCommit();
		$result = $this->execute( $this->search_engine_client, $solarium_update_query );

		return $result->get_raw_results()->getStatus();
	}

	/**
	 * @inheritdoc
	 */
	protected function search_engine_client_get_count_document( $site_id = '' ) {

		$query = $this->search_engine_client->createSelect();
		$query->setRows( 0 );

		if ( ! empty( $site_id ) ) {

			$query->setQuery( sprintf( '%s:("%s")', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $site_id ) );

		} else {

			$query->setQuery( '*:*' );
		}

		$result_set = $this->search_engine_client_execute( $this->search_engine_client, $query );

		return $result_set->get_nb_results();
	}

	/**
	 * @inheritdoc
	 */
	protected function search_engine_client_delete_all_documents( $post_types = null, $site_id = '' ) {

		/** @var string[] $query */
		$query = [];

		if ( ( is_null( $post_types ) || empty( $post_types ) ) && ( empty( $site_id ) ) ) {

			$query[] = 'id:*';

		} else {

			if ( ! ( is_null( $post_types ) || empty( $post_types ) ) ) {
				$query[] = sprintf( '%s:(%s)', WpSolrSchema::_FIELD_NAME_TYPE, implode( ' OR ', $post_types ) );
			}

			if ( ! empty( $site_id ) ) {
				$query[] = sprintf( '%s:("%s")', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $site_id );
			}

		}

		// Execute delete query
		$delete_query = $this->search_engine_client->createUpdate();

		$delete_query->addDeleteQuery( implode( ' AND ', $query ) );

		$delete_query->addCommit();

		$this->search_engine_client_execute( $this->search_engine_client, $delete_query );
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
		$response = $results->get_raw_results()->getResponse()->getBody();

		return $response;
	}

	/**
	 * @inerhitDoc
	 */
	protected function search_engine_client_delete_document( $document_id, $model = null ) {

		$deleteQuery = $this->search_engine_client->createUpdate();
		$deleteQuery->addDeleteById( $document_id );
		$deleteQuery->addCommit();

		$this->execute( $this->search_engine_client, $deleteQuery );
	}

	/**
	 * Prepare query execute
	 */
	public function search_engine_client_pre_execute() {
		// TODO: Implement search_engine_client_pre_execute() method.
	}

}
