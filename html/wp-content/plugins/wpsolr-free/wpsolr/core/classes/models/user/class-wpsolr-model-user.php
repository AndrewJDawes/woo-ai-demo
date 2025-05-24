<?php

namespace wpsolr\core\classes\models\user;


use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\models\WPSOLR_Model_Abstract;

/**
 * Class WPSOLR_Model_User
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model_User extends WPSOLR_Model_Abstract {

	/** @var \WP_User */
	protected $data;

	/**
	 * @inheritdoc
	 */
	public function get_model_meta_type_class() {
		return WPSOLR_Model_Meta_Type_User::class;
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return $this->data->ID;
	}

	/**
	 * @inheritdoc
	 */
	function get_type() {
		return WPSOLR_Model_Meta_Type_User::TYPE;
	}

	/**
	 * @inheritdoc
	 */
	public function get_date_modified() {
		return $this->data->user_registered;
	}

	/**
	 * @inheritdoc
	 */
	public function create_document_from_model_or_attachment_inner( WPSOLR_AbstractIndexClient $search_engine_client, $attachment_body ) {
		// TODO: Implement create_document_from_model_or_attachment() method.
	}

	/**
	 * @inheritdoc
	 */
	protected function get_default_result_content() {
		return $this->data->description;
	}

	/**
	 * @inheritdoc
	 */
	public function get_permalink( $url_is_edit, $model ) {
		return '';
	}

	/**
	 * @inerhitDoc
	 */
	public function update_custom_field( string $name, $value ) {
		update_user_meta( $this->get_id(), $name, $value );
	}

	/**
	 * @inerhitDoc
	 */
	public function get_custom_field( string $name, bool $single = false ) {
		return get_user_meta( $this->get_id(), $name, $single );
	}
}