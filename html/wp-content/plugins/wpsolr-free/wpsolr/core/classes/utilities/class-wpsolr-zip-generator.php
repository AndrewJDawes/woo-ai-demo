<?php

namespace wpsolr\core\classes\utilities;

if ( ! class_exists( 'WPSOLR_Zip_Generator' ) ) {

	/**
	 * Download a folder zipped utilities
	 *
	 * Based on https://github.com/bradvin/wp-zip-generator
	 */
	class WPSOLR_Zip_Generator {

		// Query string to tell it is a download
		const WPSOLR_DOWNLOAD_EXAMPLE_QUERY_STRING = 'wpsolr-download-example';


		/**
		 * Examples in plugin directory /wpsolr-examples/
		 */
		const EXAMPLE_ID_SUGGESTION_CUSTOM_TEMPLATE = 'example_id_suggestion_custom_template';

		/**
		 * Map donwloads to folders
		 */
		const WPSOLR_DOWNLOAD_EXAMPLES = [ self::EXAMPLE_ID_SUGGESTION_CUSTOM_TEMPLATE => 'wpsolr-example-suggestions-custom-templates-twentysixteen-child' ];

		var $options = array();
		var $slug = '';


		static public function get_download_link( $id_example, $text = 'Download' ) {

			return sprintf( '<a href="%s">%s</a>', WPSOLR_Escape::esc_url( add_query_arg( self::WPSOLR_DOWNLOAD_EXAMPLE_QUERY_STRING, $id_example ) ), WPSOLR_Escape::esc_html( $text ) );

		}

		function init( $args ) {

			$defaults = [
				'name'                 => '',
				'source_directory'     => WPSOLR_PLUGIN_DIR . sprintf( '/wpsolr-examples/%s/', $args['name'] ),
				'process_extensions'   => [ 'php', 'css', 'js', 'txt', 'md' ],
				'zip_root_directory'   => '',
				'zip_temp_directory'   => plugin_dir_path( __FILE__ ),
				'download_filename'    => '',
				'exclude_directories'  => [ '.git', '.svn', '.', '..' ],
				'exclude_files'        => [ '.git', '.svn', '.DS_Store', '.gitignore', '.', '..' ],
				'filename_filter'      => null,
				'file_contents_filter' => null,
				'post_process_action'  => null,
				'variables'            => []
			];

			$this->options = wp_parse_args( $args, $defaults );

			//check required args
			if ( empty( $this->options['name'] ) ) {
				throw new \Exception( "Zip_Generator class requires a name in order to function!" );
			}

			$this->slug = sanitize_title_with_dashes( $this->options['name'] );

			$this->options['download_filename'] = empty( $this->options['download_filename'] ) ? "{$this->slug}.zip" : $this->options['download_filename'];

			$this->options['zip_temp_filename'] = trailingslashit( $this->options['zip_temp_directory'] ) . sprintf( '%s-%s.zip', $this->slug, md5( print_r( $this->options['variables'], true ) ) );

			if ( ! empty( $this->options['filename_filter'] ) ) {
				add_filter( 'zip_generator_process_filename-' . $this->slug, $this->options['filename_filter'], 10, 2 );
			}

			if ( ! empty( $this->options['file_contents_filter'] ) ) {
				add_filter( 'zip_generator_process_file_contents-' . $this->slug, $this->options['file_contents_filter'], 10, 2 );
			}

			if ( ! empty( $this->options['post_process_action'] ) ) {
				add_action( 'zip_generator_post_process-' . $this->slug, $this->options['post_process_action'], 10, 2 );
			}

			$this->generate();
			$this->send_download_headers();

			// Prevent downloading again and again
			remove_query_arg( self::WPSOLR_DOWNLOAD_EXAMPLE_QUERY_STRING );

		}


		function __construct( $args = null ) {

			if ( isset( $_GET[ self::WPSOLR_DOWNLOAD_EXAMPLE_QUERY_STRING ] ) && ! empty( $_GET[ self::WPSOLR_DOWNLOAD_EXAMPLE_QUERY_STRING ] ) ) {
				$this->init( [ 'name' => self::WPSOLR_DOWNLOAD_EXAMPLES[ WPSOLR_Sanitize::sanitize_text_field( $_GET, [ self::WPSOLR_DOWNLOAD_EXAMPLE_QUERY_STRING ] ) ] ] );
			}

		}

		/**
		 * Creates the new zip file based on the source_directory
		 */
		function generate() {
			$zip = new \ZipArchive;

			$res = $zip->open( $this->options['zip_temp_filename'], \ZipArchive::CREATE && \ZipArchive::OVERWRITE );

			$iterator = new \RecursiveDirectoryIterator( $this->options['source_directory'] );
			foreach ( new \RecursiveIteratorIterator( $iterator ) as $filename ) {

				if ( in_array( basename( $filename ), $this->options['exclude_files'] ) ) {
					continue;
				}

				foreach ( $this->options['exclude_directories'] as $directory ) {
					if ( strstr( $filename, "/{$directory}/" ) ) {
						continue 2;
					}
				} // continue the parent foreach loop

				$zip_filename = str_replace( trailingslashit( $this->options['source_directory'] ), '', basename( $filename ) );

				$zip_filename = apply_filters( 'zip_generator_process_filename-' . $this->slug, $zip_filename );

				$contents = $this->process_file_contents( file_get_contents( $filename ), basename( $filename ) );

				$zip->addFromString( trailingslashit( $this->options['zip_root_directory'] ) . $zip_filename, $contents );
			}

			do_action( 'zip_generator_post_process-' . $this->slug, $zip, $this->options );

			$zip->close();
		}

		/**
		 * Process the contents of an individual file
		 *
		 * @param $contents
		 * @param $filename
		 *
		 * @return string
		 */
		function process_file_contents( $contents, $filename ) {
			// Replace only files are care about
			$valid_extensions_regex = implode( '|', $this->options['process_extensions'] );
			if ( ! preg_match( "/\.({$valid_extensions_regex})$/", $filename ) ) {
				return $contents;
			}

			foreach ( $this->options['variables'] as $key => $value ) {
				$contents = preg_replace( '/(' . $key . ')/', $value, $contents );
			}

			$contents = apply_filters( 'zip_generator_process_file_contents-' . $this->slug, $contents, $filename );

			return $contents;
		}

		/**
		 * Send the download headers to the browser
		 *
		 * @param bool $delete
		 */
		function send_download_headers( $delete = true ) {

			header( 'Content-type: application/zip' );
			header( sprintf( 'Content-Disposition: attachment; filename="%s"', $this->options['download_filename'] ) );

			// Read and output the file contents
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			WPSOLR_Escape::echo_escaped( $wp_filesystem->get_contents( $this->options['zip_temp_filename'] ) );

			if ( $delete ) {
				wp_delete_file( $this->options['zip_temp_filename'] );
			}
		}

	}

}
