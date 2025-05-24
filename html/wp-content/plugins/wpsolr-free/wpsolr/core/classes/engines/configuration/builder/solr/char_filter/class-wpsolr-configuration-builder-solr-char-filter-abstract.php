<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\char_filter;

use wpsolr\core\classes\engines\configuration\builder\solr\WPSOLR_Configuration_Builder_Solr_Abstract;

abstract class WPSOLR_Configuration_Builder_Solr_Char_Filter_Abstract extends WPSOLR_Configuration_Builder_Solr_Abstract {

	public static function get_is_char_filter() {
		return true;
	}

}

