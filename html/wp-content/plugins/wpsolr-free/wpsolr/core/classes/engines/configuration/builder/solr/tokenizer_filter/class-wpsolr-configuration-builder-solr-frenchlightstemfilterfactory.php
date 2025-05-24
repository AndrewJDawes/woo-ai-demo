<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter;

class WPSOLR_Configuration_Builder_Solr_FrenchLightStemFilterFactory extends WPSOLR_Configuration_Builder_Solr_Tokenizer_Filter_Abstract {

	/**
	 * @inheritdoc
	 */
	static function get_factory_class_name() {
		return 'solr.FrenchLightStemFilterFactory';
	}

	/**
	 * @inheritdoc
	 */
	protected function get_inner_parameters() {
		return [];
	}

}
