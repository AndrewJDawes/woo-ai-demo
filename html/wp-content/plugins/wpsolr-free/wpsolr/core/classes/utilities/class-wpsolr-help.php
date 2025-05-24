<?php

namespace wpsolr\core\classes\utilities;

/**
 * Show help links on admin screens.
 *
 * Class WPSOLR_Help
 * @package wpsolr\core\classes\utilities
 */
class WPSOLR_Help {

	// Url of help
	const _SEARCH_URL = '<a class="old-wpsolr-help" href="%s" target="_help"></a>';
	const _SEARCH_URL_HREF = 'https://www.wpsolr.com/?s=&wpsolr_fq[]=wpsolr_feature_str:%s';

	const _PAGE_URL = '<a class="wpsolr-help" href="%s" target="_help"></a>';
	const _PAGE_URL_HREF = 'https://www.wpsolr.com/%s';

	// Help ids
	const HELP_SEARCH_TEMPLATE = 3;
	const HELP_JQUERY_SELECTOR = 4;
	const HELP_SEARCH_ORDERS = 5;
	const HELP_ACF_REPEATERS_AND_FLEXIBLE_CONTENT_LAYOUTS = 6;
	const HELP_WOOCOMMERCE_REPLACE_SORT = 7;
	const HELP_ACF_GOOGLE_MAP = 8;
	const HELP_SCHEMA_TYPE_DATE = 9;
	const HELP_WOOCOMMERCE_REPLACE_CATEGORY_SEARCH = 10;
	const HELP_SEARCH_PAGE_SLUG = 11;
	const HELP_SEARCH_INFINITE_SCROLL = 12;
	const HELP_SEARCH_SUGGESTIONS = 13;
	const HELP_SEARCH_SUGGESTIONS_JQUERY_SELECTOR = 14;
	const HELP_SEARCH_DID_YOU_MEAN = 15;
	const HELP_INDEXING_STOP_REAL_TIME = 16;
	const HELP_INDEXING_POST_TYPES = 17;
	const HELP_INDEXING_CUSTOM_FIELDS = 18;
	const HELP_INDEXING_TAXONOMIES = 19;
	const HELP_INDEXING_ATTACHMENTS = 20;
	const HELP_SEARCH_BOOSTS = 21;
	const HELP_FACET_LABEL = 22;
	const HELP_FACET_HIERARCHY = 23;
	const HELP_FACET_POST_TYPE = 24;
	const HELP_SORT_LABEL = 25;
	const HELP_BATCH_DEBUG = 26;
	const HELP_BATCH_MODE_REPLACE = 27;
	const HELP_ACF_FIELD_FILE = 28;
	const HELP_LOCALIZE = 29;
	const HELP_MULTI_INDEX = 30;
	const HELP_FACET_DEFINITION = 32;
	const HELP_THEME_FACET_LAYOUT = 35;
	const HELP_TOOLSET_FIELD_FILE = 36;
	const HELP_YOAST_SEO = 45;
	const HELP_SEO_FACET_PERMALINKS = 46;
	const HELP_ALL_IN_ONE_SEO_PACK = 47;
	const HELP_WP_ALL_IMPORT_PACK = 48;
	const HELP_SEO_STEALTH_MODE = 49;
	const HELP_SEO_SEARCH_KEYWORDS_PERMALINKS = 50;
	const HELP_SEO_PERMALINKS_REDIRECT = 51;
	const HELP_SEO_PERMALINKS_STORAGE = 52;
	const HELP_SEO_SORT_PERMALINKS = 53;
	const HELP_SEO_TAG_NOFOLLOW = 54;
	const HELP_SEO_TAG_NOINDEX = 55;
	const HELP_FACET_SEO_TEMPLATE = 56;
	const HELP_FACET_SEO_TEMPLATE_LOCALIZATION = 57;
	const HELP_FACET_SEO_TEMPLATE_POSITIONS = 58;
	const HELP_CHECKER = 60;

	const HELP_FACET_THEME_SKIN_TEMPLATE = 62;
	const HELP_FACET_THEME_JS_TEMPLATE = 63;
	const HELP_FACET_THEME_MULTIPLE_TEMPLATE = 64;
	const HELP_FACET_THEME_PLACEHOLDER_TEMPLATE = 65;
	const HELP_FACET_THEME_COLOR_PICKER_TEMPLATE = 66;
	const HELP_FACET_THEME_COLOR_PICKER_TEMPLATE_LOCALIZATION = 67;
	const HELP_FACET_THEME_RANGE_REGULAR_TEMPLATE = 68;
	const HELP_FACET_THEME_RANGE_IRREGULAR_TEMPLATE = 69;

	const HELP_FACET_SHOW_WOOCOMMERCE_VARIATION_IMAGE = 70;
	const HELP_INDEXING_IMAGES = 71;

	const HELP_INDEXING_STATUS = 72;

	const HELP_FACET_THEME_GENERIC_SCRIPT_TEMPLATE = 73;

	const HELP_BATCH_EVENTS_REQUIREMENTS = 74;
	/**
	 * Add-ons
	 */
	const HELP_ADDON_AI_API_TEXT = 'help_addon_ai_api_text';
	const HELP_ADDON_AI_API_IMAGE = 'help_addon_ai_api_image';
	const HELP_ADDON_ADVANCED_SCORING = 'help_addon_advanced_scoring';
	const HELP_ADDON_CRON = 'help_addon_cron';
	const HELP_ADDON_CROSS_DOMAIN = 'help_addon_cross_domain';
	const HELP_ADDON_GEOLOCATION = 'help_addon_geolocation';
	const HELP_ADDON_PREMIUM = 'help_addon_premium';
	const HELP_ADDON_QUERY_MONITOR = 'help_addon_query_monitor';
	const HELP_ADDON_THEME = 'help_addon_theme';
	const HELP_ADDON_THEME_FACET_CSS = 'help_addon_theme_facet_css';
	const HELP_ADDON_THEME_FACET_COLLAPSING = 'help_addon_theme_facet_collapsing';
	const HELP_ADDON_THEME_AJAX_JQUERY = 'help_addon_theme_ajax_jquery';
	const HELP_ADDON_TOOLSET_VIEWS = 'help_addon_toolset_views';
	const HELP_ADDON_WOOCOMMERCE = 'help_addon_woocommerce';
	const HELP_ADDON_WPML = 'help_addon_wpml';
	const HELP_ADDON_POLYLANG = 'help_addon_polylang';

	/**
	 * Themes
	 */
	const HELP_ADDON_DIRECTORY2 = 'help_addon_directory2';
	const HELP_ADDON_LISTIFY = 'help_addon_listify';
	const HELP_ADDON_JOBIFY = 'help_addon_jobify';
	const HELP_ADDON_LISTABLE = 'help_addon_listeable';
	const HELP_ADDON_MYLISTING = 'help_addon_mylisting';
	const HELP_ADDON_VOXEL = 'help_addon_voxel';
	const HELP_ADDON_FLATSOME = 'help_addon_flatsome';


	const HELP_URLS = [
		/**
		 * Add-ons
		 */
		self::HELP_ADDON_AI_API_TEXT            => 'guide/configuration-step-by-step-schematic/activate-extensions/extension-nlp/',
		self::HELP_ADDON_AI_API_IMAGE           => 'guide/configuration-step-by-step-schematic/activate-extensions/ai-image-and-ocr-apis-add-on/',
		self::HELP_ADDON_ADVANCED_SCORING       => 'guide/configuration-step-by-step-schematic/activate-extensions/advanced-scoring/',
		self::HELP_ADDON_CRON                   => 'guide/configuration-step-by-step-schematic/activate-extensions/cron-scheduling/',
		self::HELP_ADDON_CROSS_DOMAIN           => 'guide/configuration-step-by-step-schematic/activate-extensions/cross-domain-search/',
		self::HELP_ADDON_GEOLOCATION            => 'guide/configuration-step-by-step-schematic/activate-extensions/extension-geolocation/',
		self::HELP_ADDON_PREMIUM                => 'guide/configuration-step-by-step-schematic/activate-extensions/extension-premium/',
		self::HELP_ADDON_QUERY_MONITOR          => 'guide/configuration-step-by-step-schematic/activate-extensions/query-monitor-add-on/',
		self::HELP_ADDON_THEME                  => 'guide/configuration-step-by-step-schematic/activate-extensions/extension-theme/',
		self::HELP_ADDON_THEME_FACET_CSS        => 'guide/configuration-step-by-step-schematic/activate-extensions/extension-theme/theme-custom-facets-css/',
		self::HELP_ADDON_THEME_FACET_COLLAPSING => 'guide/configuration-step-by-step-schematic/activate-extensions/extension-theme/theme-collapse-taxonomy-hierarchies/',
		self::HELP_ADDON_THEME_AJAX_JQUERY      => 'guide/configuration-step-by-step-schematic/activate-extensions/extension-theme/theme-add-ajax-to-the-current-theme/',
		self::HELP_ADDON_TOOLSET_VIEWS          => 'guide/configuration-step-by-step-schematic/activate-extensions/toolset-views-add-on/',
		self::HELP_ADDON_WOOCOMMERCE            => 'guide/configuration-step-by-step-schematic/activate-extensions/woocommerce-add-on/',
		self::HELP_ADDON_WPML                   => 'guide/configuration-step-by-step-schematic/activate-extensions/wpml-add-on/',
		self::HELP_ADDON_POLYLANG               => 'guide/configuration-step-by-step-schematic/activate-extensions/polylang-add-on/',

		/**
		 * Themes
		 */
		self::HELP_ADDON_DIRECTORY2             => 'guide/configuration-step-by-step-schematic/activate-extensions/directory-plus-add-on/',
		self::HELP_ADDON_JOBIFY                 => 'guide/configuration-step-by-step-schematic/activate-extensions/jobify-add-on/',
		self::HELP_ADDON_LISTIFY                => 'guide/configuration-step-by-step-schematic/activate-extensions/listify-add-on/',
		self::HELP_ADDON_LISTABLE               => '',
		self::HELP_ADDON_MYLISTING              => 'setup-mylisting-theme-with-elasticsearch/',
		self::HELP_ADDON_VOXEL                  => 'setup-voxel-theme-with-elasticsearch/',
		self::HELP_ADDON_FLATSOME               => 'guide/configuration-step-by-step-schematic/activate-extensions/flatsome-add-on/',

	];

	/**
	 * Show a help_id description
	 *
	 * @param $help_id
	 *
	 * @return string
	 */
	public static function get_help( $help_id ) {
		global $license_manager;

		$url = '';

		if ( isset( static::HELP_URLS[ $help_id ] ) ) {
			/**
			 * New help leading to a WPSOLR documentation
			 */

			$url = sprintf( self::_PAGE_URL_HREF, static::HELP_URLS[ $help_id ] );
			$url = sprintf( self::_PAGE_URL, WPSOLR_Escape::esc_url( $license_manager->add_campaign_to_url( $url ) ) );

		} else {
			/**
			 * Old help leading to a WPSOLR search
			 */

			//$url = sprintf( self::_SEARCH_URL_HREF, $help_id );
			//$url = sprintf( self::_SEARCH_URL, $license_manager->add_campaign_to_url( $url ) );

		}


		return $url;
	}
}