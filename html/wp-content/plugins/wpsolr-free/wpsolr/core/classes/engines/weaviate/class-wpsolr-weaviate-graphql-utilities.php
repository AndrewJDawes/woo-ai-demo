<?php

namespace wpsolr\core\classes\engines\weaviate;

use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;

/**
 * GraphQL utilities
 */
trait WPSOLR_Weaviate_GraphQL_Utilities {

	/**
	 * @param string $name
	 * @param string $alias
	 *
	 * @return QueryBuilder
	 */
	protected function _gql_new_query( string $name = '', string $alias = '', $is_use_cache = false ): QueryBuilder {

		if ( $is_use_cache ) {
			if ( isset( $_gql_cache[ $name ] ) ) {
				// Use cache
				return $this->_gql_cache[ $name ];
			}

			// Store cache and use it
			return ( $this->_gql_cache[ $name ] = new QueryBuilder( $name, $alias ) );
		}

		// No caching
		return new QueryBuilder( $name, $alias );
	}

	/**
	 * @param string $name
	 * @param string $alias
	 *
	 * @return QueryBuilder
	 */
	protected function _gql_new_query_get( string $alias = '' ): QueryBuilder {
		return $this->_gql_new_query( 'Get', $alias );
	}

	/**
	 * @param string $name
	 * @param string $alias
	 *
	 * @return QueryBuilder
	 */
	protected function _gql_new_query_aggregate( string $alias = '' ): QueryBuilder {
		return $this->_gql_new_query( 'Aggregate', $alias );
	}

	/**
	 * @param string $name
	 * @param string $alias
	 *
	 * @return QueryBuilder
	 */
	protected function _gql_new_query_index( string $alias = '' ): QueryBuilder {
		return $this->_gql_new_query( $this->get_index_label(), $alias );
	}

	/**
	 * @href https://github.com/mghoneimy/php-graphql-client#the-full-form-1
	 *
	 * @param string $argument_raw
	 *
	 * @return RawObject
	 */
	private function __gql_new_argument_raw_value( string $argument_raw ): RawObject {
		return new RawObject( $argument_raw );
	}

	/**
	 * @param string $field_name
	 * @param string $operator
	 * @param string $field_value
	 *
	 * @return RawObject
	 */
	protected function _gql_new_argument_where_value_string( string $field_name, string $operator, string $field_value ): RawObject {
		return $this->__gql_new_argument_raw_value( sprintf( '{path: ["%s"], operator: %s, valueString: "%s"}', $field_name, $operator, $field_value ) );
	}

	/**
	 * Add an argument to group by a field name
	 *
	 * @param string $field_name
	 *
	 * @return RawObject
	 */
	protected function _gql_new_argument_group_by_value_string( string $field_name ): RawObject {
		return $this->__gql_new_argument_raw_value( sprintf( '["%s"]', $field_name ) );
	}

	/**
	 * @param string $concepts
	 * @param float $distance
	 *
	 * @return RawObject
	 */
	protected function _gql_new_argument_neartext_value_string( string $concepts, float $distance ): RawObject {
		$result = '';
		if ( ! empty( $concepts ) ) {
			$result = sprintf( '{concepts: ["%s"], distance: %f}', $concepts, $distance );
		}

		return $this->__gql_new_argument_raw_value( $result );
	}

	/**
	 * @href https://weaviate.io/developers/weaviate/current/graphql-references/vector-search-parameters.html#nearobject
	 *
	 * @param string $concepts
	 * @param float $distance
	 *
	 * @return RawObject
	 */
	protected function _gql_new_argument_nearobject_value_string( string $object_uuid, float $distance ): RawObject {
		$result = '';
		if ( ! empty( $object_uuid ) ) {
			$result = sprintf( '{id: "%s", distance: %f}', $object_uuid, $distance );
		}

		return $this->__gql_new_argument_raw_value( $result );
	}

	/**
	 * @param string $query
	 * @param float $alpha
	 *
	 * @return RawObject
	 */
	protected function _gql_new_argument_hybrid_value_string( string $query, float $alpha ): RawObject {
		$result = '';
		if ( ! empty( $query ) ) {
			$result = sprintf( '{query: "%s", alpha: %f}', $query, $alpha );
		}

		return $this->__gql_new_argument_raw_value( $result );
	}

	/**
	 * @param string $question
	 * @param float $distance
	 * @param bool $rerank
	 *
	 * @return RawObject
	 */
	protected function _gql_new_argument_ask_question_value_transformer_string( string $question, float $distance, bool $rerank ): RawObject {
		$result = '';
		if ( ! empty( $question ) ) {
			// https://weaviate.io/developers/weaviate/modules/reader-generator-modules/qna-transformers#graphql-ask-search
			/**
			 * $result = sprintf( '{question: "%s", distance: %f, rerank: %s}', $question, $distance, $rerank );
			 * // Bug with distance, rerank and certainty: https://github.com/weaviate/weaviate/issues/5596
			 */
			$result = sprintf( '{question: "%s"}', $question );
		}

		return $this->__gql_new_argument_raw_value( $result );
	}

	/**
	 * @param string $question
	 *
	 * @return RawObject
	 */
	protected function _gql_new_argument_ask_question_value_openai_string( string $question ): RawObject {
		$result = '';
		if ( ! empty( $question ) ) {
			// https://weaviate.io/developers/weaviate/modules/reader-generator-modules/qna-openai#graphql-ask-search
			$result = sprintf( '{question: "%s"}', $question );
		}

		return $this->__gql_new_argument_raw_value( $result );
	}

	/**
	 * @param array $sorts
	 *
	 * @return RawObject
	 */
	protected function _gql_new_argument_sorts( $sorts = [] ): RawObject {
		$results = [];
		foreach ( $sorts as $field_name => $sort_by ) {
			$results[] = sprintf( '{path: ["%s"], order: %s}', $field_name, $sort_by );
		}

		return $this->__gql_new_argument_raw_value( sprintf( '[%s]', implode( ",", $results ) ) );
	}

	/**
	 * @return QueryBuilder
	 */
	protected function _gql_new_field_additional_ask_question(): QueryBuilder {
		// https://weaviate.io/developers/weaviate/modules/reader-generator-modules/qna-transformers

		$_answer = $this->_gql_new_query( 'answer' );
		$_answer->selectField( 'hasAnswer' );
		//$_answer->selectField( 'distance' );
		$_answer->selectField( 'property' );
		$_answer->selectField( 'result' );
		$_answer->selectField( 'startPosition' );
		$_answer->selectField( 'endPosition' );

		$_additional = $this->_gql_new_query( '_additional', '', true );
		$_additional->selectField( $_answer );

		return $_additional;
	}


	/**
	 * @return QueryBuilder
	 */
	protected function _gql_new_field_additional_distance(): QueryBuilder {

		$_additional = $this->_gql_new_query( '_additional', '', true );
		$_additional->selectField( 'distance' );

		return $_additional;
	}

	/**
	 * @return QueryBuilder
	 */
	protected function _gql_new_field_additional_rerank( $property, $query = '' ): QueryBuilder {

		$_rerank = $this->_gql_new_query( 'rerank' );
		$_rerank->setArgument( 'property', $property );
		if ( ! empty( $query ) ) {
			$_rerank->setArgument( 'query', $query );
		}
		$_rerank->selectField( 'score' );

		$_additional = $this->_gql_new_query( '_additional', '', true );
		$_additional->selectField( $_rerank );

		return $_additional;
	}

}
