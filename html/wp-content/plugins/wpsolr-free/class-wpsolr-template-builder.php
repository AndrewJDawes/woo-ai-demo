<?php

namespace wpsolr_root;

use Timber\Timber;
use wpsolr\core\classes\extensions\suggestions\WPSOLR_Option_Suggestions;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Deal with php templates
 *
 * Class WPSOLR_Template_Builder
 * @package wpsolr\core\classes\templates
 */
class WPSOLR_Template_Builder {

	/**
	 * Return template file as a string.
	 *
	 * @param string $template_name
	 * @param string $template_args_name
	 * @param array $args
	 *
	 * @return string
	 * @throws \Exception
	 * @since 1.0.0
	 *
	 */
	function load_template( $template_params, $args = [] ) {

		$template_name                = $template_params['template_file'];
		$template_name_with_extension = $template_name;
		$template_args_name           = $template_params['template_args'];

		$sub_dir = WPSOLR_Option_Suggestions::DIR_TWIG;

		// Add arguments variable. It will be extracted by load_template.
		set_query_var( WPSOLR_Option_Suggestions::TEMPLATE_BUILDER, $this );
		set_query_var( WPSOLR_Option_Suggestions::TEMPLATE_ARGS, $args );

		ob_start();

		switch ( $sub_dir ) {
			case WPSOLR_Option_Suggestions::DIR_TWIG:

				// Set the template folders and context

				if ( file_exists( $template_name_with_extension ) ) {
					// Absolute path
					$template          = apply_filters( WPSOLR_Events::WPSOLR_ACTION_BEFORE_RENDER_TEMPLATE, [
						'template_path' => basename( $template_name ),
						'template_data' => $args
					] );
					Timber::$locations = [
						dirname( $template_name ),
						dirname( dirname( $template_name ) ),
					]; // File folder and parent folder to share files

				} else {
					// Relative path to WP hierarchy
					$template_name_with_extension = sprintf( '%s/%s/%s', WPSOLR_Option_Suggestions::TEMPLATE_ROOT_DIR, $sub_dir, $template_name );
					$template                     = apply_filters( WPSOLR_Events::WPSOLR_ACTION_BEFORE_RENDER_TEMPLATE, [
						'template_path' => $template_name_with_extension,
						'template_data' => $args
					] );
					Timber::$dirname              = $this->_get_template_folder_hierarchy( $template['template_path'] );
				}

				$context                        = Timber::context();
				$context[ $template_args_name ] = $template['template_data']; // name of the context variable inside the template

				// Render the template
				Timber::render( $template['template_path'], $context );
				break;

			default:
				WPSOLR_Escape::echo_esc_html( "Template {$template_name_with_extension} should be .php or .twig, but is .{$sub_dir} extension." );
				break;
		}

		return ob_get_clean();
	}

	/**
	 * Add current file hierarchy folders to Timber locations, in reverse order
	 * (so matching is faster with other included files in same dir)
	 * /dir1/dir2/dir3/template.twig = ['dir1/dir2/dir3', 'dir1/dir2', 'dir1']
	 *
	 * @param string $template_name_with_extension
	 *
	 * @return array
	 */
	protected function _get_template_folder_hierarchy( $template_name_with_extension ) {

		$timber_folders = [];
		$folders        = explode( '/', dirname( $template_name_with_extension ) );
		$current_folder = '';
		foreach ( $folders as $folder ) {
			$current_folder .= "/{$folder}";
			array_unshift( $timber_folders, trim( $current_folder, '/' ) ); // Add in reverse order
		}

		return $timber_folders;
	}

	/**
	 * Locates a template and return the path for inclusion.
	 *
	 * This is the load order:
	 *
	 *      yourtheme       /   wpsolr-templates  /   $template_name
	 *      WPSOLR          /   wpsolr-templates /    $template_name
	 *
	 *
	 * @param string $template_name
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function _locate_template( $template_name ) {
		// Look within the theme first.
		$template = locate_template(
			[
				$this->get_template_relative_path( $template_name ),
			]
		);

		// Get plugin's template.
		if ( ! $template ) {
			$path = WPSOLR_PLUGIN_DIR . $this->get_template_relative_path( $template_name );
			if ( file_exists( $path ) ) {
				$template = $path;
			}
		}

		$template = apply_filters( 'wpsolr_locate_template', $template, $template_name );

		if ( ! $template ) {
			throw new \Exception( sprintf( "Missing template %s in /%s",
				esc_html( $template_name ), esc_html( WPSOLR_Option_Suggestions::TEMPLATE_ROOT_DIR ) ) );
		}

		return $template;
	}

	/**
	 * Template path relative to the plugin or theme
	 *
	 * @param string $template_name
	 *
	 * @return string
	 */
	protected function get_template_relative_path( $template_name ) {
		return sprintf( '/%s/', WPSOLR_Option_Suggestions::TEMPLATE_ROOT_DIR ) . $template_name;
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 * @throws \Exception
	 */
	function load_template_search( $args = [] ) {
		return $this->load_template( $this->make_template_params( WPSOLR_Option_Suggestions::TEMPLATE_SEARCH, WPSOLR_Option_Suggestions::TEMPLATE_SEARCH_ARGS_NAME ), $args );
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 * @throws \Exception
	 */
	function load_template_results_infiniscroll( $args = [] ) {
		return $this->load_template( $this->make_template_params( WPSOLR_Option_Suggestions::TEMPLATE_RESULTS_INFINISCROLL, WPSOLR_Option_Suggestions::TEMPLATE_RESULTS_INFINISCROLL_ARGS_NAME ), $args );
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 * @throws \Exception
	 */
	function load_template_sort( $args = [] ) {
		return $this->load_template( $this->make_template_params( WPSOLR_Option_Suggestions::TEMPLATE_SORT_LIST, WPSOLR_Option_Suggestions::TEMPLATE_SORT_LIST_ARGS_NAME ), $args );
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 * @throws \Exception
	 */
	function load_template_facets( $args = [] ) {
		return $this->load_template( $this->make_template_params( WPSOLR_Option_Suggestions::TEMPLATE_FACETS, WPSOLR_Option_Suggestions::TEMPLATE_FACETS_ARGS_NAME ), $args );
	}


	/**
	 * Make an array from template file and args
	 *
	 * @param string $template_file
	 * @param string $template_args
	 *
	 * @return array
	 */
	function make_template_params( $template_file, $template_args ) {
		return [ 'template_file' => $template_file, 'template_args' => $template_args ];
	}

}