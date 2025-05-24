<?php

namespace wpsolr\core\classes\engines\redisearch_php;


class WPSOLR_Search_Redisearch_Client extends WPSOLR_Search_Algolia_Client {
	use WPSOLR_RediSearch_Client;

	/**
	 * Prepare query execute
	 */
	public function search_engine_client_pre_execute() {
		// TODO: Implement search_engine_client_pre_execute() method.
	}
}
