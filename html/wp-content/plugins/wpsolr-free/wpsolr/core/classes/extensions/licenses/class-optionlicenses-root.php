<?php

namespace wpsolr\core\classes\extensions\licenses;

use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;

class OptionLicenses_Root extends WPSOLR_Extension {

	const LICENSE_EXTENSION = 'LICENSE_EXTENSION';

	// Ajax methods
	const AJAX_ACTIVATE_LICENCE = 'ajax_activate_licence';
	const AJAX_DEACTIVATE_LICENCE = 'ajax_deactivate_licence';
	const AJAX_VERIFY_LICENCE = 'ajax_verify_licence';

	// License types
	const LICENSE_PACKAGE_PREMIUM = 'LICENSE_PACKAGE_CORE';

	// License type fields
	const FIELD_LICENSE_SUBSCRIPTION_NUMBER = 'license_subscription_number';
	const FIELD_LICENSE_PACKAGE = 'license_package';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_IS_ACTIVATED = 'is_activated';
	const FIELD_ORDERS_URLS = 'orders_urls';
	const FIELD_ORDER_URL_BUTTON_LABEL = 'order_url_button_label';
	const FIELD_ORDER_URL_TEXT = 'order_url_text';
	const FIELD_ORDER_URL_LINK = 'order_url_link';
	const FIELD_FEATURES = 'features';
	const FIELD_LICENSE_TITLE = 'LICENSE_TITLE';
	const FIELD_LICENSE_ENGINE = 'LICENSE_ENGINE';
	const FIELD_LICENSE_MATCHING_REFERENCE = 'matching_license_reference';
	const FIELD_NEEDS_VERIFICATION = 'needs_verification';
	const FIELD_LICENSE_ACTIVATION_UUID = 'activation_uuid';
	const FIELD_LICENSE_EXPIRATION_DATE = 'expiration_date';
	const FIELD_LICENSE_HASH = 'hash';
	const FIELD_LICENSE_DOMAIN = 'domain';
	const FIELD_LICENSE_PACKAGES = 'packages';
	// Texts
	const TEXT_LICENSE_ACTIVATED = 'License is activated';
	const TEXT_LICENSE_DEACTIVATED = 'License is not activated. Click to activate.';
	const WPSOLR_PACKAGE_MULTI = 'wpsolr_package_multi';

	public $is_installed;
	private $_options;

	// Order link
	const FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE = '7 days free trial (Premium pack only)';
	const ORDER_LINK_URL_BESPOKE = 'https://www.wpsolr.com/pricing/';
	const FIELD_ORDER_URL_BUTTON_LABEL_ALL_INCLUDED = 'Buy the %s plugin';
	const ORDER_LINK_URL_ALL_INCLUDED = 'https://www.wpsolr.com/pricing/';
	const FIELD_ORDER_URL_BUTTON_LABEL_MANAGED = 'In a hurry ? We manage WPSOLR and Solr for you';
	const ORDER_LINK_URL_MANAGED = 'https://secure.avangate.com/order/checkout.php?PRODS=4701516&QTY=1';

	// Features
	const FEATURE_FORUMS_SUPPORT = 'Get support via WPSOLR forums';
	const FEATURE_TRELLO_SUPPORT = 'Get support via a dedicated Trello board';
	const FEATURE_FREE_UPGRADE_ONE_YEAR = 'Get free upgrades during one year';
	const LICENSE_API_URL = 'https://api.wpsolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/b553e78c-3af8-4c97-9157-db77bfa6d909/license-manager/83e214e6-54f8-4f59-ba95-889de756ebee/licenses';

	/**
	 * API field names
	 */
	const LICENSE_EXPIRATION_DATE = 'expirationDate';
	const LICENSE_HASH = 'hash';

	/**
	 * Constructor.
	 */

	function __construct() {
		$this->_options     = static::get_option_data( static::OPTION_LICENSES, [] );
		$this->is_installed = true;
	}

	public static function get_plugins_tabs() {
		return [
			'extension_premium_opt' => [
				'name'  => '>> Premium',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_PREMIUM, WPSOLR_Extension::EXTENSION_PREMIUM ),
			],
		];
	}

	public static function get_themes_tabs() {
		return [];
	}

	/**
	 * Return all activated licenses
	 */
	function get_licenses() {
		$results = $this->_options;

		return $results;
	}


	/**
	 * Upgrade all licenses
	 */
	static function upgrade_licenses() {

		// Upgrade licenses
		$licenses = static::get_option_data( static::OPTION_LICENSES, [] );

		if ( ! empty( $licenses ) ) {

			foreach ( $licenses as $license_package => $license ) {

				$licenses[ $license_package ][ static::FIELD_NEEDS_VERIFICATION ] = true;
			}

			static::set_option_data( static::OPTION_LICENSES, $licenses );

		} else {

			// Installation
			WPSOLR_Service_Container::getOption()->get_option_installation();
		}

	}

	/**
	 * Get any license
	 */
	function get_any_license() {

		foreach ( $this->get_licenses() as $license_package_installed => $license ) {

			if ( ! empty( $license[ static::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] ) && ! empty( $license[ static::FIELD_LICENSE_ACTIVATION_UUID ] ) ) {
				return $license[ static::FIELD_LICENSE_SUBSCRIPTION_NUMBER ];
			}
		}

		return '';
	}

	/**
	 * Is a license activated ?
	 */
	function get_license_is_activated( $license_type ) {

		/**
		 * If no license required, return all
		 */
		$domains             = [
			'localhost',
			'dev',
			'test',
			'staging',
			'cloudways',
			'kinsta',
			'wpengine',
			'pantheon',
			'local',
		];
		$current_admin_url   = WPSOLR_Sanitize::sanitize_text_field( $_SERVER, [ 'HTTP_HOST' ], '' );
		$test_domain_regexes = [];
		foreach ( $domains as $domain ) {
			if ( 'localhost' === $domain ) {
				$test_domain_regexes[] = $domain;
			} else {
				if ( ! ( ( 'local' === $domain ) && ( preg_match( '/github-actions/', $current_admin_url ) ) ) ) {
					$test_domain_regexes[] = '\\.' . $domain;
					$test_domain_regexes[] = $domain . '\\.';
				}
			}
		}
		foreach ( $test_domain_regexes as $regexp ) {
			if ( preg_match( sprintf( '/%s/', $regexp ), $current_admin_url ) ) {
				// It is a test domain. Authorize it.
				return true;
			}
		}


		$licenses = $this->get_licenses();

		return ( isset( $licenses[ $license_type ] )
		         && isset( $licenses[ $license_type ][ static::FIELD_IS_ACTIVATED ] )
		         && ! isset( $licenses[ $license_type ][ static::FIELD_NEEDS_VERIFICATION ] ) );
	}

	/**
	 * Get a license
	 */
	function get_license( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] ) ? $licenses[ $license_type ] : [];
	}

	/**
	 * Is a license need to be verified ?
	 */
	function get_license_is_need_verification( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] )
		       && isset( $licenses[ $license_type ][ static::FIELD_IS_ACTIVATED ] )//&& isset( $licenses[ $license_type ][ static::FIELD_NEEDS_VERIFICATION ] )
			;
	}

	/**
	 * Is a license can be deactivated ?
	 */
	function get_license_is_can_be_deactivated( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] )
		       && isset( $licenses[ $license_type ][ static::FIELD_IS_ACTIVATED ] );
	}


	/**
	 * Get licanse activation api url
	 */
	static function get_license_api_url() {

		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_ENV_LICENSE_API_URL, static::LICENSE_API_URL );
	}

	/**
	 * Return all license types
	 */
	static function get_license_types() {

		return [
			static::LICENSE_PACKAGE_PREMIUM => [
				static::LICENSE_EXTENSION                => WPSOLR_Extension::EXTENSION_PREMIUM,
				static::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_premium',
				static::FIELD_LICENSE_TITLE              => 'Premium',
				static::FIELD_DESCRIPTION                => '',
				static::FIELD_ORDERS_URLS                => [
					[
						static::FIELD_ORDER_URL_BUTTON_LABEL => static::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						static::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						static::FIELD_ORDER_URL_LINK         => static::ORDER_LINK_URL_BESPOKE,
					],
				],
				static::FIELD_FEATURES                   => [
					static::FEATURE_FORUMS_SUPPORT,
					static::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Create a test Solr index, valid 2 hours',
					'Configure several Solr indexes',
					'Select your theme search page',
					'Select Infinite Scroll navigation in Ajax search',
					'Display suggestions (Did you mean?)',
					'Index custom post types',
					'Index attachments',
					'Index custom taxonomies',
					'Index custom fields',
					'Show facets hierarchies',
					'Localize (translate) the front search page with your .po files',
					'Display debug infos during processing',
					'Reindex all your data in-place',
					'Deactivate real-time indexing to load huge external datafeeds',
				],
			],
		];

	}


	/**
	 * Show premium link in place of a text if not licensed
	 *
	 * @param $is_add_extra_text
	 * @param $license_type
	 * @param $text_to_show
	 * @param $is_show_link
	 *
	 * @param bool $is_new_feature
	 *
	 * @return string
	 */
	function show_premium_link( $is_add_extra_text, $license_type, $text_to_show, $is_show_link, $is_new_feature = false ) {

		$img_url = plugins_url( 'images/warning.png', WPSOLR_PLUGIN_FILE );

		if ( ( ! $this->is_installed && ! $is_new_feature ) || $this->get_license_is_activated( $license_type ) ) {

			if ( ( ! $is_show_link ) || ( ! $this->is_installed && ! $is_new_feature ) ) {
				return ( ( static::TEXT_LICENSE_ACTIVATED === $text_to_show ) || ( false !== strpos( $text_to_show, 'manage your license' ) ) ) ? '' : $text_to_show;
			}

			$img_url = plugins_url( 'images/success.png', WPSOLR_PLUGIN_FILE );

		} else if ( $is_add_extra_text ) {

			$img_url      = plugins_url( 'images/warning.png', WPSOLR_PLUGIN_FILE );
			$text_to_show .= '<p>(Feature-limited version, click to activate)</p>';
		}

		$result = sprintf(
			'<a href="%s" class="thickbox wpsolr_premium_class" ><img src="%s" class="wpsolr_premium_text_class" style="display:inline"><span>%s</span></a>',
			WPSOLR_Escape::esc_url( sprintf( '#TB_inline?width=800&height=700&inlineId=%s', $license_type ) ),
			WPSOLR_Escape::esc_url( $img_url ),
			WPSOLR_Escape::esc_escaped( $text_to_show )
		);

		return $result;
	}

	/**
	 * Output a disable html code if not licensed
	 *
	 * @param $license_type
	 *
	 * @param bool $is_new_feature
	 *
	 * @return string
	 */
	function get_license_enable_html_code( $license_type, $is_new_feature = false ) {

		return ( ( ! $this->is_installed && ! $is_new_feature ) || $this->get_license_is_activated( $license_type ) ) ? '' : 'disabled';
	}


	/**
	 * Output a readonly html code if not licensed
	 *
	 * @param $license_type
	 *
	 * @param bool $is_new_feature
	 *
	 * @return string
	 */
	function get_license_readonly_html_code( $license_type, $is_new_feature = false ) {

		return ( ( ! $this->is_installed && ! $is_new_feature ) || $this->get_license_is_activated( $license_type ) ) ? '' : 'readonly';
	}

	/**
	 * Get a license type order urls
	 * @return mixed
	 */
	public
	function get_license_orders_urls(
		$license_type
	) {
		$license_types = $this->get_license_types();

		//return $license_types[ $license_type ][ static::FIELD_ORDERS_URLS ];

		return array(
			array(
				static::FIELD_ORDER_URL_BUTTON_LABEL => sprintf( static::FIELD_ORDER_URL_BUTTON_LABEL_ALL_INCLUDED, WPSOLR_PLUGIN_SHORT_NAME ),
				static::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
				static::FIELD_ORDER_URL_LINK         => $this->add_campaign_to_url( static::ORDER_LINK_URL_ALL_INCLUDED ),
			),/*
			array(
				static::FIELD_ORDER_URL_BUTTON_LABEL => static::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
				static::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
				static::FIELD_ORDER_URL_LINK         => $this->add_campaign_to_url( static::ORDER_LINK_URL_BESPOKE ),
			),
			array(
				static::FIELD_ORDER_URL_BUTTON_LABEL => static::FIELD_ORDER_URL_BUTTON_LABEL_MANAGED,
				static::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
				static::FIELD_ORDER_URL_LINK         => $this->add_campaign_to_url( static::ORDER_LINK_URL_MANAGED ),
			),*/
		);

	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function add_campaign_to_url( $url ) {

		return sprintf( '%s%sutm_source=plugin_wpsolr&wpsolr_v=%s&wpsolr_plan=%s', $url, ( false === strpos( $url, '?' ) ) ? '?' : '&', WPSOLR_PLUGIN_VERSION, WPSOLR_PLUGIN_SHORT_NAME );
	}

	/**
	 * Get a license matching reference
	 * @return mixed
	 */
	public
	function get_license_matching_reference(
		$license_type
	) {
		$license_types = $this->get_license_types();

		return $license_types[ $license_type ][ static::FIELD_LICENSE_MATCHING_REFERENCE ];
	}

	/**
	 * Get a license activation uuid
	 * @return string
	 */
	public
	function get_license_activation_uuid(
		$license_type
	) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ][ static::FIELD_LICENSE_ACTIVATION_UUID ] ) ? $licenses[ $license_type ][ static::FIELD_LICENSE_ACTIVATION_UUID ] : '';
	}

	/**
	 * Get a license subscription number
	 * @return string
	 */
	public
	function get_license_subscription_number(
		$license_type
	) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ][ static::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] ) ? $licenses[ $license_type ][ static::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] : '';
	}

	/**
	 * Get a license type features
	 * @return mixed
	 */
	public
	function get_license_features(
		$license_type
	) {
		$license_types = $this->get_license_types();

		return $license_types[ $license_type ][ static::FIELD_FEATURES ];
	}

	/**
	 * Get a license expiry date, domain, and hash
	 * @return string[]
	 */
	static public function get_license_activation_infos() {

		$result = [];

		$licenses = static::get_option_data( static::OPTION_LICENSES, [] );
		if ( ! empty( $licenses ) ) {
			$active_licenses = [];
			foreach ( $licenses as $license_code => $license ) {

				if ( $license[ static::FIELD_IS_ACTIVATED ] ?? false ) {
					$active_licenses[] = $license_code;
				}
			}

			foreach ( $licenses as $license ) {

				if ( ! empty( $license[ static::FIELD_LICENSE_EXPIRATION_DATE ] ) && ! empty( $license[ static::FIELD_LICENSE_HASH ] ) ) {
					$result[ static::FIELD_LICENSE_EXPIRATION_DATE ] = $license[ static::FIELD_LICENSE_EXPIRATION_DATE ];
					$result[ static::FIELD_LICENSE_HASH ]            = $license[ static::FIELD_LICENSE_HASH ];
					$result[ static::FIELD_LICENSE_DOMAIN ]          = home_url();
					$result[ static::FIELD_LICENSE_PACKAGES ]        = $active_licenses;

					// Use the first license only.
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Ajax call to activate a license
	 */
	public
	static function ajax_activate_licence() {

		if ( isset( $_POST['data'] ) && isset( $_POST['data']['security'] ) &&
		     wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [
			     'data',
			     'security'
		     ] ), WPSOLR_NONCE_FOR_DASHBOARD ) ) {

			$subscription_number        = isset( $_POST['data'] ) && isset( $_POST['data'][ static::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] ) ?
				WPSOLR_Sanitize::sanitize_text_field( $_POST, [
					'data',
					static::FIELD_LICENSE_SUBSCRIPTION_NUMBER
				] ) : null;
			$license_package            = isset( $_POST['data'] ) && isset( $_POST['data'][ static::FIELD_LICENSE_PACKAGE ] ) ?
				WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'data', static::FIELD_LICENSE_PACKAGE ] ) : null;
			$license_matching_reference = isset( $_POST['data'] ) && isset( $_POST['data'][ static::FIELD_LICENSE_MATCHING_REFERENCE ] ) ?
				WPSOLR_Sanitize::sanitize_text_field( $_POST, [
					'data',
					static::FIELD_LICENSE_MATCHING_REFERENCE
				] ) : null;

			$managed_solr_server = new OptionManagedSolrServer();
			$response_object     = $managed_solr_server->call_rest_activate_license( static::get_license_api_url(), $license_matching_reference, $subscription_number );

			if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

				// Save the license type activation
				$licenses                     = static::get_option_data( static::OPTION_LICENSES, [] );
				$licenses[ $license_package ] = [
					static::FIELD_IS_ACTIVATED                => true,
					static::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $subscription_number,
					static::FIELD_LICENSE_ACTIVATION_UUID     => OptionManagedSolrServer::get_response_result( $response_object, 'uuid' ),
					static::FIELD_LICENSE_EXPIRATION_DATE     => OptionManagedSolrServer::get_response_result( $response_object, static::LICENSE_EXPIRATION_DATE ),
					static::FIELD_LICENSE_HASH                => OptionManagedSolrServer::get_response_result( $response_object, static::LICENSE_HASH ),
				];

				static::set_option_data( static::OPTION_LICENSES, $licenses );

			} else {

				$response_object = $managed_solr_server->call_rest_activate_license( static::get_license_api_url(), static::WPSOLR_PACKAGE_MULTI, $subscription_number );

				if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

					// Save the license type activation
					$licenses = static::get_option_data( static::OPTION_LICENSES, [] );
					foreach ( static::get_license_types() as $license_package => $license_definition ) {

						if ( ! empty( $license_definition[ static::FIELD_LICENSE_ENGINE ] ) ) {
							continue;
						}

						$licenses[ $license_package ] = [
							static::FIELD_IS_ACTIVATED                => true,
							static::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $subscription_number,
							static::FIELD_LICENSE_ACTIVATION_UUID     => OptionManagedSolrServer::get_response_result( $response_object, 'uuid' ),
							static::FIELD_LICENSE_EXPIRATION_DATE     => OptionManagedSolrServer::get_response_result( $response_object, static::LICENSE_EXPIRATION_DATE ),
							static::FIELD_LICENSE_HASH                => OptionManagedSolrServer::get_response_result( $response_object, static::LICENSE_HASH ),
						];
					}

					static::set_option_data( static::OPTION_LICENSES, $licenses );
				}
			}

			// Return the whole object
			WPSOLR_Escape::echo_esc_json( wp_json_encode( $response_object ) );

		}

		die();
	}

	/**
	 * Ajax call to deactivate a license
	 */
	public
	static function ajax_deactivate_licence() {

		if ( isset( $_POST['data'] ) && isset( $_POST['data']['security'] ) &&
		     wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [
			     'data',
			     'security'
		     ] ), WPSOLR_NONCE_FOR_DASHBOARD ) ) {

			$option_licenses = new OptionLicenses();
			$licenses        = $option_licenses->get_licenses();

			$license_package         = isset( $_POST['data'] ) && isset( $_POST['data'][ static::FIELD_LICENSE_PACKAGE ] ) ?
				WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'data', static::FIELD_LICENSE_PACKAGE ] ) : null;
			$license_activation_uuid = $option_licenses->get_license_activation_uuid( $license_package );

			if ( empty( $license_activation_uuid ) ) {

				$licenses[ $license_package ] = [
					static::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ static::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
				];
				static::set_option_data( static::OPTION_LICENSES, $licenses );

				WPSOLR_Escape::echo_esc_json( wp_json_encode( (object) array(
					'status' => (object) array(
						'state'   => 'ERROR',
						'message' => 'This license activation code is missing. Try to unactivate manually, by signin to your subscription account.'
					)
				) ) );

				die();
			}

			$managed_solr_server = new OptionManagedSolrServer();
			$response_object     = $managed_solr_server->call_rest_deactivate_license( static::get_license_api_url(), $license_activation_uuid );

			if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

			}

			// Always remove the activation, else we're stuck forever
			$licenses = static::get_option_data( static::OPTION_LICENSES, [] );
			foreach ( $licenses as $license_package_installed => $license ) {

				if ( $license_activation_uuid === $license[ static::FIELD_LICENSE_ACTIVATION_UUID ] ) {
					$licenses[ $license_package_installed ] = array(
						static::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ static::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
					);
				}
			}
			static::set_option_data( static::OPTION_LICENSES, $licenses );

			// Return the whole object
			WPSOLR_Escape::echo_esc_json( wp_json_encode( $response_object ) );

		}

		die();
	}

	/**
	 * Ajax call to verify a license
	 */
	public
	static function ajax_verify_licence() {

		if ( isset( $_POST['data'] ) && isset( $_POST['data']['security'] ) &&
		     wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [
			     'data',
			     'security'
		     ] ), WPSOLR_NONCE_FOR_DASHBOARD ) ) {

			$option_licenses = new OptionLicenses();
			$licenses        = $option_licenses->get_licenses();

			$license_package         = isset( $_POST['data'] ) && isset( $_POST['data'][ static::FIELD_LICENSE_PACKAGE ] ) ?
				WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'data', static::FIELD_LICENSE_PACKAGE ] ) : null;
			$license_activation_uuid = $option_licenses->get_license_activation_uuid( $license_package );

			if ( empty( $license_activation_uuid ) ) {

				$licenses[ $license_package ] = array(
					static::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ static::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
				);
				static::set_option_data( static::OPTION_LICENSES, $licenses );

				WPSOLR_Escape::echo_esc_json( wp_json_encode( (object) array(
					'status' => (object) array(
						'state'   => 'ERROR',
						'message' => 'This license activation code is missing. Try to unactivate manually, by signin to your subscription account.'
					)
				) ) );

				die();
			}

			$managed_solr_server = new OptionManagedSolrServer();
			$response_object     = $managed_solr_server->call_rest_verify_license( static::get_license_api_url(), $license_activation_uuid );

			if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

				// Current url is not the activated url: notify the user.
				$activated_url = OptionManagedSolrServer::get_response_result( $response_object, static::FIELD_LICENSE_DOMAIN );
				$current_url   = admin_url();

				if ( $activated_url !== $current_url ) {

					OptionManagedSolrServer::set_response_ko( $response_object );
					OptionManagedSolrServer::set_response_error_message( $response_object,
						sprintf( 'The license is already activated on site %s, while your current site is %s. <br><br>Deactivate your license before trying activating it again.', $activated_url, $current_url )
					);

				} else if ( isset( $licenses[ $license_package ] ) ) {

					// Remove the license type activation
					$licenses = static::get_option_data( static::OPTION_LICENSES, [] );
					foreach ( $licenses as $license_package_installed => $license ) {

						if ( $license_activation_uuid === $license[ static::FIELD_LICENSE_ACTIVATION_UUID ] ) {
							unset( $licenses[ $license_package_installed ][ static::FIELD_NEEDS_VERIFICATION ] );

							// Add license infos
							$licenses[ $license_package_installed ][ static::FIELD_LICENSE_EXPIRATION_DATE ] = OptionManagedSolrServer::get_response_result( $response_object, static::LICENSE_EXPIRATION_DATE );
							$licenses[ $license_package_installed ][ static::FIELD_LICENSE_HASH ]            = OptionManagedSolrServer::get_response_result( $response_object, static::LICENSE_HASH );
						}
					}
					static::set_option_data( static::OPTION_LICENSES, $licenses );
				}

			}

			// Return the whole object
			WPSOLR_Escape::echo_esc_json( wp_json_encode( $response_object ) );

		}

		die();
	}

	/**
	 * Get all activated licenses
	 *
	 * @return array
	 */
	public static function get_activated_licenses_links( $license_type = null ) {

		$results = [];

		$option_licenses = new OptionLicenses();
		$license_types   = $option_licenses->get_license_types();
		$licenses        = empty( $license_type ) ? $option_licenses->get_licenses() : [ $license_type => $option_licenses->get_license( $license_type ) ];

		foreach ( $licenses as $license_code => $license ) {

			if ( $option_licenses->get_license_is_activated( $license_code ) ) {

				if ( isset( $license_types[ $license_code ] )
				     && isset( $license_types[ $license_code ][ static::LICENSE_EXTENSION ] )
				     && ( WPSOLR_Extension::is_extension_option_activate( $license_types[ $license_code ][ static::LICENSE_EXTENSION ] ) )
				) {
					$license_link = $option_licenses->show_premium_link( true, $license_code, $option_licenses->get_license_title( $license_code ), true );
					array_push( $results, $license_link );
				}
			}
		}

		return $results;
	}


	/**
	 * Get a license title
	 *
	 * @param $license_code
	 *
	 * @return array
	 */
	public function get_license_title(
		$license_code
	) {

		$license_defs = static::get_license_types();

		return ! empty( $license_defs[ $license_code ] ) && ! empty( $license_defs[ $license_code ][ static::FIELD_LICENSE_TITLE ] ) ? $license_defs[ $license_code ][ static::FIELD_LICENSE_TITLE ] : $license_code;
	}

	/**
	 * Get a license engine
	 *
	 * @param $engine_id
	 *
	 * @return string
	 */
	public function get_license_with_engine(
		$engine_id
	) {

		foreach ( static::get_license_types() as $license_package => $license_definition ) {
			if ( $engine_id === ( $license_definition[ static::FIELD_LICENSE_ENGINE ] ?? '' ) ) {
				return $license_package;
			}
		}

		return '';
	}

}

