<?php

namespace wpsolr\core\classes\admin\ui\ajax;


/**
 * Retrieve posts from any type
 * 
 * Class WPSOLR_Admin_UI_Ajax_Search_Posts
 * @package wpsolr\core\classes\admin\ui\ajax
 */
class WPSOLR_Admin_UI_Ajax_Search_Posts extends WPSOLR_Admin_UI_Ajax_Search {

	/**
	 *    Retrieve posts with SQL, as get_posts() does not accept a search parameter.
	 *
	 * @inheritDoc
	 */
	public static function execute_parameters( $parameters ) {

		global $wpdb;

		$sql_parameters = [ ];
		$sql_statement  = " SELECT ID, post_title FROM {$wpdb->posts} AS posts WHERE posts.post_status IN ( 'publish') ";

		if ( ! empty( $parameters[ self::PARAMETER_INCLUDE ] ) ) {
			$clause_in = $parameters[ self::PARAMETER_INCLUDE ];
			$sql_statement .= " AND posts.ID IN ($clause_in) "; // Cannot use %s, because prepare() add quotes
		}

		if ( ! empty( $parameters[ self::PARAMETER_EXCLUDE ] ) ) {
			$clause_in = $parameters[ self::PARAMETER_EXCLUDE ];
			$sql_statement .= " AND posts.ID NOT IN ($clause_in) "; // Cannot use %s, because prepare() add quotes
		}

		if ( ! empty( $parameters[ self::PARAMETER_TERM ] ) ) {
			$sql_parameters[] .= '%' . $wpdb->esc_like( $parameters[ self::PARAMETER_TERM ] ) . '%';
			$sql_statement .= " AND posts.post_title LIKE %s ";
		}

		$sql_statement .= " ORDER BY posts.post_title ASC, posts.ID DESC ";

		if ( empty( $parameters[ self::PARAMETER_INCLUDE ] ) && ! empty( $parameters[ self::PARAMETER_LIMIT ] ) ) {
			$sql_parameters[] .= $parameters[ self::PARAMETER_LIMIT ];
			$sql_statement .= " LIMIT %d ";
		}

		$query = $wpdb->prepare( "{$sql_statement}", $sql_parameters );

		return $query;
	}

	/**
	 * @inheritDoc
	 */
	public static function format_results( $parameters, $not_formatted_results ) {
		global $wpdb;

		$results = [ ];
		foreach ( $wpdb->get_results( "{$not_formatted_results}" ) as $row ) {
			$results[ $row->ID ] = $row->post_title;
		}

		return $results;
	}


}