<?php

use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Solr_None;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Error_Handling;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;

// Load localization class
WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_LOCALIZATION, true );

/**
 * @param $thedate
 *
 * @return mixed
 */
function solr_format_date( $thedate ) {
	$datere  = '/(\d{4}-\d{2}-\d{2})\s(\d{2}:\d{2}:\d{2})/';
	$replstr = '${1}T${2}Z';

	return preg_replace( $datere, $replstr, $thedate );
}

function fun_search_indexed_data( $is_infiniscroll = false ) {

	$ad_url = admin_url();

	// Retrieve search form page url
	$get_page_info = WPSOLR_SearchSolariumClient::get_search_page();
	$url           = get_permalink( $get_page_info->ID );
	// Filter the search page url. Used for multi-language search forms.
	$url = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_PAGE_URL, $url, $get_page_info->ID );

	// Load localization options
	$localization_options = OptionLocalization::get_options();

	$wdm_typehead_request_handler = WPSOLR_AJAX_AUTO_COMPLETE_ACTION;
	$ajax_nonce                   = wp_create_nonce( "nonce_for_autocomplete" );

	parse_str( wp_parse_url( $url, PHP_URL_QUERY ) ?? '', $url_params );
	$lang = ( ! empty( $url_params ) && isset( $url_params['lang'] ) ) ? $url_params['lang'] : '';

	try {

		try {

			$template_data = WPSOLR_Service_Container::get_solr_client()->get_results_data( WPSOLR_Service_Container::get_query(), [], false );

			/**
			 * Fill template data with static information
			 */
			$template_data['search_form'] = [
				'admin_url'                          => $ad_url,
				'ajax_action_suggestions'            => $wdm_typehead_request_handler,
				'query_string'                       => WPSOLR_Service_Container::get_query()->get_wpsolr_query( '', true ),
				'nonce'                              => $ajax_nonce,
				'placeholder'                        => OptionLocalization::get_term( $localization_options, 'search_form_edit_placeholder' ),
				'suggest_class'                      => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT,
				'button_label'                       => OptionLocalization::get_term( $localization_options, 'search_form_button_label' ),
				'is_after_autocomplete_block_submit' => WPSOLR_Service_Container::getOption()->get_search_after_autocomplete_block_submit(),
				'paged'                              => WPSOLR_Service_Container::get_query()->get_wpsolr_paged(),
				'language'                           => $lang,
				'append_html'                        => apply_filters( WPSOLR_Events::WPSOLR_FILTER_APPEND_FIELDS_TO_AJAX_SEARCH_FORM, '' ),
				'is_infiniscroll'                    => $is_infiniscroll,
			];


		} catch ( Exception $e ) {
			// Missing template ?

			$message = $e->getMessage();
			WPSOLR_Escape::echo_escaped( sprintf( "<span class='infor'>%s</span>", WPSOLR_Escape::esc_html( $message ) ) );
			die();
		}

		/**
		 * Generate the template from its data
		 */
		if ( $is_infiniscroll ) {

			WPSOLR_Escape::echo_escaped( WPSOLR_Service_Container::get_template_builder()->load_template_results_infiniscroll( $template_data ) );

		} else {

			WPSOLR_Escape::echo_escaped( WPSOLR_Service_Container::get_template_builder()->load_template_search( $template_data ) );
		}

	} catch ( Exception $e ) {
		// Missing template ?
		WPSOLR_Escape::echo_escaped( sprintf( 'The search could not be performed. An error occured while trying to connect to the Apache Solr server. <br/><br/>%s<br/>', WPSOLR_Escape::esc_html( $e->getMessage() ) ) );
	}

}


/**
 * @throws Exception
 */
function return_solr_instance() {

	$status      = '0';
	$message     = '';
	$output_data = [];

	if ( isset( $_POST['security'] ) &&
	     wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'security' ] ), WPSOLR_NONCE_FOR_DASHBOARD ) ) {

		try {

			require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

			$index_engine = isset( $_POST['sindex_engine'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_engine' ] ) : WPSOLR_AbstractEngineClient::ENGINE_SOLR;
			$index_uuid   = isset( $_POST['sindex_uuid'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_uuid' ] ) : '';

			$index_hosting_api_id = isset( $_POST['sindex_hosting_api_id'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_hosting_api_id' ] ) : WPSOLR_Hosting_Api_Solr_None::HOSTING_API_ID;
			$index_region_id      = isset( $_POST['sindex_region_id'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_region_id' ] ) : '';
			$index_aws_region     = isset( $_POST['sindex_aws_region'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_aws_region' ] ) : '';
			$hosting_api          = WPSOLR_Hosting_Api_Abstract::get_hosting_api_by_id( $index_hosting_api_id );
			$index_language_code  = isset( $_POST['sindex_language_code'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_language_code' ] ) : '';
			$index_analyser_id    = isset( $_POST['sindex_analyser_id'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_analyser_id' ] ) : '';
			$index_weaviate_openai_config_type              = isset( $_POST['sindex_weaviate_openai_config_type'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_openai_config_type' ] ) : '';
			$index_weaviate_openai_config_model             = isset( $_POST['sindex_weaviate_openai_config_model'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_openai_config_model' ] ) : '';
			$index_weaviate_openai_config_model_version     = isset( $_POST['sindex_weaviate_openai_config_model_version'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_openai_config_model_version' ] ) : '';
			$index_weaviate_openai_config_type_qna          = isset( $_POST['sindex_weaviate_openai_config_type_qna'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_openai_config_type_qna' ] ) : '';
			$index_weaviate_openai_config_model_qna         = isset( $_POST['sindex_weaviate_openai_config_model_qna'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_openai_config_model_qna' ] ) : '';
			$index_weaviate_openai_config_model_version_qna = isset( $_POST['sindex_weaviate_openai_config_model_version_qna'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_openai_config_model_version_qna' ] ) : '';
			$index_weaviate_huggingface_config_model        = isset( $_POST['sindex_weaviate_huggingface_config_model'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_huggingface_config_model' ] ) : '';
			$index_weaviate_huggingface_config_model_query  = isset( $_POST['sindex_weaviate_huggingface_config_model_query'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_huggingface_config_model_query' ] ) : '';
			$index_weaviate_cohere_config_model             = isset( $_POST['sindex_weaviate_cohere_config_model'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_cohere_config_model' ] ) : '';
			$index_weaviate_palm_config_model               = isset( $_POST['sindex_weaviate_palm_config_model'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_weaviate_palm_config_model' ] ) : '';
			$vespa_service_content_id                       = isset( $_POST['svespa_service_content_id'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'svespa_service_content_id' ] ) : '';
			$vespa_service_container_id                     = isset( $_POST['svespa_service_container_id'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'svespa_service_container_id' ] ) : '';
			$index_client_adapter                           = isset( $_POST['sindex_client_adapter'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_client_adapter' ] ) : '';
			$text_vectorizer_id                             = isset( $_POST['stext_vectorizer_id'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'stext_vectorizer_id' ] ) : '';
			$index_cert_1                                   = isset( $_POST['sindex_cert_1'] ) ? WPSOLR_Sanitize::sanitize_textarea_field( $_POST, [ 'sindex_cert_1' ] ) : '';

			$endpoint  = ! empty( $_POST['sendpoint'] ) ? trim( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sendpoint' ] ) ) : '';
			$endpoint1 = ! empty( $_POST['sendpoint1'] ) ? trim( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sendpoint1' ] ) ) : '';
			$username  = isset( $_POST['skey'] ) ? trim( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'skey' ] ) ) : '';

			if ( ! empty( $_POST['sis_index_creation'] ) ) {
				$slabel                     = $hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_INDEX_LABEL,
					[
						'user_name'   => $username,
						'index_label' => WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'slabel' ] )
					], WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'slabel' ] ) );
				$output_data['index_label'] = $slabel;
			} else {
				$slabel = WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'slabel' ] );
			}

			$path     = ! empty( $_POST['spath'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'spath' ] ) : $hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_PATH, $slabel, '' );
			$port     = ! empty( $_POST['sport'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sport' ] ) : $hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_PORT, 'donotcare', '' );
			$host     = ! empty( $_POST['shost'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'shost' ] ) : $hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_HOST_BY_REGION_ID, $index_region_id, '' );
			$password = WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'spwd' ] );
			$protocol = ! empty( $_POST['sproto'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sproto' ] ) : $hosting_api->get_data_by_id( WPSOLR_Hosting_Api_Abstract::DATA_SCHEME, $index_region_id, WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sproto' ] ) );

			if ( ! empty( $endpoint ) ) {
				// Endpoint will replace scheme, port and host
				$url      = wp_parse_url( $endpoint );
				$protocol = $url['scheme'];
				$port     = isset( $url['port'] ) ? $url['port'] : ( ( 'https' === $protocol ) ? '443' : '80' );
				$host     = $url['host'];
				if ( isset( $url['user'] ) ) {
					$username                 = $url['user'];
					$output_data['index_key'] = $url['user'];

					if ( $hosting_api->get_is_endpoint_only() ) {
						// Remove user:password from endpoint
						$output_data['index_endpoint'] = str_replace( sprintf( '%s:%s@', $url['user'], $url['pass'] ), '', $endpoint );
					}
				}
				if ( isset( $url['pass'] ) ) {
					$password                    = $url['pass'];
					$output_data['index_secret'] = $url['pass'];
				}

				$output_data['index_protocol'] = $protocol;
				$output_data['index_port']     = $port;
				$output_data['index_host']     = $host;


			}

			if ( ! empty( $endpoint1 ) ) {
				// Endpoint will replace scheme, port and host
				$url       = wp_parse_url( $endpoint1 );
				$protocol1 = $url['scheme'];
				$port1     = isset( $url['port'] ) ? $url['port'] : ( ( 'https' === $protocol ) ? '443' : '80' );
				$host1     = $url['host'];
			}

			$solr_cloud_extra_parameters = [];
			switch ( $index_engine ) {
				case WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD:
					$solr_cloud_extra_parameters['index_solr_cloud_shards']             = isset( $_POST['index_solr_cloud_shards'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'index_solr_cloud_shards' ] ) : '2';
					$solr_cloud_extra_parameters['index_solr_cloud_replication_factor'] = isset( $_POST['index_solr_cloud_replication_factor'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'index_solr_cloud_replication_factor' ] ) : '2';
					$solr_cloud_extra_parameters['index_solr_cloud_max_shards_node']    = isset( $_POST['index_solr_cloud_max_shards_node'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'index_solr_cloud_max_shards_node' ] ) : '2';
					break;

				case WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH:
					$solr_cloud_extra_parameters[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_SHARDS ]   = isset( $_POST[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_SHARDS ] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_SHARDS ] ) : '1';
					$solr_cloud_extra_parameters[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_REPLICAS ] = isset( $_POST[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_REPLICAS ] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_REPLICAS ] ) : '1';
					break;

				case WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH:
					$solr_cloud_extra_parameters[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS ]   = isset( $_POST[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS ] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS ] ) : '1';
					$solr_cloud_extra_parameters[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS ] = isset( $_POST[ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS ] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ WPSOLR_Hosting_Api_Abstract::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS ] ) : '1';
					break;

			}


			if ( ! empty( $username ) &&
			     ! empty( $password ) &&
			     $hosting_api->get_is_host_contains_user_password()
			) {

				$host = sprintf( '%s:%s@%s', $username, $password, $host );
			}

			switch ( $index_hosting_api_id ) {


				default:
					$is_hosting_aws = false;
			}

			$client = WPSOLR_AbstractSearchClient::create_from_config( [
					'index_engine'          => $index_engine,
					'index_uuid'            => $index_uuid,
					'index_label'           => $slabel,
					'scheme'                => $protocol,
					'host'                  => $host,
					'port'                  => $port,
					'scheme1'               => $protocol1 ?? '',
					'host1'                 => $host1 ?? '',
					'port1'                 => $port1 ?? '',
					'path'                  => $path,
					'username'              => $username,
					//$username,
					'password'              => $password,
					//$password,
					'timeout'               => WPSOLR_AbstractSearchClient::DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND,
					'aws_access_key_id'     => $is_hosting_aws ? $username : '',
					'aws_secret_access_key' => $is_hosting_aws ? $password : '',
					'aws_region'            => $is_hosting_aws ? $index_aws_region : '',
					'extra_parameters'      => [
						$index_engine                                    => $solr_cloud_extra_parameters,
						'index_hosting_api_id'                           => $index_hosting_api_id,
						'index_email'                                    => isset( $_POST['sindex_email'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_email' ] ) : '',
						'index_api_key'                                  => isset( $_POST['sindex_api_key'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_api_key' ] ) : '',
						'index_api_key_1'                                => isset( $_POST['sindex_api_key_1'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_api_key_1' ] ) : '',
						'index_region_id'                                => $index_region_id,
						'index_language_code'                            => $index_language_code,
						'index_analyser_id'                              => $index_analyser_id,
						'index_weaviate_openai_config_type'              => $index_weaviate_openai_config_type,
						'index_weaviate_openai_config_model'             => $index_weaviate_openai_config_model,
						'index_weaviate_openai_config_model_version'     => $index_weaviate_openai_config_model_version,
						'index_weaviate_openai_config_type_qna'          => $index_weaviate_openai_config_type_qna,
						'index_weaviate_openai_config_model_qna'         => $index_weaviate_openai_config_model_qna,
						'index_weaviate_openai_config_model_version_qna' => $index_weaviate_openai_config_model_version_qna,
						'index_weaviate_huggingface_config_model'        => $index_weaviate_huggingface_config_model,
						'index_weaviate_huggingface_config_model_query'  => $index_weaviate_huggingface_config_model_query,
						'index_key_json'                                 => isset( $_POST['sindex_key_json'] ) ? stripslashes( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_key_json' ] ) ) : '',
						'index_key_json_1'                               => isset( $_POST['sindex_key_json_1'] ) ? stripslashes( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_key_json_1' ] ) ) : '',
						'index_catalog_branch'                           => isset( $_POST['sindex_catalog_branch'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sindex_catalog_branch' ] ) : '',
						'index_weaviate_cohere_config_model'             => $index_weaviate_cohere_config_model,
						'index_weaviate_palm_config_model'               => $index_weaviate_palm_config_model,
						'dataset_group_arn'                              => isset( $_POST['sdataset_group_arn'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sdataset_group_arn' ] ) : '',
						'dataset_items_arn'                              => isset( $_POST['sdataset_items_arn'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sdataset_items_arn' ] ) : '',
						'dataset_item_interaction_events_arn'            => isset( $_POST['sdataset_item_interaction_events_arn'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sdataset_item_interaction_events_arn' ] ) : '',
						'dataset_users_arn'                              => isset( $_POST['sdataset_users_arn'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sdataset_users_arn' ] ) : '',
						'dataset_actions_arn'                            => isset( $_POST['sdataset_actions_arn'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sdataset_actions_arn' ] ) : '',
						'dataset_user_action_interaction_events_arn'     => isset( $_POST['sdataset_user_action_interaction_events_arn'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'sdataset_user_action_interaction_events_arn' ] ) : '',
						'vespa_service_content_id'                       => $vespa_service_content_id,
						'vespa_service_container_id'                     => $vespa_service_container_id,
						'index_client_adapter'                           => $index_client_adapter,
						'text_vectorizer_id'                             => $text_vectorizer_id,
						'index_cert_1'                                   => $index_cert_1,
					]
				]
			);

			$action = isset( $_POST['wpsolr_index_action'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'wpsolr_index_action' ] ) : 'wpsolr_index_action_ping';


			if ( 'wpsolr_index_action_delete' === $action ) {

				// Delete the index and it's data.
				$client->admin_delete_index();

			} else {

				// Just trigger an exception if bad ping.
				$client->admin_ping( $output_data );
			}

		} catch ( Exception $e ) {

			$str_err      = '';
			$solr_code    = $e->getCode();
			$solr_message = $e->getMessage();

			switch ( $e->getCode() ) {

				case 401:
					$str_err .= "<br /><span>The server authentification failed. Please check your user/password (Solr code http $solr_code)</span><br />";
					break;

				case 400:
				case 404:

					$str_err .= "<br /><span>We could not join your search server. Your path could be malformed, or your search server down (error code $solr_code)</span><br />";
					break;

				default:

					// Try to interpret some special errors with code "0"
					if ( ( method_exists( $e, 'getStatusMessage' ) ) && ( strpos( $e->getStatusMessage(), 'Failed to connect' ) > 0 ) && ( strpos( $e->getStatusMessage(), 'Connection refused' ) > 0 ) ) {

						$str_err .= "<br /><span>We could not connect to your Solr server. It's probably because the port is blocked. Please try another port, for instance 443, or contact your hosting provider/network administrator to unblock your port.</span><br />";

					}

					break;

			}

			$status  = '2';
			$message = sprintf( '%s<br>%s', $str_err, $solr_message );
		}
	}

	WPSOLR_Escape::echo_esc_json( wp_json_encode( [
		'status'  => $status,
		'message' => $message,
		'return'  => $output_data
	] ) );
	die();
}

add_action( 'wp_ajax_' . 'return_solr_instance', 'return_solr_instance' );


function return_solr_status() {

	if ( isset( $_POST['security'] ) && wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'security' ] ), WPSOLR_NONCE_FOR_DASHBOARD ) ) {
		WPSOLR_Escape::echo_esc_html( WPSOLR_Service_Container::get_solr_client()->get_solr_status() );
	}

	die();
}

add_action( 'wp_ajax_' . 'return_solr_status', 'return_solr_status' );


function return_solr_results() {

	fun_search_indexed_data( true );
	die();
}

add_action( 'wp_ajax_nopriv_' . 'return_solr_results', 'return_solr_results' );
add_action( 'wp_ajax_' . 'return_solr_results', 'return_solr_results' );

/*
 * Ajax call to index Solr documents
 */
function return_solr_index_data() {

	if ( isset( $_POST['security'] ) &&
	     wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'security' ] ), WPSOLR_NONCE_FOR_DASHBOARD ) ) {
		try {

			WPSOLR_Error_Handling::log_ajax_error_handling();

			// Indice of Solr index to index
			$solr_index_indice = WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'solr_index_indice' ] );

			// Batch size
			$batch_size = intval( $_POST['batch_size'] ?? "20" );

			// nb of document sent until now
			$nb_results = intval( $_POST['nb_results'] ?? "0" );

			// Debug infos displayed on screen ?
			$is_debug_indexing = isset( $_POST['is_debug_indexing'] ) && ( 'true' === $_POST['is_debug_indexing'] );

			// Re-index all the data ?
			$is_reindexing_all_posts = isset( $_POST['is_reindexing_all_posts'] ) && ( 'true' === $_POST['is_reindexing_all_posts'] );

			// Post types to reindex
			$post_types = isset( $_POST['post_types'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'post_types' ] ) : [];


			// Stop indexing ?
			$is_stopping = isset( $_POST['is_stopping'] ) & ( 'true' === $_POST['is_stopping'] ) ? true : false;

			$solr = WPSOLR_IndexSolariumClient::create( $solr_index_indice );

			$current_user = wp_get_current_user();

			// Reset documents if requested
			if ( $is_reindexing_all_posts ) {
				$solr->reset_documents( $current_user->user_email );
			}

			$res_final = $solr->index_data( $is_stopping, $current_user->user_email, WPSOLR_Model_Builder::get_model_type_objects( $post_types ),
				$batch_size, null, $is_debug_indexing, false
			);

			// Increment nb of document sent until now
			$res_final['nb_results'] += $nb_results;

			WPSOLR_Escape::echo_esc_json( wp_json_encode( $res_final ) );

		} catch ( Exception $e ) {

			WPSOLR_Escape::echo_esc_json( wp_json_encode(
				[
					'nb_results'        => 0,
					'status'            => $e->getCode(),
					'message'           => htmlentities( $e->getMessage() ),
					'indexing_complete' => false,
				]
			) );

		}
	}

	die();
}

add_action( 'wp_ajax_' . 'return_solr_index_data', 'return_solr_index_data' );


/*
 * Ajax call to clear Solr documents
 */
function return_solr_delete_index() {

	if ( isset( $_POST['security'] ) &&
	     wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'security' ] ), WPSOLR_NONCE_FOR_DASHBOARD ) ) {
		try {

			WPSOLR_Error_Handling::log_ajax_error_handling();

			// Indice of Solr index to delete
			$solr_index_indice = WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'solr_index_indice' ] );

			// Post types to delete
			$post_types = isset( $_POST['post_types'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'post_types' ] ) : null;

			$models = null;
			if ( ! is_null( $post_types ) ) {
				$models = WPSOLR_Model_Builder::get_model_type_objects( $post_types );
			}

			$current_user = wp_get_current_user();

			$solr = WPSOLR_IndexSolariumClient::create( $solr_index_indice );
			$solr->delete_documents( $current_user->user_email, $models );

		} catch ( Exception $e ) {

			WPSOLR_Escape::echo_esc_json( wp_json_encode(
				[
					'nb_results'        => 0,
					'status'            => $e->getCode(),
					'message'           => htmlentities( $e->getMessage() ),
					'indexing_complete' => false,
				]
			) );

			die();
		}
	}

	WPSOLR_Escape::echo_esc_json( wp_json_encode( '' ) );

	die();
}

add_action( 'wp_ajax_' . 'return_solr_delete_index', 'return_solr_delete_index' );

/**
 * Ajax call to clear an indexing lock
 **/
function wpsolr_ajax_remove_process_lock() {

	try {

		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce(
				WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'security' ] ), WPSOLR_NONCE_FOR_DASHBOARD ) ) {
			throw new \Exception( 'Unauthorized Ajax call.' );
		}

		// Process to stop/unlock
		$process_id = isset( $_POST['process_id'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'process_id' ] ) : '';

		WPSOLR_AbstractIndexClient::unlock_process( $process_id );


		WPSOLR_Escape::echo_esc_json( wp_json_encode(
			[
				'status'  => 0,
				'message' => 'Indexing process has been stopped.',
			]
		) );


	} catch ( \Exception $e ) {

		WPSOLR_Escape::echo_esc_json( wp_json_encode(
			[
				'status'  => $e->getCode(),
				'message' => htmlentities( $e->getMessage() ),
			]
		) );
	}

	die();
}

// Ajax to remove locks
add_action( 'wp_ajax_' . 'wpsolr_ajax_remove_process_lock', 'wpsolr_ajax_remove_process_lock' );
