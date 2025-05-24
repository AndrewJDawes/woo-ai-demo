<?php

namespace wpsolr\core\classes\engines;

use wpsolr\core\classes\WpSolrSchema;

/**
 * Some common methods of all engine clients
 *
 */
trait WPSOLR_Client {

	/**
	 * Dos the search engine have an 'exists' filter?
	 * @return bool
	 */
	public function get_has_exists_filter(): bool {
		return true;
	}

	/**
	 * Hierarchy separator
	 * @return string
	 */
	static public function get_facet_hierarchy_separator(): string {
		return WpSolrSchema::FACET_HIERARCHY_SEPARATOR;
	}

}
