<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\tokenizer;

class WPSOLR_Configuration_Builder_Solr_ClassicTokenizerFactory extends WPSOLR_Configuration_Builder_Solr_Tokenizer_Abstract {

	/**
	 * @inheritdoc
	 */
	static function get_factory_class_name() {
		return 'solr.ClassicTokenizerFactory';
	}

	/**
	 * @inheritdoc
	 */
	protected function get_inner_parameters() {
		return [];
	}
}
