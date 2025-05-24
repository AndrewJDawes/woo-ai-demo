<?php

namespace wpsolr\core\classes\extensions\licenses;


class OptionLicenses extends OptionLicenses_Root {

	/**
	 * Is a license activated ?
	 */
	function get_license_is_activated( $license_type ) {
		return true;
	}

	function show_premium_link( $is_add_extra_text, $license_type, $text_to_show, $is_show_link, $is_new_feature = false ) {
		return $text_to_show;
	}
}
