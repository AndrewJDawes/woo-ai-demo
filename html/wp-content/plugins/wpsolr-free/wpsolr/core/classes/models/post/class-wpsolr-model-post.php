<?php

namespace wpsolr\core\classes\models\post;


use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\metabox\WPSOLR_Metabox;
use wpsolr\core\classes\models\WPSOLR_Model_Abstract;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_Model_Post
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model_Post extends WPSOLR_Model_Abstract {

	/** @var \WP_Post */
	protected $data;

	/** @var array */
	protected $facets_items_is_hidden;

	/** @var int */
	static protected $highlight_fragsize;

	public function get_id_prefix() {
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function get_model_meta_type_class() {
		return WPSOLR_Model_Meta_Type_Post::class;
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return $this->data->ID;
	}

	/**
	 * @inheritdoc
	 */
	function get_type() {
		return $this->data->post_type;
	}

	/**
	 * @inheritdoc
	 */
	public function get_date_modified() {
		return $this->data->post_modified;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->data->post_title;
	}

	/**
	 * @inheritdoc
	 * @throws \Exception
	 */
	public function create_document_from_model_or_attachment_inner( WPSOLR_AbstractIndexClient $search_engine_client, $attachment_body ) {

		$post_to_index = $this->data;

		$pauthor_id = $post_to_index->post_author;
		// Post is NOT an attachment: we get the document body from the post object
		$pcontent    = $post_to_index->post_content . ( empty( $attachment_body ) ? '' : ( '. ' . $attachment_body ) );
		$post_parent = isset( $post_to_index->post_parent ) ? $post_to_index->post_parent : 0;

		$pexcerpt   = $post_to_index->post_excerpt;
		$pauth_info = get_userdata( $pauthor_id );
		$pauthor    = isset( $pauth_info ) && isset( $pauth_info->display_name ) ? $pauth_info->display_name : '';
		$pauthor_s  = isset( $pauth_info ) && isset( $pauth_info->user_nicename ) ? get_author_posts_url( $pauth_info->ID, $pauth_info->user_nicename ) : '';

		// Get the current post language
		$post_language = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_LANGUAGE, null, $post_to_index );

		$post_date         = $search_engine_client->search_engine_client_format_date( $post_to_index->post_date );
		$post_date_gmt     = solr_format_date( ( '0000-00-00 00:00:00' === $post_to_index->post_date_gmt ) ? $post_date : $post_to_index->post_date_gmt );
		$post_modified     = $search_engine_client->search_engine_client_format_date( $post_to_index->post_modified );
		$post_modified_gmt = solr_format_date( ( '0000-00-00 00:00:00' === $post_to_index->post_modified_gmt ) ? $post_modified : $post_to_index->post_modified_gmt );

		$comments_con = [];

		$indexing_options = $search_engine_client->get_search_engine_indexing_options();
		$comm             = isset( $indexing_options[ WpSolrSchema::_FIELD_NAME_COMMENTS ] ) ? $indexing_options[ WpSolrSchema::_FIELD_NAME_COMMENTS ] : '';

		$numcomments = 0;
		if ( $comm ) {
			$comments_con = [];

			$comments = get_comments( "status=approve&post_id={$post_to_index->ID}" );
			foreach ( $comments as $comment ) {
				array_push( $comments_con, $comment->comment_content );
				$numcomments += 1;
			}

		}
		$pcomments    = $comments_con;
		$pnumcomments = $numcomments;


		/*
			Get all custom categories selected for indexing, including 'category'
		*/
		$cats                            = [];
		$categories_flat_hierarchies     = [];
		$categories_non_flat_hierarchies = [];
		$aTaxo                           = WPSOLR_Service_Container::getOption()->get_option_index_taxonomies();
		$newTax                          = []; // Add categories by default
		if ( is_array( $aTaxo ) && count( $aTaxo ) ) {
		}
		foreach ( $aTaxo as $a ) {

			if ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING === substr( $a, ( strlen( $a ) - 4 ), strlen( $a ) ) ) {
				$a = substr( $a, 0, ( strlen( $a ) - 4 ) );
			}

			// Add only non empty categories
			if ( strlen( trim( $a ) ) > 0 ) {
				array_push( $newTax, $a );
			}
		}


		// Get all categories ot this post
		$terms = wp_get_post_terms( $post_to_index->ID, [ 'category' ], [ 'fields' => 'all_with_object_id' ] );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {

				// Add category and it's parents
				$term_parents_names = [];
				// Add parents in reverse order ( top-bottom)
				$term_parents_ids = array_reverse( get_ancestors( $term->term_id, 'category' ) );
				array_push( $term_parents_ids, $term->term_id );

				foreach ( $term_parents_ids as $term_parent_id ) {
					$term_parent = get_term( $term_parent_id, 'category' );

					if ( ! $term_parent || is_wp_error( $term_parent ) ) {
						// Some users get an error "Trying to get property ‘name’ of non-object": prevent it.
						// https://www.wpsolr.com/forums/topic/search-keyword-not-working-properly/page/4/
						continue;
					}

					/**
					 * Remove hidden terms
					 */
					if ( $this->_is_facet_hidden( 'categories', $term_parent->name ) ) {
						// This term, and therefore all its descendants, must be hidden. Stop now.
						break;
					}

					array_push( $term_parents_names, $term_parent->name );

					// Add the term to the non-flat hierarchy (for filter queries on all the hierarchy levels)
					array_push( $categories_non_flat_hierarchies, $term_parent->name );
				}

				// Add the term to the flat hierarchy
				if ( ! empty( $term_parents_names ) ) {
					$this->_add_terms_to_flat_hierarchy( $categories_flat_hierarchies, $term_parents_names, $search_engine_client->get_facet_hierarchy_separator() );
				}

				// Add the term to the categories
				if ( ! $this->_is_facet_hidden( 'categories', $term->name ) ) {
					if ( ! in_array( $term->name, $cats, true ) ) {
						array_push( $cats, $term->name );
					}
				}
			}
		}

		// Get all tags of this port
		$tag_array = [];
		$tags      = get_the_tags( $post_to_index->ID );
		if ( ! $tags == null ) {
			foreach ( $tags as $tag ) {

				/**
				 * Remove hidden terms
				 */
				if ( ! $this->_is_facet_hidden( 'tags', $tag->name ) ) {
					// This tag is not be hidden.
					array_push( $tag_array, $tag->name );
				}

			}
		}

		if ( ! empty( $post_parent ) ) {
			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_PARENT_I ] = $post_parent;
		}

		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_STATUS_S ] = $post_to_index->post_status;

		if ( isset( $indexing_options['p_excerpt'] ) && ( ! empty( $pexcerpt ) ) ) {

			// Index post excerpt, by adding it to the post content and also in its own field
			// Excerpt can therefore be: searched, autocompleted, highlighted.
			$pcontent .= WPSOLR_AbstractIndexClient::CONTENT_SEPARATOR . $pexcerpt;

			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_EXCERPT_T ] = $pexcerpt;
		}

		if ( ! empty( $pcomments ) ) {

			// Index post comments, by adding it to the post content.
			// Excerpt can therefore be: searched, autocompleted, highlighted.
			//$pcontent .= static::CONTENT_SEPARATOR . implode( static::CONTENT_SEPARATOR, $pcomments );
		}


		$content_with_shortcodes_expanded_or_stripped = $pcontent;
		if ( isset( $indexing_options['is_shortcode_expanded'] ) && ( strpos( $pcontent, '[solr_search_shortcode]' ) === false ) ) {

			// Expand shortcodes which have a plugin active, and are not the search form shortcode (else pb).
			global $post;
			$post                                         = $post_to_index;
			$content_with_shortcodes_expanded_or_stripped = do_shortcode( $pcontent );
		}

		// Remove shortcodes tags remaining, but not their content.
		// strip_shortcodes() does nothing, probably because shortcodes from themes are not loaded in admin.
		// Credit: https://wordpress.org/support/topic/stripping-shortcodes-keeping-the-content.
		// Modified to enable "/" in attributes
		$content_with_shortcodes_expanded_or_stripped = preg_replace( "~(?:\[/?)[^\]]+/?\]~s", '', $content_with_shortcodes_expanded_or_stripped );  # strip shortcodes, keep shortcode content;

		// Non-breaking space and other bad stuff
		$content_with_shortcodes_expanded_or_stripped = $this->_clean_string( $content_with_shortcodes_expanded_or_stripped );

		// No front-end search
		$is_excluded = WPSOLR_Metabox::get_metabox_is_do_not_index( $post_to_index->ID );
		// This field will be filtered on front-end search, but not filtered on admin search (so excluded documents appear only on admin search)
		if ( $search_engine_client->get_has_exists_filter() ) {
			if ( $is_excluded ) {
				$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_IS_EXCLUDED_S ] = 'y';
			}
		} else {
			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_IS_EXCLUDED_S ] = $is_excluded ? 'y' : WPSOLR_AbstractEngineClient::FIELD_VALUE_UNDEFINED;
		}

		// Remove HTML tags
		$stripped_content                                                        = wp_strip_all_tags( $content_with_shortcodes_expanded_or_stripped );
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] = ! empty( $stripped_content ) ? $stripped_content : ' '; // Prevent empty content error with ES

		// Snippet
		if ( isset( $indexing_options['p_excerpt'] ) ) {
			if ( ! isset( static::$highlight_fragsize ) ) {
				static::$highlight_fragsize = WPSOLR_Service_Container::getOption()->get_search_max_length_highlighting();
			}
			$snippet                                                                   = wp_strip_all_tags( $pexcerpt );
			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_SNIPPET_S ] =
				( ! empty( $snippet ) ) ? $snippet : mb_substr( $this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ], 0, static::$highlight_fragsize );
		}


		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR_ID_S ] = $pauthor_id;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR ]      = $pauthor;
		$author_copy_to                                                              = $search_engine_client->copy_field_name( WpSolrSchema::_FIELD_NAME_AUTHOR );
		if ( WpSolrSchema::_FIELD_NAME_AUTHOR !== $author_copy_to ) {
			$this->solarium_document_for_update[ $author_copy_to ] = $pauthor;
		}


		if ( isset( $post_to_index->menu_order ) ) {
			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_MENU_ORDER_I ] = $post_to_index->menu_order;
		}

		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PID_I ]    = $post_to_index->ID;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR_S ] = $pauthor_s;

		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_DATE ]         = $post_date;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_DATE_DT ]   = $post_date;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_DATE_GMT ]     = $post_date_gmt;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_MODIFIED ]     = $post_modified;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_MODIFIED_GMT ] = $post_modified_gmt;

		$this->_prepare_all_dates();

		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_COMMENTS ]           = $pcomments;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS ] = $pnumcomments;

		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ] = $cats;
		$categories_copy_to                                                             = $search_engine_client->copy_field_name( WpSolrSchema::_FIELD_NAME_CATEGORIES );
		if ( WpSolrSchema::_FIELD_NAME_CATEGORIES_STR !== $categories_copy_to ) {
			$this->solarium_document_for_update[ $categories_copy_to ] = $cats;
		}

		// Hierarchy of categories
		$this->solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ) ]     = $categories_flat_hierarchies;
		$this->solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ) ] = $categories_non_flat_hierarchies;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TAGS ]                                                                    = $tag_array;
		$tags_copy_to                                                                                                                            = $search_engine_client->copy_field_name( WpSolrSchema::_FIELD_NAME_TAGS );
		if ( WpSolrSchema::_FIELD_NAME_TAGS !== $tags_copy_to ) {
			$this->solarium_document_for_update[ $tags_copy_to ] = $tag_array;
		}

		$taxonomies = (array) get_taxonomies( [ '_builtin' => false ], 'names' );
		foreach ( $taxonomies as $parent ) {
			if ( in_array( $parent, $newTax, true ) ) {
				$terms = apply_filters(
					WPSOLR_Events::WPSOLR_FILTER_POST_TERMS_TO_INDEX,
					get_the_terms( $post_to_index->ID, $parent ),
					[
						'post'          => $post_to_index,
						'taxonomy_name' => $parent,
					]
				);
				if ( (array) $terms === $terms ) {
					$parent    = strtolower( str_replace( ' ', '_', $parent ) );
					$nm1       = $parent . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;
					$nm1_array = [];

					$taxonomy_non_flat_hierarchies = [];
					$taxonomy_flat_hierarchies     = [];

					foreach ( $terms as $term ) {

						// Add taxonomy and it's parents
						$term_parents_names = [];
						// Add parents in reverse order ( top-bottom)
						$term_parents_ids = array_reverse( get_ancestors( $term->term_id, $parent ) );
						array_push( $term_parents_ids, $term->term_id );

						foreach ( $term_parents_ids as $term_parent_id ) {
							$term_parent = get_term( $term_parent_id, $parent );

							if ( $term_parent instanceof \WP_Error ) {
								throw new \Exception( sprintf( 'WPSOLR: error on term %s for taxonomy \'%s\': %s',
									esc_html( $term_parent_id ), esc_html( $parent ), esc_html( $term_parent->get_error_message() ) ) );
							}

							if ( $term_parent && ( $term_parent instanceof \WP_Term ) && property_exists( $term_parent, 'name' ) ) {

								/**
								 * Remove hidden terms
								 */
								if ( $this->_is_facet_hidden( $nm1, $term_parent->name ) ) {
									// This term, and therefore all its descendants, must be hidden. Stop now.
									break;
								}

								array_push( $term_parents_names, $term_parent->name );

								// Add the term to the non-flat hierarchy (for filter queries on all the hierarchy levels)
								if ( ! in_array( $term_parent->name, $taxonomy_non_flat_hierarchies, true ) ) {
									array_push( $taxonomy_non_flat_hierarchies, $term_parent->name );
								}

							} else {
								// Some terms can trigger unexpected errors. Probably due to bad imports. https://www.wpsolr.com/forums/topic/wpsolr-error-on-term/

								//throw new \Exception( sprintf( "WPSOLR: term %s for taxonomy %s has no name: %s", esc_html($term_parent_id), esc_html($parent), esc_html(print_r( $term_parent, true) ) ) );
								continue;
							}

						}

						// Add the term to the flat hierarchy
						if ( ! empty( $term_parents_names ) ) {
							$this->_add_terms_to_flat_hierarchy( $taxonomy_flat_hierarchies, $term_parents_names, $search_engine_client->get_facet_hierarchy_separator() );
						}

						if ( ! $this->_is_facet_hidden( $nm1, $term->name ) ) {
							// Add the term to the taxonomy
							if ( ! in_array( $term->name, $nm1_array, true ) ) {
								array_push( $nm1_array, $term->name );
							}

							// Add the term to the categories searchable
							if ( ! in_array( $term->name, $cats, true ) ) {
								array_push( $cats, $term->name );
							}
						}

					}

					if ( count( $nm1_array ) > 0 ) {
						$this->solarium_document_for_update[ $nm1 ] = $nm1_array;
						$nm1_copy_to                                = $search_engine_client->copy_field_name( $nm1 );
						if ( $nm1 !== $nm1_copy_to ) {
							$this->solarium_document_for_update[ $nm1_copy_to ] = $nm1_array;
						}


						$this->solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $nm1 ) ]     = $taxonomy_flat_hierarchies;
						$this->solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, $nm1 ) ] = $taxonomy_non_flat_hierarchies;

					}
				}
			}
		}

		// Set categories and custom taxonomies as searchable
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CATEGORIES ] = $cats;

		// Add custom fields to the document
		$this->set_custom_fields( $search_engine_client, $solarium_document_for_update, $post_to_index );

	}

	/**
	 * @inheridoc
	 */
	public function after_create_document_from_model_or_attachment_inner( WPSOLR_AbstractIndexClient $search_engine_client, $attachment_body ) {

		$indexing_options = $search_engine_client->get_search_engine_indexing_options();
		if ( isset( $indexing_options['p_custom_fields'] ) && isset( $this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] ) ) {

			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] .= WPSOLR_AbstractIndexClient::CONTENT_SEPARATOR . implode( ". ", $this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] );
		}

	}

	/**
	 * Set custom fields to the update document.
	 * HTML and php tags are removed.
	 *
	 * @param WPSOLR_AbstractIndexClient $search_engine_client
	 * @param $solarium_document_for_update
	 * @param \WP_Post $post
	 *
	 * @throws \Exception
	 */
	function set_custom_fields( WPSOLR_AbstractIndexClient $search_engine_client, &$solarium_document_for_update, $post ) {

		$custom_fields = WPSOLR_Service_Container::getOption()->get_option_index_custom_fields();

		if ( is_countable( $custom_fields ) && count( $custom_fields ) > 0 ) {
			$post_id   = $this->get_id();
			$post_type = $this->get_type();
			if ( count( $post_custom_fields = $this->_get_model_custom_fields( $post_id ) ) ) {

				// Apply filters on custom fields
				$post_custom_fields = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_CUSTOM_FIELDS, $post_custom_fields, $post_id );

				$existing_custom_fields = isset( $this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] )
					? $this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ]
					: [];

				foreach ( ! empty( $custom_fields[ $post_type ] ) ? $custom_fields[ $post_type ] : [] as $field_name_with_str_ending ) {

					$field_name = WpSolrSchema::get_field_without_str_ending( $field_name_with_str_ending );

					if ( isset( $post_custom_fields[ $field_name ] ) ) {
						$field = (array) $post_custom_fields[ $field_name ];

						// Unserialize eventually
						$field_unserialized = array_map( 'maybe_unserialize', $field );
						if ( is_array( $field_unserialized[0] ) ) {
							// It is an array. Get its values.
							$field = $field_unserialized[0];
						}

						$is_date_type = WpSolrSchema::get_custom_field_is_date_type( $field_name_with_str_ending );

						$nm1                      = WpSolrSchema::replace_field_name_extension( $field_name_with_str_ending );
						$array_nm1                = [];
						$array_unformatted_values = [];
						foreach ( $field as $field_value ) {

							/**
							 * Remove hidden values
							 */
							if ( $this->_is_facet_hidden( $field_name_with_str_ending, $field_value ) ) {
								// This value must be hidden.
								continue;
							}

							/**
							 * Manage relations to this field
							 */
							$facet_relation = WPSOLR_Service_Container::getOption()->get_option_index_custom_field_relation_parent( $field_name_with_str_ending );
							$is_relation    = ! empty( $facet_relation );

							$field_value_sanitized = WpSolrSchema::get_sanitized_value( $search_engine_client, $field_name_with_str_ending, $field_value, $post, $is_relation, $facet_relation );
							// Only index the field if it has a value.
							if ( ! empty( $field_value_sanitized ) || ( '0' === $field_value_sanitized ) || ( 0 === $field_value_sanitized ) || ( 0.0 === $field_value_sanitized ) ) {

								if ( $is_relation ) {
									// Convert format
									$field_value_sanitized = $search_engine_client->format_relation_value( $field_value_sanitized, $facet_relation, $nm1 );
								}

								if ( is_array( $field_value_sanitized ) ) {
									foreach ( $field_value_sanitized as $field_value_sanitized_value ) {
										array_push( $array_nm1, $field_value_sanitized_value );
									}
								} else {
									array_push( $array_nm1, $field_value_sanitized );
								}

								/**
								 * Add un-formatted dates to the index (epoch formats can be used raw, as in Toolset Views custom field date filters)
								 */
								if ( $is_date_type ) {
									if ( WpSolrSchema::is_string_an_integer( $field_value ) ) {

										array_push( $array_unformatted_values, (int) $field_value );

									} else {

										array_push( $array_unformatted_values, $this->_convert_epoch_time_to_ms( strtotime( $field_value ) ) );
									}
								}

								switch ( WpSolrSchema::get_custom_field_dynamic_type( $field_name_with_str_ending ) ) {
									case WpSolrSchema::_SOLR_DYNAMIC_TYPE_EMBEDDED_OBJECT:
										// Not a text
										break;

									default:
										// Add current custom field values to custom fields search field
										// $field being an array, we add each of it's element
										// Convert values to string, else error in the search engine if number, as a string is expected.
										if ( ! $is_relation ) {
											array_push( $existing_custom_fields, is_array( $field_value_sanitized ) ? $field_value_sanitized : strval( $field_value_sanitized ) );
										}
										break;
								}
							}

							$this->_update_document_field( $nm1, $array_nm1, $facet_relation );

							switch ( WpSolrSchema::get_custom_field_dynamic_type( $field_name_with_str_ending ) ) {
								case WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT:
									// Would cause immense field error
									break;
								case WpSolrSchema::_SOLR_DYNAMIC_TYPE_EMBEDDED_OBJECT:
									// Not a text
									break;

								default:
									// Copy fields to _str
									$nm1_copy = $search_engine_client->copy_field_name( $field_name_with_str_ending );
									if ( ( $nm1 !== $nm1_copy ) && ! $is_relation ) {
										$this->_update_document_field( $nm1_copy, $array_nm1 );
									}
									break;
							}

							if ( ! empty( $array_unformatted_values ) ) {
								// Add the epoch field content to a new integer field
								$this->solarium_document_for_update[ $nm1 . WpSolrSchema::_SOLR_DYNAMIC_TYPE_INTEGER ] = $array_unformatted_values;
							}
						}
					}
				}


				if ( count( $existing_custom_fields ) > 0 ) {
					$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] = $existing_custom_fields;
				}

			}

		}

	}

	/**
	 * @inheritdoc
	 */
	public function get_permalink( $url_is_edit, $model ) {
		return $url_is_edit ? get_edit_post_link( $model ) : get_permalink( $model );
	}

	/**
	 * @inheritdoc
	 */
	protected function _get_thumbnail_image_src(): string {
		$thumbnail_id = get_post_thumbnail_id( $this->get_data() );
		if ( empty( $thumbnail_id ) ) {
			return '';
		}

		$result = wp_get_attachment_image_src( $thumbnail_id, 'full' );

		return empty( $result ) ? '' : $result[0];
	}


	/**
	 * @inheritdoc
	 */
	protected function get_default_result_content() {
		return ( ! empty( trim( $this->data->post_excerpt ) ) ) ? $this->data->post_excerpt : $this->data->post_content;
	}

	/**
	 * @param string $facet_name
	 * @param string|array $facet_value
	 *
	 * @return bool
	 */
	protected function _is_facet_hidden( $facet_name, $facet_value ) {

		if ( is_array( $facet_value ) ) {
			// array of values are not hidden
			return false;
		}

		$facets_items_to_hide = $this->_get_facets_items_is_hidden();

		return ( ! empty( $facets_items_to_hide[ $facet_name ] ) ) &&
		       ( ! empty( $facets_items_to_hide[ $facet_name ][ $facet_value ] ) );
	}


	/**
	 * Cache values
	 * @return array
	 */
	protected function _get_facets_items_is_hidden() {

		if ( ! isset( $this->facets_items_is_hidden ) ) {
			$this->facets_items_is_hidden = WPSOLR_Service_Container::getOption()->get_facets_items_is_hidden();
		}

		return $this->facets_items_is_hidden;
	}

	/**
	 * @param int $epoch_time_in_sec
	 *
	 * @return int
	 */
	protected function _convert_epoch_time_to_ms( int $epoch_time_in_sec ) {
		return 1000 * $epoch_time_in_sec;
	}

	/**
	 * @inerhitDoc
	 */
	public function update_custom_field( string $name, $value ) {
		update_post_meta( $this->get_id(), $name, $value );
	}

	/**
	 * @inerhitDoc
	 */
	public function get_custom_field( string $name, bool $single = false ) {
		return get_post_meta( $this->get_id(), $name, $single );
	}

	/**
	 * Update a document field value(s)
	 *
	 * @param string $field_name
	 * @param mixed $field_values
	 * @param string $parent_relation_field_str
	 *
	 * @return void
	 */
	protected function _update_document_field( string $field_name, array $field_values, $parent_relation_field_str = '' ): void {

		if ( ! empty( $parent_relation_field_str ) ) {


			/**
			 * Merge fields with same parent values:
			 * [['taxo1' => 'cat 1', 'price_f' => 10]] && ['taxo1' => 'cat 1', 'color_s' => 'blue'] ==> [['taxo1' => 'cat 1', 'price_f' => 10, 'color_s' => 'blue']]
			 */
			$parent_relation_field_obj   = WpSolrSchema::replace_field_name_extension_with( $parent_relation_field_str, WpSolrSchema::_SOLR_DYNAMIC_TYPE_EMBEDDED_OBJECT );
			$is_parent_field_value_exist = false;
			foreach ( $field_values ?? [] as $field_value ) {
				foreach ( $this->solarium_document_for_update[ $parent_relation_field_obj ] ?? [] as $pos => $document_value ) {
					if ( isset( $field_value[ $parent_relation_field_str ] ) &&
					     isset( $document_value[ $parent_relation_field_str ] ) &&
					     ( $document_value[ $parent_relation_field_str ] === $field_value[ $parent_relation_field_str ] ) ) {
						// Concat fields with same parent value
						$this->solarium_document_for_update[ $parent_relation_field_obj ][ $pos ] = array_merge( $document_value, $field_value );
						$is_parent_field_value_exist                                              = true;
						break;
					}
				}
			}

			/**
			 * No merge with different parent values:
			 * [['taxo1' => 'cat 1', 'price_f' => 10]] && ['taxo1' => 'cat 2', 'color_s' => 'blue'] ==> [['taxo1' => 'cat 1', 'price_f' => 10], ['taxo1' => 'cat 2', 'color_s' => 'blue']]
			 */
			if ( ! $is_parent_field_value_exist ) {
				$this->solarium_document_for_update[ $parent_relation_field_obj ] = array_merge( $this->solarium_document_for_update[ $parent_relation_field_obj ] ?? [], $field_values );
			}

			/**
			 * Done !!!
			 */

		} else {
			$this->solarium_document_for_update[ $field_name ] = $field_values;
		}
	}

	/**
	 * @param array $flat_hierarchies
	 * @param array $term_parents_names
	 * @param string $hierarchy_separator
	 */
	protected function _add_terms_to_flat_hierarchy( array &$flat_hierarchies, array $term_parents_names, string $hierarchy_separator ) {
		$top_term_parents_names = [];
		foreach ( $term_parents_names as $term_parents_name ) {
			// If cat0->cat1->cat2, then add also cat0 and cat0->cat1
			$top_term_parents_names[] = $term_parents_name;
			array_push( $flat_hierarchies, implode( $hierarchy_separator, $top_term_parents_names ) );
		}
	}

	protected function _get_model_custom_fields( $model_id ) {
		return get_post_custom( $model_id );
	}
}
