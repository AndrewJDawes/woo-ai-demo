<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter;

class WPSOLR_Configuration_Builder_Solr_ElisionFilterFactory extends WPSOLR_Configuration_Builder_Solr_Tokenizer_Filter_Abstract {

	/**
	 * Parameters name
	 */
	const PARAMETER_ARTICLES = 'articles';
	const PARAMETER_IGNORE_CASE = 'ignoreCase';

	/**
	 * Parameters description
	 */
	const DESCRIPTION_PARAM_ARTICLES = 'The pathname of a file that contains a list of articles, one per line, to be stripped. 
Articles are words such as "le", which are commonly abbreviated, such as in l’avion (the plane). 
This file should include the abbreviated form, which precedes the apostrophe. In this case, simply "l". 
If no articles attribute is specified, a default set of French articles is used.';

	const DESCRIPTION_PARAM_IGNORE_CASE = '(boolean) If true, the filter ignores the case of words when comparing them to the common word file. Defaults to false';


	/**
	 * @inheritdoc
	 */
	static function get_factory_class_name() {
		return 'solr.ElisionFilterFactory';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_link() {
		return 'https://lucene.apache.org/solr/guide/6_6/language-analysis.html#LanguageAnalysis-ElisionFilter';
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return 'Removes article elisions from a token stream. This filter can be useful for languages such as French, Catalan, Italian, and Irish.';
	}

	/**
	 * @inheritdoc
	 */
	public function set_parameter_articles( $file_path ) {

		return $this->set_parameter_file_value( self::PARAMETER_ARTICLES, $file_path );
	}

	/**
	 * @inheritdoc
	 */
	protected function get_inner_parameters() {
		$this->add_parameter_file( self::PARAMETER_ARTICLES, '', true, self::DESCRIPTION_PARAM_ARTICLES )
		     ->add_parameter_true_false( self::PARAMETER_IGNORE_CASE, false, true, self::DESCRIPTION_PARAM_IGNORE_CASE );
	}

}
