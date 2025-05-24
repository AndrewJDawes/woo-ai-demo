<?php

namespace wpsolr\core\classes\engines\configuration;

use wpsolr\core\classes\engines\configuration\builder\WPSOLR_Analyser_Abstract;
use wpsolr\core\classes\engines\configuration\builder\WPSOLR_Configuration_utils;

/**
 * Class WPSOLR_Configurations_Builder_Factory
 * @package wpsolr\core\classes\engines
 */
abstract class WPSOLR_Configurations_Builder_Factory {
	use WPSOLR_Configuration_utils;

	/** @var array $configurations */
	private static $configurations;

	const DIR_SOLR_ANALYSER = 'solr/analyser';
	const DIR_ELASTICSEARCH_ANALYSER = 'elasticsearch/analyser';
	const DIRS = [
		self::DIR_SOLR_ANALYSER,
		self::DIR_ELASTICSEARCH_ANALYSER,
	];

	const LABEL = 'label';
	const ANALYSER = 'analyser';

	/**
	 * Configuration ids
	 */
	const CONFIGURATION_ID_FRENCH_PORTER = 'fr_porter';
	const CONFIGURATION_ID_FRENCH_LIGHT = 'fr_light';
	const CONFIGURATION_ID_FRENCH_MINIMAL = 'fr_minimal';
	const CONFIGURATION_ID_GREEK = 'gr';

	/**
	 * Retrieve all configurations
	 *
	 * @return WPSOLR_Analyser_Abstract[]
	 * @throws \Exception
	 */
	public static function get_configurations() {

		if ( isset( self::$configurations ) ) {
			// Already done. Leave.
			return self::$configurations;
		}

		/** @var WPSOLR_Analyser_Abstract $analyser_class_name */
		foreach ( self::get_class_names() as $analyser_class_name ) {

			self::add_analyser( new $analyser_class_name() );
		}

		return self::$configurations;
	}

	/**
	 *
	 * @param WPSOLR_Analyser_Abstract $analyser
	 *
	 * @throws \Exception
	 */
	protected static function add_analyser( $analyser ) {

		if ( ! empty( self::$configurations[ $analyser->get_id() ] ) ) {
			throw new \Exception( sprintf( 'Duplicate configuration %s "%s"', esc_html( $analyser->get_id() ), esc_html( $analyser->get_label() ) ) );
		}

		self::$configurations[ $analyser->get_id() ] = $analyser;

	}

	/**
	 * Retrieve a configuration by id
	 *
	 * @param string $configuration_id
	 *
	 * @return WPSOLR_Analyser_Abstract
	 * @throws \Exception
	 */
	public static function get_configuration_by_id( $configuration_id ) {

		$configurations = static::get_configurations();

		if ( empty( $configurations[ $configuration_id ] ) ) {
			throw new \Exception( esc_html( "Index configuration {$$configuration_id} is unknown." ) );
		}

		return $configurations[ $configuration_id ];
	}

	/**
	 * Retrieve a configuration label by id
	 *
	 * @param string $configuration_id
	 *
	 * @return string
	 */
	public static function get_configuration_label_by_id( $configuration_id ) {

		try {

			$analyser = static::get_configuration_by_id( $configuration_id );

			return sprintf( sprintf( '%s - %s', $analyser->get_label(), $analyser->get_id() ) );

		} catch ( \Exception $e ) {

			return "Unknown configuration '{$configuration_id}'";
		}
	}

}

