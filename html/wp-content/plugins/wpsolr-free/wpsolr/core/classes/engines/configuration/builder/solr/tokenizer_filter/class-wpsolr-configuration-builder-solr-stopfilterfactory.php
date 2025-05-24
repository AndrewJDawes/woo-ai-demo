<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter;

class WPSOLR_Configuration_Builder_Solr_StopFilterFactory extends WPSOLR_Configuration_Builder_Solr_Tokenizer_Filter_Abstract {
	const FORMAT_SNOWBALL = 'snowball';

	/**
	 * Parameters name
	 */
	const PARAMETER_WORDS = 'words';
	const PARAMETER_FORMAT = 'format';
	const PARAMETER_IGNORE_CASE = 'ignoreCase';
	const PARAMETER_ENABLE_POSITION_INCREMENTS = 'enablePositionIncrements';

	/**
	 * Parameters value
	 */
	const PARAMETER_FORMAT_VALUE_SNOWBALL = self::FORMAT_SNOWBALL;

	/**
	 * Parameters description
	 */
	const DESCRIPTION_PARAM_WORDS = '(optional) The path to a file that contains a list of stop words, one per line. 
Blank lines and lines that begin with "#" are ignored. 
This may be an absolute path, or path relative to the Solr conf directory.';

	const DESCRIPTION_PARAM_FORMAT = '(optional) If the stopwords list has been formatted for Snowball, you can specify format="snowball" so Solr can read the stopwords file.';

	const DESCRIPTION_PARAM_IGNORE_CASE = '(true/false, default false) Ignore case when testing for stop words. If true, the stop list should contain lowercase words.';

	const DESCRIPTION_PARAM_ENABLEPOSITIONINCREMENTS = 'if luceneMatchVersion is 4.4 or earlier and enablePositionIncrements="false", no position holes will be left by this filter when it removes tokens. 
This argument is invalid if luceneMatchVersion is 5.0 or later.';

	/**
	 * @inheritdoc
	 */
	static function get_factory_class_name() {
		return 'solr.StopFilterFactory';
	}

	/**
	 * @inheritdoc
	 */
	public function get_documentation_link() {
		return 'https://lucene.apache.org/solr/guide/7_3/filter-descriptions.html#stop-filter';
	}

	/**
	 * @param string $file_path
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function set_parameter_words( $file_path ) {

		return $this->set_parameter_file_value( self::PARAMETER_WORDS, $file_path );
	}

	/**
	 * @inheritdoc
	 */
	protected function get_inner_parameters() {

		$this->add_parameter_file( self::PARAMETER_WORDS, '', false, self::DESCRIPTION_PARAM_WORDS )
		     ->add_parameter_drop_down_list( self::PARAMETER_FORMAT, self::FORMAT_SNOWBALL, [
			     [
				     self::PARAMETER_LIST_ID    => '',
				     self::PARAMETER_LIST_LABEL => self::PARAMETER_LIST_USE_DEFAULT
			     ],
			     [
				     self::PARAMETER_LIST_ID    => self::FORMAT_SNOWBALL,
				     self::PARAMETER_LIST_LABEL => 'The stopwords list has been formatted for Snowball'
			     ],
		     ], true, self::DESCRIPTION_PARAM_FORMAT )
		     ->add_parameter_true_false( self::PARAMETER_IGNORE_CASE, false, true, self::DESCRIPTION_PARAM_IGNORE_CASE )
		     ->add_parameter_true_false( self::PARAMETER_ENABLE_POSITION_INCREMENTS, '', true, self::DESCRIPTION_PARAM_ENABLEPOSITIONINCREMENTS );

	}

}
