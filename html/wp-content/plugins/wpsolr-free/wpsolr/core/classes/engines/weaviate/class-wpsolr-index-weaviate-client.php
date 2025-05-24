<?php

namespace wpsolr\core\classes\engines\weaviate;

use GraphQL\QueryBuilder\QueryBuilder;
use Weaviate\WeaviateSearch\SearchClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\models\WPSOLR_Model_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Post_Type_Image;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_IndexWeaviateClient
 */
class WPSOLR_Index_Weaviate_Client extends WPSOLR_AbstractIndexClient {
	use WPSOLR_Weaviate_Client;
	use WPSOLR_Post_Type_Image;

	const MEDIA_FORMATS_REGEXP = '/\.jpeg|\.jpg|\.png/';
	const WPSOLR_FIELD_UUID_4 = 'wpsolr_weaviate_uuid4';

	/**
	 * @inheritDoc
	 */
	public function search_engine_client_execute( $search_engine_client, $query ) {
		// Nothing here.
	}


	/**
	 * @param string $index_name
	 * @param array $documents
	 *
	 * @return array
	 */
	protected function search_engine_client_prepare_documents_for_update( $index_name, array $documents ) {

		$formatted_document = [];

		$is_featured_image = WPSOLR_Service_Container::getOption()->get_option_index_post_types_is_image_featured();
		$is_embedded_image = WPSOLR_Service_Container::getOption()->get_option_index_post_types_is_embedded_image();

		$images_params = [
			'internal_image_mode' => static::$FILE_SEND_AS_CONTENT,
			'external_image_mode' => static::$FILE_SEND_AS_CONTENT,
			'is_attachment_image' => false,
			'is_featured_image'   => false,
			'is_embedded_image'   => false,
		];

		foreach ( $documents as &$document ) {

			// Control uuid for each document
			if ( empty( $document[ self::WPSOLR_FIELD_UUID_4 ] ) ) {
				throw new \Exception( 'Missing uuid in documents' );
			}
			if ( ! wp_is_uuid( $uuid4 = $document[ self::WPSOLR_FIELD_UUID_4 ], 4 ) ) {
				throw new \Exception( sprintf( 'Wrong uuid format %s in documents', esc_html( $uuid4 ) ) );
			}
			// No need to index uuid
			unset( $document[ self::WPSOLR_FIELD_UUID_4 ] );

			$document[ WpSolrSchema::_FIELD_NAME_BASE64 ] = []; // Reset image before
			$images_params['is_featured_image']           = isset( $is_featured_image[ $document[ WpSolrSchema::_FIELD_NAME_TYPE ] ] );
			$images_params['is_attachment_image']         = ( 'attachment' === $document[ WpSolrSchema::_FIELD_NAME_TYPE ] );
			$images_params['is_embedded_image']           = isset( $is_embedded_image[ $document[ WpSolrSchema::_FIELD_NAME_TYPE ] ] );
			if ( $images_params['is_featured_image'] || $images_params['is_attachment_image'] || $images_params['is_embedded_image'] ) {
				// Retrieve all images from the document
				$images = $this->_extract_images_from_document( $document, $images_params );
				$i      = 0;
				foreach ( $images as $image_def ) {
					$document[ $this->_generate_blob_field_name( $i, false ) ] = $image_def[ static::$FILE_SEND_AS_CONTENT ];
					$i ++;
				}
			}

			$document_with_formated_names = [];
			foreach ( $document as $field_name => $field_value ) {

				if ( empty( $field_value ) ) {
					// Weaviate does not accept empty values
					continue;
				}

				$field_type = $this->_get_field_data_type( $field_name );
				if ( ! is_array( $field_value ) && ( false !== strpos( $field_type, '[]' ) ) ) {
					// scalar value on an array data type: convert it to array
					$field_value = [ $field_value ];
				}

				// Weaviate does not accept a number in string => convert it to a string if necessary
				switch ( $field_type ) {
					case 'string':
						$field_value = strval( $field_value );
						break;

					case 'string[]':
						$field_value = array_map( 'strval', $field_value );
						break;

					case 'blob':
						// https://weaviate.io/developers/weaviate/current/data-schema/datatypes.html#datatype-blob
						$field_value = base64_encode( $field_value );
						break;

					case 'date[]':
					case 'date':
						$field_value = $this->_convert_to_date_if_unix_time( $field_value );
						break;
				}

				$document_with_formated_names[ $this->convert_field_name( $field_name ) ] = $field_value;
			}

			// https://www.semi.technology/developers/weaviate/current/restful-api-references/objects.html#example-request-1
			$formatted_document[] = [
				'class'      => $index_name,
				'id'         => $uuid4,
				'properties' => $document_with_formated_names,
			];
		}

		return [ 'objects' => $formatted_document, 'documents' => $documents, ];
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
		// No Tikka
		return '';
	}

	/**
	 * @param array[] $documents
	 *
	 * @return int|mixed
	 * @throws \Exception
	 */
	public function send_posts_or_attachments_to_solr_index( $documents, $is_error = false ) {

		$index = $this->get_search_index();

		$formatted_docs = $this->search_engine_client_prepare_documents_for_update( $index->get_index_label(), $documents );

		$documents = $formatted_docs['documents'];
		unset( $formatted_docs['documents'] );

		try {
			$response = $index->index_objects( $formatted_docs );

		} catch ( \Exception $e ) {

			try {
				if ( false !== strpos( $e->getMessage(), 'no such prop' ) ) {
					// Update the index schema before retrying
					$this->_add_index_fields_definitions( $this->get_all_fields( $documents ) );

					// At last, retry
					$index->index_objects( $formatted_docs );

				} else {
					throw $e;
				}
			} catch ( \Exception $e ) {
				throw new \Exception( sprintf( "(Weaviate) \"%s\"\n", esc_html( $e->getMessage() ) ) );
			}

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
	 * @href https://github.com/semi-technologies/weaviate/issues/1683
	 * @inheritdoc
	 */
	protected function search_engine_client_delete_all_documents( $post_types = null, $site_id = '' ) {

		if ( ( is_null( $post_types ) || empty( $post_types ) ) && ( empty( $site_id ) ) ) {

			// Weaviate cannot delete index content: delete and recreate the index

			try {
				// Try to delete
				$this->admin_delete_index();
			} catch ( \Exception $e ) {
			}

			// Create the index from existing settings
			$settings = $this->get_index_settings( $this->config );
			$this->get_search_index()->create_index( $settings );

		} else {

			throw new \Exception( 'Weaviate does not enable deleting parts of an index. Please select all post types and retry.' );
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function search_engine_client_get_count_document( $site_id = '' ) {

		$bool_queries = [];

		// Filter out the attachment document
		$bool_queries[] = sprintf( 'NOT %s:"%s"', WpSolrSchema::_FIELD_NAME_INTERNAL_ID, $this->WPSOLR_DOC_ID_ATTACHMENT );

		if ( ! empty( $site_id ) ) {
			$bool_queries[] = sprintf( '%s:"%s"', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $site_id );
		}

		$params = [ 'filters' => implode( ' AND ', $bool_queries ), 'hitsPerPage' => 0 ];

		$query_builder = ( new QueryBuilder( 'Aggregate' ) )
			->selectField(
				( new QueryBuilder( $this->get_index_label() ) )
					->selectField(
						( new QueryBuilder( 'meta' ) )
							->selectField( 'count' )
					)
			);

		$results = $this->get_search_index()->search( $query_builder->getQuery() );

		return $results->get_count( $this->get_index_label() );
	}

	/**
	 * @inheritDoc
	 */
	protected function search_engine_client_delete_document( $document_id, $model = null ) {
		if ( ! empty( $uuid4 = $model->get_custom_field( self::WPSOLR_FIELD_UUID_4, true ) ) ) {
			$this->get_search_index()->delete_object_uuid( $uuid4 );
		}

	}

	/**
	 * https://www.weaviate.com/doc/guides/managing-results/refine-results/geolocation/how-to/filter-results-around-a-location/#dataset
	 *
	 * @inheritDoc
	 */
	public function get_geolocation_field_value( $field_name, $lat, $long ) {

		return [
			'field_name'  => static::FIELD_NAME_GEOLOC, // Weaviate uses a default field geolocation
			'field_value' => [ 'lat' => $lat, 'lng' => $long ],
		];
	}

	/**
	 * Add uuid4 meta for Weaviate class property 'id'
	 * @inerhitDoc
	 */
	protected function update_model( WPSOLR_Model_Abstract $model_to_index, &$solarium_document_for_update, $attached_files ) {
		if ( empty( $uuid4 = $model_to_index->get_custom_field( self::WPSOLR_FIELD_UUID_4, true ) ) ) {
			$uuid4 = wp_generate_uuid4();
			$model_to_index->update_custom_field( self::WPSOLR_FIELD_UUID_4, $uuid4 );
		}
		$solarium_document_for_update[ self::WPSOLR_FIELD_UUID_4 ] = $uuid4;

	}

	protected function _get_is_image_format_supported( $image_src ) {
		// Check in lower or upper case
		return ( 1 === preg_match( static::MEDIA_FORMATS_REGEXP, $image_src ) ) ||
		       ( 1 === preg_match( strtoupper( static::MEDIA_FORMATS_REGEXP ), $image_src ) );
	}

	protected function _get_file_content_or_source( $send_file_as, $url ) {
		if ( ! file_exists( $url ) ) {
			throw new \Exception( sprintf( "%s does not exist", esc_url( $url ) ) );
		}

		return [ static::$FILE_SEND_AS_CONTENT => file_get_contents( $url ) ];
	}

}
