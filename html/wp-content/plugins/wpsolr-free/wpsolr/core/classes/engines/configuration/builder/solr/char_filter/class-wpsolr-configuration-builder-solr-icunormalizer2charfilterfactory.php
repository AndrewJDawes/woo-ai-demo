<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\char_filter;

class WPSOLR_Configuration_Builder_Solr_ICUNormalizer2CharFilterFactory extends WPSOLR_Configuration_Builder_Solr_Char_Filter_Abstract {


	/**
	 * @inheritdoc
	 */
	static function get_factory_class_name() {
		return 'solr.ICUNormalizer2CharFilterFactory';
	}

	/**
	 * @inheritdoc
	 */
	public function get_documentation_link() {
		return 'https://lucene.apache.org/solr/guide/charfilterfactories.html#CharFilterFactories-solr.ICUNormalizer2CharFilterFactory';
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return 'This filter performs pre-tokenization Unicode normalization using ICU4J';
	}


	/**
	 * @inheritdoc
	 */
	protected function get_inner_parameters() {
		// No parameters
	}


}
