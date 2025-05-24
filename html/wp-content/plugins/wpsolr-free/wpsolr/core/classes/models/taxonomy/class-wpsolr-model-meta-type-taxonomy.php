<?php

namespace wpsolr\core\classes\models\taxonomy;

use wpsolr\core\classes\models\WPSOLR_Model_Meta_Type_Abstract;


/**
 * Class WPSOLR_Model_Meta_Type_Taxonomy
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model_Meta_Type_Taxonomy extends WPSOLR_Model_Meta_Type_Abstract {

	const META_TYPE = 'taxonomy';

	const TABLE_TERM_TAXONOMY = 'term_taxonomy';

	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function __construct( $taxonomy ) {

		if ( ! isset( $taxonomy ) ) {
			throw new \Exception( 'WPSOLR: Missing post type parameter in model constructor.' );
		}

		$taxonomy_obj = $this->get_taxonomy( $taxonomy );

		$this->set_label( $taxonomy_obj->label )
		     ->set_singular_label( $taxonomy_obj->labels->singular_name )
		     ->set_table_name( 'term_taxonomy' )
		     ->set_column_id( 'term_id' )
		     ->set_column_last_updated( null )
		     ->set_type( $taxonomy );
	}

	/**
	 * @inheritDoc
	 */
	static protected function _child_get_model_type_object( $type ) {
		return ( false !== get_taxonomy( $type ) ) ? new static( $type ) : null;
	}

	/**
	 * @inheritdoc
	 *
	 * @param WPSOLR_Model_Meta_Type_Taxonomy $taxonomy
	 * @param string $term_id
	 *
	 * @return WPSOLR_Model_Taxonomy_Term
	 */
	public static function get_model( $taxonomy, $term_id ) {
		return ( new WPSOLR_Model_Taxonomy_Term() )->set_data( $term_id, get_term( $term_id, $taxonomy->get_type() ) );
	}

	/**
	 * @inherit
	 */
	public function get_fields() {
		return [];
	}

	/**
	 * @param bool $is_deleted *
	 *
	 * @inheritdoc
	 *
	 */
	public function get_indexing_sql( $index_indice, $debug_text, $batch_size = 100, $post = null, $is_debug_indexing = false, $is_only_exclude_ids = false, $last_post_date_indexed = null, bool $is_deleted = false ) {

		if ( ! empty( $this->indexing_sql ) ) {
			return $this->indexing_sql;
		}

		global $wpdb;

		$taxonomy = $this->get_type();

		$query_from = '';
		$query_from .= "(";
		$query_from .= sprintf( "SELECT A.term_id as term_id %s ", ( 0 === $batch_size ) ? '' : ', NULL as post_modified' );
		$query_from .= sprintf( " FROM %s AS A ", $wpdb->prefix . $this->get_table_name() );
		$query_from .= ( $is_only_exclude_ids || isset( $post ) ) ? " WHERE (1 = 1) " : " WHERE (A.term_id > %d) ";
		$query_from .= " AND (A.taxonomy = '{$taxonomy}') ";
		$query_from .= " UNION ";
		$query_from .= sprintf( "SELECT C.model_id as term_id %s ", ( 0 === $batch_size ) ? '' : ', C.modified as post_modified' );
		$query_from .= sprintf( " FROM %s%s C ", $wpdb->prefix, static::INDEX_HISTORY_TABLE_NAME );
		$query_from .= " WHERE C.model_meta_type = 'taxonomy' ";
		$query_from .= " AND C.model_id in (SELECT term_id FROM {$wpdb->prefix}{$this->get_table_name()} WHERE taxonomy = '{$taxonomy}') ";
		$query_from .= ") A ";


		$query_join_stmt  = '';
		$query_where_stmt = ' (1 = 1)';

		if ( 0 === $batch_size ) {
			// count only
			$query_select_stmt   = 'count(A.term_id) as TOTAL';
			$query_order_by_stmt = '';

			if ( ! $is_only_exclude_ids ) {
				$query_where_stmt = " (%s >= '') ";
			}

		} else {

			$query_select_stmt   = sprintf( "A.term_id as ID, post_modified, null as post_parent, '%s' as post_type", $taxonomy );
			$query_order_by_stmt = sprintf( 'A.post_modified asc, A.term_id + 0 ASC' );

			$query_where_stmt = " (%s >= '') ";
		}

		if ( isset( $post ) ) {
			// Add condition on the term
			$query_where_stmt = " (A.term_id = %d) ";
		}

		return [
			'debug_info' => '',
			'SELECT'     => $query_select_stmt,
			'FROM'       => $query_from,
			'JOIN'       => $query_join_stmt,
			'WHERE'      => $query_where_stmt,
			'ORDER'      => $query_order_by_stmt,
			'LIMIT'      => $batch_size,
		];
	}

	/**
	 * @param $taxonomy
	 *
	 * @return false|\WP_Taxonomy
	 * @throws \Exception
	 */
	protected function get_taxonomy( $taxonomy ) {

		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( false === $taxonomy_obj ) {
			throw new \Exception( sprintf( "WPSOLR: Undefined taxonomy '%s'.", esc_html( $taxonomy ) ) );
		}

		return $taxonomy_obj;
	}

	/**
	 * @inheritdoc
	 */
	public function get_sql_join_on_for_wpml() {
		global $wpdb;

		$result = " JOIN {$wpdb->prefix}term_taxonomy B ON B.term_id = A.term_id ";
		$result .= " JOIN " . $wpdb->prefix . WPSOLR_Plugin_Wpml::TABLE_ICL_TRANSLATIONS . ' AS ' . 'icl_translations';
		$result .= " ON A.term_id = icl_translations.element_id AND icl_translations.element_type = CONCAT('tax_', B.taxonomy) AND icl_translations.language_code = '%s' ";

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function get_sql_join_on_for_polylang() {
		global $wpdb;

		// Join statement
		$result = ' JOIN ' . $wpdb->prefix . WPSOLR_Plugin_Polylang::TABLE_TERM_RELATION_SHIPS . ' AS ' . 'wp_term_relationships';
		$result .= " ON A.term_id = wp_term_relationships.object_id AND wp_term_relationships.term_taxonomy_id = '%s' ";

		return $result;
	}

}