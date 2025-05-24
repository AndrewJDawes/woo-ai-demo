<?php

namespace wpsolr\core\classes\engines\vespa;

use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Post_Type_Image;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_IndexVespaClient
 */
class WPSOLR_Index_Vespa_Client extends WPSOLR_AbstractIndexClient {
	use WPSOLR_Vespa_Client;
	use WPSOLR_Post_Type_Image;

	const PATTERN_CONTROL_CHARACTERS = '@[\x00-\x08\x0B\x0C\x0E-\x1F]@';

	const MEDIA_FORMATS_REGEXP = '/\.jpeg|\.jpg|\.png/';

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

			/*
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
			}*/

			$document_with_formated_names = [];
			foreach ( $document as $field_name => $field_value ) {

				if ( empty( $field_value ) ) {
					// Vespa does not accept empty values
					continue;
				}

				$field_type = $this->_get_field_definition( $field_name, $field_value )['type'];
				if ( ! is_array( $field_value ) && ( false !== strpos( $field_type, 'array<' ) ) ) {
					// scalar value on an array data type: convert it to array
					$field_value = [ $field_value ];
				}

				// Vespa does not accept a number in string => convert it to a string if necessary
				switch ( $field_type ) {
					case 'string':
						$field_value = strval( $field_value );
						break;

					case 'array<string>':
						$field_value = array_map( 'strval', $field_value );
						break;

					case 'blob':
						// https://vespa.io/developers/vespa/current/data-schema/datatypes.html#datatype-blob
						$field_value = base64_encode( $field_value );
						break;
				}

				$document_with_formated_names[ $this->convert_field_name( $field_name ) ] = $field_value;
			}

			$formatted_document[] = [
				'fields' => $document_with_formated_names,
			];

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

		try {
			$index->index_objects( $formatted_docs );

		} catch ( \GuzzleHttp\Exception\BadResponseException $e ) {
			try {

				if ( false !== strpos( $e->getResponse()->getBody()->getContents(), 'No field ' ) ) {
					// Update the index schema before retrying
					$this->_add_index_fields_definitions( $this->get_all_fields( $documents ) );

					// At last, retry
					$index->index_objects( $formatted_docs );

				} else {
					throw new \Exception( esc_html( $e->getMessage() ) );
				}
			} catch ( \Exception $e ) {
				throw new \Exception( sprintf( "(Vespa) \"%s\"\n", esc_html( $e->getMessage() ) ) );
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
	 * @href https://docs.vespa.ai/en/reference/document-select-language.html
	 * @inheritdoc
	 */
	protected function search_engine_client_delete_all_documents( $post_types = null, $site_id = '' ) {

		$query = [];

		if ( ( is_null( $post_types ) || empty( $post_types ) ) && ( empty( $site_id ) ) ) {

			$query[] = 'true';

		} else {

			if ( ! ( is_null( $post_types ) || empty( $post_types ) ) ) {
				$post_type_query = [];
				foreach ( $post_types as $post_type ) {
					$post_type_query[] = sprintf( '%s.%s = "%s"', $this->index_label, $this->convert_field_name( WpSolrSchema::_FIELD_NAME_TYPE ), $post_type );
				}
				$query[] = sprintf( implode( ' or ', $post_type_query ) );
			}

			if ( ! empty( $site_id ) ) {
				$query[] = sprintf( '%s.%s = "%s"', $this->index_label, $this->convert_field_name( WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR ), $site_id );
			}

		}

		$this->get_search_index()->delete_objects( implode( ' and ', $query ) );

	}

	/**
	 * @inheritdoc
	 */
	protected function search_engine_client_get_count_document( $site_id = '' ) {

		$where = 'true';

		if ( ! empty( $site_id ) ) {
			$where = sprintf( '%s contains "%s"', $this->convert_field_name( WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR ), $site_id );
		}

		$query = [ 'yql' => sprintf( 'select * from %s where %s', $this->get_index_label(), $where ), ];

		$results = $this->get_search_index()->search( $query );

		return $results->get_count();
	}

	/**
	 * @inheritDoc
	 */
	protected function search_engine_client_delete_document( $document_id, $model = null ) {
		$this->get_search_index()->delete_object_id( $document_id );
	}

	/**
	 * https://www.vespa.com/doc/guides/managing-results/refine-results/geolocation/how-to/filter-results-around-a-location/#dataset
	 *
	 * @inheritDoc
	 */
	public function get_geolocation_field_value( $field_name, $lat, $long ) {

		return [
			'field_name'  => static::FIELD_NAME_GEOLOC, // Vespa uses a default field geolocation
			'field_value' => [ 'lat' => $lat, 'lng' => $long ],
		];
	}

	protected function _get_is_image_format_supported( $image_src ) {
		// Check in lower or upper case
		return ( 1 === preg_match( static::MEDIA_FORMATS_REGEXP, $image_src ) ) ||
		       ( 1 === preg_match( strtoupper( static::MEDIA_FORMATS_REGEXP ), $image_src ) );
	}

	protected function _get_file_content_or_source( $send_file_as, $url ) {
		return [ static::$FILE_SEND_AS_CONTENT => file_get_contents( $url ) ];
	}

	/**
	 * @inerhitDoc
	 */
	public function get_server_file_content( string $file_name ): string {
		if ( empty( $result = $this->get_search_index()->get_application_file_content( $file_name ) ) ) {
			throw new \Exception( sprintf( '%s could not be found.', esc_html( $file_name ) ) );
		}

		return $result;
	}

	/**
	 * @inerhitDoc
	 *
	 * @param string $file_name
	 * @param string $file_content
	 *
	 * @throws \Exception
	 */
	public function deploy_server_file_content( string $file_name, string $file_content ) {
		$this->get_search_index()->deploy_application_file_content( $file_name, $file_content );
	}
}
