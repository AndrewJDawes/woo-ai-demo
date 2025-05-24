<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\char_filter;

class WPSOLR_Configuration_Builder_Solr_HTMLStripCharFilterFactory extends WPSOLR_Configuration_Builder_Solr_Char_Filter_Abstract {


	/**
	 * @inheritdoc
	 */
	static function get_factory_class_name() {
		return 'solr.HTMLStripCharFilterFactory';
	}

	/**
	 * @inheritdoc
	 */
	public function get_documentation_link() {
		return 'https://lucene.apache.org/solr/guide/charfilterfactories.html#CharFilterFactories-solr.HTMLStripCharFilterFactory';
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return 'This CharFilter strips HTML from the input stream and passes the result to another CharFilter or a Tokenizer.';
	}


	/**
	 * @inheritdoc
	 */
	protected function get_inner_parameters() {
		// No parameters
	}


}
