<?php

namespace wpsolr\core\classes\engines\configuration\builder;

use wpsolr\core\classes\utilities\WPSOLR_Option;


/**
 * Class WPSOLR_Analyser_Abstract
 * @package wpsolr\core\classes\engines
 */
abstract class WPSOLR_Analyser_Abstract {

	const ID = 'id';
	const LABEL = 'label';

	/** @var WPSOLR_Configuration_Builder_Abstract $tokenizer */
	protected static $tokenizer;

	/** @var WPSOLR_Configuration_Builder_Abstract[] $tokenizer_filters */
	protected static $tokenizer_filters;

	/** @var WPSOLR_Configuration_Builder_Abstract[] $filters */
	protected static $char_filters;

	/**
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * @return WPSOLR_Configuration_Builder_Abstract
	 */
	abstract public function get_tokenizer();

	/**
	 * @return WPSOLR_Configuration_Builder_Abstract[]
	 */
	abstract public function get_tokenizer_filters();

	/**
	 * @return WPSOLR_Configuration_Builder_Abstract[]
	 */
	public function get_char_filters() {
		return [];
	}

	/**
	 * @param string $configuration_builder_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_builders( $configuration_builder_id = '' ) {

		/** @var WPSOLR_Configuration_Builder_Abstract[] $builders */
		$builders = array_merge( $this->get_char_filters(), [ $this->get_tokenizer() ], $this->get_tokenizer_filters() );

		// Retrieve the builder id only
		if ( ! empty( $configuration_builder_id ) ) {

			$is_found = false;
			foreach ( $builders as $builder ) {
				if ( $configuration_builder_id === $builder->get_factory_class_name() ) {
					// Found the builder.
					$builders = [ $builder ];
					$is_found = true;
				}
			}

			if ( ! $is_found ) {
				// Not in the current configuration. Find it anyway.
				$builders = [ WPSOLR_Configuration_Builder_Factory::get_builder_by_id( $configuration_builder_id ) ];
			}

		}

		$results = [];

		foreach ( $builders as $builder ) {
			$result = [
				WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_ID => $builder->get_factory_class_name()
			];

			$new_parameters = [];
			foreach ( $builder->get_parameters() as $parameter ) {
				$new_parameter                                                                        = [];
				$new_parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_ID ]    = $parameter[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_NAME ];
				$new_parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] = $parameter[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_VALUE ];

				$new_parameters[] = $new_parameter;
			}

			if ( ! empty( $new_parameters ) ) {
				$result[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETERS ] = $new_parameters;
			}

			// Add it
			$results[] = $result;
		}

		return $results;
	}

}

