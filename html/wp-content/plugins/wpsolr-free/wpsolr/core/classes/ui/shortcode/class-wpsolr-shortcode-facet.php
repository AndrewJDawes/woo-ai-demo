<?php

namespace wpsolr\core\classes\ui\shortcode;

use wpsolr\core\classes\exceptions\WPSOLR_Exception_Security;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class WPSOLR_Shortcode_Facet
 */
class WPSOLR_Shortcode_Facet extends WPSOLR_Shortcode_Abstract {

	const SHORTCODE_NAME = 'wpsolr_facet';

	/**
	 * @inheritdoc
	 */
	public static function get_html( $attributes = [] ) {

		try {

			$view_uuid  = $attributes[ static::INSTANCE_ID ] ?? '';
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );
			WPSOLR_Option_View::set_current_index_uuid( $index_uuid = WPSOLR_Service_Container::getOption()->get_view_index_uuid() );


			$results    = WPSOLR_Service_Container::get_solr_client(false, $index_uuid)->get_results_data(
				WPSOLR_Service_Container::get_query(),
				[ 'facets_skins' => ( empty( $attributes ) || empty( $attributes['facets_skins'] ) ) ? [] : $attributes['facets_skins'] ]
			);
			WPSOLR_Option_View::restore_current_view_uuid();

			$html_inner = WPSOLR_Service_Container::get_template_builder()->load_template_facets( $results['facets'] );

			$data_wpsolr_group_facets = apply_filters( WPSOLR_Events::WPSOLR_FILTER_JAVASCRIPT_FRONT_LOCALIZED_PARAMETERS,
				[
					'data' =>
						[
							'is_ajax'                            => WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_with_ajax(),
							'css_ajax_container_page_title'      => empty( $container_page_title ) ? WPSOLR_Option::OPTION_THEME_AJAX_PAGE_TITLE_JQUERY_SELECTOR_DEFAULT : $container_page_title,
							'css_ajax_container_page_sort'       => empty( $container_page_sort ) ? WPSOLR_Option::OPTION_THEME_AJAX_SORT_JQUERY_SELECTOR_DEFAULT : $container_page_sort,
							'css_ajax_container_results'         => empty( $container_results ) ? WPSOLR_Option::OPTION_THEME_AJAX_RESULTS_JQUERY_SELECTOR_DEFAULT : $container_results,
							'css_ajax_container_pagination'      => empty( $container_pagination ) ? WPSOLR_Option::OPTION_THEME_AJAX_PAGINATION_JQUERY_SELECTOR_DEFAULT : $container_pagination,
							'css_ajax_container_pagination_page' => empty( $container_pagination_page ) ? WPSOLR_Option::OPTION_THEME_AJAX_PAGINATION_PAGE_JQUERY_SELECTOR_DEFAULT : $container_pagination_page,
							'css_ajax_container_results_count'   => empty( $container_results_count ) ? WPSOLR_Option::OPTION_THEME_AJAX_RESULTS_COUNT_JQUERY_SELECTOR_DEFAULT : $container_results_count,
						],
				]
			);

			$facets_container_id                                   = ! empty( $data_wpsolr_group_facets['data']['id'] ) ?
				$data_wpsolr_group_facets['data']['id'] : 'res_facets';
			$data_wpsolr_group_facets['data']['url_params_prefix'] = ! empty( $data_wpsolr_group_facets['data']['id'] ) ?
				$data_wpsolr_group_facets['data']['id'] : '';

			$html = sprintf( '<div id="%s" class="wpsolr_group_facets" data-wpsolr-facet-data="%s">%s</div>',
				$facets_container_id,
				esc_js( wp_json_encode( $data_wpsolr_group_facets['data'] ) ),
				$html_inner
			);

		} catch ( WPSOLR_Exception_Security $e ) {

			$html = $e->getMessage();
		}

		return $html;

	}

}