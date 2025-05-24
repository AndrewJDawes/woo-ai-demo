<?php

namespace wpsolr\core\classes\engines\elasticsearch_php;

use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_IndexElasticsearchClient
 *
 * @property \Elasticsearch\Client $search_engine_client
 */
class WPSOLR_IndexElasticsearchClient extends WPSOLR_AbstractIndexClient {
	use WPSOLR_ElasticsearchClient;

	const PIPELINE_INGEST_ATTACHMENT_ID = 'wpsolr_attachment';
	const PIPELINE_INGEST_ATTACHMENT_DEFINITION =
		'{
  "description" : "WPSOLR - Ingest attachment pipeline",
  "processors" : [
    {
      "attachment" : {
        "field" : "data"
      }
    }
  ]
}';


	/**
	 * @inheritDoc
	 */
	public function search_engine_client_execute( $search_engine_client, $query ) {
		// Nothing here.
	}


	/**
	 * @param array $documents
	 */
	protected function search_engine_client_prepare_documents_for_update( array $documents ) {

		$formatted_document = [];

		$doc_type = $this->_get_index_doc_type();

		foreach ( $documents as $document ) {

			if ( ! empty( $doc_type ) ) {

				$formatted_document[] = [ 'index' => [ '_type' => $doc_type, '_id' => $document['id'] ] ];

			} else {

				$formatted_document[] = [ 'index' => [ '_id' => $document['id'] ] ];
			}

			//$formatted_document[] = [ 'index' => [ '_id' => $document['id'] ] ];
			$formatted_document[] = $document;
		}

		return $formatted_document;
	}

	/**
	 * {"index":{"_type":"wpsolr_types","_id":3264}}
	 * {"id":3264,"PID":3264,"type":"job_listing","meta_type_s":"post_type","displaymodified":"2018-12-10T19:37:20Z","title":"Typing Room","title_s":"Typing Room","permalink":"http:\/\/src-wpsolr-search-engine.test\/listing\/typing-room\/","post_status_s":"publish","content":"Typing Room is located in East London’s Town Hall Hotel, built in 1910, and is named after the building’s original typing room in which all communications from the mayoral, council and judicial system were put to ink.","post_author_s":"1","author":"admin","menu_order_i":-1,"PID_i":3264,"author_s":"http:\/\/src-wpsolr-search-engine.test\/author\/admin\/","displaydate":"2018-12-10T19:37:20Z","displaydate_dt":"2018-12-10T19:37:20Z","date":"2018-12-10T19:37:20Z","displaymodified_dt":"2018-12-10T19:37:20Z","modified":"2018-12-10T19:37:20Z","modified_y_i":2018,"modified_ym_i":12,"modified_yw_i":50,"modified_yd_i":344,"modified_md_i":10,"modified_wd_i":2,"modified_dh_i":19,"modified_dm_i":37,"modified_ds_i":20,"displaydate_y_i":2018,"displaydate_ym_i":12,"displaydate_yw_i":50,"displaydate_yd_i":344,"displaydate_md_i":10,"displaydate_wd_i":2,"displaydate_dh_i":19,"displaydate_dm_i":37,"displaydate_ds_i":20,"displaydate_dt_y_i":2018,"displaydate_dt_ym_i":12,"displaydate_dt_yw_i":50,"displaydate_dt_yd_i":344,"displaydate_dt_md_i":10,"displaydate_dt_wd_i":2,"displaydate_dt_dh_i":19,"displaydate_dt_dm_i":37,"displaydate_dt_ds_i":20,"displaymodified_dt_y_i":2018,"displaymodified_dt_ym_i":12,"displaymodified_dt_yw_i":50,"displaymodified_dt_yd_i":344,"displaymodified_dt_md_i":10,"displaymodified_dt_wd_i":2,"displaymodified_dt_dh_i":19,"displaymodified_dt_dm_i":37,"displaymodified_dt_ds_i":20,"comments":[],"numcomments":0,"categories_str":[],"categories":["Restaurants","London","Accepts Credit Cards","Bike Parking","Coupons","Parking Street","Smoking Allowed","Wireless Internet","51.530675","-0.054321","9.5","place","1"],"flat_hierarchy_categories_str":[],"non_flat_hierarchy_categories_str":[],"tags":[],"job_listing_category_str":["Restaurants"],"flat_hierarchy_job_listing_category_str":["Restaurants"],"non_flat_hierarchy_job_listing_category_str":["Restaurants"],"region_str":["London"],"flat_hierarchy_region_str":["London"],"non_flat_hierarchy_region_str":["London"],"case27_job_listing_tags_str":["Accepts Credit Cards","Bike Parking","Coupons","Parking Street","Smoking Allowed","Wireless Internet"],"flat_hierarchy_case27_job_listing_tags_str":["Accepts Credit Cards","Bike Parking","Coupons","Parking Street","Smoking Allowed","Wireless Internet"],"non_flat_hierarchy_case27_job_listing_tags_str":["Accepts Credit Cards","Bike Parking","Coupons","Parking Street","Smoking Allowed","Wireless Internet"],"geolocation_lat_s":["51.530675"],"geolocation_lat_str":["51.530675"],"geolocation_long_s":["-0.054321"],"geolocation_long_str":["-0.054321"],"_case27_average_rating_f":[9.5],"_case27_average_rating_str":[9.5],"_case27_listing_type_str":["place"],"_featured_i":[1],"_featured_str":[1],"wpsolr_mylisting_geolocation_ll":"51.530675,-0.054321"}
	 * {"index":{"_type":"wpsolr_types","_id":3275}}
	 * {"id":3275,"PID":3275,"type":"job_listing","meta_type_s":"post_type","displaymodified":"2018-12-10T19:54:21Z","title":"The Ledbury","title_s":"The Ledbury","permalink":"http:\/\/src-wpsolr-search-engine.test\/listing\/the-ledbury\/","post_status_s":"publish","content":"At distant inhabit amongst by. Appetite welcomed interest the goodness boy not. Estimable education for disposing pronounce her. John size good gay plan sent old roof own. Inquietude saw understood his friendship frequently yet. Nature his marked ham wished","post_author_s":"1","author":"admin","menu_order_i":0,"PID_i":3275,"author_s":"http:\/\/src-wpsolr-search-engine.test\/author\/admin\/","displaydate":"2018-12-10T19:54:21Z","displaydate_dt":"2018-12-10T19:54:21Z","date":"2018-12-10T19:54:21Z","displaymodified_dt":"2018-12-10T19:54:21Z","modified":"2018-12-10T19:54:21Z","modified_y_i":2018,"modified_ym_i":12,"modified_yw_i":50,"modified_yd_i":344,"modified_md_i":10,"modified_wd_i":2,"modified_dh_i":19,"modified_dm_i":54,"modified_ds_i":21,"displaydate_y_i":2018,"displaydate_ym_i":12,"displaydate_yw_i":50,"displaydate_yd_i":344,"displaydate_md_i":10,"displaydate_wd_i":2,"displaydate_dh_i":19,"displaydate_dm_i":54,"displaydate_ds_i":21,"displaydate_dt_y_i":2018,"displaydate_dt_ym_i":12,"displaydate_dt_yw_i":50,"displaydate_dt_yd_i":344,"displaydate_dt_md_i":10,"displaydate_dt_wd_i":2,"displaydate_dt_dh_i":19,"displaydate_dt_dm_i":54,"displaydate_dt_ds_i":21,"displaymodified_dt_y_i":2018,"displaymodified_dt_ym_i":12,"displaymodified_dt_yw_i":50,"displaymodified_dt_yd_i":344,"displaymodified_dt_md_i":10,"displaymodified_dt_wd_i":2,"displaymodified_dt_dh_i":19,"displaymodified_dt_dm_i":54,"displaymodified_dt_ds_i":21,"comments":[],"numcomments":0,"categories_str":[],"categories":["Restaurants","London","Accepts Credit Cards","Bike Parking","Coupons","Parking Street","Smoking Allowed","Wireless Internet","51.535627","-0.183318","8.5","place","0","0"],"flat_hierarchy_categories_str":[],"non_flat_hierarchy_categories_str":[],"tags":[],"job_listing_category_str":["Restaurants"],"flat_hierarchy_job_listing_category_str":["Restaurants"],"non_flat_hierarchy_job_listing_category_str":["Restaurants"],"region_str":["London"],"flat_hierarchy_region_str":["London"],"non_flat_hierarchy_region_str":["London"],"case27_job_listing_tags_str":["Accepts Credit Cards","Bike Parking","Coupons","Parking Street","Smoking Allowed","Wireless Internet"],"flat_hierarchy_case27_job_listing_tags_str":["Accepts Credit Cards","Bike Parking","Coupons","Parking Street","Smoking Allowed","Wireless Internet"],"non_flat_hierarchy_case27_job_listing_tags_str":["Accepts Credit Cards","Bike Parking","Coupons","Parking Street","Smoking Allowed","Wireless Internet"],"geolocation_lat_s":["51.535627"],"geolocation_lat_str":["51.535627"],"geolocation_long_s":["-0.183318"],"geolocation_long_str":["-0.183318"],"_case27_average_rating_f":[8.5],"_case27_average_rating_str":[8.5],"_case27_listing_type_str":["place"],"_featured_i":["0","0"],"_featured_str":["0","0"],"wpsolr_mylisting_geolocation_ll":"51.535627,-0.183318"}
	 */

	/**
	 * Use Tika to extract a file content.
	 *
	 * @param $file
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function search_engine_client_extract_document_content( $file ) {

		// Decoded value
		$decoded_attached_value = '';

		$params_index = $this->get_index();

		$params = [
			'id'       => $this->WPSOLR_DOC_ID_ATTACHMENT,
			'pipeline' => self::PIPELINE_INGEST_ATTACHMENT_ID,
			'body'     => [
				'data' => base64_encode( file_get_contents( $file ) )
			]
		];
		$params = array_merge( $params_index, $params );

		try {

			$response = $this->search_engine_client->index( $params );

		} catch ( \Exception $e ) {

			if (
				( false !== strpos( $e->getMessage(), sprintf( 'pipeline with id [%s] does not exist', self::PIPELINE_INGEST_ATTACHMENT_ID ) ) )
				|| ( false !== strpos( $e->getMessage(), 'There are no ingest nodes in this cluster' ) )
			) {

				// Create our attachment pipeline as it does not exist yet.
				$response = $this->search_engine_client->ingest()->putPipeline(
					[
						'id'   => self::PIPELINE_INGEST_ATTACHMENT_ID,
						'body' => self::PIPELINE_INGEST_ATTACHMENT_DEFINITION,
					]
				);

				// then retry
				$response = $this->search_engine_client->index( $params );

			} else {
				// Not a missing ingest pipeline error. Don't catch it here.
				throw $e;
			}

		}

		if ( ! isset( $response['error'] ) ) {

			$params = array_merge( $params_index, [
				'id'      => $this->WPSOLR_DOC_ID_ATTACHMENT,
				'_source' => 'attachment.content',
			] );

			$response = $this->search_engine_client->get( $params );

			$decoded_attached_value = $response['_source']['attachment']['content'] ?? '';

		} else {

			throw new \Exception( esc_html( $response['error'] ) );
		}

		return sprintf( '<body>%s</body>', $decoded_attached_value );
	}

	/**
	 * @param array[] $documents
	 *
	 * @return int|mixed
	 * @throws \Exception
	 */
	public function send_posts_or_attachments_to_solr_index( $documents, $is_error = false ) {

		$formatted_docs = $this->search_engine_client_prepare_documents_for_update( $documents );

		/**
		 * We set the type (and therefore the index) on the bulk object,
		 * to prevent https://www.elasticpress.io indexing error "explicit index in bulk is not allowed"
		 */
		$params         = $this->get_index();
		$params['body'] = $formatted_docs;

		try {

			$response = $this->search_engine_client->bulk( $params );

		} catch ( \Exception $e ) {

			if ( ! $is_error && $this->_try_to_fix_error_doc_type( $e->getMessage() ) ) {

				// Retry once
				return $this->send_posts_or_attachments_to_solr_index( $documents, true );
			}

			throw $e;
		}

		if ( $this->_has_error( $response )
		) {

			$error_msg = $this->_get_error( $response );
			if ( ! $is_error && $this->_try_to_fix_error_doc_type( $error_msg ) ) {

				// Retry once
				return $this->send_posts_or_attachments_to_solr_index( $documents, true );
			}

			throw new \Exception( esc_html( $error_msg ) );
		}

		return true;
	}

	/**
	 * @param array $response
	 *
	 * @return bool
	 */
	protected function _has_error( $response ) {

		return (bool) $response['errors'];
	}

	/**
	 * @param array $response
	 *
	 * @return string
	 */
	protected function _get_error( $response ) {

		if ( $this->_has_error( $response ) &&
		     isset( $response["items"] ) &&
		     isset( $response["items"][0] ) &&
		     isset( $response["items"][0]["index"] ) &&
		     isset( $response["items"][0]["index"]["error"] ) &&
		     isset( $response["items"][0]["index"]["error"]["reason"] )
		) {
			return $response["items"][0]["index"]["error"]["reason"];
		}

		return '';
	}

	/**
	 * @inheritdoc
	 */
	protected function search_engine_client_delete_all_documents( $post_types = null, $site_id = '' ) {

		if ( ( is_null( $post_types ) || empty( $post_types ) ) && ( empty( $site_id ) ) ) {

			$params = $this->_create_match_all_query();

			$this->search_engine_client->deleteByQuery( $params );

		} else {

			$bool_queries = [];

			if ( ! ( is_null( $post_types ) || empty( $post_types ) ) ) {
				$bool_queries[] = [ 'terms' => [ WpSolrSchema::_FIELD_NAME_TYPE => $post_types ] ];
			}

			if ( ! empty( $site_id ) ) {
				$bool_queries[] = [ 'term' => [ WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR => $site_id ] ];
			}

			$params = $this->_create_bool_query( [ 'must' => $bool_queries ] );

			$this->search_engine_client->deleteByQuery( $params );
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function search_engine_client_get_count_document( $site_id = '' ) {

		$bool_queries = [];

		// Filter out the attachment document
		$bool_queries['must_not'] = [ 'term' => [ WpSolrSchema::_FIELD_NAME_INTERNAL_ID => $this->WPSOLR_DOC_ID_ATTACHMENT ] ];

		if ( ! empty( $site_id ) ) {

			$bool_queries['must'] = [ 'term' => [ WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR => $site_id ] ];
		}

		$params = $this->_create_bool_query( $bool_queries );

		$nb_documents = $this->search_engine_client->count( $params );
		if ( empty( $nb_documents['count'] ) && ! empty( $this->_get_index_doc_type() ) ) {
			// Index 7.x with a default type => returns 0 but no warning. Retry without the type
			unset( $params['type'] );
			$nb_documents = $this->search_engine_client->count( $params );
		}

		return $nb_documents['count'];
	}

	/**
	 * @inerhitDoc
	 */
	protected function search_engine_client_delete_document( $document_id, $model = null ) {

		$bool_query         = [];
		$bool_query['must'] = [ 'term' => [ 'id' => $document_id ] ];

		$params = $this->_create_bool_query( $bool_query );

		$this->search_engine_client->deleteByQuery( $params );
	}

}
