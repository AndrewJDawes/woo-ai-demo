<?php

use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Identifiers of the 3 custom templates. Change them as you wish, but ensure they remain unique.
 */
define( 'WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_KEYWORDS', 'wpsolr-custom-example-twentysixteen-child-keywords' );
define( 'WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_CONTENT', 'wpsolr-custom-example-twentysixteen-child-content' );
define( 'WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_CONTENT_GROUPED', 'wpsolr-custom-example-twentysixteen-child-content-grouped' );

/**
 * Enqueue child theme style.css
 */
function mychildtheme_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

add_action( 'wp_enqueue_scripts', 'mychildtheme_enqueue_styles' );


/**
 * WPSOLR customizations example
 */
add_action( 'admin_init', function () {

	/**
	 * Add custom suggestions templates
	 */
	add_filter( WPSOLR_Events::WPSOLR_FILTER_SUGGESTIONS_TEMPLATES, function ( $current_templates ) {

		/**
		 * The following 3 local twig templates will be shown in the "Template" dropdown list of suggestions in admin screen 2.3.
		 * Just select one in the admin, and your local template will be used to show the suggestions.
		 */
		$current_templates[] = [
			'code'          => WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_KEYWORDS,
			'label'         => 'Custom - Keywords (example from Twentysixteen child theme)',
			'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS,
			'template_file' => 'suggestions/example1/suggestions-keywords.twig',
			// Relative to path "my_theme/wpsolr-templates/twig"
		];
		$current_templates[] = [
			'code'          => WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_CONTENT,
			'label'         => 'Custom - Content flat (example from Twentysixteen child theme)',
			'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT,
			'template_file' => 'suggestions/example1/suggestions-content.twig',
			// Relative to path "my_theme/wpsolr-templates/twig"
		];
		$current_templates[] = [
			'code'          => WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_CONTENT_GROUPED,
			'label'         => 'Custom - Content grouped (example from Twentysixteen child theme)',
			'type'          => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_CONTENT_GROUPED,
			'template_file' => 'suggestions/example1/suggestions-content-grouped.twig',
			// Relative to path "my_theme/wpsolr-templates/twig"
		];

		return $current_templates;
	}, 10, 1 );

	// Add/update some extra data to be shown in your local template
	add_filter( WPSOLR_Events::WPSOLR_ACTION_BEFORE_RENDER_TEMPLATE, function ( $template_params ) {

		// Add a header field in the settings
		$template_params['template_data']['settings']['example_custom_header'] = 'Example: my own header';

		// Add /update template results, depending on the template
		switch ( $template_params['template_data']['settings']['layout_id'] ) {

			case WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_KEYWORDS:
				// For the keywords template, add '>>>' before each result title
				foreach ( $template_params['template_data']['results'] as &$result ) {
					$result['keyword'] = '*** ' . $result['keyword'];
				}
				break;

			case WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_CONTENT:
				// For the flat content template, add '!!!' before each result title
				foreach ( $template_params['template_data']['results'] as &$result ) {
					$result['title']['title'] = '!!! ' . $result['title']['title'];
				}
				break;

			case WPSOLR_CUSTOM_EXAMPLE_TWENTYSIXTEEN_CHILD_CONTENT_GROUPED:
				foreach ( $template_params['template_data']['results'] as $post_type => &$result ) {

					// For the grouped content template, add '$$$' before each result title. Also change the group label.
					$result['label'] .= ': custom';
					foreach ( $result['items'] as &$item ) {
						$item['title']['title'] = '$$$ ' . $item['title']['title'];
					}
				}
				break;
		}


		return $template_params;
	}, 10, 1 );

} );