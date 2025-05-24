<?php

namespace wpsolr\core\classes\engines\opensearch_php;

use wpsolr\core\classes\engines\elasticsearch_php\WPSOLR_IndexElasticsearchClient;

class WPSOLR_IndexOpenSearchClient extends WPSOLR_IndexElasticSearchClient {
	use WPSOLR_OpenSearchClient;
}
