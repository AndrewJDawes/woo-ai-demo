<?php

namespace wpsolr\core\classes\hosting_api;

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;

class WPSOLR_Hosting_Api_Vespa_None extends WPSOLR_Hosting_Api_Abstract {
	const WPSOLR_FIELD_VECTOR_TEXT = 'wpsolr_field_vector_text';
	const WPSOLR_FIELD_VECTOR_IMAGE = 'wpsolr_field_vector_image';

	/**
	 * Get text vectorizers available from WPSOLR
	 *
	 * @return array
	 */
	static public function get_components_vectorizer_text(): array {
		return [
			# https://blog.vespa.ai/bge-embedding-models-in-vespa-using-bfloat16/
			/*'wpsolr_bge_small_en_onnx'          =>
				[
					'label'                 => 'BAAI/bge-small-en',
					'query_pattern'         => 'Represent this sentence for searching relevant passages: %s',
					'index_pattern'         => '%s',
					'vector_dimension'      => 384,
					'attribute'             => [ 'distance-metric' => 'prenormalized-angular', ],
					'type'                  => 'hugging-face-embedder',
					'transformer-model-url' => '',
					'tokenizer-model-url'   => '',
					'pooling-strategy'      => 'cls',
					'normalize'             => 'true',
				],
			# https://blog.vespa.ai/simplify-search-with-multilingual-embeddings/
			'wpsolr_multilingual_e5_small_onnx' =>
				[
					'label'                 => 'intfloat/multilingual-e5-small',
					'query_pattern'         => 'query: %s',
					'index_pattern'         => '"passage: " . %s',
					'vector_dimension'      => 384,
					'type'                  => 'hugging-face-embedder',
					'transformer-model-url' => '',
					'tokenizer-model-url'   => '',
				],*/
			# https://docs.vespa.ai/en/embedding.html#huggingface-embedder-models
			'wpsolr_all_minilm_l6_v2_onnx' =>
				[
					'label'                 => 'sentence-transformers/all-MiniLM-L6-v2',
					'query_pattern'         => '%s',
					'index_pattern'         => '%s',
					'vector_dimension'      => 384,
					'type'                  => 'hugging-face-embedder',
					'transformer-model-url' => 'https://huggingface.co/optimum/all-MiniLM-L6-v2/resolve/main/model.onnx',
					'tokenizer-model-url'   => 'https://huggingface.co/optimum/all-MiniLM-L6-v2/resolve/main/tokenizer.json',
				],
		];
	}

	public function get_server_files( array $index ): array {
		return [
			'services.xml'        => [ 'label' => 'services.xml', ],
			'schema_generated.sd' => [ 'label' => 'generated schema.sd', ],
			'schema_custom.sd'    => [ 'label' => 'custom schema.sd', ],
		];
	}

	const HOSTING_API_ID = 'none_vespa';

	/**
	 * @inerhitDoc
	 */
	public function get_is_disabled() {
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function get_is_no_hosting() {
		return true;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_is_endpoint_only() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return sprintf( self::NONE_LABEL, 'Vespa.ai' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_search_engine() {
		return WPSOLR_AbstractEngineClient::ENGINE_VESPA;
	}

	/**
	 * @inheritDoc
	 */
	public function get_is_host_contains_user_password() {
		return false; // Vespa does not require it
	}

	/**
	 * @return string
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/configure-your-indexes/create-vespa-index/';
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		$result = [
			static::FIELD_NAME_FIELDS_INDEX_LABEL_DEFAULT,
			[
				self::FIELD_NAME_FIELDS_VESPA_SERVICE_CONTAINER_ID => [
					self::FIELD_NAME_LABEL                 => 'Container id',
					self::FIELD_NAME_PLACEHOLDER           => 'Container id in your services.xml. If left empty, "default" will be used.',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_COPY . 'your content id here.',
					],
				],
			],
			[
				self::FIELD_NAME_FIELDS_VESPA_SERVICE_CONTENT_ID => [
					self::FIELD_NAME_LABEL                 => 'Content id',
					self::FIELD_NAME_PLACEHOLDER           => 'Content id in your services.xml. If left empty, "wpsolr" will be used.',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_COPY . 'your content id here.',
					],
				],
			],
			[
				self::FIELD_NAME_FIELDS_INDEX_ENDPOINT => [
					self::FIELD_NAME_LABEL                 => 'Admin and config cluster',
					self::FIELD_NAME_PLACEHOLDER           => 'Copy a Vespa instance URL here, like http://localhost:19071',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_COPY . 'your Server URL here',
					],
				],
			],
			[
				self::FIELD_NAME_FIELDS_INDEX_ENDPOINT_1 => [
					self::FIELD_NAME_LABEL                 => 'Stateless container cluster',
					self::FIELD_NAME_PLACEHOLDER           => 'Copy a Vespa instance URL here, like http://localhost:8080',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_COPY . 'your Server URL here',
					],
				],
			],

		];

		return $result;
	}

}