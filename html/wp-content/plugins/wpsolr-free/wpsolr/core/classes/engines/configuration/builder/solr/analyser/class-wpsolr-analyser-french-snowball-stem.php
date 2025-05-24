<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\analyser;

use wpsolr\core\classes\engines\configuration\builder\solr\char_filter\WPSOLR_Configuration_Builder_Solr_HTMLStripCharFilterFactory;
use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer\WPSOLR_Configuration_Builder_Solr_StandardTokenizerFactory;
use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter\WPSOLR_Configuration_Builder_Solr_ElisionFilterFactory;
use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter\WPSOLR_Configuration_Builder_Solr_LowerCaseFilterFactory;
use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter\WPSOLR_Configuration_Builder_Solr_SnowballPorterFilterFactory;
use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter\WPSOLR_Configuration_Builder_Solr_StopFilterFactory;
use wpsolr\core\classes\engines\configuration\builder\WPSOLR_Analyser_Abstract;

class WPSOLR_Analyser_French_SnowBall_Stem extends WPSOLR_Analyser_Abstract {


	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'id_fr_snowball';
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return 'French (Snowball Porter stem)';
	}

	/**
	 * @inheritdoc
	 */
	public function get_char_filters() {
		return [
			( new WPSOLR_Configuration_Builder_Solr_HTMLStripCharFilterFactory() ),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function get_tokenizer() {
		return new WPSOLR_Configuration_Builder_Solr_StandardTokenizerFactory();
	}

	/**
	 * @inheritdoc
	 */
	public function get_tokenizer_filters() {
		return [
			( new WPSOLR_Configuration_Builder_Solr_LowerCaseFilterFactory() ),
			( new WPSOLR_Configuration_Builder_Solr_ElisionFilterFactory() )->set_parameter_articles( 'contractions_fr.txt' ),
			( new WPSOLR_Configuration_Builder_Solr_StopFilterFactory() )->set_parameter_words( 'stopwords_fr.txt' ),
			( new WPSOLR_Configuration_Builder_Solr_SnowballPorterFilterFactory() )->set_parameter_language(
				WPSOLR_Configuration_Builder_Solr_SnowballPorterFilterFactory::PARAMETER_FORMAT_LANGUAGE_FRENCH ),
		];
	}

}

