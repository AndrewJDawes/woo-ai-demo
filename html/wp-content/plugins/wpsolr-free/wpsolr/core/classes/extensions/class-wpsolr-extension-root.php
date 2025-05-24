<?php

namespace wpsolr\core\classes\extensions;

use wpsolr\core\classes\extensions\import_export\WPSOLR_Option_Import_Export;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;
use wpsolr\core\classes\extensions\premium\WPSOLR_Option_Premium;
use wpsolr\core\classes\extensions\recommendations\WPSOLR_Option_Recommendations;
use wpsolr\core\classes\extensions\suggestions\WPSOLR_Option_Suggestions;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\yith_woocommerce_ajax_search_free\WPSOLR_Plugin_YITH_WooCommerce_Ajax_Search_Free;
use wpsolr\core\classes\models\WPSOLR_Model_Builder;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\services\WPSOLR_Service_Container_Factory;
use wpsolr\core\classes\ui\layout\checkboxes\WPSOLR_UI_Layout_Check_Box;
use wpsolr\core\classes\ui\layout\select\WPSOLR_UI_Layout_Select;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_Extension_Root {
	use WPSOLR_Service_Container_Factory;

	static $wpsolr_extensions;

	/**
	 * @var string[]
	 */
	protected $views_uuids = [];

	/*
    * Private constants
    */
	const _CONFIG_EXTENSION_DIRECTORY = 'config_extension_directory';
	const _CONFIG_EXTENSION_CLASS_NAME = 'config_extension_class_name';
	const _CONFIG_PLUGIN_CLASS_NAME = 'config_plugin_class_name';
	const _CONFIG_PLUGIN_FUNCTION_NAME = 'config_plugin_function_name';
	const _CONFIG_PLUGIN_CONSTANT_NAME = 'config_plugin_constant_name';
	const _CONFIG_PLUGIN_IS_AUTO_ACTIVE = 'config_plugin_is_auto_active';
	const _CONFIG_EXTENSION_FILE_PATH = 'config_extension_file_path';
	const _CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH = 'config_extension_admin_options_file_path';
	const _CONFIG_OPTIONS = 'config_extension_options';
	const _CONFIG_OPTIONS_DATA = 'data';
	const _CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME = 'is_active_field';
	const _CONFIG_IS_PRO = 'is_pro';

	const _SOLR_OR_OPERATOR = ' OR ';
	const _SOLR_AND_OPERATOR = ' AND ';

	const _METHOD_CUSTOM_QUERY = 'set_custom_query';

	const _FIELD_POST_TYPES = 'post_types';

	/*
	 * Public constants
	 */
	// Option: Import / Export
	const OPTION_IMPORT_EXPORT = 'wpsolr_import_export';

	// Option: localization
	const OPTION_INDEXES = 'Indexes';

	// Option: localization
	const OPTION_LOCALIZATION = 'Localization';

	// Extension: Gotosolr hosting
	const OPTION_MANAGED_SOLR_SERVERS = 'Managed Solr Servers';

	// Option: licenses
	const OPTION_LICENSES = 'Licenses';

	// Extension Premium
	const EXTENSION_PREMIUM = 'wpsolr_premium';

	// Extension: YITH WooCommerce Ajax Search (free)
	const EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE = 'EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE';

	// Option: suggestions
	const OPTION_SUGGESTIONS = 'suggestions';

	// Option: views
	const OPTION_VIEWS = 'views';

	// Option: recommendations
	public const OPTION_RECOMMENDATIONS = 'recommendations';

	/**
	 * Mapping of layout id to layout class
	 * @var array $layout_classes
	 */
	static protected $layout_classes = [
		WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID => WPSOLR_UI_Layout_Check_Box::class,
		WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID    => WPSOLR_UI_Layout_Select::class,
	];

	/**
	 * Get feature layouts
	 * @return array[]
	 */
	public static function get_feature_layouts_ids(): array {
		return [
			WPSOLR_UI_Layout_Abstract::FEATURE_GRID                      => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_EXCLUSION                 => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_HIERARCHY                 => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_OR                        => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_SORT_ALPHABETICALLY       => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_LOCALIZATION              => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_LOCALIZATION_FIELD        => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_SEO_TEMPLATE              => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_SEO_TEMPLATE_LOCALIZATION => [
				WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_MULTIPLE                  => [
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_PLACEHOLDER               => [
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
			WPSOLR_UI_Layout_Abstract::FEATURE_SIZE                      => [
				WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
			],
		];
	}

	/*
	 * Extensions configuration
	 * @return array[]
	 */
	public static function get_extensions_def(): array {
		/**
		 * Views must be loaded first, else during activation the list of views is not filled
		 */
		return [
			static::OPTION_VIEWS                                =>
				[
					static::_CONFIG_IS_PRO                            => true,
					static::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_View::class,
					static::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
					static::_CONFIG_EXTENSION_DIRECTORY               => 'view/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'view/class-wpsolr-option-views.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'view/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_VIEW,
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			static::OPTION_INDEXES                              =>
				[
					static::_CONFIG_IS_PRO                            => false,
					static::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Indexes::class,
					static::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => false,
					static::_CONFIG_PLUGIN_CLASS_NAME                 => WPSOLR_Option_Indexes::class,
					static::_CONFIG_EXTENSION_DIRECTORY               => 'indexes/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'indexes/class-wpsolr-option-indexes.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'indexes/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_INDEXES,
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			self::OPTION_IMPORT_EXPORT                          =>
				[
					self::_CONFIG_IS_PRO                            => true,
					self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Import_Export::class,
					self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
					self::_CONFIG_EXTENSION_DIRECTORY               => 'import_export/',
					self::_CONFIG_EXTENSION_FILE_PATH               => 'import_export/class-option-import-export.php',
					self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'import_export/admin_options.inc.php',
					self::_CONFIG_OPTIONS                           => [
						self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_IMPORT_EXPORT,
						self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			static::OPTION_LOCALIZATION                         =>
				[
					static::_CONFIG_IS_PRO                            => false,
					static::_CONFIG_EXTENSION_CLASS_NAME              => OptionLocalization::class,
					static::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => false,
					static::_CONFIG_PLUGIN_CLASS_NAME                 => OptionLocalization::class,
					static::_CONFIG_EXTENSION_DIRECTORY               => 'localization/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'localization/class-optionlocalization.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'localization/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_LOCALIZATION,
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			static::OPTION_MANAGED_SOLR_SERVERS                 =>
				[
					static::_CONFIG_IS_PRO                            => false,
					static::_CONFIG_EXTENSION_CLASS_NAME              => OptionManagedSolrServer::class,
					static::_CONFIG_PLUGIN_FUNCTION_NAME              => 'OptionManagedSolrServers',
					static::_CONFIG_EXTENSION_DIRECTORY               => 'managed_solr_servers/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'managed_solr_servers/class-optionmanagedsolrserver.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'managed_solr_servers/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_managed_solr_servers_data',
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			static::OPTION_LICENSES                             =>
				[
					static::_CONFIG_IS_PRO                            => false,
					static::_CONFIG_EXTENSION_CLASS_NAME              => OptionLicenses::class,
					static::_CONFIG_PLUGIN_CLASS_NAME                 => 'OptionLicenses',
					static::_CONFIG_EXTENSION_DIRECTORY               => 'licenses/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'licenses/class-optionlicenses.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'licenses/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_LICENSES,
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			static::EXTENSION_PREMIUM                           =>
				[
					static::_CONFIG_IS_PRO                            => false,
					static::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Premium::class,
					static::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
					static::_CONFIG_EXTENSION_DIRECTORY               => 'premium/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'premium/option-premium.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'premium/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_PREMIUM,
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			static::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE =>
				[
					static::_CONFIG_IS_PRO                            => false,
					static::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_YITH_WooCommerce_Ajax_Search_Free::class,
					static::_CONFIG_PLUGIN_CONSTANT_NAME              => 'YITH_WCAS',
					static::_CONFIG_EXTENSION_DIRECTORY               => 'yith_woocommerce_ajax_search_free/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'yith_woocommerce_ajax_search_free/class-wpsolr-plugin-yith-woocommerce-ajax-search-free.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'yith_woocommerce_ajax_search_free/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE,
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			static::OPTION_SUGGESTIONS                          =>
				[
					static::_CONFIG_IS_PRO                            => false,
					static::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Suggestions::class,
					static::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
					static::_CONFIG_PLUGIN_CLASS_NAME                 => WPSOLR_Option_Suggestions::class,
					static::_CONFIG_EXTENSION_DIRECTORY               => 'suggestions/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'suggestions/class-wpsolr-option-suggestions.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'suggestions/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_SUGGESTIONS,
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
			static::OPTION_RECOMMENDATIONS                      =>
				[
					static::_CONFIG_IS_PRO                            => false,
					static::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Recommendations::class,
					static::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
					static::_CONFIG_EXTENSION_DIRECTORY               => 'recommendations/',
					static::_CONFIG_EXTENSION_FILE_PATH               => 'recommendations/class-wpsolr-option-recommendations.php',
					static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'recommendations/admin_options.inc.php',
					static::_CONFIG_OPTIONS                           => [
						static::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_RECOMMENDATIONS,
						static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
					],
				],
		];
	}

	/*
	 * Array of active extension objects
	 */
	private $extension_objects = [];

	/**
	 * Factory to load extensions
	 * @return WPSOLR_Extension
	 */
	static function load() {

		if ( ! isset( static::$wpsolr_extensions ) ) {

			static::$wpsolr_extensions = new static();
		}
	}

	/**
	 * Constructor.
	 */
	function __construct() {

		// Instantiate active extensions.
		$this->extension_objects = $this->instantiate_active_extension_objects();

	}

	/**
	 * @return string[]
	 */
	public function get_views_uuids(): array {
		return $this->views_uuids ?? [];
	}

	/**
	 * @param string[] $views_uuids
	 */
	public function set_views_uuids( array $views_uuids ): void {
		$this->views_uuids = $views_uuids;
	}

	protected function init_default_events() {

		if ( is_admin() ) {

			if ( ! empty( $this->get_default_custom_fields() ) ) {

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS, [
					$this,
					'wpsolr_filter_index_custom_fields',
				], 10, 2 );

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS_PROPERTIES_SELECTED, [
					$this,
					'wpsolr_filter_index_custom_fields_properties_selected',
				], 10, 1 );

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS_SELECTED, [
					$this,
					'wpsolr_filter_index_custom_fields_selected',
				], 10, 1 );
			}

			if ( ! empty( $this->get_default_post_types() ) ) {

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_POST_TYPES_SELECTED, [
					$this,
					'wpsolr_filter_index_post_types_selected',
				], 10, 1 );
			}

			if ( ! empty( $this->get_default_taxonomies() ) ) {

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_TAXONOMIES_SELECTED, [
					$this,
					'wpsolr_filter_index_taxonomies_selected',
				], 10, 1 );
			}

			if ( ! empty( $this->get_default_sorts() ) ) {

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_SORTS_SELECTED, [
					$this,
					'wpsolr_filter_index_sorts_selected',
				], 10, 1 );
			}

		}

	}

	/**
	 * Include a file with a set of parameters.
	 * All other parameters are not passed, because they are out of the function scope.
	 *
	 * @param string $pg File to include
	 * @param mixed $vars Parameters to pass to the file
	 */
	public static function require_with( $pg, $vars = null ) {

		if ( isset( $vars ) ) {
			extract( $vars );
		}

		require $pg;
	}

	/**
	 * Instantiate all active extension classes
	 *
	 * @return array extension objects instantiated
	 */
	private function instantiate_active_extension_objects() {

		$extension_objects = [];

		foreach ( static::get_extensions_def() as $key => $class ) {
			$views_uuids = [];
			if ( $this->require_once_wpsolr_extension( $key, false, $views_uuids ) ) {

				/** @var WPSOLR_Extension $extension */
				$extension = new $class[ static::_CONFIG_EXTENSION_CLASS_NAME ]();
				$extension->set_views_uuids( $views_uuids );
				$extension_objects[] = $extension;
			}
		}

		return $extension_objects;
	}

	/**
	 * Include the admin options extension file.
	 *
	 * @param string $extension
	 *
	 * @return bool
	 */
	public static function require_once_wpsolr_extension_admin_options( $extension ) {

		// Called from admin: we active the extension, whatever.
		return static::load_file( static::get_extensions_def()[ $extension ][ static::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH ], true );
	}

	/**
	 * Is the extension's plugin active ?
	 *
	 * @param $extension
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $extension ) {

		// Configuration array of $extension
		$extension_config_array = static::get_extensions_def()[ $extension ];

		// Is extension's plugin installed and activated ?
		if ( isset( $extension_config_array[ static::_CONFIG_PLUGIN_IS_AUTO_ACTIVE ] ) ) {

			return $extension_config_array[ static::_CONFIG_PLUGIN_IS_AUTO_ACTIVE ];

		} elseif ( isset( $extension_config_array[ static::_CONFIG_PLUGIN_CLASS_NAME ] ) ) {

			return class_exists( $extension_config_array[ static::_CONFIG_PLUGIN_CLASS_NAME ] );

		} elseif ( isset( $extension_config_array[ static::_CONFIG_PLUGIN_FUNCTION_NAME ] ) ) {

			return function_exists( $extension_config_array[ static::_CONFIG_PLUGIN_FUNCTION_NAME ] );

		} elseif ( isset( $extension_config_array[ static::_CONFIG_PLUGIN_CONSTANT_NAME ] ) ) {

			return defined( $extension_config_array[ static::_CONFIG_PLUGIN_CONSTANT_NAME ] );

		}

		return false;
	}

	/**
	 * @param string $extension
	 * @param string $custom_field_name
	 */
	public static function update_custom_field_capabilities(
		$extension,
		$custom_field_name
	) {

		// Get options contening custom fields
		$option_indexing      = WPSOLR_Service_Container::getOption()->get_option_index();
		$option_custom_fields = WPSOLR_Service_Container::getOption()->get_option_index_custom_fields();

		// is extension active checked in options ?
		$extension_is_active = static::is_extension_option_activate( $extension );


		if ( $extension_is_active
		     && ! static::get_custom_field_capabilities( $custom_field_name )
		     && isset( $option_indexing )
		     && isset( $option_custom_fields )
		) {

			foreach ( $option_custom_fields as $model_type => $custom_fields ) {

				if ( empty( $option_custom_fields[ $model_type ] ) ) {
					// Initialize custom fields
					$option_custom_fields[ $model_type ] = [];
				}

				if ( ! isset( $option_custom_fields[ $model_type ] [ $custom_field_name ] ) ) {

					$option_custom_fields[ $model_type ] [ $custom_field_name ] = $custom_field_name;
				}
			}

			// Update the option now
			/*
			 * Not necessary apprently, and does not work with a custom index settings becase already saved on the default index on page load
			$option_indexing[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELDS ] = $option_custom_fields;
			update_option( WPSOLR_Option_View::get_current_index_options_name( WPSOLR_Option::OPTION_INDEX ), $option_indexing );
			*/

		}
	}

	/**
	 * Is the extension activated ?
	 *
	 * @param string $extension
	 *
	 * @return bool
	 */
	public
	static function is_extension_option_activate(
		$extension
	) {


		// Configuration array of $extension
		$extension_config_array = static::get_extensions_def()[ $extension ];

		// Configuration not set, return
		if ( ! is_array( $extension_config_array ) ) {
			return false;
		}

		// Configuration options array: setup in extension options tab admin
		$extension_options_array = WPSOLR_Service_Container::getOption()->get_option( true, $extension_config_array[ static::_CONFIG_OPTIONS ][ static::_CONFIG_OPTIONS_DATA ] );

		// Configuration option says that user did not choose to active this extension: return
		if ( isset( $extension_options_array ) && isset( $extension_options_array[ $extension_config_array[ static::_CONFIG_OPTIONS ][ static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME ] ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $custom_field_name
	 *
	 * @return bool
	 */
	public
	static function get_custom_field_capabilities(
		$custom_field_name
	) {

		// Get custom fields selected for indexing
		$array_cust_fields = WPSOLR_Service_Container::getOption()->get_option_index_custom_fields();

		if ( ! is_array( $array_cust_fields ) ) {
			return false;
		}

		return false !== array_search( $custom_field_name, $array_cust_fields, true );
	}


	/*
	 * If extension is active, check its custom field in indexing options
	 */

	/**
	 * Include the extension file.
	 * If called from admin, always do.
	 * Else, do it if the extension options say so, and the extension's plugin is activated.
	 *
	 * @param string $extension
	 * @param bool $is_admin
	 *
	 * @return bool
	 */
	public
	static function require_once_wpsolr_extension(
		$extension, $is_admin = false, &$views_uuids = []
	) {

		// Configuration array of $extension
		$extension_config_array = static::get_extensions_def()[ $extension ];

		if ( ! defined( 'WPSOLR_PLUGIN_DIR' ) && $extension_config_array[ static::_CONFIG_IS_PRO ] ) {
			// Pro extension called in free plugin.
			return false;
		}

		if ( $is_admin ) {
			// Called from admin: we active the extension, whatever.
			return true; //static::load_file( $extension_config_array[ static::_CONFIG_EXTENSION_FILE_PATH ] );
		}

		// Configuration not set, return
		if ( ! is_array( $extension_config_array ) ) {
			return false;
		}

		// Exception to some extensions, always loaded because present in all packs.
		if ( in_array( $extension, [
			static::EXTENSION_PREMIUM,
			static::OPTION_SUGGESTIONS,
			static::OPTION_RECOMMENDATIONS,
		] ) ) {
			return true;
		}

		// Is extension's plugin installed and activated ? Tested before options, before it discards unused plugins with very small load.
		$result = static::is_plugin_active( $extension );
		if ( ! $result ) {
			return false;
		}

		$result            = false;
		$current_view_uuid = WPSOLR_Option_View::get_current_view_uuid();
		WPSOLR_Option_View::backup_current_view_uuid();
		$views = apply_filters( WPSOLR_Events::WPSOLR_FILTER_VIEWS, WPSOLR_Option_View::get_list_default_view(), false, 10, 2 );
		foreach ( $views as $view_uuid => $view ) {
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

			// Configuration options array: setup in extension options tab admin
			$extension_options_array = WPSOLR_Service_Container::getOption()->get_option( true, $extension_config_array[ static::_CONFIG_OPTIONS ][ static::_CONFIG_OPTIONS_DATA ] );

			// Configuration option says that user did not choose to active this extension: return
			if ( ( isset( $extension_options_array ) && isset( $extension_options_array[ $extension_config_array[ static::_CONFIG_OPTIONS ][ static::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME ] ] ) ) ) {
				// Load extension's plugin
				$result        = true;
				$views_uuids[] = $view_uuid;
			}

		}
		WPSOLR_Option_View::restore_current_view_uuid();

		return $result;
	}

	/**
	 * Load an extension file
	 *
	 * @param $file
	 * @param bool $is_admin_option
	 * @param array $vars
	 *
	 * @return bool
	 */
	static public function load_file( $file, $is_admin_option = false, $vars = null ) {

		if ( isset( $vars ) ) {
			extract( $vars );
		}

		if ( defined( 'WPSOLR_PLUGIN_DIR' ) && file_exists( WPSOLR_PLUGIN_DIR . '/wpsolr/pro/extensions/' . $file ) ) {
			require_once( WPSOLR_PLUGIN_DIR . '/wpsolr/pro/extensions/' . $file );

			return true;

		} elseif ( defined( 'WPSOLR_PLUGIN_DIR' ) && file_exists( WPSOLR_PLUGIN_DIR . '/wpsolr/enterprise/extensions/' . $file ) ) {
			require_once( WPSOLR_PLUGIN_DIR . '/wpsolr/enterprise/extensions/' . $file );

			return true;

		} elseif ( file_exists( plugin_dir_path( __FILE__ ) . $file ) ) {
			require_once( plugin_dir_path( __FILE__ ) . $file );

			return true;
		}

		if ( $is_admin_option ) {
			// Show a message when no extension is found.
			require_once( plugin_dir_path( __FILE__ ) . 'wpsolr-no-extension.inc.php' );
		}

		return false;
	}

	/**
	 * Get the option data of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	public
	static function get_option_data(
		$extension, $default = false
	) {

		return WPSOLR_Service_Container::getOption()->get_option( true, static::get_option_name( $extension ), $default );
	}


	/**
	 * Get the option name of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	public
	static function get_option_name(
		$extension
	) {

		return static::get_extensions_def()[ $extension ][ static::_CONFIG_OPTIONS ][ static::_CONFIG_OPTIONS_DATA ];
	}

	/**
	 * Get the option PRO/NOT PRO of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	public
	static function get_option_is_pro(
		$extension
	) {

		return static::get_extensions_def()[ $extension ][ static::_CONFIG_IS_PRO ];
	}

	/**
	 * Set the option value of an extension
	 *
	 * @param $extension
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public
	static function set_option_data(
		$extension, $option_value
	) {

		return update_option( static::get_extensions_def()[ $extension ][ static::_CONFIG_OPTIONS ][ static::_CONFIG_OPTIONS_DATA ], $option_value );
	}

	/**
	 * Get the extension template path
	 *
	 * @param $extension
	 *
	 * @param $template_file_name
	 *
	 * @return string Template file path
	 *
	 */
	public
	static function get_option_template_file(
		$extension, $template_file_name
	) {

		return plugin_dir_path( __FILE__ ) . static::get_extensions_def()[ $extension ][ static::_CONFIG_EXTENSION_DIRECTORY ] . 'templates/' . $template_file_name;
	}

	/**
	 * Get the extension file
	 *
	 * @param $extension
	 *
	 * @param $file_name
	 *
	 * @return string File path
	 *
	 */
	public
	static function get_option_file(
		$extension, $file_name
	) {

		$file = static::get_extensions_def()[ $extension ][ static::_CONFIG_EXTENSION_DIRECTORY ] . $file_name;

		if ( defined( 'WPSOLR_PLUGIN_DIR' ) && file_exists( WPSOLR_PLUGIN_DIR . '/wpsolr/pro/extensions/' . $file ) ) {
			$file = WPSOLR_PLUGIN_DIR . '/wpsolr/pro/extensions/' . $file;
		} else {
			$file = plugin_dir_path( __FILE__ ) . $file;
		}

		return $file;
	}

	/*
	 * Templates methods
	 */

	/**
	 * @param bool $is_submit
	 * @param array $fields
	 *
	 * @return array
	 */
	public
	static function extract_form_data(
		$is_submit, $fields
	) {

		$form_data = [];

		$is_error = false;

		foreach ( $fields as $key => $field ) {

			$value = ( isset( $_POST[ $key ] ) && check_ajax_referer( 'security', 'security' ) ) ?
				WPSOLR_Sanitize::sanitize_text_field( $_POST, [ $key ] ) : $field['default_value'];
			$error = '';

			// Check format errors id it is a form post (submit)
			if ( $is_submit ) {

				$error = '';

				if ( isset( $field['can_be_empty'] ) && ! $field['can_be_empty'] ) {
					$error = empty( $value ) ? 'This field cannot be empty.' : '';
				}

				if ( isset( $field['is_email'] ) ) {
					$error = is_email( $value ) ? '' : 'This does not look like an email address.';
				}
			}

			$is_error = $is_error || ( '' !== $error );

			$form_data[ $key ] = array( 'value' => $value, 'error' => $error );
		}

		// Is there an error in any field ?
		$form_data['is_error'] = $is_error;

		return $form_data;
	}

	/**
	 * Get the dynamic strings to translate among the group data of all extensions translatable.
	 *
	 * @param array $translations
	 *
	 * @return array Translations
	 */
	protected function extract_strings_to_translate_for_all_extensions( &$translations = [] ) {

		// Translate localization static texts for WPML and Polylang
		$localizations = OptionLocalization::get_default_options();
		if ( is_array( $localizations ) && ! empty( $localizations ) && isset( $localizations['terms'] ) ) {
			foreach ( $localizations['terms'] as $name => $label ) {
				if ( ! empty( $label ) ) {
					$translation           = [];
					$translation['domain'] = 'wpsolr';
					$translation['name']   = $name;
					$translation['text']   = $label;

					array_push( $translations, $translation );
				}
			}
		}


		// Translate SEO facet template(s)
		$labels = WPSOLR_Service_Container::getOption()->get_facets_seo_permalink_templates();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_SEO_TEMPLATE;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate SEO facet item templates
		$facet_item_seo_templates = WPSOLR_Service_Container::getOption()->get_facets_seo_permalink_items_templates();
		if ( is_array( $facet_item_seo_templates ) && ! empty( $facet_item_seo_templates ) ) {
			foreach ( $facet_item_seo_templates as $facet_name => $facet_items_seo_templates ) {
				foreach ( $facet_items_seo_templates as $facet_item_name => $facet_item_seo_template ) {
					if ( ! empty( $facet_item_seo_template ) ) {
						$translation           = [];
						$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_ITEM_SEO_TEMPLATE;
						$translation['name']   = $facet_item_name;
						$translation['text']   = $facet_item_seo_template;

						array_push( $translations, $translation );
					}
				}
			}
		}

		// Translate facet labels
		$labels = WPSOLR_Service_Container::getOption()->get_facets_labels();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate facet placeholders
		$labels = WPSOLR_Service_Container::getOption()->get_facets_placeholder();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_PLACEHOLDER;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate facet js
		$labels = WPSOLR_Service_Container::getOption()->get_facets_js();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_JS;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate facet items labels
		$labels = WPSOLR_Service_Container::getOption()->get_facets_items_labels();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_items_labels ) {
				foreach ( $facet_items_labels as $facet_item_name => $facet_item_label ) {
					if ( ! empty( $facet_item_label ) ) {
						$translation           = [];
						$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL;
						$translation['name']   = $facet_item_name;
						$translation['text']   = $facet_item_label;

						array_push( $translations, $translation );
					}
				}
			}
		}

		// Translate sort labels
		$labels = WPSOLR_Service_Container::getOption()->get_sortby_items_labels();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_SORT_LABEL;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate geolocation labels
		$label = WPSOLR_Service_Container::getOption()->get_option_geolocation_user_aggreement_label();
		if ( ! empty( $label ) ) {
			$translation           = [];
			$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_GEOLOCATION_LABEL;
			$translation['name']   = WPSOLR_Option::OPTION_GEOLOCATION_USER_AGREEMENT_LABEL;
			$translation['text']   = $label;

			array_push( $translations, $translation );
		}

		// Translate suggestions groups labels
		$suggestions = WPSOLR_Service_Container::getOption()->get_option_suggestions_suggestions();
		foreach ( $suggestions as $suggestion_uuid => $suggestion ) {
			if ( ! empty( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] ) ) {
				foreach ( $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_MODELS ] as $post_type => $model ) {
					if ( ! empty( $model[ WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL ] ) ) {
						$translation           = [];
						$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_SUGGESTION_LABEL;
						$translation['name']   = sprintf( '%s: %s', $suggestion[ WPSOLR_Option::OPTION_SUGGESTION_LABEL ], $post_type ); // each suggestion_uuid have its own translation
						$translation['text']   = $model[ WPSOLR_Option::OPTION_SUGGESTION_MODEL_LABEL ];

						array_push( $translations, $translation );
					}
				}
			}
		}

		if ( count( $translations ) > 0 ) {

			// Translate
			do_action( WPSOLR_Events::ACTION_TRANSLATION_REGISTER_STRINGS,
				[
					'translations' => $translations,
				]
			);
		}

	}

	/**
	 * Default custom fields to add to the indexed custom fields
	 * @return array
	 */
	protected function get_default_custom_fields() {
		// Override in child
		return [];
	}

	/**
	 * Force custom fields selection if the custom fields are empty.
	 *
	 * @param array $custom_fields_properties
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_custom_fields_properties_selected( $custom_fields_properties ) {

		$was_empty = empty( $custom_fields_properties );
		if ( $was_empty ) {
			$has_been_modified = false;
			foreach ( $this->get_default_custom_fields() as $custom_field_name => $custom_field_property ) {

				$custom_field_name = $custom_field_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;

				if ( ! isset( $custom_fields_properties[ $custom_field_name ] ) ) {
					$custom_fields_properties[ $custom_field_name ] = [
						WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE               => $custom_field_property[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ],
						WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION => $custom_field_property[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ],
					];
					$has_been_modified                              = true;
				}
			}

			/*
			 * Not necessary apprently, and does not work with a custom index settings becase already saved on the default index on page load
			if ( $was_empty && $has_been_modified ) {
				$option                                                        = $this->get_container()->get_service_option()->get_option_index();
				$option[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES ] = $custom_fields_properties;
				update_option( WPSOLR_Option_View::get_current_index_options_name( WPSOLR_Option::OPTION_INDEX ), $option, true );
			}
			*/
		}

		return $custom_fields_properties;
	}

	/**
	 * Default sorts to add to the sorts
	 * @return array
	 */
	protected function get_default_sorts() {
		// Override in child
		return [];
	}

	/**
	 * Force sort properties selection if the sorts are empty.
	 *
	 * @param array $sorts_selected
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_sorts_selected( $sorts_selected ) {

		$was_empty         = empty( $sorts_selected );
		$has_been_modified = false;
		if ( $was_empty ) {
			foreach ( $this->get_default_sorts() as $sort_field_name ) {

				if ( ! in_array( $sort_field_name, $sorts_selected, true ) ) {
					$sorts_selected[]  = $sort_field_name;
					$has_been_modified = true;
				}
			}

			if ( $was_empty && $has_been_modified ) {
				$option                                            = $this->get_container()->get_service_option()->get_option_sortby();
				$option[ WPSOLR_Option::OPTION_SORTBY_ITEM_ITEMS ] = implode( ',', $sorts_selected );
				update_option( WPSOLR_Option_View::get_current_view_options_name( WPSOLR_Option::OPTION_SORTBY ), $option, true );
			}
		}

		return $sorts_selected;
	}

	/**
	 * Force listify custom fields properties selection if the custom fields are empty.
	 *
	 * @param array $custom_fields_properties
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_custom_fields_selected( $custom_fields_selected ) {

		//return $custom_fields_selected;

		$was_empty         = empty( $custom_fields_selected );
		$has_been_modified = false;
		if ( $was_empty ) {
			foreach ( $this->get_default_custom_fields() as $custom_field_name => $custom_field_property ) {
				// Add the 2 geolocation custom fields automatically if they are missing.

				$custom_field_name = $custom_field_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;

				$model_type_objects = [];
				if ( isset( $custom_field_property[ static::_FIELD_POST_TYPES ] ) ) {
					$model_type_objects = WPSOLR_Model_Builder::get_model_type_objects( $custom_field_property[ static::_FIELD_POST_TYPES ] );
				}

				foreach ( $model_type_objects as $model_type_object ) {

					$model_type = $model_type_object->get_type();
					if ( ! isset( $custom_fields_selected[ $model_type ] ) ) {
						$custom_fields_selected[ $model_type ] = [];
					}

					if ( ! in_array( $custom_field_name, $custom_fields_selected[ $model_type ], true ) ) {
						$custom_fields_selected[ $model_type ][ $custom_field_name ] = $custom_field_name;
						$has_been_modified                                           = true;
					}
				}
			}

			/*
			 * Not necessary apprently, and does not work with a custom index settings becase already saved on the default index on page load
			if ( $was_empty && $has_been_modified ) {
				$option                                              = $this->get_container()->get_service_option()->get_option_index();
				$option[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELDS ] = $custom_fields_selected;
				update_option( WPSOLR_Option_View::get_current_index_options_name( WPSOLR_Option::OPTION_INDEX ), $option, true );
			}
			*/
		}

		return $custom_fields_selected;
	}


	/**
	 * Default post types to add to the indexed post types
	 * @return array
	 */
	protected function get_default_post_types() {
		// Override in child
		return [];
	}

	/**
	 * Force listify post types selection if the post types are empty.
	 *
	 * @param array $post_types_selected
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_post_types_selected( $post_types_selected ) {

		$was_empty         = empty( $post_types_selected );
		$has_been_modified = false;
		foreach ( $this->get_default_post_types() as $post_type_name ) {

			if ( ! in_array( $post_type_name, $post_types_selected, true ) ) {
				$post_types_selected[] = $post_type_name;
				$has_been_modified     = true;
			}
		}

		/*
		 * Not necessary apprently, and does not work with a custom index settings becase already saved on the default index on page load
		if ( $was_empty && $has_been_modified ) {
			$option                                           = $this->get_container()->get_service_option()->get_option_index();
			$option[ WPSOLR_Option::OPTION_INDEX_POST_TYPES ] = implode( ',', $post_types_selected );
			update_option( WPSOLR_Option_View::get_current_index_options_name( WPSOLR_Option::OPTION_INDEX ), $option, true );
		}
		*/

		return $post_types_selected;
	}

	/**
	 * Default taxonomies to add to the indexed taxonomies
	 * @return array
	 */
	protected function get_default_taxonomies() {
		// Override in child
		return [];
	}

	/**
	 * Force listify taxonomies selection if the taxonomies are empty.
	 *
	 * @param array $taxonomies_selected
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_taxonomies_selected( $taxonomies_selected ) {

		$has_been_modified = false;
		if ( empty( $taxonomies_selected ) ) {
			foreach ( $this->get_default_taxonomies() as $taxonomy_name ) {

				$taxonomy_name = $taxonomy_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;

				if ( ! in_array( $taxonomy_name, $taxonomies_selected, true ) ) {
					$taxonomies_selected[] = $taxonomy_name;
					$has_been_modified     = true;
				}
			}

			/*
			 * Not necessary apprently, and does not work with a custom index settings becase already saved on the default index on page load
			if ( $has_been_modified ) {
				$option                                           = $this->get_container()->get_service_option()->get_option_index();
				$option[ WPSOLR_Option::OPTION_INDEX_TAXONOMIES ] = implode( ',', $taxonomies_selected );
				update_option( WPSOLR_Option_View::get_current_index_options_name( WPSOLR_Option::OPTION_INDEX ), $option, true );
			}
			*/
		}

		return $taxonomies_selected;
	}

	/**
	 * Add custom geolocation field to list of fields
	 *
	 * @param string[] $custom_fields
	 * @param string $model_type
	 *
	 * @return string[]
	 */
	function wpsolr_filter_index_custom_fields( $custom_fields, $model_type ) {

		if ( ! isset( $custom_fields ) ) {
			$custom_fields = [];
		}

		foreach ( $this->get_default_custom_fields() as $custom_field_name => $custom_field_property ) {

			if ( in_array( $model_type, $custom_field_property[ static::_FIELD_POST_TYPES ], true ) && ! in_array( $custom_field_name, $custom_fields, true ) ) {
				array_push( $custom_fields, $custom_field_name );
			}
		}

		return $custom_fields;
	}


	/**
	 * Layouts
	 */

	/**
	 * Layouts ids available for a facet feature
	 *
	 * @param string[] $default_layouts_ids
	 * @param string $facet_feature
	 *
	 * @return array
	 */

	public function wpsolr_filter_facet_feature_layouts( $default_layouts_ids, $facet_feature ) {

		if ( ! empty( static::get_feature_layouts_ids()[ $facet_feature ] ) ) {
			return static::get_feature_layouts_ids()[ $facet_feature ];
		}

		return ! empty( $default_layouts_ids ) ? $default_layouts_ids : [ WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID ];
	}

	/**
	 * @param string $default_layout_object
	 * @param string $layout_id
	 *
	 * @return string CSS class name
	 */
	function wpsolr_filter_layout_object( $default_layout_object, $layout_id ) {

		return empty( static::$layout_classes[ $layout_id ] ) ? $default_layout_object : ( new static::$layout_classes[ $layout_id ]() );
	}

	/**
	 * @param $layout_id
	 *
	 * @return WPSOLR_UI_Layout_Abstract
	 * @throws \Exception
	 */
	protected static function get_layout_class( $layout_id ) {

		if ( ! empty( static::$layout_classes[ $layout_id ] ) ) {

			return static::$layout_classes[ $layout_id ];

		} else {

			throw new \Exception( sprintf( "WPSOLR: unknown layout class for '{%s}'", esc_html( $layout_id ) ) );
		}
	}

	/**
	 * Layouts available for a field type
	 *
	 * @param array $layouts
	 * @param string $field_type
	 *
	 * @return array
	 */
	public function get_layouts_for_field_type( $layouts, $field_type = '' ) {

		$results = [];
		/**
		 * @var string $layout_id
		 * @var WPSOLR_UI_Layout_Abstract $layout_class
		 */
		foreach ( static::$layout_classes as $layout_id => $layout_class ) {
			$types = $layout_class::get_types();

			if ( empty( $types ) || in_array( $field_type, $types, true ) ) {
				// All field types authorized, or our $field_type authorized
				$results[ $layout_id ] = new $layout_class();
			}
		}

		return $results;
	}

	/**
	 * Get all facet skins, by facet layout.
	 *
	 * @return array
	 */
	public function get_facet_layout_skins( $facet_layout_skins = [] ) {

		$results = [];
		/**
		 * @var string $layout_id
		 * @var WPSOLR_UI_Layout_Abstract $layout_class
		 */
		foreach ( static::$layout_classes as $layout_id => $layout_class ) {

			$layout_class = $this->get_layout_class( $layout_id );
			$skins        = $layout_class::get_skins();

			if ( ! empty( $skins ) ) {
				$results[ $layout_id ] = $skins;
			}
		}

		return $results;
	}

	/**
	 * Get all facet skins, by facet layout.
	 *
	 * @return array
	 */
	public function get_facet_generic_scripts( $facet_layout_skins = [] ) {

		$results = [];
		/**
		 * @var string $layout_id
		 * @var WPSOLR_UI_Layout_Abstract $layout_class
		 */
		foreach ( static::$layout_classes as $layout_id => $layout_class ) {

			$generic_scripts = $layout_class::get_generic_scripts();

			if ( ! empty( $generic_scripts ) ) {
				$results[ $layout_id ] = $generic_scripts;
			}
		}

		return $results;
	}

	/**
	 * Get js placeholder for a layout.
	 *
	 * @param string $layout_id
	 *
	 * @return string
	 */
	static public function get_layout_js_help( $layout_id ) {

		$layout_class = static::get_layout_class( $layout_id );

		return $layout_class::get_js_help_text();
	}

	/**
	 * Get the facet type from a facet layout
	 *
	 * @param string $default_value
	 * @param string $facet_name
	 *
	 * @return string
	 */
	public function wpsolr_filter_facet_type( $default_value, $facet_name ) {

		$facet_layout_id = $this->get_container()->get_service_option()->get_facets_layout_id( $facet_name );

		if ( ! empty( $facet_layout_id ) && ! empty( static::$layout_classes[ $facet_layout_id ] ) ) {
			// Get the facet type of the layout
			$layout_class = $this->get_layout_class( $facet_layout_id );

			return $layout_class::get_facet_type();
		}

		return isset( $default_value ) ? $default_value : WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD;
	}

	/**
	 * @return bool
	 */
	protected function is_active_on_current_view(): bool {
		return in_array( WPSOLR_Option_View::get_current_view_uuid(), $this->get_views_uuids() );
	}

}
