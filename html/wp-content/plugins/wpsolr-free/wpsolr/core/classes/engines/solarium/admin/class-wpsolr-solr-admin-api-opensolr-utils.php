<?php

namespace wpsolr\core\classes\engines\solarium\admin;

trait WPSOLR_Solr_Admin_Api_Opensolr_Utils {

	/**
	 * @param $response_json
	 * @param $response_body
	 *
	 * @return object
	 * @throws \Exception
	 */
	protected function manage_response_result( $response_json, $response_body ) {

		/*
		if ( strlen( $response_body ) >= 500 ) {
			// Long body means error
			throw new \Exception( esc_html($response_body) );
		}
		*/

		if ( is_string( $response_json ) ) {
			// Not a json object. Probably an unexpected error on the server.
			throw new \Exception( esc_html( $response_json ) );
		}

		if ( $response_json->msg && is_object( $response_json->msg ) && property_exists( $response_json->msg, 'solrconfig.xml' ) && ( false !== strpos( $response_json->msg->{'solrconfig.xml'}, 'ERROR' ) ) ) {
			throw new \Exception( sprintf( 'The solrconfig.xml file was rejected by the Solr server:<br/><br/>%s',
				esc_html( $response_json->msg->{'solrconfig.xml'} ) ) );
		}

		if ( $response_json->msg && is_object( $response_json->msg ) && property_exists( $response_json->msg, 'schema.xml' ) && ( false !== strpos( $response_json->msg->{'schema.xml'}, 'ERROR' ) ) ) {
			throw new \Exception( sprintf( 'The schema.xml file was rejected by the Solr server:<br/><br/>%s',
				esc_html( $response_json->msg->{'schema.xml'} ) ) );
		}

		if ( ! empty( $response_json ) && ! $response_json->status ) {
			// Throw error instead
			throw new \Exception( esc_html( is_string( $response_json->msg ) ? $response_json->msg : ( is_object( $response_json->msg ) && is_string( $response_json->msg->error ?? $response_json->msg->ERROR ) ? ( $response_json->msg->error ?? $response_json->msg->ERROR ) : 'Unknown error during JSON call' ) ) );
		}

		return $response_json;
	}

}