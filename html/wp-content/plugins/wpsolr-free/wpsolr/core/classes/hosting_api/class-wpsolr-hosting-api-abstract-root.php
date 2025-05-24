<?php

namespace wpsolr\core\classes\hosting_api;

use wpsolr\core\classes\engines\solarium\admin\WPSOLR_Solr_Admin_Api_Core;
use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;

abstract class WPSOLR_Hosting_Api_Abstract_Root {

	public function is_show_create(): bool {
		return true;
	}

	protected static function _get_hosting_apis() {
		return [
			/** Solr */
			WPSOLR_Hosting_Api_Solr_None::class,
			WPSOLR_Hosting_Api_Solr_Opensolr_Free::class,
			/** SolrCloud */
			WPSOLR_Hosting_Api_Solrcloud_None::class,
			/** Elasticsearch */
			WPSOLR_Hosting_Api_Elasticsearch_None::class,
			/** OpenSearch */
			WPSOLR_Hosting_Api_OpenSearch_None::class,
			/** Weaviate */
			WPSOLR_Hosting_Api_Weaviate_None::class,
			/** Vespa */
			WPSOLR_Hosting_Api_Vespa_None::class,
			/** RediSearch */
			WPSOLR_Hosting_Api_RediSearch_None::class,
		];
	}

	protected static function _get_all_ui_fields() {

		return [
			WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH => [ self::FIELD_NAME_DEFAULT_API => WPSOLR_Hosting_Api_Elasticsearch_None::HOSTING_API_ID, ],
			WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH    => [ self::FIELD_NAME_DEFAULT_API => WPSOLR_Hosting_Api_OpenSearch_None::HOSTING_API_ID, ],
			WPSOLR_AbstractEngineClient::ENGINE_SOLR          => [ self::FIELD_NAME_DEFAULT_API => WPSOLR_Hosting_Api_Solr_None::HOSTING_API_ID ],
			WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD    => [ self::FIELD_NAME_DEFAULT_API => WPSOLR_Hosting_Api_Solrcloud_None::HOSTING_API_ID ],
			WPSOLR_AbstractEngineClient::ENGINE_WEAVIATE      => [ self::FIELD_NAME_DEFAULT_API => WPSOLR_Hosting_Api_Weaviate_None::HOSTING_API_ID ],
			WPSOLR_AbstractEngineClient::ENGINE_VESPA         => [ self::FIELD_NAME_DEFAULT_API => WPSOLR_Hosting_Api_Vespa_None::HOSTING_API_ID ],
		];
	}

	const HOSTING_API_ID = 'Hosting API to be defined';

	const NONE_LABEL = 'No hosting service. I installed my own local %s server.';

	const DATA_HOST_BY_REGION_ID = 'DATA_HOST_BY_REGION_ID';
	const DATA_PATH = 'DATA_PATH';
	const DATA_PORT = 'DATA_PORT';
	const DATA_SCHEME = 'DATA_SCHEME';
	const DATA_REGION_LABEL_BY_REGION_ID = 'DATA_REGION_LABEL_BY_REGION_ID';
	const DATA_INDEX_LABEL = 'DATA_INDEX_LABEL';

	/**
	 * Field names used in javascript
	 */
	const FIELD_NAME_ENGINES = 'engines';
	const FIELD_NAME_HOSTING_APIS = 'hosting_apis';
	const FIELD_NAME_DEFAULT_API = 'default_api';
	const FIELD_NAME_ENGINE = 'engine';
	const FIELD_NAME_URL = 'url';
	const FIELD_NAME_DOCUMENTATION_URL = 'documentation_url';
	const FIELD_NAME_LABEL = 'label';
	const FIELD_NAME_DEFAULT_VALUE = 'default';
	const FIELD_NAME_FIELDS = 'fields';
	const FIELD_NAME_PLACEHOLDER = 'placeholder';
	const FIELD_NAME_FORMAT_ERROR_LABEL = 'wpsolr_err';
	const FIELD_NAME_FORMAT = 'format';
	const FIELD_NAME_FORMAT_TYPE = 'format_type';
	const FIELD_NAME_FORMAT_TYPE_MANDATORY = 'format_type_mandatory';
	const FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY = 'format_type_not_mandatory';
	const FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_2_DIGITS = 'format_type_integer_2_digits';
	const FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_POSITIVE = 'format_type_integer_positive';
	const FIELD_NAME_FORMAT_ERROR_LABEL_MANDATORY = '';
	const FIELD_NAME_FORMAT_TYPE_READONLY = 'format_type_readonly';
	const FIELD_NAME_FORMAT_IS_CREATE_ONLY = 'format_is_create_only';
	const FIELD_NAME_FORMAT_IS_UPDATE_ONLY = 'format_is_update_only';
	const FIELD_NAME_ENGINE_ANALYSERS = 'engine_analysers';

	const FIELD_NAME_FIELDS_INDEX_NAME = 'index_name';
	const FIELD_NAME_FIELDS_INDEX_NAME_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_NAME => [
			self::FIELD_NAME_LABEL                 => 'WPSOLR internal name',
			self::FIELD_NAME_PLACEHOLDER           => 'Give a name to your index',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a WPSOLR internal name',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_LABEL = 'index_label';
	const FIELD_NAME_FIELDS_INDEX_LABEL_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_LABEL => [
			self::FIELD_NAME_LABEL                 => 'Index name',
			self::FIELD_NAME_PLACEHOLDER           => 'Index name inside the search engine server, like "my_index". Only characters and "_", no white spaces.',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a server index label',
			],
		]
	];

	const FIELD_NAME_FIELDS_INDEX_EMAIL = 'index_email';
	const FIELD_NAME_FIELDS_INDEX_EMAIL_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_EMAIL => [
			self::FIELD_NAME_LABEL                 => 'E-mail',
			self::FIELD_NAME_PLACEHOLDER           => 'Your account E-mail',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your E-mail',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_API_KEY_1 = 'index_api_key_1';
	const FIELD_NAME_FIELDS_INDEX_API_KEY = 'index_api_key';
	const FIELD_NAME_FIELDS_INDEX_API_KEY_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_API_KEY => [
			self::FIELD_NAME_LABEL                 => 'API key',
			self::FIELD_NAME_PLACEHOLDER           => 'Your account API key',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your API key',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_REGION_ID = 'index_region_id';
	const FIELD_NAME_FIELDS_INDEX_REGION_ID_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_REGION_ID => [
			self::FIELD_NAME_LABEL                 => 'Region',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your region',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_CLIENT_ADAPTER = 'index_client_adapter';
	const FIELD_NAME_FIELDS_INDEX_CLIENT_ADAPTER_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_CLIENT_ADAPTER => [
			self::FIELD_NAME_LABEL                 => 'Client adapter',
			self::FIELD_NAME_DEFAULT_VALUE         => 'http',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		]
	];
	const FIELD_NAME_FIELDS_INDEX_PROTOCOL = 'index_protocol';
	const FIELD_NAME_FIELDS_INDEX_PROTOCOL_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_PROTOCOL => [
			self::FIELD_NAME_LABEL                 => 'Scheme',
			self::FIELD_NAME_DEFAULT_VALUE         => 'http',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_HOST = 'index_host';
	const FIELD_NAME_FIELDS_INDEX_HOST_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_HOST => [
			self::FIELD_NAME_LABEL                 => 'Host',
			self::FIELD_NAME_PLACEHOLDER           => "localhost or ip adress or hostname. No 'http', no '/', no ':'",
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'an index host',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_ENDPOINT = 'index_endpoint';
	const FIELD_NAME_FIELDS_INDEX_ENDPOINT_1 = 'index_endpoint_1';
	const FIELD_NAME_FIELDS_INDEX_ENDPOINT_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_ENDPOINT => [
			self::FIELD_NAME_LABEL                 => 'Endpoint URL',
			self::FIELD_NAME_PLACEHOLDER           => 'Copy your endpoint URL here',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_COPY . 'your endpoint URL here',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_PORT = 'index_port';
	const FIELD_NAME_FIELDS_INDEX_PORT_SOLR_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_PORT => [
			self::FIELD_NAME_LABEL                 => 'Port',
			self::FIELD_NAME_PLACEHOLDER           => '8983 is the default port with http. Or 443 with https. Or any other port.',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_2_DIGITS,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a valid port',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];
	const FIELD_NAME_FIELDS_INDEX_PORT_ELASTICSEARCH_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_PORT => [
			self::FIELD_NAME_LABEL                 => 'Port',
			self::FIELD_NAME_PLACEHOLDER           => '9200 is the default port with http. Or 443 with https. Or any other port.',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_2_DIGITS,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a valid port',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_INDEX_PATH = 'index_path';
	const FIELD_NAME_FIELDS_INDEX_PATH_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_PATH => [
			self::FIELD_NAME_LABEL                 => 'Path',
			self::FIELD_NAME_PLACEHOLDER           => 'For instance /solr/index_name. Begins with \'/\', no \'/\' at the end',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a path for your index',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_SHARDS = 'index_elasticsearch_shards';
	const FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_SHARDS_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_SHARDS => [
			self::FIELD_NAME_LABEL                 => 'Number of shards',
			self::FIELD_NAME_PLACEHOLDER           => 'Number of shards',
			self::FIELD_NAME_DEFAULT_VALUE         => '1',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_POSITIVE,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a number of shards > 0',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_REPLICAS = 'index_elasticsearch_replicas';
	const FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_REPLICAS_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_ELASTICSEARCH_REPLICAS => [
			self::FIELD_NAME_LABEL                 => 'Number of replicas',
			self::FIELD_NAME_PLACEHOLDER           => 'Number of replicas',
			self::FIELD_NAME_DEFAULT_VALUE         => '0',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS = 'index_opensearch_shards';
	const FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS => [
			self::FIELD_NAME_LABEL                 => 'Number of shards',
			self::FIELD_NAME_PLACEHOLDER           => 'Number of shards',
			self::FIELD_NAME_DEFAULT_VALUE         => '1',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_POSITIVE,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a number of shards > 0',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS = 'index_opensearch_replicas';
	const FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS => [
			self::FIELD_NAME_LABEL                 => 'Number of replicas',
			self::FIELD_NAME_PLACEHOLDER           => 'Number of replicas',
			self::FIELD_NAME_DEFAULT_VALUE         => '0',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_SHARDS = 'index_solr_cloud_shards';
	const FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_SHARDS_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_SHARDS => [
			self::FIELD_NAME_LABEL                 => 'Number of shards',
			self::FIELD_NAME_PLACEHOLDER           => 'Number of shards',
			self::FIELD_NAME_DEFAULT_VALUE         => '1',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_POSITIVE,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a number of shards > 0',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_REPLICATION_FACTOR = 'index_solr_cloud_replication_factor';
	const FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_REPLICATION_FACTOR_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_REPLICATION_FACTOR => [
			self::FIELD_NAME_LABEL                 => 'Replication factor',
			self::FIELD_NAME_PLACEHOLDER           => 'Replication factor',
			self::FIELD_NAME_DEFAULT_VALUE         => '1', // 0 not authorized
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_MAX_SHARDS_NODE = 'index_solr_cloud_max_shards_node';
	const FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_MAX_SHARDS_NODE_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_SOLR_CLOUD_MAX_SHARDS_NODE => [
			self::FIELD_NAME_LABEL                 => 'Max shards per node',
			self::FIELD_NAME_PLACEHOLDER           => 'Max shards per node',
			self::FIELD_NAME_DEFAULT_VALUE         => '1',
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_POSITIVE,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a maximum shards per node > 0',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		]
	];

	const FIELD_NAME_FIELDS_INDEX_KEY = 'index_key';
	const FIELD_NAME_FIELDS_INDEX_KEY_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_KEY => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_LABEL                 => 'Key',
			self::FIELD_NAME_PLACEHOLDER           => 'Optional security user if the index is protected with Http Basic Authentication',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_INDEX_SECRET = 'index_secret';
	const FIELD_NAME_FIELDS_INDEX_SECRET_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_SECRET => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_LABEL                 => 'Secret/Password',
			self::FIELD_NAME_PLACEHOLDER           => 'Optional security password if the index is protected with Http Basic Authentication',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_INDEX_AWS_ACCESS_KEY_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_KEY => [
			self::FIELD_NAME_LABEL                 => 'AWS access key',
			self::FIELD_NAME_PLACEHOLDER           => 'AWS access key here',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'an AWS access key',
			],
		],
	];

	const FIELD_NAME_FIELDS_INDEX_AWS_SECRET_ACCESS_KEY_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_SECRET => [
			self::FIELD_NAME_LABEL                 => 'AWS secret access key',
			self::FIELD_NAME_PLACEHOLDER           => 'AWS secret access key here',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'an AWS secret access key'
			],
		],
	];
	const FIELD_NAME_FIELDS_INDEX_AWS_REGION = 'index_aws_region';
	const FIELD_NAME_FIELDS_INDEX_AWS_REGION_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_AWS_REGION => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your AWS Region here',
			],
			self::FIELD_NAME_LABEL                 => 'AWS Region',
			self::FIELD_NAME_PLACEHOLDER           => 'AWS Region',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_INDEX_LANGUAGE_CODE = 'index_language_code';
	const FIELD_NAME_FIELDS_INDEX_LANGUAGE_CODE_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_LANGUAGE_CODE => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_LABEL                 => 'Language',
			self::FIELD_NAME_PLACEHOLDER           => 'Optimizations for a specific language will be applied to your data.',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		],
	];

	const FIELD_NAME_FIELDS_INDEX_TOKEN = 'index_token';
	const FIELD_NAME_FIELDS_INDEX_TOKEN_DEFAULT = [
		self::FIELD_NAME_FIELDS_INDEX_TOKEN => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_READONLY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your token here',
			],
			self::FIELD_NAME_LABEL                 => 'Last Token ID generated from your credentials',
			self::FIELD_NAME_PLACEHOLDER           => 'No token yet. A token is created and cached at first usage of the index (indexing or querying)',
			self::FIELD_NAME_FORMAT_IS_UPDATE_ONLY => true,
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
		],
	];

	const FIELD_NAME_FIELDS_INDEX_PUBLIC_KEY = 'index_public_key';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL = 'index_weaviate_openai_config_model';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL_VERSION = 'index_weaviate_openai_config_model_version';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_TYPE = 'index_weaviate_openai_config_type';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL_QNA = 'index_weaviate_openai_config_model_qna';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_MODEL_VERSION_QNA = 'index_weaviate_openai_config_model_version_qna';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_OPENAI_CONFIG_TYPE_QNA = 'index_weaviate_openai_config_type_qna';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_HUGGINGFACE_CONFIG_MODEL = 'index_weaviate_huggingface_config_model';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_HUGGINGFACE_CONFIG_MODEL_QUERY = 'index_weaviate_huggingface_config_model_query';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_COHERE_CONFIG_MODEL = 'index_weaviate_cohere_config_model';
	const FIELD_NAME_FIELDS_INDEX_WEAVIATE_GOOGLE_PALM_CONFIG_MODEL = 'index_weaviate_palm_config_model';
	const FIELD_NAME_FIELDS_INDEX_KEY_JSON = 'index_key_json';
	const FIELD_NAME_FIELDS_INDEX_KEY_JSON_1 = 'index_key_json_1';
	const FIELD_NAME_FIELDS_INDEX_CATALOG_BRANCH = 'index_catalog_branch';


	const FIELD_NAME_FIELDS_INDEX_DATASET_GROUP_ARN = 'dataset_group_arn';
	const FIELD_NAME_FIELDS_INDEX_DATASET_ITEMS_ARN = 'dataset_items_arn';
	const FIELD_NAME_FIELDS_INDEX_DATASET_ITEM_INTERACTION_EVENTS_ARN = 'dataset_item_interaction_events_arn';
	const FIELD_NAME_FIELDS_INDEX_DATASET_USERS_ARN = 'dataset_users_arn';
	const FIELD_NAME_FIELDS_INDEX_DATASET_ACTIONS_ARN = 'dataset_actions_arn';
	const FIELD_NAME_FIELDS_INDEX_DATASET_USER_ACTION_INTERACTION_EVENTS_ARN = 'dataset_user_action_interaction_events_arn';

	const FIELD_NAME_FIELDS_VESPA_SERVICE_CONTENT_ID = 'vespa_service_content_id';
	const FIELD_NAME_FIELDS_VESPA_SERVICE_CONTAINER_ID = 'vespa_service_container_id';

	const FIELD_NAME_FIELDS_TEXT_VECTORIZER_ID = 'text_vectorizer_id';
	const FIELD_NAME_FIELDS_IMAGE_VECTORIZER_ID = 'image_vectorizer_id';

	const FIELD_NAME_FIELDS_CERT_1 = 'index_cert_1';
	const FIELD_NAME_FIELDS_CERT_1_DEFAULT = [
		self::FIELD_NAME_FIELDS_CERT_1 => [
			self::FIELD_NAME_LABEL                 => 'Self-signed node certificate<br><br>(.pem file generated and stored in WP home directory)',
			self::FIELD_NAME_PLACEHOLDER           => 'Self-signed node certificate',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your self-signed node certificate',
			],
		],
	];

	const FIELD_NAME_RELATED_INDEX_UUID = 'index_related_index_uuid';
	const FIELD_NAME_RELATED_INDEX_UUID_DEFAULT = [
		self::FIELD_NAME_RELATED_INDEX_UUID => [
			self::FIELD_NAME_LABEL                 => 'Followed by connector',
			self::FIELD_NAME_PLACEHOLDER           => 'Followed by connector',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'the following connector',
			],
		], // Field not required: hardcoded on the page settings with a drop down
	];

	const PLEASE_ENTER = 'Please enter ';
	const PLEASE_COPY = 'Please copy ';
	const ERROR_LABEL_EMPTY = '';

	/*
	const FIELD_NAME_FIELDS_ = '';
	const _DEFAULT = [ self:: => [] ];
	*/


	/** @var array */
	protected static $hosting_apis, $hosting_apis_by_engine;
	/** @var array */
	protected static $all_ui_fields, $all_ui_fields_flatten;

	/**
	 * Return which UI fields are displayed for each hosting api ID
	 *
	 * @return array
	 */
	static function get_all_ui_fields() {

		if ( ! isset( static::$all_ui_fields ) ) {

			static::$all_ui_fields[ self::FIELD_NAME_ENGINES ] = static::_get_all_ui_fields();

			foreach ( static::$all_ui_fields[ self::FIELD_NAME_ENGINES ] as $search_engine_id => &$search_engine_def ) {
				// Add the analysers to each engine
				$search_engine_def[ self::FIELD_NAME_ENGINE_ANALYSERS ] = WPSOLR_AbstractEngineClient::get_search_engine_type_analysers( $search_engine_id );
			}


			foreach ( self::get_hosting_apis() as $hosting_api ) {

				static::$all_ui_fields[ self::FIELD_NAME_HOSTING_APIS ][ $hosting_api->get_id() ] = $hosting_api->_get_ui_fields();
			}

		}

		return static::$all_ui_fields;
	}

	/**
	 * @return array
	 */
	static function get_all_ui_fields_flatten() {

		if ( ! isset( static::$all_ui_fields_flatten ) ) {
			$all_fields = self::get_all_ui_fields();

			foreach ( $all_fields[ self::FIELD_NAME_HOSTING_APIS ] as $hosting_api_id => $hosting_api_def ) {
				foreach ( $hosting_api_def[ self::FIELD_NAME_FIELDS ] as $field_label => $field_def ) {

					static::$all_ui_fields_flatten[] = [ $field_label => $field_def ];
				}
			}
		}

		return static::$all_ui_fields_flatten;
	}

	protected function _get_ui_fields() {
		$result = [];

		// Add the engine
		$result [ self::FIELD_NAME_ENGINE ] = $this->get_search_engine();

		// Add the name
		$result [ self::FIELD_NAME_LABEL ] = $this->get_label();

		// Add the url
		$result [ self::FIELD_NAME_URL ] = $this->get_url();

		// Add the documentation url
		$result [ self::FIELD_NAME_DOCUMENTATION_URL ] = $this->get_documentation_url();

		// Add the child fields
		$fields_child         = array_merge( [ static::FIELD_NAME_FIELDS_INDEX_NAME_DEFAULT ], $this->get_ui_fields_child() ); // Field name is always there
		$fields_child_flatten = [];
		foreach ( $fields_child as $fields ) {
			$fields_child_flatten[ key( $fields ) ] = $fields[ key( $fields ) ];
		}
		$result[ self::FIELD_NAME_FIELDS ] = $fields_child_flatten;

		return $result;
	}

	/**
	 * @return array
	 */
	public function get_ui_fields_child() {
		return [];
	}

	/**
	 * @return WPSOLR_Hosting_Api_Abstract[]
	 */
	static function get_hosting_apis() {

		if ( ! isset( static::$hosting_apis ) ) {
			$hosting_apis_by_name_asc = [];
			foreach ( static::_get_hosting_apis() as $hosting_api_class ) {
				/** @var WPSOLR_Hosting_Api_Abstract $object */
				$object                                                               = new $hosting_api_class();
				$hosting_apis_by_name_asc[ $object->get_label() . $object->get_id() ] = $object;
			}


			foreach ( $hosting_apis_by_name_asc as $label => $object ) {
				static::$hosting_apis[] = $object;
			}
		}

		return static::$hosting_apis;
	}

	/**
	 * @param string $search_engine
	 *
	 * @return array
	 */
	static function get_hosting_apis_by_engine( $search_engine ) {

		if ( ! isset( static::$hosting_apis_by_engine ) ) {

			$hosting_apis = self::get_hosting_apis();

			static::$hosting_apis_by_engine = [];
			foreach ( $hosting_apis as $hosting_api ) {
				if ( $search_engine === $hosting_api->get_search_engine() ) {

					static::$hosting_apis_by_engine[ $search_engine ] = $hosting_api;
				}
			}

		}

		return static::$hosting_apis_by_engine;
	}

	static function get_hosting_apis_id_by_engine( $search_engine ) {

		$results = [];
		foreach ( self::get_hosting_apis_by_engine( $search_engine ) as $hosting_api ) {
			$results[] = $hosting_api->get_id();
		}

		return $results;
	}

	/**
	 * @param string $hosting_api_id
	 *
	 * @param string $search_engine
	 *
	 * @return WPSOLR_Hosting_Api_Abstract
	 * @throws \Exception
	 */
	static function get_hosting_api_by_id( $hosting_api_id, $search_engine = WPSOLR_AbstractEngineClient::ENGINE_SOLR ) {

		$hosting_apis = self::get_hosting_apis();

		if ( empty( $search_engine ) ) {
			$search_engine = WPSOLR_AbstractEngineClient::ENGINE_SOLR;
		}

		if ( empty( $hosting_api_id ) ) {
			// Old common empty api id for all engines. Now there is one empty hosting api per engine
			switch ( $search_engine ) {
				case WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH:
					$hosting_api_id = WPSOLR_Hosting_Api_Elasticsearch_None::HOSTING_API_ID;
					break;
				case WPSOLR_AbstractEngineClient::ENGINE_SOLR:
					$hosting_api_id = WPSOLR_Hosting_Api_Solr_None::HOSTING_API_ID;
					break;
				case WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD:
					$hosting_api_id = WPSOLR_Hosting_Api_Solrcloud_None::HOSTING_API_ID;
					break;
			}
		}

		foreach ( $hosting_apis as $hosting_api ) {
			if ( $hosting_api_id === $hosting_api->get_id() ) {
				return $hosting_api;
			}
		}

		// Not found
		throw new \Exception( sprintf( 'Hosting %s is undefined.', esc_html( $hosting_api_id ) ) );
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return static::HOSTING_API_ID;
	}

	/**
	 *
	 * @param string $hosting_api_id
	 * @param array $config
	 * @param \Solarium\Client $search_engine_client
	 *
	 * @return WPSOLR_Solr_Admin_Api_Core
	 * @throws \Exception
	 */
	public static function new_solr_admin_api_by_id( $hosting_api_id, $config, $search_engine_client ) {

		$hosting_api = self::get_hosting_api_by_id( $hosting_api_id );

		return $hosting_api->new_solr_admin_api( $config, $search_engine_client );
	}

	/**
	 * @param string $host
	 *
	 * @return string
	 */
	public function get_host( $host ) {
		return $host;
	}


	/**
	 * @param string $label
	 * @param string|array $id
	 * @param string $default
	 *
	 * @return string
	 */
	public function get_data_by_id( $label, $id, $default ) {
		return $default;
	}

	/**
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * @return string
	 */
	public function get_url() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_latest_version() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_incompatibility_reason() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_documentation_url() {
		return $this->get_is_engine_elasticsearch()
			? 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/install-elasticsearch/'
			: 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/install-apache-solr/';
	}

	/**
	 * @return array
	 */
	public function get_credentials() {
		return [];
	}

	/**
	 * @return string
	 */
	abstract public function get_search_engine();

	/**
	 * @return bool
	 */
	public function get_is_engine_elasticsearch() {
		return ( WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH === $this->get_search_engine() );
	}

	/**
	 * @return bool
	 */
	public function get_is_engine_opensearch() {
		return ( WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH === $this->get_search_engine() );
	}

	/**
	 * @return bool
	 */
	public function get_is_engine_solr() {
		return ( WPSOLR_AbstractEngineClient::ENGINE_SOLR === $this->get_search_engine() );
	}

	/**
	 * @return bool
	 */
	public function get_is_engine_solrcloud() {
		return ( WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD === $this->get_search_engine() );
	}

	/**
	 * @return bool
	 */
	public function get_is_disabled() {
		return false;
	}

	public function get_server_files( array $index ): array {
		return [];
	}

	/**
	 *
	 * @param array $config
	 * @param \Solarium\Client $search_engine_client
	 *
	 * @return WPSOLR_Solr_Admin_Api_Core
	 */
	protected function new_solr_admin_api( $config, $search_engine_client ) {

		return new WPSOLR_Solr_Admin_Api_Core( $config, $search_engine_client );
	}

	/**
	 * Key/password is extracted from endpoint only during creation phase.
	 *
	 * @return bool
	 */
	public function get_is_endpoint_only() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function get_is_no_hosting() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function get_has_search() {
		return WPSOLR_AbstractEngineClient::get_engines_definitions()[ $this->get_search_engine() ]['has_search'] ?? true;
	}


	/**
	 * @return bool
	 */
	public function get_has_recommendation() {
		return WPSOLR_AbstractEngineClient::get_engines_definitions()[ $this->get_search_engine() ]['has_recommendations'] ?? false;
	}

	/**
	 * @return bool
	 */
	public function get_has_indexing() {
		return true;
	}

	/**
	 * Escape index label characters
	 *
	 * @param $index_label
	 *
	 * @return string|string[]|null
	 */
	protected function escape_index_label( $index_label ) {
		return trim( strtolower( preg_replace( '#[^\w]#', '', $index_label ) ) );
	}

	/**
	 * @return bool
	 */
	public function get_is_host_contains_user_password() {
		return ( ! $this->get_is_engine_elasticsearch() && ! $this->get_is_engine_opensearch() ); // By default solarium needs it, and elasticsearch-php not
	}

	/**
	 * Generate the url of the current engine's account dashboard menu
	 *
	 * @param array $current_index
	 * @param string $menu
	 *
	 * @return string
	 */
	public function get_account_dashboard_url( $current_index, $menu ) {
		return '';
	}

}
