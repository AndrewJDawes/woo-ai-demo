<?php

namespace wpsolr\core\classes\engines\configuration\builder\solr\analyser;

use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter\WPSOLR_Configuration_Builder_Solr_ElisionFilterFactory;
use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter\WPSOLR_Configuration_Builder_Solr_FrenchLightStemFilterFactory;
use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter\WPSOLR_Configuration_Builder_Solr_LowerCaseFilterFactory;
use wpsolr\core\classes\engines\configuration\builder\solr\tokenizer_filter\WPSOLR_Configuration_Builder_Solr_StopFilterFactory;

class WPSOLR_Analyser_French_Light_Stem extends WPSOLR_Analyser_French_SnowBall_Stem {


	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'id_fr_light';
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return 'French (Light stem)';
	}

	/**
	 * @inheritdoc
	 */
	public function get_tokenizer_filters() {
		return [
			( new WPSOLR_Configuration_Builder_Solr_LowerCaseFilterFactory() ),
			( new WPSOLR_Configuration_Builder_Solr_ElisionFilterFactory() )->set_parameter_articles( 'lang/contractions_fr.txt' ),
			( new WPSOLR_Configuration_Builder_Solr_StopFilterFactory() )->set_parameter_words( 'lang/stopwords_fr.txt' ),
			( new WPSOLR_Configuration_Builder_Solr_FrenchLightStemFilterFactory() ),
		];
	}

}

