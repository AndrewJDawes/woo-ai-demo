<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\char_filter;

class WPSOLR_Configuration_Builder_Solr_PatternReplaceCharFilterFactory extends WPSOLR_Configuration_Builder_Solr_Char_Filter_Abstract {

	/**
	 * Parameters name
	 */
	const PARAMETER_PATTERN = 'pattern';
	const PARAMETER_REPLACEMENT = 'replacement';

	const DESCRIPTION_PARAMETER_PATTERN = 'The regular expression pattern to apply to the incoming text.';

	const DESCRIPTION_PARAMETER_REPLACEMENT = 'The text to use to replace matching patterns.';

	/**
	 * @inheritdoc
	 */
	static function get_factory_class_name() {
		return 'solr.PatternReplaceCharFilterFactory';
	}

	/**
	 * @inheritdoc
	 */
	public function get_documentation_link() {
		return 'https://lucene.apache.org/solr/guide/charfilterfactories.html#CharFilterFactories-solr.PatternReplaceCharFilterFactory';
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return 'This filter uses regular expressions to replace or change character patterns.';
	}

	/**
	 * @inheritdoc
	 */
	protected function get_inner_parameters() {

		$this->add_parameter_input( self::PARAMETER_PATTERN, '', false, self::DESCRIPTION_PARAMETER_PATTERN )
		     ->add_parameter_input( self::PARAMETER_REPLACEMENT, '', false, self::DESCRIPTION_PARAMETER_REPLACEMENT );
	}


}
