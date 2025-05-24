<?php

use wpsolr\core\classes\extensions\WPSOLR_Extension;

?>

<?php

$subtabs2 = [];
foreach ( $subtabs1 as $indice => $subtab ) {
	if ( false !== strpos( $subtab['class'], 'wpsolr_is_available' ) ) {
		$subtabs2[ $indice ] = $subtab;
	}
}
foreach ( $subtabs1 as $indice => $subtab ) {
	if ( false === strpos( $subtab['class'], 'wpsolr_is_available' ) ) {
		$subtabs2[ $indice ] = $subtab;
	}
}
$subtabs = [];
foreach ( $subtabs2 as $indice => $subtab ) {
	if ( false !== strpos( $subtab['class'], 'wpsolr_tab_active' ) ) {
		$subtabs[ $indice ] = $subtab;
	}
}
foreach ( $subtabs2 as $indice => $subtab ) {
	if ( false === strpos( $subtab['class'], 'wpsolr_tab_active' ) ) {
		$subtabs[ $indice ] = $subtab;
	}
}

$subtab = wpsolr_admin_sub_tabs( $subtabs );

switch ( $subtab ) {
	case 'extension_groups_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_GROUPS );
		break;

	case 'extension_s2member_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_S2MEMBER );
		break;

	case 'extension_wpml_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_WPML );
		break;

	case 'extension_polylang_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_POLYLANG );
		break;

	case 'extension_qtranslatex_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_QTRANSLATEX );
		break;

	case 'extension_woocommerce_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_WOOCOMMERCE );
		break;

	case 'extension_acf_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_ACF );
		break;

	case 'extension_toolset_types_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_TOOLSET_TYPES );
		break;

	case 'extension_toolset_views_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_TOOLSET_VIEWS );
		break;

	case 'extension_bbpress_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_BBPRESS );
		break;

	case 'extension_embed_any_document_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_EMBED_ANY_DOCUMENT );
		break;

	case 'extension_pdf_embedder_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_PDF_EMBEDDER );
		break;

	case 'extension_google_doc_embedder_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_GOOGLE_DOC_EMBEDDER );
		break;

	case 'extension_tablepress_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_TABLEPRESS );
		break;

	case 'extension_geolocation_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_GEOLOCATION );
		break;

	case 'extension_premium_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_PREMIUM );
		break;

	case 'extension_theme_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_THEME );
		break;

	case 'extension_yoast_seo_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_YOAST_SEO );
		break;

	case 'extension_all_in_one_seo_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_ALL_IN_ONE_SEO );
		break;

	case 'extension_wp_all_import_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_WP_ALL_IMPORT );
		break;

	case 'extension_scoring_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_SCORING );
		break;

	case 'extension_yith_woocommerce_ajax_search_free_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE );
		break;

	case 'extension_theme_listify_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_THEME_LISTIFY );
		break;

	case 'extension_cron_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_CRON );
		break;

	case 'extension_theme_jobify_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_THEME_JOBIFY );
		break;

	case 'extension_theme_listable_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_THEME_LISTABLE );
		break;

	case 'extension_theme_listingpro_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_THEME_LISTINGPRO );
		break;

	case 'extension_theme_directory2_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_THEME_DIRECTORY2 );
		break;

	case 'extension_ajax_search_pro':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_AJAX_SEARCH_PRO );
		break;

	case 'extension_wp_google_map_pro_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_WP_GOOGLE_MAP_PRO );
		break;

	case 'extension_theme_mylisting_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_THEME_MYLISTING );
		break;

	case 'extension_theme_voxel_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_THEME_VOXEL );
		break;

	case 'extension_theme_flatsome_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_THEME_FLATSOME );
		break;

	case 'extension_ai_api_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_AI_API );
		break;

	case 'extension_cross_domain_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_CROSS_DOMAIN );
		break;

	case 'extension_jet_smart_filters_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_JET_SMART_FILTERS );
		break;

	case 'extension_jet_engine_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_JET_ENGINE );
		break;

	case 'extension_query_monitor_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_QUERY_MONITOR );
		break;

	case 'extension_wp_rocket_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::EXTENSION_WP_ROCKET );
		break;

	case 'extension_views_opt':
		WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_VIEWS );
		break;


}

