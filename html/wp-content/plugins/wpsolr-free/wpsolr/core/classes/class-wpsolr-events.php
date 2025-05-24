<?php

namespace wpsolr\core\classes;

/**
 * Interface for WP actions/filters definitions.
 *
 * Developers: try to use these constants in your filters.
 */
class WPSOLR_Events {

	// Add 'groups' plugin infos to a Solr results document
	const WPSOLR_FILTER_SOLR_RESULTS_DOCUMENT_GROUPS_INFOS = 'wpsolr_filter_solr_results_document_groups_infos';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/index-modify-custom-fields/
	 * Customize a post custom fields before they are processed in a Solarium update document
	 **/
	const WPSOLR_FILTER_POST_CUSTOM_FIELDS = 'wpsolr_filter_post_custom_fields';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/index-modify-a-document/
	 * Customize a fully processed Solarium update document before sending to Solr for indexing
	 **/
	const WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE = 'wpsolr_filter_solarium_document_for_update';

	/**
	 * @link ???
	 * Customize a not yet processed Solarium update document before sending to Solr for indexing
	 **/
	const WPSOLR_FILTER_SOLARIUM_DOCUMENT_BEFORE_UPDATE = 'wpsolr_filter_solarium_document_before_update';

	// Customize a fully processed attachment content before sending to Solr for indexing
	const WPSOLR_FILTER_ATTACHMENT_TEXT_EXTRACTED_BY_APACHE_TIKA = 'wpsolr_filter_attachment_text_extracted_by_apache_tika';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-query-query/
	 * Solarium query before a search is performed
	 **/
	const WPSOLR_ACTION_SOLARIUM_QUERY = 'wpsolr_action_solarium_query';
	const WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_QUERY = 'solarium_query_object';
	const WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_TERMS = 'keywords';
	const WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_USER = 'user';
	const WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_WPSOLR_QUERY = 'wpsolr_query';
	const WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_CLIENT = 'solarium_client';

	// Customize the search page url
	const WPSOLR_FILTER_SEARCH_PAGE_URL = 'wpsolr_filter_search_page_url';

	// Action before a solr index configuration is deleted
	const WPSOLR_ACTION_BEFORE_A_SOLR_INDEX_CONFIGURATION_DELETION = 'wpsolr_action_before_a_solr_index_configuration_deletion';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/index-modify-sql-statement/
	 * Modify sql query statement used to retrieve the posts to be indexed
	 **/
	const WPSOLR_FILTER_SQL_QUERY_STATEMENT = 'wpsolr_filter_sql_query_statement';

	// Filter to get the default search index indice
	const WPSOLR_FILTER_SEARCH_GET_DEFAULT_SOLR_INDEX_INDICE = 'wpsolr_filter_get_default_search_solr_index_indice';

	// Filter to get the indexing index indice for a post
	const WPSOLR_FILTER_INDEXING_GET_SOLR_INDEX_INDICE_FOR_A_POST = 'wpsolr_filter_get_default_indexing_solr_index_indice_for_a_post';

	// Filter to change search page parameters before creation
	const WPSOLR_FILTER_BEFORE_CREATE_SEARCH_PAGE = 'wpsolr_filter_before_create_search_page';

	// Filter to change search page slug parameters before creation
	const WPSOLR_FILTER_SEARCH_PAGE_SLUG = 'wpsolr_filter_search_page_slug';

	// Filter to retrieve a post language from multi-language extensions
	const WPSOLR_FILTER_POST_LANGUAGE = 'wpsolr_filter_post_language';

	// Filter to change a facet name on search page
	const WPSOLR_FILTER_SEARCH_PAGE_FACET_NAME = 'wpsolr_filter_search_page_facet_name';

	// Filter before retrieving an option value
	const WPSOLR_FILTER_BEFORE_GET_OPTION_VALUE = 'wpsolr_filter_before_get_option_value';

	// Filter after retrieving an option value
	const WPSOLR_FILTER_AFTER_GET_OPTION_VALUE = 'wpsolr_filter_after_get_option_value';

	// Filter a sort option
	const WPSOLR_FILTER_SORT = 'wpsolr_filter_sort';

	// Action to add string translations to WPML/Polylang
	const ACTION_TRANSLATION_REGISTER_STRINGS = 'wpsolr_action_translation_register_strings';

	// Get a translated string from WPML/Polylang
	const WPSOLR_FILTER_TRANSLATION_STRING = 'wpsolr_filter_translation_string';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/index-embedded-files/
	 * Embedded files in post content.
	 **/
	const WPSOLR_FILTER_GET_POST_ATTACHMENTS = 'wpsolr_filter_get_post_attachments';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/options-custom-fields/
	 * Custom fields shown in admin screen.
	 **/
	const WPSOLR_FILTER_INDEX_CUSTOM_FIELDS = 'wpsolr_filter_index_custom_fields';

	// Filter to add additional fields to the Ajax search form
	const WPSOLR_FILTER_APPEND_FIELDS_TO_AJAX_SEARCH_FORM = 'wpsolr_filter_append_fields_to_ajax_search_form';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-results-url-parameters/
	 * Search url parameters
	 **/
	const WPSOLR_ACTION_URL_PARAMETERS = 'wpsolr_filter_url_parameters';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-results-javascript-parameters/
	 * Javascript search parameters (fron-end)
	 **/
	const WPSOLR_FILTER_JAVASCRIPT_FRONT_LOCALIZED_PARAMETERS = 'wpsolr_filter_javascript_front_localized_parameters';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-query-fields-list/
	 * Fields list in Solr query
	 **/
	const WPSOLR_FILTER_FIELDS = 'wpsolr_filter_fields';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/options-solr-dynamic-field-types/
	 * Solr dynamic field types
	 **/
	const WPSOLR_FILTER_SOLR_FIELD_TYPES = 'wpsolr_filter_solr_field_types';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/options-default-sort-list/
	 * Default sort list shown in admin.
	 **/
	const WPSOLR_FILTER_DEFAULT_SORT_FIELDS = 'wpsolr_filter_default_sort_fields';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-results-sort-fields/
	 * Sort items to be shown in the drop-down list (front-end).
	 **/
	const WPSOLR_FILTER_SORT_FIELDS = 'wpsolr_filter_sort_fields';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-results-default-sort/
	 * Default sort when none selected (front-end).
	 **/
	const WPSOLR_FILTER_DEFAULT_SORT = 'wpsolr_filter_default_sort';

	/**
	 * @link ????
	 * Default secondary sort (front-end).
	 **/
	const WPSOLR_FILTER_DEFAULT_SORT_SECONDARY = 'wpsolr_filter_default_sort_secondary';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-results-append-html/
	 * Append custom html to each ajax results snippet
	 **/
	const WPSOLR_FILTER_SOLR_RESULTS_APPEND_CUSTOM_HTML = 'wpsolr_filter_solr_results_append_custom_html';

	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-results-modify-posts/
	 * Modify posts before rendering
	 **/
	const WPSOLR_ACTION_POSTS_RESULTS = 'wpsolr_action_posts_results';


	/**
	 * @link ????
	 * Sanitize a field content before indexing.
	 **/
	const WPSOLR_FILTER_INDEX_SANITIZE_FIELD = '';

	/**
	 * @link ????
	 * Array of post statuses which can be indexed. Default is array('published').
	 **/
	const WPSOLR_FILTER_POST_STATUSES_TO_INDEX = 'wpsolr_filter_post_statuses_to_index';


	/**
	 * @link ????
	 * Replace the WP query by a WPSOLR query ?.
	 **/
	const WPSOLR_FILTER_IS_REPLACE_BY_WPSOLR_QUERY = 'wpsolr_filter_is_replace_by_wpsolr_query';

	/**
	 * @link ????
	 * After 'posts_pre_query' in "is replace query" code
	 **/
	const WPSOLR_ACTION_IS_REPLACE_BY_WPSOLR_QUERY_AFTER_POST_PRE_QUERY = 'wpsolr_action_is_replace_by_wpsolr_query_after_post_pre_query';

	/**
	 * @link ????
	 * Replace the WP query by a WPSOLR query for post types in admin ?.
	 **/
	const WPSOLR_FILTER_IS_REPLACE_ADMIN_POST_TYPE_BY_WPSOLR_QUERY = 'wpsolr_filter_is_replace_admin_post_type_by_wpsolr_query';

	/**
	 * @link ????
	 * Replace the WP query by a WPSOLR query for taxonomy in admin ?.
	 **/
	const WPSOLR_FILTER_IS_REPLACE_ADMIN_TAXONOMY_BY_WPSOLR_QUERY = 'wpsolr_filter_is_replace_admin_taxonomy_by_wpsolr_query';

	/**
	 * @link ????
	 * Facets to display.
	 **/
	const WPSOLR_FILTER_FACETS_TO_DISPLAY = 'wpsolr_filter_facets_to_display';

	/**
	 * @link ????
	 * Filter facets content to display.
	 **/
	const WPSOLR_FILTER_FACETS_CONTENT_TO_DISPLAY = 'wpsolr_filter_facets_content_to_display';


	/**
	 * @link ????
	 * Filter to get a file path to include.
	 **/
	const WPSOLR_FILTER_INCLUDE_FILE = 'wpsolr_filter_include_file';

	/**
	 * @link ????
	 * Filter to managed services for a specific environment.
	 **/
	const WPSOLR_FILTER_ENV_MANAGED_SERVICES = 'wpsolr_filter_env_managed_services';

	/**
	 * @link ????
	 * Filter to license api url for a specific environment.
	 **/
	const WPSOLR_FILTER_ENV_LICENSE_API_URL = 'wpsolr_filter_env_license_api_url';

	/**
	 * @link ????
	 * Filter to package url for a specific environment.
	 **/
	const WPSOLR_FILTER_ENV_PACKAGE_URL = 'wpsolr_filter_env_package_url';

	/**
	 * @link ????
	 * Filter to upgrade url check for a specific environment.
	 **/
	const WPSOLR_FILTER_ENV_CHECK_UPGRADE_VERSION_URL = 'wpsolr_filter_env_check_upgrade_version_url';


	/**
	 * @link ????
	 * Filter to decide if parse_query() should be called on wpsolr_query.
	 **/
	const WPSOLR_FILTER_IS_PARSE_QUERY = 'wpsolr_filter_is_parse_query';


	/**
	 * @link https://www.wpsolr.com/guide/actions-and-filters/search-results-replace-facets-html/
	 * Filter to replace the facets html generated both in the facets widget and in the Ajax shortcode.
	 **/
	const WPSOLR_FILTER_FACETS_REPLACE_HTML = 'wpsolr_filter_facets_replace_html';

	/**
	 * @link ????
	 * Filter to get all items of a facet.
	 **/
	const WPSOLR_FILTER_FACET_ITEMS = 'wpsolr_filter_facet_items';

	/**
	 * @link ????
	 * Filter to get a facet custom description.
	 **/
	const WPSOLR_FILTER_FACET_CUSTOM_DESCRIPTION = 'wpsolr_filter_facet_custom_description';

	/**
	 * @link ????
	 * Filter to replace a facet name just before indexing or just before retrieving facet results.
	 **/
	const WPSOLR_FILTER_FACET_NAME_SUBSTITUTE = 'wpsolr_filter_facet_name_substitute';

	/**
	 * @link ????
	 * Filter to get max items labels per facet in settings.
	 **/
	const WPSOLR_FILTER_FACET_ITEMS_MAX_LABELS_SHOWN = 'wpsolr_filter_facet_items_max_labels_shown';

	/**
	 * @link ????
	 * Filter to get some custom css to insert before the facets HTML.
	 **/
	const WPSOLR_FILTER_FACETS_CSS = 'wpsolr_filter_facets_css';

	/**
	 * @link ????
	 * Filter to get the facet type of a facet.
	 **/
	const WPSOLR_FILTER_FACET_TYPE = 'wpsolr_filter_facet_type';


	/**
	 * @link ????
	 * Get layouts for a field type (like integer, float, string ...).
	 **/
	const WPSOLR_FILTER_GET_FIELD_TYPE_LAYOUTS = 'wpsolr_filter_get_field_type_layouts';

	/**
	 * @link ????
	 * Update facets data
	 **/
	const WPSOLR_FILTER_UPDATE_FACETS_DATA = 'wpsolr_filter_update_facets_data';

	/**
	 * @link ????
	 * Update the WPSOLR_QUERY before it is transformed in a search client query
	 **/
	const WPSOLR_FILTER_UPDATE_WPSOLR_QUERY = 'wpsolr_filter_update_wpsolr_query';

	/**
	 * @link ????
	 * Redirect search home
	 **/
	const WPSOLR_FILTER_REDIRECT_SEARCH_HOME = 'wpsolr_filter_redirect_search_home';

	/**
	 * @link ????
	 * Redirect facets home
	 **/
	const WPSOLR_FILTER_FACET_PERMALINK_HOME = 'wpsolr_filter_facet_permalink_home';

	/**
	 * @link ????
	 * Generate facets permalinks ?
	 **/
	const WPSOLR_FILTER_IS_GENERATE_FACET_PERMALINK = 'wpsolr_filter_is_generate_facet_permalink';

	/**
	 * @link ????
	 * Page meta value used with title and description SEO templates
	 **/
	const WPSOLR_FILTER_SEO_PAGE_META_VALUE = 'wpsolr_filter_seo_page_meta_value';

	/**
	 * @link ????
	 * Array of extra parameters to add to the search url: ['post_type'=> 'product', 'color' => 'blue']
	 **/
	const WPSOLR_FILTER_EXTRA_URL_PARAMETERS = 'wpsolr_filter_extra_url_parameters';

	/**
	 * @link ????
	 * Translate a localization
	 **/
	const WPSOLR_FILTER_TRANSLATION_LOCALIZATION_STRING = 'wpsolr_filter_translation_localization_string';

	/**
	 * Custom fields properties shown in admin screen.
	 **/
	const WPSOLR_FILTER_INDEX_CUSTOM_FIELDS_PROPERTIES_SELECTED = 'wpsolr_filter_index_custom_fields_properties';


	/**
	 * Custom fields selected for indexing shown in admin screen.
	 **/
	const WPSOLR_FILTER_INDEX_CUSTOM_FIELDS_SELECTED = 'wpsolr_filter_index_custom_fields_selected';

	/**
	 * Post types selected for indexing shown in admin screen.
	 **/
	const WPSOLR_FILTER_INDEX_POST_TYPES_SELECTED = 'wpsolr_filter_index_post_types_selected';

	/**
	 * Taxonomies selected for indexing shown in admin screen.
	 **/
	const WPSOLR_FILTER_INDEX_TAXONOMIES_SELECTED = 'wpsolr_filter_index_taxonomies_selected';

	/**
	 * Fields selected for sorting in admin screen.
	 **/
	const WPSOLR_FILTER_INDEX_SORTS_SELECTED = 'wpsolr_filter_index_sorts_selected';

	/**
	 * Filter for phpunit Selenium2 UAT tests. Do not use !!!
	 **/
	const WPSOLR_FILTER_UAT_TEST = 'wpsolr_filter_uat_test';

	/**
	 * @link ????
	 * Filter on admin_url() injected in dashboard pages for tests. Do not use !!!
	 **/
	const WPSOLR_FILTER_UAT_TEST_ADMIN_URL = 'wpsolr_filter_admin_url';

	/**
	 * Action to activate/deactivate real-time indexing before importing data and starting a cron..
	 **/
	const WPSOLR_ACTION_OPTION_SET_REALTIME_INDEXING = 'wpsolr_action_option_set_realtime_indexing';

	/**
	 * Filter to retrieve all skins for each facet layout
	 */
	const WPSOLR_FILTER_FACET_LAYOUT_SKINS = 'wpsolr_filter_facet_layout_skins';

	/**
	 * Filter to retrieve a script for each facet layout
	 */
	const WPSOLR_FILTER_FACET_GENERIC_SCRIPTS = 'wpsolr_filter_facet_generic_scripts';

	/**
	 * Filter to retrieve the class for a layout id
	 */
	const WPSOLR_FILTER_LAYOUT_CLASS = 'wpsolr_filter_layout_class';

	/**
	 * Filter to retrieve the layout object for a layout id
	 */
	const WPSOLR_FILTER_LAYOUT_OBJECT = 'wpsolr_filter_layout_object';

	/**
	 * Filter to retrieve the layouts ids for facet feature
	 */
	const WPSOLR_FILTER_FACET_FEATURE_LAYOUTS = 'wpsolr_filter_facet_feature_layouts';

	/**
	 * @link ????
	 * Array of post statuses which are filtered out from all queries.
	 **/
	const WPSOLR_FILTER_POST_STATUSES_TO_FILTER_OUT = 'wpsolr_filter_post_statuses_to_filter_out';


	/**
	 * @link ????
	 * get_posts() arguments used to retrieve posts from query results documents PIDs.
	 **/
	const WPSOLR_FILTER_QUERY_RESULTS_GET_POSTS_ARGUMENTS = 'wpsolr_filter_query_results_get_posts_arguments';

	/**
	 * @link ????
	 * Add extra field queries to the main query.
	 * ['_location' => 'New York', 'field1' => 'value1']
	 **/
	const WPSOLR_FILTER_QUERY_ADD_EXTRA_FIELD_QUERIES = 'wpsolr_filter_query_add_extra_field_queries';

	/**
	 * @link ????
	 * Last chance to update WPSOLR_Query before its execution.
	 **/
	const WPSOLR_ACTION_PRE_EXECUTE_QUERY = 'wpsolr_action_pre_execute_query';

	/**
	 * @link ????
	 * Override post type (default is 'post')
	 **/
	const WPSOLR_FILTER_POST_TYPES = 'wpsolr_filter_post_types';

	/**
	 * Eventually update a template file or context informations before rendering.
	 * @link ????
	 **/
	const WPSOLR_ACTION_BEFORE_RENDER_TEMPLATE = 'wpsolr_action_before_render_template';

	/**
	 * @link ????
	 * Override suggestions templates
	 **/
	const WPSOLR_FILTER_SUGGESTIONS_TEMPLATES = 'wpsolr_filter_suggestions_templates';

	/**
	 * @link ????
	 * Override highlighting prefix tag
	 **/
	const WPSOLR_FILTER_HIGHLIGHTING_PREFIX = 'wpsolr_filter_highlighting_prefix';

	/**
	 * @link ????
	 * Override highlighting posfix tag
	 **/
	const WPSOLR_FILTER_HIGHLIGHTING_POSFIX = 'wpsolr_filter_highlighting_posfix';

	/**
	 * @link ????
	 * Override highlighting fields
	 **/
	const WPSOLR_FILTER_HIGHLIGHTING_FIELDS = 'wpsolr_filter_highlighting_fields';

	/**
	 * @link ????
	 * Array of post terms which can be indexed. Default is the post terms.
	 **/
	const WPSOLR_FILTER_POST_TERMS_TO_INDEX = 'wpsolr_filter_post_terms_to_index';

	/**
	 * @link ????
	 * Array of images urls which can be processed by AI Image APIs for a post type.
	 **/
	const WPSOLR_FILTER_AI_POST_TYPE_IMAGES_URLS = 'wpsolr_filter_ai_post_type_images_urls';

	/**
	 * @link ????
	 * Content logged
	 **/
	const WPSOLR_FILTER_LOG = 'wpsolr_filter_log';

	/**
	 * @link ????
	 * Action to change current language with ML plugins
	 **/
	const WPSOLR_ACTION_SET_CURRENT_LANGUAGE = 'wpsolr_action_set_current_language';

	/**
	 * @link ????
	 * Override views list
	 **/
	const WPSOLR_FILTER_VIEWS = 'wpsolr_filter_views';

	/**
	 * @link ????
	 * Override indexes list
	 **/
	const WPSOLR_FILTER_INDEXES = 'wpsolr_filter_indexes';

	/**
	 * @link ????
	 * Get the current search archive view
	 **/
	const WPSOLR_FILTER_VIEW_ARCHIVE_SEARCH = 'wpsolr_filter_view_archive_search';

	/**
	 * @link ????
	 * Override recommendations templates
	 **/
	const WPSOLR_FILTER_RECOMMENDATIONS_TEMPLATES = 'wpsolr_filter_recommendations_templates';

	/**
	 * @link ????
	 * Results data
	 **/
	const WPSOLR_FILTER_RESULTS = 'wpsolr_filter_results';

	/**
	 * @link ????
	 * Get all model types visible in screen 2.2
	 **/
	const WPSOLR_FILTER_GET_ALL_MODEL_TYPES = 'wpsolr_filter_get_all_model_types';

}
