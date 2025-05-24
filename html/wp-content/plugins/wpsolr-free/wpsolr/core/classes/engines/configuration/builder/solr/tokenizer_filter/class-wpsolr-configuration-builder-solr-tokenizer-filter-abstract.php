<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter;

use wpsolr\core\classes\engines\configuration\builder\solr\WPSOLR_Configuration_Builder_Solr_Abstract;

abstract class WPSOLR_Configuration_Builder_Solr_Tokenizer_Filter_Abstract extends WPSOLR_Configuration_Builder_Solr_Abstract {

	public static function get_is_tokenizer_filter() {
		return true;
	}

}

