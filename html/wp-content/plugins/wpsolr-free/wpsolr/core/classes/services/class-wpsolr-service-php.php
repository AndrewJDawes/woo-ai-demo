<?php

namespace wpsolr\core\classes\services;


use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

/**
 * Class WPSOLR_Service_PHP
 * @package wpsolr\core\classes\services
 */
class WPSOLR_Service_PHP {

	/**
	 */
	public function do_exit() {
		exit();
	}

	/**
	 */
	public function do_die() {
		die();
	}

	/**
	 * @return string
	 */
	public function get_server_request_uri() {
		return WPSOLR_Sanitize::sanitize_request_uri();
	}

	/**
	 * @return string
	 */
	public function get_server_query_string() {
		return WPSOLR_Sanitize::sanitize_query_string();
	}

	/**
	 * @return array
	 */
	public function get_request() {
		return $_REQUEST;
	}
}