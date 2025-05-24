<?php

namespace wpsolr\core\classes\models\post;

use wpsolr\core\classes\metabox\WPSOLR_Metabox;
use wpsolr\core\classes\models\WPSOLR_Model_Meta_Type_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;


/**
 * Class WPSOLR_Model_Meta_Type_Post
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model_Meta_Type_Post extends WPSOLR_Model_Meta_Type_Abstract {

	const META_TYPE = 'post_type';

	/**
	 * Some post types
	 */
	const POST_TYPE_ATTACHMENT = 'attachment';

	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function __construct( $post_type ) {

		if ( ! isset( $post_type ) ) {
			throw new \Exception( 'WPSOLR: Missing post type parameter in model constructor.' );
		}

		$post_type_obj = get_post_type_object( $post_type );
		if ( is_null( $post_type_obj ) ) {
			throw new \Exception( sprintf( "WPSOLR: Undefined post type '%s'.", esc_html( $post_type ) ) );
		}
		if ( ! isset( $post_type_obj->label ) ) {
			throw new \Exception( sprintf( "WPSOLR: no label for post type '%s'.", esc_html( $post_type ) ) );
		}

		$this->set_label( $post_type_obj->label )
		     ->set_singular_label( $post_type_obj->labels->singular_name )
		     ->set_table_name( 'posts' )
		     ->set_column_id( 'ID' )
		     ->set_column_last_updated( 'post_modified' )
		     ->set_type( $post_type );
	}

	/**
	 * @inheritdoc
	 */
	public function get_is_search() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	static protected function _child_get_model_type_object( $type ) {
		$object = get_post_type_object( $type );

		return ! is_null( $object ) ? new static( $type ) : null;
	}

	/**
	 * @inheritdoc
	 *
	 * @return WPSOLR_Model_Post
	 */
	public static function get_model( $post_type, $post_id ) {
		return ( new WPSOLR_Model_Post() )->set_data( $post_id, get_post( $post_id ) );
	}

	/**
	 * @inerhitDoc
	 */
	public function has_attachments() {
		return ( static::POST_TYPE_ATTACHMENT === $this->type );
	}

	/**
	 * @inerhitDoc
	 */
	public function has_images() {
		return ! $this->has_attachments();
	}

	/**
	 * Get all mime types for attachments
	 *
	 * @return array
	 */
	public function get_allowed_mime_types() {
		return $this->has_attachments() ? get_allowed_mime_types() : [];
	}

	/**
	 * Convert old format, with only post types fields, and without the post_type's field.
	 * Add the post type for each custom field
	 *
	 * @param string[] $field_names_with_str
	 * @param string[] $post_types
	 *
	 * @return array
	 */
	public static function reformat_old_custom_fields( $field_names_with_str, $post_types ) {
		global $wpdb;

		if ( empty( $field_names_with_str ) || empty( $post_types ) ) {
			return [];
		}

		$results = [];

		// Remove each last '_str'
		$field_names = [];
		foreach ( $field_names_with_str as $field_name_str ) {
			$field_names[] = WpSolrSchema::replace_field_name_extension_with( $field_name_str, '' );
		}

		$format_fields     = implode( ', ', array_fill( 0, count( $field_names ), '%s' ) );
		$format_post_types = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		$sql = $wpdb->prepare( "
								SELECT 		distinct POSTS.post_type as post_type, GROUP_CONCAT( distinct CONCAT(POSTMETAS.meta_key, %s) SEPARATOR ',') as field_names
								FROM 		$wpdb->posts as POSTS INNER JOIN $wpdb->postmeta  AS POSTMETAS
								ON			POSTS.ID = POSTMETAS.post_id
								WHERE   	POSTMETAS.meta_key IN ($format_fields)
								GROUP BY 	POSTS.post_type
								HAVING 		POSTS.post_type IN ($format_post_types)",
			array_merge( [ WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ], $field_names, $post_types )
		);

		$query_results = $wpdb->get_results( "{$sql}" );

		$nb_results = count( $query_results );
		for ( $i = 0; $i < $nb_results; $i ++ ) {
			foreach ( explode( ',', $query_results[ $i ]->field_names ) as $field_name ) {
				$results[ $query_results[ $i ]->post_type ][ $field_name ] = '';
			}
		}

		return $results;
	}

	/**
	 * @inherit
	 */
	public function get_fields() {
		global $wpdb;

		$sql = $wpdb->prepare( "
								SELECT distinct meta_key 
								FROM 	$wpdb->posts as POSTS INNER JOIN $wpdb->postmeta  AS POSTMETAS
								ON		POSTS.ID = POSTMETAS.post_id
								WHERE 	POSTS.post_type = %s",
			$this->get_type()
		);

		$results = $wpdb->get_col( "{$sql}" );

		$results = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS, $results, $this->get_type() );

		return $results;
	}

	/**
	 * @param bool $is_deleted *
	 *
	 * @inheritDoc
	 */
	public function get_indexing_sql( $index_indice, $debug_text, $batch_size = 100, $post = null, $is_debug_indexing = false, $is_only_exclude_ids = false, $last_post_date_indexed = null, bool $is_deleted = false ) {

		if ( ! empty( $this->indexing_sql ) ) {
			return $this->indexing_sql;
		}

		global $wpdb;

		$query_from         = $wpdb->prefix . $this->get_table_name() . ' AS A';
		$query_join_stmt    = '';
		$query_where_stmt   = '';
		$post_type          = $this->get_type();
		$post_type_is_admin = $this->get_post_type_is_admin( $post_type );
		$sql_post_type      = $this->_get_sql_post_type();

		// Build the WHERE clause

		if ( static::POST_TYPE_ATTACHMENT !== $sql_post_type ) {
			// Where clause for post types

			$where_p = " post_type = '{$sql_post_type}' ";

		} else {
			// Build the attachment types clause

			$attachment_types = str_replace( ',', "','", WPSOLR_Service_Container::getOption()->get_option_index_attachment_types_str() );
			if ( isset( $attachment_types ) && ( '' !== $attachment_types ) ) {
				$where_a = sprintf( " ( post_status='%s' OR post_status='inherit' ) AND post_type='attachment' AND post_mime_type in ('$attachment_types') ",
					$is_deleted ? 'trash' : ( $post_type_is_admin ? 'private' : 'publish' ) );
			} else {
				$where_a = ' (1 = 2) '; // No attachment type selected: should return nothing.
			}
		}


		if ( isset( $where_p ) ) {

			if ( $post_type_is_admin ) {
				// All status to be indexed by default (for search in admin)
				$query_where_stmt = $this->_get_sql_where_statuses() . " AND ( $where_p ) ";

			} else {
				// Only published status by default (for search on front only)

				$index_post_statuses = implode( ',', $is_deleted ? [ 'trash' ] : apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_STATUSES_TO_INDEX, [ 'publish' ], $post_type ) );
				$index_post_statuses = str_replace( ',', "','", $index_post_statuses );
				$query_where_stmt    = "post_status IN ('$index_post_statuses') AND ( $where_p )";
			}

			if ( isset( $where_a ) ) {
				$query_where_stmt = "( $query_where_stmt ) OR ( $where_a )";
			}

		} elseif ( isset( $where_a ) ) {

			$query_where_stmt = $where_a;
		}

		if ( 0 === $batch_size ) {
			// count only
			$query_select_stmt = 'count(ID) as TOTAL';

		} else {

			$query_select_stmt = 'ID, post_modified, post_parent, post_type';
		}

		if ( isset( $post ) ) {
			// Add condition on the $post

			$query_where_stmt = $this->_get_sql_where_id() . " AND ( $query_where_stmt ) ";

		} elseif ( $is_only_exclude_ids ) {
			// No condition on the date for $is_only_exclude_ids

			$query_where_stmt = " ( $query_where_stmt ) ";

		} else {
			// Condition on the date only for the batch, not for individual posts

			$query_where_stmt = $this->_get_sql_where_id_and_date() . " AND ( $query_where_stmt ) ";
		}

		// Excluded ids from SQL
		$blacklisted_ids  = $post_type_is_admin ? [] : $this->get_blacklisted_ids();
		$debug_info       = [
			'Posts excluded from the index' => implode( ',', $blacklisted_ids ),
		];
		$query_where_stmt .= $this->get_sql_statement_blacklisted_ids( $blacklisted_ids, $is_only_exclude_ids );

		$query_order_by_stmt = 'post_modified ASC, ID ASC';

		return [
			'debug_info' => $debug_info,
			'SELECT'     => $query_select_stmt,
			'FROM'       => $query_from,
			'JOIN'       => $query_join_stmt,
			'WHERE'      => $query_where_stmt,
			'ORDER'      => $query_order_by_stmt,
			'LIMIT'      => $batch_size,
		];
	}

	/**
	 * Get blacklisted post ids
	 * @return array
	 */
	public function get_blacklisted_ids() {

		$excluded_meta_ids = WPSOLR_Metabox::get_blacklisted_ids();
		$excluded_list_ids = WPSOLR_Service_Container::getOption()->get_option_index_post_excludes_ids_from_indexing();

		$all_excluded_ids = array_merge( $excluded_meta_ids, $excluded_list_ids );

		return $all_excluded_ids;
	}

	/**
	 * Generate a SQL restriction on all blacklisted post ids
	 *
	 * @param array $blacklisted_ids Array of post ids blaclisted
	 *
	 * @param bool $is_only_exclude_ids Do we find only excluded posts ?
	 *
	 * @return string
	 */
	private function get_sql_statement_blacklisted_ids( $blacklisted_ids, $is_only_exclude_ids = false ) {

		if ( empty( $blacklisted_ids ) ) {

			$result = $is_only_exclude_ids ? ' AND (1 = 2) ' : '';

		} else {

			$result = sprintf( $is_only_exclude_ids ? ' AND ID IN (%s) ' : ' AND ID NOT IN (%s) ', implode( ',', $blacklisted_ids ) );
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function get_sql_join_on_for_wpml() {
		global $wpdb;

		$result = " JOIN " . $wpdb->prefix . WPSOLR_Plugin_Wpml::TABLE_ICL_TRANSLATIONS . ' AS ' . 'icl_translations';
		$result .= " ON A.ID = icl_translations.element_id AND icl_translations.element_type = CONCAT('post_', A.post_type) AND icl_translations.language_code = '%s' ";

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function get_sql_join_on_for_polylang() {
		global $wpdb;

		// Join statement
		$result = ' JOIN ' . $wpdb->prefix . WPSOLR_Plugin_Polylang::TABLE_TERM_RELATION_SHIPS . ' AS ' . 'wp_term_relationships';
		$result .= " ON A.ID = wp_term_relationships.object_id AND wp_term_relationships.term_taxonomy_id = '%s' ";

		return $result;
	}

	protected function _get_sql_post_type(): string {
		return $this->get_type();
	}

	/**
	 * @inheritdoc
	 */
	public function get_linked_model_event() {
		return ( 'shop_order' === $this->get_type() ) ? new WPSOLR_Model_Meta_Type_Event_Post( $this->get_type() ) : null;
	}

	protected function _get_sql_where_id_and_date() {
		return ' ((post_modified = %s AND ID > %d) OR (post_modified > %s)) ';
	}

	/**
	 * @param string $post_type
	 *
	 * @return bool
	 */
	protected function get_post_type_is_admin( string $post_type ): bool {
		return WPSOLR_Service_Container::getOption()->get_option_index_post_type_is_admin( $post_type );
	}

	/**
	 * @param string $post_type
	 *
	 * @return string
	 */
	protected function _get_sql_where_statuses(): string {
		$index_post_statuses = implode( ',', apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_STATUSES_TO_INDEX, [], $this->get_type() ) );
		if ( ! empty( $index_post_statuses ) ) {

			$index_post_statuses = str_replace( ',', "','", $index_post_statuses );
			$query_where_stmt    = " post_status IN ('$index_post_statuses') ";

		} else {

			$index_post_statuses = str_replace( ',', "','", implode( ',', [ 'auto-draft' ] ) ); // All valid statuses
			$query_where_stmt    = " post_status NOT IN ('$index_post_statuses') ";

		}

		return $query_where_stmt;
	}

	protected function _get_sql_where_id() {
		return ' (ID = %d) ';
	}

	/**
	 * @inheritdoc
	 */
	public function not_used_get_subtypes( $model_type ): array {

		switch ( $model_type ) {
			case 'product':
				return function_exists( 'wc_get_product_types' ) ? wc_get_product_types() : [];

			default:
				return [];
		}

	}

}