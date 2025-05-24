<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr;


use wpsolr\core\classes\engines\configuration\builder\WPSOLR_Configuration_Builder_Abstract;

abstract class WPSOLR_Configuration_Builder_Solr_Abstract extends WPSOLR_Configuration_Builder_Abstract {

	/**
	 * @inheritdoc
	 */
	public static function get_is_solr() {
		return true;
	}

}

