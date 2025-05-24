<?php

namespace wpsolr\core\classes\engines\weaviate;

class WPSOLR_Weaviate_Constants {

	const MODULE_TEXT_2_VEC_CONTEXTIONARY = 'text2vec-contextionary';

	const MODULE_MULTI2VEC_CLIP = 'multi2vec-clip';
	const MODULE_NONE = 'none';
	const MODULE_TEXT_2_VEC_TRANSFORMERS = 'text2vec-transformers';

	const MODULE_MULTI2VEC_BIND = 'multi2vec-bind';
	const MODULE_TEXT_2_VEC_GPT4ALL = 'text2vec-gpt4all';
	const MODULE_IMG2VEC_NEURAL = 'img2vec-neural';

	const MODULE_NER_TRANSFORMERS = 'ner-transformers';
	const MODULE_QNA_TRANSFORMERS = 'qna-transformers';
	const MODULE_TEXT_SPELLCHECK = 'text-spellcheck';
}
