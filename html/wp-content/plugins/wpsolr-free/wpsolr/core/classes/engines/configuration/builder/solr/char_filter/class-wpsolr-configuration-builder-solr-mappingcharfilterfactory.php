<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\char_filter;

class WPSOLR_Configuration_Builder_Solr_MappingCharFilterFactory extends WPSOLR_Configuration_Builder_Solr_Char_Filter_Abstract {

	/**
	 * Parameters name
	 */
	const PARAMETER_MAPPING = 'mapping';

	const DESCRIPTION_PARAM_MAPPING = 'Path and name of a file containing the mappings to perform.';


	/**
	 * @inheritdoc
	 */
	static function get_factory_class_name() {
		return 'solr.MappingCharFilterFactory';
	}

	/**
	 * @inheritdoc
	 */
	public function get_documentation_link() {
		return 'https://lucene.apache.org/solr/guide/charfilterfactories.html#CharFilterFactories-solr.MappingCharFilterFactory';
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return 'This filter creates org.apache.lucene.analysis.MappingCharFilter, which can be used for changing one string to another (for example, for normalizing é to e.).

This filter requires specifying a mapping argument, which is the path and name of a file containing the mappings to perform.';
	}


	/**
	 * @param string $mapping
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function set_parameter_mapping( $mapping ) {
		return $this->set_parameter_file_value( self::PARAMETER_MAPPING, $mapping );
	}

	/**
	 * @inheritdoc
	 */
	protected function get_inner_parameters() {

		$this->add_parameter_file( self::PARAMETER_MAPPING, '', false, self::DESCRIPTION_PARAM_MAPPING );
	}


}
