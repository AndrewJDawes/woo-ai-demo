<?php

namespace wpsolr\core\classes\utilities;

class WPSOLR_Admin_Utilities {

	const ADMIN_PAGE_EXTENSIONS = 'admin.php?page=solr_settings&tab=solr_plugins';

	/**
	 * @param string $tab
	 *
	 * @return string|void
	 */
	static public function get_admin_url( $tab = '' ) {
		$base_admin_url = admin_url( self::ADMIN_PAGE_EXTENSIONS );

		return empty( $tab ) ? $base_admin_url : sprintf( '%s%s', $base_admin_url, $tab );
	}

	/**
	 * Transform Dropbox download url from HTML to zip
	 *
	 * @param string $dropbox_url_html
	 *
	 * @return string
	 */
	public static function convert_dropbox_url_from_html_to_zip( string $dropbox_url_html ): string {
		return str_replace( 'dl=0', 'dl=1', $dropbox_url_html );
	}

}