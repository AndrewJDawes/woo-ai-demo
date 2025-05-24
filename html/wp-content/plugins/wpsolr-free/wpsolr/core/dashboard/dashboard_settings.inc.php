<div id="solr-option-tab" xmlns="http://www.w3.org/1999/html">

	<?php

	use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
	use wpsolr\core\classes\engines\weaviate\WPSOLR_Weaviate_Constants;
	use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
	use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
	use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient_Root;
	use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
	use wpsolr\core\classes\extensions\recommendations\WPSOLR_Option_Recommendations;
	use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
	use wpsolr\core\classes\extensions\WPSOLR_Extension;
	use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract;
	use wpsolr\core\classes\models\WPSOLR_Model_Builder;
	use wpsolr\core\classes\services\WPSOLR_Service_Container;
	use wpsolr\core\classes\ui\shortcode\WPSOLR_Shortcode_Facet;
	use wpsolr\core\classes\ui\shortcode\WPSOLR_Shortcode_Sort;
	use wpsolr\core\classes\utilities\WPSOLR_Escape;
	use wpsolr\core\classes\utilities\WPSOLR_Help;
	use wpsolr\core\classes\utilities\WPSOLR_Option;
	use wpsolr\core\classes\WPSOLR_Events;
	use wpsolr\core\classes\WpSolrSchema;

	$subtabs = [
		'result_opt'           => '2.1 Connector',
		'index_opt'            => '2.2 Data',
		'field_opt'            => '2.3 Boosts',
		'suggestion_opt'       => '2.3 Suggestions',
		'recommendation_opt'   => '2.3 Recommendations',
		'facet_opt'            => '2.4 Facets',
		'sort_opt'             => '2.5 Sorts',
		'localization_options' => '2.6 Texts',
	];

	$subtab = wpsolr_admin_sub_tabs( $subtabs );

	$current_view_uuid = WPSOLR_Option_View::get_current_view_uuid();

	switch ( $subtab ) {
	case 'result_opt':

	/**
	 * Set the options name from the view
	 */
	$view_options_name = WPSOLR_Option_View::get_view_uuid_options_name( WPSOLR_Option::OPTION_SEARCH );

	WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );
	$option_indexes = new WPSOLR_Option_Indexes( false, false );
	$solr_indexes   = $option_indexes->get_indexes();

	$replace_search_text               = "%s";
	$is_replace_option_names_front_end = [
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_SEARCH              => 'Search',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_HOME                => 'Home/Blog',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_AUTHOR              => 'Author',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_YEAR                => 'Year',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_MONTH               => 'Month',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_DAY                 => 'Day',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_CATEGORY_FRONT_END  => 'Categories',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_TAG_FRONT_END       => 'Tags',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_POST_TYPE_FRONT_END => 'Post types',
	];
	$is_replace_option_names_admin     = [
		//WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_CATEGORY_ADMIN  => 'Categories',
		//WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_TAG_ADMIN       => 'Tags',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_POST_TYPE_ADMIN  => 'Post types',
		WPSOLR_OPTION::OPTION_SEARCH_ITEM_REPLACE_WP_MEDIA_ADMIN      => 'Media library',
	];
	?>

    <style>
        .wpsolr_engine {
            display: none;
        }
    </style>
    <script>
        jQuery(document).ready(function ($) {

            /*
			 * Refresh fields with index selection's search engine
			 */
            const indexes_search_engines = <?php WPSOLR_Escape::echo_esc_json( wp_json_encode( $solr_indexes ) ); ?>;
            show_index_search_engine_fields($('#wpsolr_view_uuid').val());

            function show_index_search_engine_fields(index_uuid) {
                $('.wpsolr_engine').hide();
                $('.wpsolr_engine_not').show();
                $('.wpsolr_index_uuid').hide();
                $('.wpsolr_index_uuid_not').show();

                if ('' !== index_uuid) {
                    $('.wpsolr_index_uuid.' + index_uuid).show();
                    $('.wpsolr_index_uuid_not.' + index_uuid).hide();
                }

                if (undefined !== indexes_search_engines[index_uuid]) {
                    const index_search_engine = indexes_search_engines[index_uuid]['index_engine'];
                    $('.wpsolr_engine.' + index_search_engine).show();
                    $('.wpsolr_engine_not.' + index_search_engine).hide();
                }
            }

            $(document).on('change', '#wpsolr_view_uuid', function (e) {
                show_index_search_engine_fields($(this).val());
            });


        });
    </script>


    <div id="solr-results-options" class="wdm-vertical-tabs-content">
        <form action="options.php" method="POST" id='res_settings_form'>
			<?php
			WPSOLR_Option_View::output_form_view_hidden_fields( 'solr_res_options' );
			$solr_res_options = WPSOLR_Service_Container::getOption()->get_option_search();

			?>

            <div class='wrapper'>
                <h4 class='head_div'><?php WPSOLR_Escape::echo_escaped( WPSOLR_Option_View::get_views_html( 'Connector' ) ); ?> </h4>

                <div class="wdm_note">

                    In this section, you will configure connector-specific settings.

                </div>

                <div class="wdm_row">
                    <div class='col_left'>Connector<br/>

                    </div>
                    <div class='col_right'>
                        <select id="wpsolr_view_uuid"
                                name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_DEFAULT_SOLR_INDEX_FOR_SEARCH ); ?>]'>
							<?php
							// Empty option
							WPSOLR_Escape::echo_escaped( sprintf( "<option value='%s' %s>%s</option>",
								'',
								'',
								'Your view is not configured with a connector. Please select one here.'
							) );

							foreach (
								$solr_indexes as $solr_index_indice => $solr_index
							) {

								WPSOLR_Escape::echo_escaped(
									sprintf( "<option value='%s' %s>%s</option>",
										WPSOLR_Escape::esc_attr( $solr_index_indice ),
										selected( $solr_index_indice, $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_DEFAULT_SOLR_INDEX_FOR_SEARCH ] ?? '' ),
										isset( $solr_index['index_name'] ) ? WPSOLR_Escape::esc_attr( $solr_index['index_name'] ) : 'Unnamed connector'
									)
								);

							}
							?>
                        </select>

                    </div>
                    <div class="clear"></div>
                </div>





            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
                <div class='col_left'>
                    Replace
                    <bold>front-end</bold>
                    archives
                </div>
                <div class='col_right'>
					<?php foreach (
						$is_replace_option_names_front_end

						as $is_replace_option_name => $is_replace_option_label
					) { ?>
                        <input type='checkbox'
                               class="wpsolr_collapser"
                               name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( $is_replace_option_name ); ?>]'
                               value='1'
							<?php checked( '1', $solr_res_options[ $is_replace_option_name ] ?? '0' ); ?>>
						<?php WPSOLR_Escape::echo_esc_html( sprintf( $replace_search_text, $is_replace_option_label ) ); ?>

					<?php } ?>

                    <p>
                        Warning: permalinks must be activated. Check this option only after tabs 0-3 are
                        completed.
                    </p>

                </div>
            </div>
            <div class="clear"></div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
                <div class='col_left'>
                    Replace admin archives
                </div>
                <div class='col_right'>
					<?php foreach (
						$is_replace_option_names_admin

						as $is_replace_option_name => $is_replace_option_label
					) { ?>
                        <input type='checkbox'
                               class="wpsolr_collapser"
                               name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( $is_replace_option_name ); ?>]'
                               value='1'
							<?php checked( '1', $solr_res_options[ $is_replace_option_name ] ?? '0' ); ?>>
						<?php WPSOLR_Escape::echo_escaped( sprintf( $replace_search_text, $is_replace_option_label ) ); ?>

					<?php } ?>
                    <p class="wpsolr_collapsed">
                        Searching in admin requires an index created from WPSOLR 21.6 and after, to be able
                        to see keyword suggestions. If your current index
                        is older, just create a new one,
                        reindex all you data, and select the new index below.
                    </p>

                </div>
            </div>
            <div class="clear"></div>


            <div class="wdm_row wpsolr_engine_not
                    ">
                <div class='col_left'>Log the search engine queries?<br/>

                </div>
                <div class='col_right'>
                    <select name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_MODE ); ?>]'>
						<?php
						// Empty option
						WPSOLR_Escape::echo_escaped( sprintf( "<option value='%s' %s>%s</option>",
							'',
							'',
							'No'
						) );
						WPSOLR_Escape::echo_escaped( sprintf( "<option value='%s' %s>%s</option>",
							WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_MODE_DEBUG_FILE ),
							selected( WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_MODE_DEBUG_FILE, $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_MODE ] ?? '' ),
							"In WordPress debug.log - Requires define( 'WP_DEBUG_LOG', true ); in wp-config.php" ) );

						WPSOLR_Escape::echo_escaped( sprintf( "<option value='%s' %s>%s</option>",
							WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_MODE_DEBUG_QUERY_MONITOR ),
							selected( WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_MODE_DEBUG_QUERY_MONITOR, $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_MODE ] ?? '' ),
							"In Query Monitor - Requires activation of the WPSOLR Query Monitor extension " ) );

						?>

                    </select>

                </div>
                <div class="clear"></div>
            </div>

			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_TEMPLATE ) ) ) {
				require_once $file_to_include;
			}
			?>

			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_PAGE_SLUG ) ) ) {
				require_once $file_to_include;
			}
			?>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
                <div class='col_left'>Deactivate Ajax security
                </div>
                <div class='col_right'>
					<?php $is_prevent_nonce = isset( $solr_res_options[ WPSOLR_OPTION::OPTION_SEARCH_ITEM_IS_PREVENT_AJAX_NONCE_SECURITY_FRONT_END ] ) ? '1' : '0'; ?>
                    <input type='checkbox'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_OPTION::OPTION_SEARCH_ITEM_IS_PREVENT_AJAX_NONCE_SECURITY_FRONT_END ); ?>]'
                           value='1'
						<?php checked( '1', $is_prevent_nonce ); ?>>
                    If you need to cache the whole HTML pages. This option will prevent all Ajax nonce
                    verifications on facets selection, or suggestions.
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr_engine_not
                    ">
                <div class='col_left'>Do not load WPSOLR front-end css.<br/>You can then use
                    your
                    own theme css.
                </div>
                <div class='col_right'>
					<?php $is_prevent_loading_front_end_css = isset( $solr_res_options['is_prevent_loading_front_end_css'] ) ? '1' : '0'; ?>
                    <input type='checkbox'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[is_prevent_loading_front_end_css]'
                           value='1'
						<?php checked( '1', $is_prevent_loading_front_end_css ); ?>>
                </div>
                <div class="clear"></div>
            </div>

			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_INFINITE_SCROLL ) ) ) {
				require_once $file_to_include;
			}
			?>

			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_SUGGESTIONS ) ) ) {
				//require_once $file_to_include;
			}
			?>

			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_SUGGESTIONS_JQUERY_SELECTOR ) ) ) {
				//require_once $file_to_include;
			}
			?>

            <div class="wdm_row wpsolr_engine_not
               ">
                <div class='col_left'>Do not automatically trigger the search, when a user
                    clicks on the
                    autocomplete list
                </div>
                <div class='col_right'>
					<?php $is_after_autocomplete_block_submit = isset( $solr_res_options['is_after_autocomplete_block_submit'] ) ? '1' : '0'; ?>
                    <input type='checkbox'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[is_after_autocomplete_block_submit]'
                           value='1'
						<?php checked( '1', $is_after_autocomplete_block_submit ); ?>>
                </div>
                <div class="clear"></div>
            </div>

			<?php
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_DID_YOU_MEAN ) ) ) {
				require_once $file_to_include;
			}
			?>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
                <div class='col_left'>Display number of results and current page</div>
                <div class='col_right'>
                    <input type='checkbox'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[res_info]'
                           value='res_info'
						<?php checked( 'res_info', isset( $solr_res_options['res_info'] ) ? $solr_res_options['res_info'] : '?' ); ?>>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE ); ?>">
                <div class='col_left'>Filter</div>
                <div class='col_right'>
                    <select name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER ); ?>]">
						<?php foreach (
							[
								WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER_WHERE       => [ 'Where' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER_HYBRID      => [ 'Hybrid' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER_NEAR_TEXT   => [ 'Near Text' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER_NEAR_IMAGE  => [ 'Near Image' => false ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER_NEAR_VECTOR => [ 'Near Vector' => false ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER_NEAR_OBJECT => [ 'Near Object' => false ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER_BM25        => [ 'BM25' => false ],
							] as $filter_code => $filter_def
						) {
							$selected = ( ( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER ] ?? WPSOLR_Option::OPTION_SEARCH_ITEM_FILTER_NEAR_TEXT ) === $filter_code ) ? 'selected' : '';
							?>
                            <option <?php WPSOLR_Escape::echo_escaped( $filter_def[ key( $filter_def ) ] ? '' : 'disabled' ); ?>
                                    value="<?php WPSOLR_Escape::echo_esc_attr( $filter_code ); ?>" <?php WPSOLR_Escape::echo_esc_attr( $selected ); ?> ><?php WPSOLR_Escape::echo_esc_html( key( $filter_def ) ); ?></option>
						<?php } ?>
                    </select>
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE ); ?>">
                <div class='col_left'>Autocut</div>
                <div class='col_right'>
                    <input type='number' id='autocut'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_AUTOCUT ); ?>]'
                           placeholder="Enter an autocut"
                           value="<?php WPSOLR_Escape::echo_escaped( empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_AUTOCUT ] ) ? '0' : WPSOLR_Escape::esc_attr( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_AUTOCUT ] ) ); ?>">
                    <a href="https://weaviate.io/developers/weaviate/api/graphql/additional-operators#autocut"
                       target="_new">Enter an autocut</a> to limit results based on discontinuities in the
                    result set.
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE ); ?>">
                <div class='col_left'>Near distance</div>
                <div class='col_right'>
                    <input type='text' id='certainty'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_CERTAINTY ); ?>]'
                           placeholder="Enter a certainty"
                           value="<?php WPSOLR_Escape::echo_escaped( empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_CERTAINTY ] ) ? '0.5' : WPSOLR_Escape::esc_attr( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_CERTAINTY ] ) ); ?>">
                    <a href="https://weaviate.io/developers/weaviate/configuration/distances"
                       target="_new">Enter a maximum distance</a>
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE ); ?>">
                <div class='col_left'>Hybrid alpha</div>
                <div class='col_right'>
                    <input type='text' id='alpha'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_ALPHA ); ?>]'
                           placeholder="Enter an alpha"
                           value="<?php WPSOLR_Escape::echo_escaped( empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_ALPHA ] ) ? '0.75' : WPSOLR_Escape::esc_attr( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_ALPHA ] ) ); ?>">
                    Enter alpha between 0 and 1 (default 0.5). 0 will show pure sparse search results. 1 will
                    only show pure vector search results.
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE ); ?>">
                <div class='col_left'>Reranker</div>
                <div class='col_right'>
                    <select name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_RERANKER ); ?>]">
						<?php foreach (
							[
								''                                                               => [ 'No reranker' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_WEAVIATE_RERANKER_TRANSFORMERS => [ 'Transformers reranker' => true ],
							] as $reranker_code => $reranker_def
						) {
							$selected = ( ( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_RERANKER ] ?? WPSOLR_Option::OPTION_SEARCH_ITEM_RERANKER ) === $reranker_code ) ? 'selected' : '';
							?>
                            <option <?php WPSOLR_Escape::echo_escaped( $reranker_def[ key( $reranker_def ) ] ? '' : 'disabled' ); ?>
                                    value="<?php WPSOLR_Escape::echo_esc_attr( $reranker_code ); ?>" <?php WPSOLR_Escape::echo_esc_attr( $selected ); ?> ><?php WPSOLR_Escape::echo_esc_html( key( $reranker_def ) ); ?></option>
						<?php } ?>
                    </select>
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>


            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_VESPA ); ?>">
                <div class='col_left'><a
                            href="https://docs.vespa.ai/en/reference/query-api-reference.html#tracing"
                            target="_new">Trace level</a></div>
                <div class='col_right'>
                    <select name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_TRACE_LEVEL ); ?>]">
						<?php foreach (
							[
								''  => '0 - None',
								'1' => '1 - Basic tracing in container',
								'2' => '2 - Basic tracing, more details',
								'3' => '3 - Basic tracing, even more details',
								'4' => '4 - Include timing info from backend',
								'5' => '5 - More timing info from backend',
								'6' => '6 - Include query blueprint',
								'7' => '7 - Include query execution tree',
							] as $trace_level_code => $trace_level_label
						) {
							$selected = ( ( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_LOG_QUERY_TRACE_LEVEL ] ?? '' ) === (string) $trace_level_code ) ? 'selected' : '';
							?>
                            <option value="<?php WPSOLR_Escape::echo_esc_attr( $trace_level_code ); ?>" <?php WPSOLR_Escape::echo_esc_attr( $selected ); ?> ><?php WPSOLR_Escape::echo_esc_html( $trace_level_label ); ?></option>
						<?php } ?>
                    </select>
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_VESPA ); ?>">
                <div class='col_left'>Search type</div>
                <div class='col_right'>
                    <select id="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE ); ?>"
                            name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE ); ?>]">
						<?php foreach (
							[
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT        => [ 'Text search only' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_VECTOR => [ 'Text Vector search only' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT_HYBRID => [ 'Text Hybrid search' => true ],
							] as $query_mode => $query_mode_def
						) {
							$selected = ( ( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE ] ?? WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_MODE_TEXT ) === $query_mode ) ? 'selected' : '';
							?>
                            <option <?php WPSOLR_Escape::echo_escaped( $query_mode_def[ key( $query_mode_def ) ] ? '' : 'disabled' ); ?>
                                    value="<?php WPSOLR_Escape::echo_esc_attr( $query_mode ); ?>" <?php WPSOLR_Escape::echo_esc_attr( $selected ); ?> ><?php WPSOLR_Escape::echo_esc_html( key( $query_mode_def ) ); ?></option>
						<?php } ?>
                    </select>
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_VESPA ); ?>">
                <div class='col_left'>Rank profile</div>
                <div class='col_right'>
                    <input type='text'
                           id="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_RANK_PROFILE ); ?>"
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_RANK_PROFILE ); ?>]'
                           placeholder="Enter a rank profile, if you defined one in your custom index schema. Leave empty for 'default'."
                           value="<?php WPSOLR_Escape::echo_esc_attr( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_RANK_PROFILE ] ?? '' ); ?>">
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_VESPA ); ?>">
                <div class='col_left'><a
                            href="https://docs.vespa.ai/en/reference/simple-query-language-reference.html"
                            target="_new">Text query type</a></div>
                <div class='col_right'>
                    <select name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE ); ?>]">
						<?php foreach (
							[
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_ALL      => [ 'all - Default. Creates an AND: All the words of the query must match' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_ANY      => [ 'any - Creates an OR: At least one of the words of the query must match' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_WEAKAND  => [ 'weakAnd - Creates a WeakAnd: Like "any", but with performance more like "all"' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_TOKENIZE => [ 'tokenize - Simply splits the text into word tokens and assembles them into a WeakAnd item.' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_WEB      => [ 'Web - Like the all type with + in front of a term means "search for this term as-is' => true ],
								WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_PHRASE   => [ 'phrase - The words of the query is considered a phrase, the words must match in the given order. Colon, plus and so on is ignored' => true ],
							] as $query_type => $query_type_def
						) {
							$selected = ( ( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE ] ?? WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_QUERY_TYPE_ALL ) === $query_type ) ? 'selected' : '';
							?>
                            <option <?php WPSOLR_Escape::echo_escaped( $query_type_def[ key( $query_type_def ) ] ? '' : 'disabled' ); ?>
                                    value="<?php WPSOLR_Escape::echo_esc_attr( $query_type ); ?>" <?php WPSOLR_Escape::echo_esc_attr( $selected ); ?> ><?php WPSOLR_Escape::echo_esc_html( key( $query_type_def ) ); ?></option>
						<?php } ?>
                    </select>
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_VESPA ); ?>">
                <div class='col_left'>Fieldset</div>
                <div class='col_right'>
                    <input type='text'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_FIELDSET ); ?>]'
                           placeholder="Enter a fieldset, if you defined one in your custom index schema. Leave empty for 'default'."
                           value="<?php WPSOLR_Escape::echo_esc_attr( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_VESPA_FIELDSET ] ?? '' ); ?>">
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
                <div class='col_left'>No. of results per page</div>
                <div class='col_right'>
                    <input type='text' id='number_of_res'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[no_res]'
                           placeholder="Enter a Number"
                           value="<?php WPSOLR_Escape::echo_escaped( empty( $solr_res_options['no_res'] ) ? '20' : WPSOLR_Escape::esc_attr( $solr_res_options['no_res'] ) ); ?>">
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>
            </div>
            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    ">
                <div class='col_left'>No. of values to be displayed by filters</div>
                <div class='col_right'>
                    <input type='text' id='number_of_fac'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[no_fac]'
                           placeholder="Enter a Number"
                           value="<?php WPSOLR_Escape::echo_escaped( ( isset( $solr_res_options['no_fac'] ) && ( '' !== trim( $solr_res_options['no_fac'] ) ) ) ? WPSOLR_Escape::esc_attr( $solr_res_options['no_fac'] ) : '20' ); ?>"><span
                            class='fac_err'></span>
                    0 for unlimited values
                </div>
                <div class="clear"></div>
            </div>
            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine_not
                    <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE ); ?>
                    ">
                <div class='col_left'>Maximum size of each snippet text in results</div>
                <div class='col_right'>
                    <input type='text' id='highlighting_fragsize'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[highlighting_fragsize]'
                           placeholder="Enter a Number"
                           value="<?php WPSOLR_Escape::echo_escaped( empty( $solr_res_options['highlighting_fragsize'] ) ? '100' : WPSOLR_Escape::esc_attr( $solr_res_options['highlighting_fragsize'] ) ); ?>"><span
                            class='highlighting_fragsize_err'></span> <br>
                </div>
                <div class="clear"></div>
            </div>
            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH ); ?> <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH ); ?> <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_SOLR ); ?> <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD ); ?>">
                <div class='col_left'>Use partial keyword matches in results</div>
                <div class='col_right'>
                    <input type='checkbox' class='wpsolr_checkbox_mono_wpsolr_is_partial'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES ); ?>]'
                           value='1'
						<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES ] ) ); ?>>
                    Warning: this will hurt both search performance and search accuracy !
                    <p>This adds '*' to all keywords.
                        For instance, 'search apache' will return results
                        containing 'searching apachesolr'</p>
                </div>
                <div class="clear"></div>
            </div>
            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH ); ?> <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH ); ?> <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_SOLR ); ?> <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD ); ?>">
                <div class='col_left'>Use fuzzy keyword matches in results</div>
                <div class='col_right'>
                    <input type='checkbox' class='wpsolr_checkbox_mono_wpsolr_is_partial other'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES ); ?>]'
                           value='1'
						<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES ] ) ); ?>>
                    See <a
                            href="https://cwiki.apache.org/confluence/display/solr/The+Standard+Query+Parser#TheStandardQueryParser-FuzzySearches"
                            target="_new">Fuzzy description at Solr wiki</a>
                    <p>The search 'roam' will match terms like roams, foam, & foams. It will
                        also
                        match the word "roam" itself.</p>
                </div>
                <div class="clear"></div>
            </div>
            <div class="wdm_row wpsolr-remove-if-hidden wpsolr_engine <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH ); ?>  <?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH ); ?>">
                <div class='col_left'>Show all results</div>
                <div class='col_right'>
                    <input type='checkbox'
                           name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SEARCH_IS_SHOW_ALL_RESULTS ); ?>]'
                           value='1'
						<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_IS_SHOW_ALL_RESULTS ] ) ); ?>>
                    Remove the limit on the number of results displayed. <a
                            href="https://www.elastic.co/guide/en/elasticsearch/reference/current/search-your-data.html#track-total-hits"
                            target="_new">For Elasticsearch, the default limit is 10,000.</a>
                </div>
                <div class="clear"></div>
            </div>
            <div class='wdm_row'>
                <div class="submit">
                    <input name="save_selected_options_res_form"
                           id="save_selected_res_options_form" type="submit"
                           class="button-primary wdm-save" value="Save Options"/>


                </div>
            </div>
    </div>

    </form>
</div>
<?php
break;

case 'index_opt':

	/**
	 * Set the options name from the index
	 */
	$wpsolr_index_uuid = WPSOLR_Option_View::get_url_index_uuid();
	$index_options_name = WPSOLR_Option_View::get_index_uuid_options_name( WPSOLR_Option::OPTION_INDEX );

	$custom_fields_error_message = '';

	WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );
	$option_indexes     = new WPSOLR_Option_Indexes();
	$solr_indexes       = $option_indexes->get_indexes();
	$wpsolr_index_label = isset( $solr_indexes[ $wpsolr_index_uuid ] ) ? $option_indexes->get_index_name( $solr_indexes[ $wpsolr_index_uuid ] ) : '';

	$option_index              = $option_indexes->get_index( $wpsolr_index_uuid );
	$search_engine             = $option_indexes->get_index_search_engine( $option_index );
	$is_engine_indexing_images = ( WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE === $search_engine ) &&
	                             ( in_array( $option_index[ WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ], [
		                             WPSOLR_Weaviate_Constants::MODULE_MULTI2VEC_CLIP,
		                             WPSOLR_Weaviate_Constants::MODULE_MULTI2VEC_BIND
	                             ] ) );

	if ( isset( $_GET['settings-updated'] ) ) {
		// Reset index stored settings
		WPSOLR_Service_Container::getOption()->set_option_index_filtered_fields();
	}

	?>

    <div id="solr-indexing-options" class="wdm-vertical-tabs-content">
        <form action="options.php" method="POST" id='settings_form'>
			<?php
			WPSOLR_Option_View::output_form_index_hidden_fields( 'solr_form_options' );
			$solr_options = WPSOLR_Service_Container::getOption()->get_option_index( false );
			?>


            <div class='indexing_option wrapper'>
                <h4 class='head_div'><?php WPSOLR_Escape::echo_escaped( WPSOLR_Option_View::get_indexes_html( 'Processing options' ) ); ?></h4>

                <div class="wdm_note">

                    In this section, you will choose among all the data stored in your Wordpress
                    site, which you want to
                    load <?php WPSOLR_Escape::echo_escaped( ! empty( $wpsolr_index_label ) ?
						sprintf( "in your connector '%s'.", WPSOLR_Escape::esc_html( $wpsolr_index_label ) ) : "in your connectors." ); ?>

                </div>

				<?php
				if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_STOP_REAL_TIME ) ) ) {
					require_once $file_to_include;
				}
				?>

                <div class="wdm_row">
                    <div class='col_left'>
                        Process post excerpt
                    </div>
                    <div class='col_right'>
                        <input type='checkbox'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[p_excerpt]'
                               class="wpsolr_collapser"
                               value='1' <?php checked( '1', isset( $solr_options['p_excerpt'] ) ? $solr_options['p_excerpt'] : '' ); ?>>
                        <span class="wpsolr_collapsed">Excerpt will be added to the post content.</span>

                    </div>
                    <div class="clear"></div>
                </div>
                <div class="wdm_row">
                    <div class='col_left'>
                        Process custom fields and categories
                    </div>
                    <div class='col_right'>
                        <input type='checkbox'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[p_custom_fields]'
                               class="wpsolr_collapser"
                               value='1' <?php checked( '1', isset( $solr_options['p_custom_fields'] ) ? $solr_options['p_custom_fields'] : '' ); ?>>
                        <span class="wpsolr_collapsed">
                                    Custom fields and categories will be added to the post content.
                                </span>

                    </div>
                    <div class="clear"></div>
                </div>
                <div class="wdm_row">
                    <div class='col_left'>
                        Expand shortcodes
                    </div>
                    <div class='col_right'>
                        <input type='checkbox'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[is_shortcode_expanded]'
                               class="wpsolr_collapser"
                               value='1' <?php checked( '1', isset( $solr_options['is_shortcode_expanded'] ) ? $solr_options['is_shortcode_expanded'] : '' ); ?>>
                        <span class="wpsolr_collapsed">Expand shortcodes of post content before processing. Else, shortcodes will simply be stripped.</span>

                    </div>
                    <div class="clear"></div>
                </div>

				<?php
				/**
				 * Show models to index
				 */
				$field_types_opt = WPSOLR_Service_Container::getOption()->get_option_index_custom_fields();
				if ( true ) {
					$field_types_opt = apply_filters(
						WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS_SELECTED,
						$field_types_opt
					);
				}

				$engine_models               = [];
				$model_type_objects_to_index = WPSOLR_Model_Builder::get_model_type_objects( $engine_models );
				$model_types                 = [];
				foreach ( $model_type_objects_to_index as $model_to_index ) {
					array_push( $model_types, $model_to_index->get_type() );
				}
				// Sort post types
				asort( $model_types );
				$post_types_selected = apply_filters(
					WPSOLR_Events::WPSOLR_FILTER_INDEX_POST_TYPES_SELECTED,
					WPSOLR_Service_Container::getOption()->get_option_index_post_types()
				);

				$custom_field_properties = WPSOLR_Service_Container::getOption()->get_option_index_custom_field_properties();
				if ( empty( $solr_options ) ) {
					$custom_field_properties = apply_filters(
						WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS_PROPERTIES_SELECTED,
						$custom_field_properties
					);
				}
				//$field_types_opt = WPSOLR_Model_Meta_Type_Post::reformat_old_custom_fields( $field_types_opt, $model_types );

				$post_types_is_admin = WPSOLR_Service_Container::getOption()->get_option_index_post_types_is_admin();

				$text_catalog = 'Select data catalog to process';
				$loop_values  = [ false => $text_catalog, ];
				foreach ( $loop_values as $loop_value => $loop_header_text ) {
					$loop_value = (bool) $loop_value;
					?>

                    <div class="wdm_row">
                        <div class='col_left'>
							<?php if ( ! $loop_value ) { // Display once only hidden fields ?>
                                <input type='hidden'
                                       name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEX_POST_TYPES ); ?>]'
                                       id='p_types'>
                                <input type='hidden'
                                       name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[taxonomies]'
                                       id='tax_types'>
							<?php } ?>

                            <h2><?php WPSOLR_Escape::echo_esc_html( $loop_header_text ); ?></h2>

                        </div>
                        <div class='col_right'>
                        </div>
                        <div class="clear"></div>
                    </div>

					<?php
					foreach (
						[
							true,
							false
						] as $is_show_models_already_selected
					) {
						// Show selected first, then not selected
						foreach ( $model_type_objects_to_index as $model_type_object ) {


							$model_type                         = $model_type_object->get_type();
							$is_model_already_selected          = in_array( $model_type, $post_types_selected );
							$is_show_model_already_selected     = ( $is_show_models_already_selected && $is_model_already_selected );
							$is_show_model_not_already_selected = ( ! $is_show_models_already_selected && ! $is_model_already_selected );
							$is_model_checked                   = $is_show_model_already_selected ? 'checked' : '';
							$is_model_shown                     = ( $is_show_model_already_selected || $is_show_model_not_already_selected );
							//$taxonomies                      = get_taxonomies( [], 'names', 'and' );
							$model_type_taxonomies = $model_type_object->get_taxonomies();
							$model_type_fields     = [];
							try {// Filter custom fields to be indexed.
								$model_type_fields = $model_type_object->get_fields();
							} catch ( Exception $e ) {
								$custom_fields_error_message = $e->getMessage();
							}

							if ( $is_model_checked ) {
								// Upgrade selected model database if necessary
								$model_type_object->upgrade_database_if_model_has_no_modified_date();
							}

							$post_type_is_admin = ! empty( $post_types_is_admin[ $model_type ] );

							if ( $is_model_shown ) { ?>
                                <div class="wdm_row">
                                    <div class='col_left'>


                                        <div style="float:left;width:100%">
                                            <input type='checkbox' name='post_tys'
                                                   class="wpsolr_checked wpsolr_column_collapser wpsolr-remove-if-empty"
                                                   style="float:left;margin-top:3px;"
                                                   value='<?php WPSOLR_Escape::echo_esc_attr( $model_type ) ?>'
												<?php WPSOLR_Escape::echo_esc_attr( $is_model_checked ); ?>>
                                            <span style="float:left"><?php WPSOLR_Escape::echo_esc_html( $model_type_object->get_label() ); ?></span>
                                        </div>
                                        <br>

                                    </div>
                                    <div class='col_right'>

                                        <div class="wpsolr_column_collapsed wpsolr-remove-if-hidden  <?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>" <?php WPSOLR_Escape::echo_escaped( $is_show_model_already_selected ? '' : 'style="display:none;"' ); ?>>

											<?php
											$is_post_type = ! ( empty( $model_type_fields ) && empty( $model_type_taxonomies ) );
											if ( ! $loop_value ) { ?>
                                                <div style="float:left;width:100%;margin-bottom:20px;">
                                                    <input type='checkbox'
                                                           name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_INDEX_POST_TYPES_IS_ADMIN ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>]'
                                                           class="wpsolr_checked"
                                                           style="float:left"
                                                           value='<?php WPSOLR_Escape::echo_esc_attr( $model_type ); ?>'
														<?php checked( $post_type_is_admin ) ?>>
                                                    <span style="float:left">Process all statuses. Otherwise, only published statuses are processed.</span>
                                                </div>

												<?php
												if ( $is_engine_indexing_images &&
												     ( $model_type_object->has_images() ) &&
												     file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_IMAGES ) ) ) {
													require $file_to_include;
												}

												?>

												<?php WPSOLR_Escape::echo_escaped( $model_type_object->get_is_search() ? '' : sprintf( '%s are displayed in suggestions only, not in front search results.', WPSOLR_Escape::esc_html( $model_type_object->get_label() ) ) ); ?>

												<?php if ( $is_post_type ) { ?>

													<?php
													if ( ( $model_type_object->has_attachments() ) && file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_ATTACHMENTS ) ) ) {
														require $file_to_include;
														?>
                                                        <br>
														<?php
													}
													?>

													<?php
													if ( ! empty( $model_type_taxonomies ) && file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_TAXONOMIES ) ) ) {
														require $file_to_include;
													}
													?>

													<?php if ( ! empty( $model_type_fields ) && ! empty( $model_type_taxonomies ) ) { ?>
                                                        <br>
													<?php } ?>

													<?php
													if ( ! empty( $model_type_fields ) && file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_CUSTOM_FIELDS ) ) ) {
														require $file_to_include;
													}
													?>

												<?php } ?>

											<?php } else {
											}
											?>
                                        </div>

                                    </div>

                                    <div class="clear"></div>
                                </div>
                                <hr>
							<?php } ?>

						<?php } ?>
					<?php }
				}

				?>


                <div class="wdm_row">
                    <div class='col_left'>Process comments</div>
                    <div class='col_right'>
                        <input type='checkbox'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[comments]'
                               value='1' <?php checked( '1', isset( $solr_options['comments'] ) ? $solr_options['comments'] : '' ); ?>>

                    </div>
                    <div class="clear"></div>
                </div>
                <div class="wdm_row">
                    <div class='col_left'>Do not process items (post, pages, ...)</div>
                    <div class='col_right'>
                        <input type='text'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_OPTION::OPTION_INDEX_POST_EXCLUDES_IDS_FROM_INDEXING ); ?>]'
                               placeholder="Comma separated ID's list"
                               value="<?php WPSOLR_Escape::echo_escaped( empty( $solr_options[ WPSOLR_OPTION::OPTION_INDEX_POST_EXCLUDES_IDS_FROM_INDEXING ] ) ? '' : WPSOLR_Escape::esc_attr( $solr_options[ WPSOLR_OPTION::OPTION_INDEX_POST_EXCLUDES_IDS_FROM_INDEXING ] ) ); ?>">
                    </div>
                    <div class="clear"></div>
                </div>
                <div class="wdm_row">
                    <div class='col_left'>Filter items from search results (post, pages, ...)</div>
                    <div class='col_right'>
                        <input type='text'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $index_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_OPTION::OPTION_INDEX_POST_EXCLUDES_IDS_FROM_SEARCHING ); ?>]'
                               placeholder="Comma separated ID's list"
                               value="<?php WPSOLR_Escape::echo_escaped( empty( $solr_options[ WPSOLR_OPTION::OPTION_INDEX_POST_EXCLUDES_IDS_FROM_SEARCHING ] ) ? '' : WPSOLR_Escape::esc_attr( $solr_options[ WPSOLR_OPTION::OPTION_INDEX_POST_EXCLUDES_IDS_FROM_SEARCHING ] ) ); ?>">
                    </div>
                    <div class="clear"></div>
                </div>
                <div class='wdm_row'>
                    <div class="submit">
                        <input name="save_selected_index_options_form"
                               id="save_selected_index_options_form" type="submit"
                               class="button-primary wdm-save" value="Save Options"/>


                    </div>
                </div>

            </div>
        </form>
    </div>
	<?php
	break;

case 'field_opt':
	if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_BOOSTS ) ) ) {
		require_once $file_to_include;
	} else {
		?>
        <div id="solr-facets-options" class="wdm-vertical-tabs-content">
            <div class='wrapper'>
                <h4 class='head_div'>Boost Options</h4>

                <div class="wdm_note">

                    With <?php WPSOLR_Escape::echo_esc_url( sprintf( '<a href="%s" target="__new"><?php WPSOLR_Escape::echo_esc_html( WPSOLR_PLUGIN_SHORT_NAME ); ?></a>', $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) ) ); ?>
                    , you can add boosts (weights) to the fields you think are the most
                    important.
                </div>
            </div>
        </div>
		<?php
	}
	break;

case 'facet_opt':
	/**
	 * Set the options name from the view
	 */
	$view_options_name = WPSOLR_Option_View::get_view_uuid_options_name( WPSOLR_Option::OPTION_FACET );

	$solr_options = WPSOLR_Service_Container::getOption()->get_option_index();

	$checked_fields = array_merge( WPSOLR_Service_Container::getOption()->get_option_index_custom_fields( true ), WPSOLR_Service_Container::getOption()->get_option_index_taxonomies() );
	$img_path       = plugins_url( '../images/plus.png', __FILE__ );
	$minus_path     = plugins_url( '../images/minus.png', __FILE__ );
	$built_in       = [
		'Type',
		'Author',
		'Categories',
		'Tags',
		WpSolrSchema::_FIELD_NAME_STATUS_S,
		WpSolrSchema::_FIELD_NAME_DISPLAY_DATE_DT,
		WpSolrSchema::_FIELD_NAME_POST_MODIFIED,
		WpSolrSchema::_FIELD_NAME_META_TYPE_S,
	];
	$built_in       = array_merge( $built_in, array_diff( $checked_fields, [
		WpSolrSchema::_FIELD_NAME_CATEGORY_STR,
		WpSolrSchema::_FIELD_NAME_POST_TAG_STR
	] ) );

	$built_in_can_show_hierarchy = array_merge( [ 'Categories' ], WPSOLR_Service_Container::getOption()->get_option_index_taxonomies() );

	$facet_layout_skins_available           = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_LAYOUT_SKINS, [] );
	$facet_layout_generic_scripts_available = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_GENERIC_SCRIPTS, [] );

	?>
    <div id="solr-facets-options" class="wdm-vertical-tabs-content">
        <form action="options.php" method="POST" id='fac_settings_form'>
			<?php
			WPSOLR_Option_View::output_form_view_hidden_fields( 'solr_facet_options' );

			$solr_fac_options                        = WPSOLR_Service_Container::getOption()->get_option_facet();
			$selected_facets_value                   = WPSOLR_Service_Container::getOption()->get_facets_to_display_str();
			$selected_array                          = WPSOLR_Service_Container::getOption()->get_facets_to_display();
			$selected_facets_is_hierarchy            = ! empty( $solr_fac_options[ WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH ] ) ? $solr_fac_options[ WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH ] : array();
			$selected_facets_labels                  = WPSOLR_Service_Container::getOption()->get_facets_labels();
			$selected_facets_item_labels             = WPSOLR_Service_Container::getOption()->get_facets_items_labels();
			$selected_facets_item_is_default         = WPSOLR_Service_Container::getOption()->get_facets_items_is_default();
			$selected_facets_item_is_hidden          = WPSOLR_Service_Container::getOption()->get_facets_items_is_hidden();
			$selected_facets_sorts                   = WPSOLR_Service_Container::getOption()->get_facets_sort();
			$selected_facets_is_exclusions           = WPSOLR_Service_Container::getOption()->get_facets_is_exclusion();
			$selected_facets_layouts                 = WPSOLR_Service_Container::getOption()->get_facets_layouts_ids();
			$selected_facets_is_or                   = WPSOLR_Service_Container::getOption()->get_facets_is_or();
			$selected_facets_seo_is_permalink        = WPSOLR_Service_Container::getOption()->get_facets_seo_is_permalinks();
			$selected_facets_seo_templates           = WPSOLR_Service_Container::getOption()->get_facets_seo_permalink_templates();
			$selected_facets_seo_items_templates     = WPSOLR_Service_Container::getOption()->get_facets_seo_permalink_items_templates();
			$selected_facets_is_show_variation_image = WPSOLR_Service_Container::getOption()->get_facets_is_show_variation_image();
			$selected_facets_is_hide_if_no_choice    = WPSOLR_Service_Container::getOption()->get_facets_is_hide_if_no_choice();
			$selected_facets_is_can_not_exist        = WPSOLR_Service_Container::getOption()->get_facets_is_can_not_exist();

			?>
            <div class='wrapper'>
                <h4 class='head_div'><?php WPSOLR_Escape::echo_escaped( WPSOLR_Option_View::get_views_html( 'Filters Options' ) ); ?> </h4>

                <div class="wdm_note">

                    In this section, you will choose which data you want to display as filters in
                    your search results. filters are extra filters usually seen in the left hand
                    side of the results, displayed as a list of links. You can add filters only
                    to data you've selected to be indexed.

                </div>
                <div class="wdm_note">
                    <h4>Instructions</h4>
                    <ul class="wdm_ul wdm-instructions">
                        <li>Click on the 'Plus' icon to add the filters</li>
                        <li>Click on the 'Minus' icon to remove the filters</li>
                        <li>Sort the items in the order you want to display them by dragging and
                            dropping them at the desired place
                        </li>
                    </ul>
                </div>


				<?php
				if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_SEO_TEMPLATE_POSITIONS ) ) ) {
					require $file_to_include;
				}
				?>

                <div class="wdm_row">
                    <div class='col_left'>
                        Orientation
                    </div>
                    <div class='col_right'>
                        <select name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_FACETS_ORIENTATION ); ?>]">
							<?php
							$orientation = WPSOLR_Service_Container::getOption()->get_facets_orientation();
							foreach (
								[
									WPSOLR_Option::OPTION_FACETS_ORIENTATION_VERTICAL   => 'Vertical',
									WPSOLR_Option::OPTION_FACETS_ORIENTATION_HORIZONTAL => 'Horizontal',
								] as $orientation_code => $orientation_label
							) {
								$selected = ( $orientation === $orientation_code ) ? 'selected' : '';
								?>
                                <option
                                        value="<?php WPSOLR_Escape::echo_esc_attr( $orientation_code ); ?>" <?php WPSOLR_Escape::echo_esc_attr( $selected ); ?> ><?php WPSOLR_Escape::echo_esc_attr( $orientation_label ); ?></option>
							<?php } ?>
                        </select>
                    </div>
                </div>
                <br/><br/><br/>

                <div class="wdm_row">
                    <div style="float: right">
                        <a href="javascript:void(0);" class="plus_icon_all">All</a> |
                        <a href="javascript:void(0);" class="minus_icon_all">None</a>
                    </div>

                    <div class='avail_fac' style="width:100%">
                        <h4>Available items for filters</h4>

                        <input type='hidden' id='select_fac'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[facets]'
                               value='<?php WPSOLR_Escape::echo_esc_attr( $selected_facets_value ); ?>'>

                        <ul id="sortable1" class="wdm_ul connectedSortable">
							<?php

							if ( $selected_facets_value != '' ) {
								foreach ( $selected_array as $selected_val ) {
									if ( $selected_val != '' ) {
										if ( substr( $selected_val, ( strlen( $selected_val ) - 4 ), strlen( $selected_val ) ) == WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) {
											$dis_text = substr( $selected_val, 0, ( strlen( $selected_val ) - 4 ) );
										} else {
											$dis_text = $selected_val;
										}

										?>
                                        <li id='<?php WPSOLR_Escape::echo_esc_attr( $selected_val ); ?>'
                                            class='ui-state-default facets facet_selected wpsolr_checked'>
															<span style="float:left;width: 300px;">
                                                                <?php WPSOLR_Escape::echo_esc_html( $dis_text ); ?>
                                                            </span>
                                            <img src='<?php WPSOLR_Escape::echo_esc_url( $img_path ); ?>'
                                                 class='plus_icon'
                                                 style='display:none'>
                                            <img src='<?php WPSOLR_Escape::echo_esc_url( $minus_path ); ?>'
                                                 class='minus_icon'
                                                 style='display:inline'
                                                 title='Click to Remove the filter'>
                                            <br/>
											<?php
											if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_LABEL ) ) ) {
												require $file_to_include;
											}

											if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_THEME_FACET_LAYOUT ) ) ) {
												require $file_to_include;
											}
											?>

                                        </li>

									<?php }
								}
							}
							foreach ( $built_in as $built_fac ) {
								if ( $built_fac != '' ) {
									$buil_fac = strtolower( $built_fac );
									if ( substr( $buil_fac, ( strlen( $buil_fac ) - 4 ), strlen( $buil_fac ) ) == WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) {
										$dis_text = substr( $buil_fac, 0, ( strlen( $buil_fac ) - 4 ) );
									} else {
										$dis_text = $buil_fac;
									}

									if ( ! in_array( $buil_fac, $selected_array )
									     && ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT !== WpSolrSchema::get_custom_field_dynamic_type( $buil_fac ) ) // Long texts cannot be faceted (due to analysers)
									) {

										WPSOLR_Escape::echo_escaped(
											sprintf( "<li id='%s' class='ui-state-default facets wpsolr_checked'>%s
                                                                                                    <img src='%s'  class='plus_icon' style='display:inline' title='Click to Add the Facet'>
                                                                                                <img src='%s' class='minus_icon' style='display:none'></li>",
												WPSOLR_Escape::esc_attr( $buil_fac ),
												WPSOLR_Escape::esc_html( $dis_text ),
												WPSOLR_Escape::esc_url( $img_path ),
												WPSOLR_Escape::esc_url( $minus_path )
											)
										);
									}
								}
							}
							?>


                        </ul>
                    </div>

                    <div class="clear"></div>
                </div>

                <div class='wdm_row'>
                    <div class="submit">
                        <input name="save_facets_options_form" id="save_facets_options_form"
                               type="submit" class="button-primary wdm-save"
                               value="Save Options"/>


                    </div>
                </div>
            </div>
        </form>
    </div>
	<?php
	break;

case 'sort_opt':
	/**
	 * Set the options name from the view
	 */
	$view_options_name = WPSOLR_Option_View::get_view_uuid_options_name( WPSOLR_Option::OPTION_SORTBY );

	$img_path   = plugins_url( '../images/plus.png', __FILE__ );
	$minus_path = plugins_url( '../images/minus.png', __FILE__ );

	$built_in = WpSolrSchema::get_sort_fields();
	?>
    <div id="solr-sort-options" class="wdm-vertical-tabs-content">
        <form action="options.php" method="POST" id='sort_settings_form'>
			<?php
			WPSOLR_Option_View::output_form_view_hidden_fields( 'solr_sort_options' );
			$selected_array         = apply_filters(
				WPSOLR_Events::WPSOLR_FILTER_INDEX_SORTS_SELECTED,
				WPSOLR_Service_Container::getOption()->get_sortby_items_as_array()
			);
			$selected_sort_value    = WPSOLR_Service_Container::getOption()->get_sortby_items();
			$selected_sortby_labels = WPSOLR_Service_Container::getOption()->get_sortby_items_labels();
			?>
            <div class='wrapper'>
                <h4 class='head_div'><?php WPSOLR_Escape::echo_escaped( WPSOLR_Option_View::get_views_html( 'Sort Options' ) ); ?> </h4>

                <div class="wdm_note">

                    In this section, you will choose which elements will be displayed as sort
                    criteria for your search results, and in which order.

                </div>
                <div class="wdm_note">
                    <h4>Instructions</h4>
                    <ul class="wdm_ul wdm-instructions">
                        <li>Click on the 'Plus' icon to add the sort</li>
                        <li>Click on the 'Minus' icon to remove the sort</li>
                        <li>Sort the items in the order you want to display them by dragging and
                            dropping them at the desired place
                        </li>
                    </ul>
                </div>

				<?php if ( ! empty( $selected_array ) ) { ?>
                    <div class="wdm_row">
                        <div class='col_left'>
                            Shortcode
                        </div>
                        <div class='col_right'>

                            <input id="<?php WPSOLR_Escape::echo_esc_attr( sprintf( 'shortcode_%s', $current_view_uuid ) ); ?>"
                                   type='text' readonly
                                   value="<?php WPSOLR_Escape::echo_escaped( WPSOLR_Shortcode_Sort::get_shortcode_html( $current_view_uuid, WPSOLR_Option_View::get_view_label(), true ) ); ?>"
                            >

                        </div>
                        <div class="clear"></div>
                    </div>
				<?php } ?>

                <div class="wdm_row">
                    <div class='col_left'>
                        Default first sort, if no sort is selected by the user
                    </div>
                    <div class='col_right'>
                        <select name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SORTBY_ITEM_DEFAULT_FIRST ); ?>]">
							<?php foreach ( apply_filters( WPSOLR_Events::WPSOLR_FILTER_DEFAULT_SORT_FIELDS, $built_in ) as $sort ) {
								$selected = WPSOLR_Service_Container::getOption()->get_first_sort_by_default() == $sort['code'] ? 'selected' : '';
								?>
                                <option
                                        value="<?php WPSOLR_Escape::echo_esc_attr( $sort['code'] ); ?>" <?php WPSOLR_Escape::echo_esc_attr( $selected ); ?> ><?php WPSOLR_Escape::echo_esc_html( $sort['label'] ); ?></option>
							<?php } ?>
                        </select>
                    </div>
                </div>

                <div class="wdm_row">
                    <div class='col_left'>Default second sort</div>
                    <div class='col_right'>
                        <select name="<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_SORTBY_ITEM_DEFAULT_SECOND ); ?>]">
							<?php foreach (
								array_merge( [
									[
										'code'  => '',
										'label' => 'No second sort',
									]
								], apply_filters( WPSOLR_Events::WPSOLR_FILTER_DEFAULT_SORT_FIELDS, $built_in ) ) as $sort
							) {
								if ( $sort['code'] !== WPSOLR_AbstractSearchClient::SORT_CODE_BY_RANDOM ) {
									$selected = WPSOLR_Service_Container::getOption()->get_second_sort_by_default() == $sort['code'] ? 'selected' : '';
									?>
                                    <option
                                            value="<?php WPSOLR_Escape::echo_esc_attr( $sort['code'] ); ?>" <?php WPSOLR_Escape::echo_esc_attr( $selected ); ?> ><?php WPSOLR_Escape::echo_esc_html( $sort['label'] ); ?>
                                    </option>
								<?php }
							} ?>
                        </select>
                    </div>
                </div>

                <div class="wdm_row">
                    <div class='avail_fac'>
                        <h4>Activate/deactivate items in the sort list</h4>
                        <input type='hidden' id='select_sort'
                               name='<?php WPSOLR_Escape::echo_esc_attr( $view_options_name ); ?>[sort]'
                               value='<?php WPSOLR_Escape::echo_esc_attr( $selected_sort_value ); ?>'>


                        <ul id="sortable_sort" class="wdm_ul connectedSortable_sort">
							<?php
							foreach ( $selected_array

							as $selected_sort ) {
							foreach ( $built_in

							as $built ) {
							if ( ! empty( $built ) && ( $selected_sort === $built['code'] ) ) {
							$sort_code = $built['code'];
							$dis_text  = $built['label'];

							if ( in_array( $sort_code, $selected_array ) ) {

							?>
                            <li id='<?php WPSOLR_Escape::echo_esc_attr( $sort_code ); ?>'
                                class='ui-state-default facets sort_selected'>
                                <span
                                        style="float:left;width: 300px;"><?php WPSOLR_Escape::echo_esc_html( $dis_text ); ?></span>
                                <img src='<?php WPSOLR_Escape::echo_esc_url( $img_path ); ?>'
                                     class='minus_icon_sort'
                                     style='display:none'>
                                <img src='<?php WPSOLR_Escape::echo_esc_url( $minus_path ); ?>'
                                     class='minus_icon_sort'
                                     style='display:inline'
                                     title='Click to Remove the sort item'>
                                <br/>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SORT_LABEL ) ) ) {
									require $file_to_include;
								}
								?>

								<?php
								}
								}
								}
								}
								foreach ( $built_in as $built ) {
									if ( $built != '' ) {
										$buil_fac = $built['code'];
										$dis_text = $built['label'];

										if ( ! in_array( $buil_fac, $selected_array ) ) {

											WPSOLR_Escape::echo_escaped(
												sprintf( "<li id='%s' class='ui-state-default facets'>%s
                                                                                                    <img src='%s'  class='plus_icon_sort' style='display:inline' title='Click to Add the Sort'>
                                                                                                <img src='%s' class='minus_icon_sort' style='display:none'></li>",
													WPSOLR_Escape::esc_attr( $buil_fac ),
													WPSOLR_Escape::esc_html( $dis_text ),
													WPSOLR_Escape::esc_url( $img_path ),
													WPSOLR_Escape::esc_url( $minus_path )
												)
											);
										}
									}
								}
								?>
                            </li>

                        </ul>
                    </div>

                    <div class="clear"></div>
                </div>

                <div class='wdm_row'>
                    <div class="submit">
                        <input name="save_sort_options_form" id="save_sort_options_form"
                               type="submit" class="button-primary wdm-save"
                               value="Save Options"/>


                    </div>
                </div>
            </div>
        </form>
    </div>
	<?php
	break;

case 'localization_options':
	WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_LOCALIZATION );
	break;

case 'suggestion_opt':
	WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_SUGGESTIONS );
	break;

case 'recommendation_opt':
	WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_RECOMMENDATIONS );
	break;

}

?>

</div>
