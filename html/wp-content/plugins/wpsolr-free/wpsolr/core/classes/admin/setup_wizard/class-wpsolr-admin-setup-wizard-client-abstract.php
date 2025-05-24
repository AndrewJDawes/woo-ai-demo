<?php

namespace wpsolr\core\classes\admin\setup_wizard;

use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\import_export\WPSOLR_Option_Import_Export;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\utilities\WPSOLR_Option;

abstract class WPSOLR_Admin_Setup_Wizard_Client_Abstract {

	const MODELS_NB_RESULTS = 'models_nb_results';
	const NB_DOCUMENTS = 'nb_documents';
	const NB_CALLS_TO_INDEX = 'nb_calls_to_index';
	const INDEXING_COMPLETE = 'indexing_complete';

	private array $_settings = [];
	private array $_ajax_data = [];
	private array $_cache_content_files = [];
	private array $_placeholders = [];

	protected function get_hosting_index_uuid(): string {
		return sprintf( 'wpsolr_%s_free_index', $this->_get_ajax_hosting() );
	}

	/**
	 * @throws \Exception
	 */
	protected function _create_free_account(): void {
		throw new \Exception( sprintf( 'Not implemented: %s::%s', esc_html( static::class ), esc_html( '_create_free_account' ) ) );
	}

	/**
	 * @throws \Exception
	 */
	protected function _confirm_free_account(): void {
		throw new \Exception( sprintf( 'Not implemented: %s::%s', esc_html( static::class ), esc_html( '_confirm_free_account' ) ) );
	}

	/**
	 * @throws \Exception
	 */
	protected function _save_settings_before(): void {

		$index_name   = sprintf( '%s free index', ucfirst( $this->_get_ajax_hosting() ) );
		$index_label  = sprintf( 'wpsolr_free_index_%s', $this->generate_random_alphanum() );
		$index_secret = sprintf( 's%s', $this->generate_random_alphanum() );;
		$this->_placeholders = $this->_get_placeholders( $index_name, $index_label, $index_secret );

		$this->_cache_content_files = $this->_get_content_from_files(
			$this->_get_before_files_names()
		);

		WPSOLR_Option_Import_Export::import_data( $this->_cache_content_files );
	}

	/**
	 * @throws \Exception
	 */
	protected function _save_settings_after(): void {

		$settings = array_merge_recursive( $this->_cache_content_files, $this->_get_content_from_files( $this->_get_after_files_names() ) );

		WPSOLR_Option_Import_Export::import_data( $settings );
	}

	protected function _get_content_from_files( array $file_names ): array {

		$settings = [];

		foreach (
			$file_names as $file_name
		) {
			$file_content = file_get_contents( DIRNAME( __FILE__ ) . $file_name );

			// Replace placeholders
			foreach ( $this->_placeholders ?? [] as $field_name => $field_value ) {
				$file_content = str_replace( $field_name, $field_value, $file_content );
			}

			$settings = array_merge_recursive( $settings, json_decode( $file_content, true ) );
		}

		return $settings;
	}

	public function get_ajax_data(): array {
		return $this->_ajax_data;
	}

	public function set_ajax_data( array $ajax_data ): void {
		$this->_ajax_data = $ajax_data;
	}

	public function _set_ajax_data_value( string $name, string $value ): void {
		$this->_ajax_data[ $name ] = $value;
	}

	public function _set_ajax_data_api_key( string $api_key ): void {
		$this->_set_ajax_data_value( 'api_key', $api_key );
	}

	/**
	 * @throws \Exception
	 */
	abstract protected function _create_index_config( array $index_data ): array;

	protected function _create_index(): void {

		$index_data = ( new WPSOLR_Option_Indexes() )->get_index( $this->get_hosting_index_uuid() );

		if ( empty( $index_data ) ) {
			throw new \Exception( 'The index definition cannot be found.' );
		}

		$config = $this->_create_index_config( $index_data );

		$client = WPSOLR_AbstractSearchClient::create_from_config( $config );

		// Just trigger an exception if bad ping.
		$output_data = [];
		try {
			$client->admin_ping( $output_data );
		} catch ( \Exception $e ) {
			$this->_manage_index_ping_errors( $e );
		}
	}

	/**
	 * @throws \Exception
	 */
	public function manage_step( array $ajax_data ) {

		$this->set_ajax_data( $ajax_data );

		switch ( $step = $this->_get_ajax_step() ) {
			case WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CREATE:
				$this->_check_email_format( $this->_get_ajax_email() );
				$this->_create_free_account( $ajax_data );
				break;

			case WPSOLR_Admin_Setup_Wizard_Factory::STEP_ACCOUNT_CONFIRM:
				$this->_check_account_confirmation( $ajax_data );
				$this->_confirm_free_account();
				$this->_save_settings_before();
				$this->_create_index();
				$this->_index_data();
				$this->_save_settings_after();
				$this->_add_widgets();
				break;

			default:
				throw new \Exception( sprintf( 'Step is incorrect: %s', esc_html( $step ) ) );
		}
	}

	protected function _index_data(): void {

		$batch_size       = 100;
		$index_post_types = $this->_get_settings_post_types();
		$models           = WPSOLR_Model_Builder::get_model_type_objects( $index_post_types );
		$cron_uuid        = 'wizard_index';

		$index = WPSOLR_IndexSolariumClient::create( $this->get_hosting_index_uuid() );

		$index->delete_documents( $cron_uuid );

		foreach ( $models as $model ) {
			// Indexing model after model is more efficient than all models in parallel

			$post_type                                                  = $model->get_type();
			$models_nb_results[ $post_type ]                            = [];
			$models_nb_results[ $post_type ][ self::NB_DOCUMENTS ]      = 0;
			$models_nb_results[ $post_type ][ self::NB_CALLS_TO_INDEX ] = 0; // measure nb calls to the index

			$is_indexing_complete = false;
			while ( ! $is_indexing_complete ) {

				// Let's index now
				$res_final = $index->index_data( false, $cron_uuid, [ $model ], $batch_size, null, false );

				$is_indexing_complete = $res_final[ self::INDEXING_COMPLETE ];

				// One more call
				$models_nb_results[ $post_type ][ self::NB_CALLS_TO_INDEX ] ++;

				if ( ! empty( $res_final[ self::MODELS_NB_RESULTS ] ) ) {

					$models_nb_results[ $post_type ][ self::NB_DOCUMENTS ] += $res_final[ self::MODELS_NB_RESULTS ][ $post_type ];
				}
			}
		}

	}

	/**
	 * @throws \Exception
	 */
	protected function _check_email_format( string $email ): void {
		if ( ! is_email( $email ) ) {
			throw new \Exception( sprintf( 'The email format is incorrect: %s', esc_html( $email ) ) );
		}
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _check_account_confirmation( array $data ) {
		if ( empty( $this->_get_ajax_account_confirmation() ) ) {
			throw new \Exception( 'Please copy your confirmation code.' );
		}
	}

	protected function _get_ajax_step() {
		return $this->get_ajax_data()['step'] ?? '';
	}

	protected function _get_ajax_email() {
		return $this->get_ajax_data()['email'] ?? '';
	}

	protected function _get_ajax_api_key() {
		return $this->get_ajax_data()['api_key'] ?? '';
	}

	protected function _get_ajax_hosting() {
		return $this->get_ajax_data()['hosting'] ?? '';
	}

	protected function _get_ajax_account_confirmation() {
		return $this->get_ajax_data()['account_confirmation'] ?? '';
	}

	protected function _get_settings_post_types(): array {
		return explode( ',', $this->_cache_content_files[ WPSOLR_Option::OPTION_INDEX ][ WPSOLR_Option::OPTION_INDEX_POST_TYPES ] ?? '' );
	}

	protected function _add_widgets() {
		global $wp_registered_sidebars;
		$active_sidebars_widgets = wp_get_sidebars_widgets();

		// Look if 'Sidebar' exists, and add the widgets if not already

		foreach (
			[
				'wpsolr_widget_sort'            => [ 'text' => 'WPSOLR Sort list', ],
				'wpsolr_widget_facets'          => [ 'text' => 'WPSOLR Facets', ],
				'wpsolr_widget_recommendations' => [
					'text' => 'WPSOLR Recommendations',
					'args' => [ [ 'recommendation_uuid' => $this->_placeholders['WPSOLR_REPLACE_SETTINGS_TEMPLATE_RECOMMENDATION_UUID'], ], ]
				],
			] as $widget_to_add_id => $widget_def
		) {
			$found = false;
			foreach ( $wp_registered_sidebars as $sidebar ) {
				if ( 'Sidebar' === $sidebar['name'] ) {
					if ( ! empty( $active_sidebar_widgets = ( $active_sidebars_widgets[ $sidebar['id'] ?? '' ] ?? [] ) ) ) {
						foreach ( $active_sidebar_widgets as $active_widget ) {
							if ( str_contains( $active_widget, $widget_to_add_id ) ) {
								$found = true;
								break;
							}
						}
					}
					if ( ! $found ) {
						// No widget yet in the sidebar, add it.
						$demo_widget_content[0] = [
							'text' => sprintf( '%s added by the WPSOLR wizard.', $widget_def['text'] ),
						];
						foreach ( $widget_def['args'] ?? [] as $arg ) {
							$demo_widget_content[0][ key( $arg ) ] = $arg[ key( $arg ) ];
						}
						update_option( sprintf( 'widget_%s', $widget_to_add_id ), $demo_widget_content );
						$active_sidebars_widgets[ $sidebar['id'] ][] = sprintf( '%s-0', $widget_to_add_id );
						wp_set_sidebars_widgets( $active_sidebars_widgets );
					}
					break;
				}
			}
		}

	}

	/**
	 * @param \Exception $e
	 *
	 * @throws \Exception
	 */
	abstract protected function _manage_index_ping_errors( \Exception $e ): void;

	/**
	 * @return string
	 * @throws \Random\RandomException
	 */
	protected function generate_random_alphanum(): string {
		return bin2hex( random_bytes( 5 ) );
	}

	protected function _get_before_files_names(): array {
		return [
			'/settings/wpsolr_config_template_before.json',
			sprintf( '/settings/%s/wpsolr_config_template_before.json', $this->_get_ajax_hosting() )
		];
	}

	protected function _get_after_files_names(): array {
		return [ '/settings/wpsolr_config_template_after.json' ];
	}

	/**
	 * @param string $index_label
	 * @param string $index_secret
	 *
	 * @return array
	 */
	protected function _get_placeholders( string $index_name, string $index_label, string $index_secret ): array {
		return [
			'WPSOLR_REPLACE_SETTINGS_INDEX_NAME'                   => $index_name,
			'WPSOLR_REPLACE_SETTINGS_INDEX_LABEL'                  => $index_label,
			'WPSOLR_REPLACE_SETTINGS_TEMPLATE_INDEX_UUID'          => $this->get_hosting_index_uuid(),
			'WPSOLR_REPLACE_SETTINGS_TEMPLATE_RECOMMENDATION_UUID' => WPSOLR_Option_Indexes::generate_uuid(),
			'WPSOLR_REPLACE_SETTINGS_INDEX_API_KEY'                => $this->_get_ajax_api_key(),
			'WPSOLR_REPLACE_SETTINGS_INDEX_EMAIL'                  => $this->_get_ajax_email(),
			'WPSOLR_REPLACE_SETTINGS_INDEX_SECRET'                 => $index_secret,
		];
	}

}
