<?php

namespace wpsolr\core\classes\engines\configuration\builder;

use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax;
use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax_Search;
use wpsolr\core\classes\admin\ui\WPSOLR_Admin_UI_Select2;
use wpsolr\core\classes\engines\configuration\WPSOLR_Configurations_Builder_Factory;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

abstract class WPSOLR_Configuration_Builder_Factory {
	use WPSOLR_Configuration_utils;

	/** @var bool $is_registered */
	protected static $is_registered = false;

	/** @var WPSOLR_Configuration_Builder_Abstract[] */
	static $registered_tokenizers;

	/** @var WPSOLR_Configuration_Builder_Abstract[] */
	static $registered_tokenizer_filters;

	/** @var WPSOLR_Configuration_Builder_Abstract[] */
	static $registered_char_filters;

	const DIR_SOLR_TOKENIZER = 'solr/tokenizer';
	const DIR_SOLR_TOKENIZER_FILTER = 'solr/tokenizer_filter';
	const DIR_SOLR_CHAR_FILTER = 'solr/char_filter';
	const DIR_ELASTICSEARCH_TOKENIZER = 'elasticsearch/tokenizer';
	const DIR_ELASTICSEARCH_TOKENIZER_FILTER = 'elasticsearch/tokenizer_filter';
	const DIR_ELASTICSEARCH_CHAR_FILTER = 'elasticsearch/char_filter';
	const DIRS = [
		self::DIR_SOLR_TOKENIZER,
		self::DIR_SOLR_TOKENIZER_FILTER,
		self::DIR_SOLR_CHAR_FILTER,
		self::DIR_ELASTICSEARCH_TOKENIZER,
		self::DIR_ELASTICSEARCH_TOKENIZER_FILTER,
		self::DIR_ELASTICSEARCH_CHAR_FILTER,
	];


	/**
	 * Help
	 */
	const HELP_TOKENIZER = [
		'text' => 'A tokenizer is responsible for breaking field data into lexical units, or tokens (words)',
		'href' => 'https://lucene.apache.org/solr/guide/tokenizers.html'
	];
	const HELP_TOKENIZER_FILTER = [
		'text' => 'Filters examine a stream of tokens and keep them, transform them or discard them, depending on the filter type being used.',
		'href' => 'https://lucene.apache.org/solr/guide/filter-descriptions.html'
	];
	const HELP_CHAR_FILTER = [
		'text' => 'CharFilter is a component that pre-processes input characters.

CharFilters can be configured like Token Filters and placed in front of a Tokenizer. CharFilters can add, change, or remove characters while preserving the original character offsets to support features like highlighting.',
		'href' => 'https://lucene.apache.org/solr/guide/charfilterfactories.html'
	];


	/**
	 * @param string $option_name
	 * @param array $option_data
	 * @param string $index_uuid
	 * @param string $configuration_id
	 * @param string $builder_id
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	static public function build_form( $option_name, $option_data, $index_uuid, $configuration_id, $configuration_builder_id ) {

		// Start capturing output
		ob_start();

		if ( ! empty( $configuration_id )
		     || ( empty( $option_data )
		          || empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] )
		          || empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_uuid ] )
		          || empty( $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_uuid ][ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER ] )
		     )

		) {

			$analyser = WPSOLR_Configurations_Builder_Factory::get_configuration_by_id( $configuration_id );

			$builders = $analyser->get_builders( $configuration_builder_id );

		} else {

			$builders = $option_data[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $index_uuid ][ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER ];
		}


		//$img_path   = plugins_url( 'images/plus.png', WPSOLR_PLUGIN_FILE );
		$minus_path = plugins_url( 'images/minus.png', WPSOLR_PLUGIN_FILE );

		if ( empty( $configuration_builder_id ) ) {
			?>
            <ul class="wpsolr_section_char_filters wdm_ul connectedSortable ui-sortable" xmlns="http://www.w3.org/1999/html">
			<?php
		}

		foreach ( $builders as $builder_def ) {

			// Common verifications
			$builder_id = $builder_def[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_ID ];
			if ( empty( $builder_id ) ) {
				throw new \Exception( sprintf( 'Missing id attribute in %s', json_encode( $builder_def, true ) ) );
			}

			try {
				$builder             = self::get_builder_by_id( $builder_id );
				$builder_label_by_id = self::get_builder_label_by_id( $builder_id );

			} catch ( \Exception $e ) {

				// Replace builder label with error message.
				$builder_label_by_id = sprintf( '"%s" %s', $builder_id, $e->getMessage() );
			}

			$loop_option_name = sprintf( '%s[%s][%s][%s][%s]', $option_name, WPSOLR_Option::OPTION_INDEXES_INDEXES, $index_uuid,
				WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER, WPSOLR_Option_Indexes::generate_uuid() );

			// Depends on type and class
			if ( $builder->get_is_tokenizer() || $builder->get_is_tokenizer_filter() || $builder->get_is_char_filter() ) { ?>

				<?php
				if ( $builder->get_is_tokenizer() ) {
					if ( empty( $configuration_builder_id ) ) {
						?>
                        </ul>
                        <div style="float:left;margin-top:10px;margin-bottom:20px;width:100%">
                            <input type="button" class="button-primary btn_add_char_filter" value="Add a Char Filter">
                            then position it by drag&drop among other Char Filters.
                        </div>
                        <ul class="wpsolr_section_tokenizer wdm_ul">
						<?php
					}
				}
				?>

                <li class="wpsolr_configuration_builder_form <?php WPSOLR_Escape::echo_escaped( $builder->get_is_tokenizer() ? '' : 'ui-state-default ui-sortable-handle' ); ?>">

				<?php if ( ! $builder::get_is_tokenizer() ) { ?>

                    <div style="float:right;width:5%:margin:3px">
                        <img src='<?php WPSOLR_Escape::echo_esc_url( $minus_path ); ?>'
                             class='minus_icon wpsolr_remove_builder'
                             style='margin:5px;display:inline'
                        >
                    </div>

				<?php } ?>

                <div class="wdm_row" style="width:95%">
                    <div class='col_left' style="width:30%">
						<?php WPSOLR_Escape::echo_escaped( sprintf( '%s', $builder::get_is_tokenizer() ? 'Tokenizer' : ( $builder::get_is_tokenizer_filter() ? 'Tokenizer Filter' : 'Char Filter' ) ) ); ?>

						<?php self::insert_help( $builder::get_is_tokenizer() ? self::HELP_TOKENIZER : ( $builder::get_is_tokenizer_filter() ? self::HELP_TOKENIZER_FILTER : self::HELP_CHAR_FILTER ) ); ?>
                    </div>
                    <div class='col_right'>
						<?php

						WPSOLR_Admin_UI_Select2::dropdown_select2( [
							WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_IS_MULTISELECT       => false,
							WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_CLASS                => WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_ID,
							WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_SELECTED_IDS         => [
								WPSOLR_Admin_UI_Ajax_Search::FORM_FIELD_FILTER_QUERY_CONTENT =>
									[
										$builder_id => $builder_label_by_id
									]
							],
							WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_AJAX_EVENT           => $builder::get_is_tokenizer() ? WPSOLR_Admin_UI_Ajax::AJAX_INDEX_CONFIGURATIONS_TOKENIZERS_SEARCH : ( $builder::get_is_tokenizer_filter() ? WPSOLR_Admin_UI_Ajax::AJAX_INDEX_CONFIGURATIONS_TOKENIZER_FILTERS_SEARCH : WPSOLR_Admin_UI_Ajax::AJAX_INDEX_CONFIGURATIONS_CHAR_FILTERS_SEARCH ),
							WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_PLACEHOLDER_TEXT     => 'Choose &hellip;',
							WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_OPTION_ABSOLUTE_NAME => sprintf( '%s[%s]', $loop_option_name, WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_ID ),
							WPSOLR_Admin_UI_Select2::PARAM_MULTISELECT_OPTION_RELATIVE_NAME => WPSOLR_Admin_UI_Ajax_Search::FORM_FIELD_FILTER_QUERY_CONTENT,
							WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS                   => [ WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_EXTRAS => [ 'index_uuid' => $index_uuid ] ],
							WPSOLR_Admin_UI_Ajax_Search::PARAMETER_PARAMS_SELECTORS         => [],
						] );

						?>

						<?php self::insert_help(
							[
								'text' => $builder->get_description(),
								'href' => $builder->get_documentation_link(),
							]
						); ?>
                    </div>
                    <div class="clear"/>
                </div>

				<?php

			} else {

				throw new \Exception( sprintf( 'Unsupported builder type attribute in %s', json_encode( $builder_def, true ) ) );
			}

			// Output current builder parameters
			static::builder_parameters_form( $builder, $builder_def, $loop_option_name );


			?>

			<?php if ( empty( $configuration_builder_id ) && $builder->get_is_tokenizer() ) {
				?>
                </ul>
				<?php
			}
			?>

			<?php if ( ! $builder::get_is_tokenizer() ) { ?>
                <div style="float:right;margin:3px">
                    <a class="wpsolr_clone_builder" href="javascript:void(0)">Clone</a>
                </div>
			<?php } ?>

            </li>
			<?php
			if ( empty( $configuration_builder_id ) && $builder->get_is_tokenizer() ) {
				?>
                <ul class="wpsolr_section_tokenizer_filters wdm_ul connectedSortable ui-sortable">
				<?php
			}
		} ?>

		<?php


		// ui-sortable
		if ( empty( $configuration_builder_id ) ) { ?>
            </ul>
            <div style="float:left;margin-top:10px;margin-bottom:20px;width:100%">
                <input type="button" class="button-primary btn_add_tokenizer_filter" value="Add a Tokenizer Filter">
                then position
                it by
                drag&drop among other Tokenizer Filters.
            </div>
		<?php }

		// Retrieve output
		$results = ob_get_contents();
		// Clean output to prevent it's display
		ob_end_clean();

		return $results;
	}

	/**
	 * Output current builder parameters
	 *
	 * @param WPSOLR_Configuration_Builder_Abstract $builder
	 * @param array $builder_def
	 * @param string $loop_option_name
	 */
	public static function builder_parameters_form( $builder, $builder_def, $loop_option_name ) {

		// Display attribute form(s)
		if ( isset( $builder_def[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETERS ] ) ) {


			foreach ( $builder_def[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETERS ] as $loop_parameters => $parameter ) {
				$parameter_definition  = $builder->get_parameter_by_name( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_ID ] );
				$parameter_stored_name = sprintf( '%s[%s][%s][%s]', $loop_option_name, WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETERS,
					$loop_parameters, WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE );
				?>
                <div class="wdm_row">
                    <div class='col_left'>
                        <label><?php WPSOLR_Escape::echo_esc_html( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_ID ] ); ?></label>
                        <span style="font-size: 10px"><br><?php WPSOLR_Escape::echo_escaped( $parameter_definition[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_IS_OPTIONAL ] ? '(Optional)' : '(Mandatory)' ); ?></span>
                    </div>
                    <div class='col_right'>

                        <input type="hidden"
                               name="<?php WPSOLR_Escape::echo_escaped( sprintf( '%s[%s][%s][%s]',
							       WPSOLR_Escape::esc_attr( $loop_option_name ),
							       WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETERS ),
							       WPSOLR_Escape::esc_attr( $loop_parameters ),
							       WPSOLR_Escape::esc_attr( WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_ID )
						       ) ); ?>"
                               value="<?php WPSOLR_Escape::echo_esc_attr( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_ID ] ); ?>">

						<?php
						switch ( $parameter_definition[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_TYPE ] ) {

							case WPSOLR_Configuration_Builder_Abstract::PARAMETER_TYPE_CHECKBOX:

								?>
                                <div class="wpsolr_builder_type_checkbox_mandatory">
                                    <input type="hidden"
                                           class="wpsolr_builder_type_checkbox_mandatory"
                                           name="<?php WPSOLR_Escape::echo_esc_attr( $parameter_stored_name ); ?>"
                                           value="<?php WPSOLR_Escape::echo_esc_attr( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] ); ?>">
									<?php
									$values = empty( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] )
										? [
											WPSOLR_Configuration_Builder_Abstract::PARAMETER_ANALYSER_TYPE_QUERY,
											WPSOLR_Configuration_Builder_Abstract::PARAMETER_ANALYSER_TYPE_INDEX
										]
										: explode( ',', $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] );
									foreach ( $parameter_definition[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_VALUES ] as $list_value ) {
										?>
                                        <input type="checkbox"
                                               class="wpsolr_builder_type_checkbox_mandatory"
                                               value="<?php WPSOLR_Escape::echo_esc_attr( $list_value[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_ID ] ); ?>"
											<?php checked( in_array( $list_value[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_ID ], $values ) ); ?>
                                        >
										<?php

										WPSOLR_Escape::echo_esc_html( $list_value[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_LABEL ] );
									}

									?>
                                </div>
								<?php

								break;

							case WPSOLR_Configuration_Builder_Abstract::PARAMETER_TYPE_TRUE_FALSE:
							case WPSOLR_Configuration_Builder_Abstract::PARAMETER_TYPE_DROP_DOWN_LIST:
								?>
                                <select name="<?php WPSOLR_Escape::echo_esc_attr( $parameter_stored_name ); ?>"
                                        style="width:100%">
									<?php
									foreach ( $parameter_definition[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_VALUES ] as $list_value ) {
										?>
                                        <option
                                                value="<?php WPSOLR_Escape::echo_esc_attr( $list_value[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_ID ] ); ?>"
											<?php selected( $list_value[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_ID ], $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] ); ?>
                                        >
											<?php WPSOLR_Escape::echo_esc_html( $list_value[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_LABEL ] ); ?>
                                        </option>
										<?php
									}
									?>
                                </select>
								<?php
								break;

							case WPSOLR_Configuration_Builder_Abstract::PARAMETER_TYPE_FILE:

								$file_name_full_path = $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ];
								if ( is_numeric( $file_name_full_path ) ) {

									// Media file
									$file_name_full_path = get_attached_file( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] );

								} else {

									// Predefined file from the builder file
									$file_name_full_path = $builder->get_file_full_path( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] );

								}
								$file_base_name = basename( $file_name_full_path );

								$popup_inline_id = WPSOLR_Option_Indexes::generate_uuid();
								?>
                                <div class="wpsolr_attachment">
                                    <div class="wpsolr_attachment_infos">

                                         <span <?php WPSOLR_Escape::echo_escaped( ! empty( $file_base_name ) ? 'style="display:none;"' : '' ); ?>
                                                 class="wpsolr_attachment_file_selected_filename_button
                                                 <?php WPSOLR_Escape::echo_escaped( $parameter_definition[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_IS_OPTIONAL ] ? '' : 'wpsolr_err' ); ?>"
                                         >
                                            No file selected
                                        </span>
                                        <input
											<?php WPSOLR_Escape::echo_escaped( empty( $file_base_name ) ? 'style="display:none;"' : '' ); ?>
                                                type="button"
                                                class="<?php WPSOLR_Escape::echo_esc_attr( $popup_inline_id ); ?> wpsolr_attachment_file_selected_filename_button button"
                                                value="Edit <?php WPSOLR_Escape::echo_esc_attr( $file_base_name ); ?>"
                                        />
                                        <br>
                                        <hr>
                                        Replace it from : <br>
										<?php
										if ( ! empty( $parameter_definition[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_VALUES ] ) ) {
											?>
                                            <select class="wpsolr_attachment_file_predefined_list">
												<?php
												foreach ( $parameter_definition[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_VALUES ] as $list_value ) {
													?>
                                                    <option
                                                            value="<?php WPSOLR_Escape::echo_esc_attr( $list_value[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_ID ] ); ?>"
                                                    >
														<?php WPSOLR_Escape::echo_esc_html( $list_value[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_LIST_LABEL ] ); ?>
                                                    </option>
													<?php
												}
												?>
                                            </select>
                                            <br>
                                            or from :
                                            <br>
											<?php
										}
										?>
                                        <input type="button"
                                               class="button-primary wdm-save button wpsolr_attachment_button"
                                               value="Media library"/>
                                        <br>
                                        <span>(Upload your files)</span>


                                        <div id="<?php WPSOLR_Escape::echo_esc_attr( $popup_inline_id ); ?>"
                                             class="wpsolr_attachment_content"
                                             style="display:none;">
                                            <div class="wpsolr_attachment_content">

                                                <input type='hidden'
                                                       name="wpsolr_popup_id"
                                                       value="<?php WPSOLR_Escape::echo_esc_attr( $popup_inline_id ); ?>"/>

                                                <input type='hidden'
                                                       class="wpsolr_attachment_file_selected_id"
                                                       name="<?php WPSOLR_Escape::echo_esc_attr( $parameter_stored_name ); ?>"
                                                       value="<?php WPSOLR_Escape::echo_esc_attr( basename( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] ) ); ?>"/>

                                                <input type='hidden'
                                                       class="wpsolr_attachment_file_selected_id_path"
                                                       value="<?php WPSOLR_Escape::echo_esc_attr( $file_name_full_path ); ?>"/>

                                                <label>Filename (set with another name to create a new file) :</label>
                                                <input type="edit"
                                                       style="width:100%;margin-top: 5px"
                                                       class="wpsolr_attachment_content_file_basename"
                                                       value="<?php WPSOLR_Escape::echo_esc_attr( $file_base_name ); ?>"
                                                />
                                                <label>File content :</label>
                                                <textarea class="wpsolr_attachment_content_textarea"
                                                          style="height:80%;width:100%;margin-top: 5px"></textarea>

                                                <input type="button"
                                                       class="wpsolr_attachment_file_selected_upload_to_media_library_button button-primary wpsolr_save button"
                                                       value="Save and upload in media library"/>
                                            </div>
                                        </div>

                                        <div class="wpsolr_attachment_err wpsolr_err"></div>
                                    </div>

                                </div>

								<?php
								break;

							default:
								?>
                                <input type="edit"
                                       style="width:100%"
                                       name="<?php WPSOLR_Escape::echo_esc_attr( $parameter_stored_name ); ?>"
                                       value="<?php WPSOLR_Escape::echo_esc_attr( $parameter[ WPSOLR_Option::OPTION_INDEXES_CONFIGURATION_BUILDER_PARAMETER_VALUE ] ); ?>">
								<?php
								break;
						}

						?>

						<?php self::insert_help( [
							'text' => $parameter_definition[ WPSOLR_Configuration_Builder_Abstract::PARAMETER_DESCRIPTION ],
							'href' => $builder->get_documentation_link(),
						] ); ?>
                    </div>
                </div>
                <div class="clear"/>
				<?php
			}
		}
	}

	/**
	 * Register all builders in directories
	 *
	 * @throws \Exception
	 */
	public
	static function register_builder() {

		if ( self::$is_registered ) {
			// Already done. Leave.
			return;
		}

		/** @var WPSOLR_Configuration_Builder_Abstract $builder_class_name */
		foreach ( self::get_class_names() as $builder_class_name ) {
			if ( empty( trim( $builder_class_name::get_factory_class_name() ) ) ) {

				throw new \Exception( sprintf( 'Missing factory name in class %s', esc_html( $builder_class_name ) ) );

			} elseif ( $builder_class_name::get_is_tokenizer() && ! isset( self::$registered_tokenizers[ $builder_class_name ] ) ) {

				self::$registered_tokenizers[] = $builder_class_name;

			} elseif ( $builder_class_name::get_is_tokenizer_filter() && ! isset( self::$registered_tokenizer_filters[ $builder_class_name ] ) ) {

				self::$registered_tokenizer_filters[] = $builder_class_name;

			} elseif ( $builder_class_name::get_is_char_filter() && ! isset( self::$registered_char_filters[ $builder_class_name ] ) ) {

				self::$registered_char_filters[] = $builder_class_name;
			}
		}

		self::$is_registered = true;
	}

	/**
	 * @return WPSOLR_Configuration_Builder_Abstract[]
	 * @throws \Exception
	 */
	public
	static function get_tokenizers() {

		self::register_builder();

		return self::$registered_tokenizers;
	}

	/**
	 * @return WPSOLR_Configuration_Builder_Abstract[]
	 * @throws \Exception
	 */
	public
	static function get_tokenizer_filters() {

		self::register_builder();

		return self::$registered_tokenizer_filters;
	}

	/**
	 * @return WPSOLR_Configuration_Builder_Abstract[]
	 * @throws \Exception
	 */
	public
	static function get_char_filters() {

		self::register_builder();

		return self::$registered_char_filters;
	}

	/**
	 * Retrieve a builder by id
	 *
	 * @param string $builder_class_name
	 *
	 * @return WPSOLR_Configuration_Builder_Abstract
	 * @throws \Exception
	 */
	public
	static function get_builder_by_id(
		$builder_class_name
	) {

		$char_filters      = static::get_char_filters();
		$tokenizers        = static::get_tokenizers();
		$tokenizer_filters = static::get_tokenizer_filters();

		$all_builders = array_merge( $char_filters, $tokenizers, $tokenizer_filters );

		/** @var WPSOLR_Configuration_Builder_Abstract $builder */
		foreach ( $all_builders as $builder ) {
			if ( $builder_class_name === $builder::get_factory_class_name() ) {
				return new $builder();
			}
		}

		// Not found
		throw new \Exception( esc_html( "Tokenizer or Filter {$$builder_class_name} is unknown." ) );
	}


	/**
	 * Retrieve a builder label by id
	 *
	 * @param string $builder_class_name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public
	static function get_builder_label_by_id(
		$builder_class_name
	) {


		$builder = static::get_builder_by_id( $builder_class_name );

		return sprintf( sprintf( '%s', $builder::get_factory_class_name() ) );
	}

	/**
	 * Insert help in the form
	 *
	 * @param array $help_content
	 */
	private
	static function insert_help(
		$help_content
	) {
		?>

        <div>
            <a href="javascript::void(0);" class="wpsolr_collapser" style="font-size:12px;">help</a>
            <div class="wpsolr_collapsed" style="margin-top:10px">
                <a style="font-size:12px" href="<?php WPSOLR_Escape::echo_esc_url( $help_content['href'] ); ?>"
                   target="_blank">
					<?php WPSOLR_Escape::echo_esc_html( $help_content['text'] ); ?>
                </a>
            </div>
        </div>
		<?php
	}

}

