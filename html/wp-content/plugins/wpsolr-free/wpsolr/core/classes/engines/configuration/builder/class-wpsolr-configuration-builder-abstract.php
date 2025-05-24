<?php

namespace wpsolr\core\classes\engines\configuration\builder;


use DirectoryIterator;
use ReflectionClass;

abstract class WPSOLR_Configuration_Builder_Abstract {
	/**
	 * Parameters
	 */
	const PARAMETER_TYPE = 'type';
	const PARAMETER_TYPE_INPUT_READONLY = 'input_readonly';
	const PARAMETER_TYPE_TRUE_FALSE = 'true_false';
	const PARAMETER_TYPE_FILE = 'file';

	const PARAMETER_NAME = 'name';
	const PARAMETER_VALUE = 'value';
	const PARAMETER_IS_OPTIONAL = 'is_optional';
	const PARAMETER_DESCRIPTION = 'description';
	const PARAMETER_TYPE_INPUT = 'input';
	const PARAMETER_TYPE_DROP_DOWN_LIST = 'drop_down_list';
	const PARAMETER_TYPE_CHECKBOX = 'checkbox';

	const PARAMETER_LIST_VALUES = 'list_values';
	const PARAMETER_LIST_ID = 'id';
	const PARAMETER_LIST_LABEL = 'label';
	const PARAMETER_LIST_USE_DEFAULT = 'Use default';

	/**
	 * Default common parameters
	 */
	const PARAMETER_ANALYSER_TYPE = 'analyser_type';
	const PARAMETER_ANALYSER_TYPE_QUERY = 'query';
	const PARAMETER_ANALYSER_TYPE_INDEX = 'index';
	const DESCRIPTION_PARAM_ANALYSER_TYPE = 'Analysis takes place in two contexts. At index time, when a field is being created, the token stream that results from analysis is added to an index and defines the set of terms (including positions, sizes, and so on) for the field. At query time, the values being searched for are analyzed and the terms that result are matched against those that are stored in the field’s index.

In many cases, the same analysis should be applied to both phases. This is desirable when you want to query for exact string matches, possibly with case-insensitivity, for example. In other cases, you may want to apply slightly different analysis steps during indexing than those used at query time.

If you provide a simple <analyzer> definition for a field type, as in the examples above, then it will be used for both indexing and queries. If you want distinct analyzers for each phase, you may include two <analyzer> definitions distinguished with a type attribute.';


	/** @var array $parameters_definitions */
	protected $parameters_definitions = [];

	/** @var array $parameter_values
	 * ['word' => 'lang/fr-stop-words.txt', 'size' => '10']
	 **/
	protected $parameter_values = [];

	/** @var string */
	protected $files_directory;

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return $this
	 * @throws \Exception
	 */
	protected function set_parameter_value( $name, $value ) {

		$this->get_parameters();

		$is_found = false;
		foreach ( $this->parameters_definitions as &$parameter ) {

			if ( $name === $parameter[ self::PARAMETER_NAME ] ) {

				$parameter[ self::PARAMETER_VALUE ] = $value;

				// ok, found.
				$is_found = true;
				break;
			}
		}

		if ( ! $is_found ) {
			throw new \Exception( sprintf( 'WPSOLR error : parameter %s does not exist in class %s', esc_html( $name ), esc_html( static::class ) ) );
		}

		return $this;
	}


	/**
	 * @param string $name
	 * @param string $file
	 *
	 * @return $this
	 * @throws \Exception
	 */
	protected function set_parameter_file_value( $name, $file ) {

		return $this->set_parameter_value( $name, sprintf( '%s/%s', $this->get_files_directory(), $file ) );
	}

	/**
	 * Returns a full path from basefile name
	 *
	 * @param $file
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	public function get_file_full_path( $basefile ) {
		return empty( trim( $basefile ) ) ? '' : sprintf( '%s/%s', $this->get_files_directory(), basename( $basefile ) );
	}

	/**
	 * @param string $type
	 * @param string $name
	 * @param string $value
	 * @param array $values
	 * @param $is_optional
	 * @param $description
	 *
	 * @return $this
	 */
	protected function add_parameter_list( $type, $name, $value, $values, $is_optional, $description ) {

		// Sort values by the label
		uasort( $values, function ( $a, $b ) {
			if ( empty( $a[ self::PARAMETER_LIST_ID ] ) ) {
				return - 1;
			}

			if ( $a[ self::PARAMETER_LIST_LABEL ] == $b[ self::PARAMETER_LIST_LABEL ] ) {
				return 0;
			}

			return ( $a[ self::PARAMETER_LIST_LABEL ] < $b[ self::PARAMETER_LIST_LABEL ] ) ? - 1 : 1;
		} );

		$this->parameters_definitions[] = [
			self::PARAMETER_TYPE        => $type,
			self::PARAMETER_NAME        => $name,
			self::PARAMETER_VALUE       => $value,
			self::PARAMETER_LIST_VALUES => $values,
			self::PARAMETER_IS_OPTIONAL => $is_optional,
			self::PARAMETER_DESCRIPTION => $description,
		];

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param array $values
	 * @param $is_optional
	 * @param $description
	 *
	 * @return $this
	 */
	protected function add_parameter_drop_down_list( $name, $value, $values, $is_optional, $description ) {
		return $this->add_parameter_list( self::PARAMETER_TYPE_DROP_DOWN_LIST, $name, $value, $values, $is_optional, $description );
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param array $values
	 * @param $is_optional
	 * @param $description
	 *
	 * @return $this
	 */
	protected function add_parameter_checkbox( $name, $value, $values, $is_optional, $description ) {
		return $this->add_parameter_list( self::PARAMETER_TYPE_CHECKBOX, $name, $value, $values, $is_optional, $description );
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param bool $is_optional
	 * @param string $description
	 *
	 * @return $this
	 */
	protected function add_parameter_input( $name, $value, $is_optional, $description ) {
		$this->parameters_definitions[] = [
			self::PARAMETER_TYPE        => self::PARAMETER_TYPE_INPUT,
			self::PARAMETER_NAME        => $name,
			self::PARAMETER_VALUE       => $value,
			self::PARAMETER_IS_OPTIONAL => $is_optional,
			self::PARAMETER_DESCRIPTION => $description,
		];

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param bool $is_optional
	 * @param string $description
	 *
	 * @return $this
	 */
	protected function add_parameter_input_readonly( $name, $value, $is_optional, $description ) {
		$this->parameters_definitions[] = [
			self::PARAMETER_TYPE        => self::PARAMETER_TYPE_INPUT_READONLY,
			self::PARAMETER_NAME        => $name,
			self::PARAMETER_VALUE       => $value,
			self::PARAMETER_IS_OPTIONAL => $is_optional,
			self::PARAMETER_DESCRIPTION => $description,
		];

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param bool $is_optional
	 * @param string $description
	 *
	 * @return $this
	 */
	protected function add_parameter_true_false( $name, $value, $is_optional, $description ) {
		$this->parameters_definitions[] = [
			self::PARAMETER_TYPE        => self::PARAMETER_TYPE_TRUE_FALSE,
			self::PARAMETER_NAME        => $name,
			self::PARAMETER_VALUE       => $value,
			self::PARAMETER_LIST_VALUES => [
				[ self::PARAMETER_LIST_ID => '', self::PARAMETER_LIST_LABEL => self::PARAMETER_LIST_USE_DEFAULT ],
				[ self::PARAMETER_LIST_ID => 'true', self::PARAMETER_LIST_LABEL => 'True' ],
				[ self::PARAMETER_LIST_ID => 'false', self::PARAMETER_LIST_LABEL => 'False' ]
			],
			self::PARAMETER_IS_OPTIONAL => $is_optional,
			self::PARAMETER_DESCRIPTION => $description,
		];

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param bool $is_optional
	 * @param string $description
	 *
	 * @return $this
	 */
	protected function add_parameter_file( $name, $value, $is_optional, $description ) {
		$this->parameters_definitions[] = [
			self::PARAMETER_TYPE        => self::PARAMETER_TYPE_FILE,
			self::PARAMETER_NAME        => $name,
			self::PARAMETER_VALUE       => $value,
			self::PARAMETER_LIST_VALUES => $this->get_resource_file_names(),
			self::PARAMETER_IS_OPTIONAL => $is_optional,
			self::PARAMETER_DESCRIPTION => $description,
		];

		return $this;
	}

	/**
	 * Retrieve all class names in all files of a relative directory
	 *
	 * @param string[] $relative_dirs Relative dir
	 *
	 * @return array
	 */
	protected function get_resource_file_names() {

		$results = [];

		try {
			$full_path         = $this->get_files_directory();
			$directoryIterator = new DirectoryIterator( $full_path );

			$results[] =
				[
					self::PARAMETER_LIST_ID    => '',
					self::PARAMETER_LIST_LABEL => 'Standard files',
				];

			foreach ( $directoryIterator as $fileInfo ) {

				if ( $fileInfo->isDot() || ( false === strpos( $fileInfo->getFilename(), '.txt' ) ) ) {
					continue;
				}

				$results[] = [
					self::PARAMETER_LIST_ID    => $fileInfo->getPathname(),
					self::PARAMETER_LIST_LABEL => $fileInfo->getFilename(),
				];
			}

		} catch ( \Exception $e ) {
			// No folder : empty values, the list will no be shown.
		}

		return $results;
	}

	/**
	 * Get the directory containing all files for the current builder
	 * @return string
	 * @throws \ReflectionException
	 */
	protected function get_files_directory() {

		if ( empty( $this->files_directory ) ) {

			$reflection = new ReflectionClass( $this );

			$directory = dirname( $reflection->getFileName() );

			$this->files_directory = sprintf( '%s/../resource/%s', $directory, static::get_factory_class_name() );
		}

		return $this->files_directory;
	}

	/**
	 * @return string
	 */
	static public function get_factory_class_name() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_documentation_link() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return 'Missing description !';
	}

	/**
	 * @return bool
	 */
	public static function get_is_tokenizer() {
		return false;
	}

	/**
	 * @return bool
	 */
	public static function get_is_tokenizer_filter() {
		return false;
	}

	/**
	 * @return bool
	 */
	public static function get_is_char_filter() {
		return false;
	}

	/**
	 * @return bool
	 */
	public static function get_is_solr() {
		return false;
	}

	/**
	 * @return bool
	 */
	public static function get_is_elasticsearch() {
		return false;
	}


	/**
	 * Get data parameters
	 *
	 * @return array
	 */
	public function get_parameters() {

		if ( empty( $this->parameters_definitions ) ) {

			// Add default parameters
			if ( ! $this->get_is_tokenizer() ) {
				$this->add_parameter_analyser_type();
			}

			$this->get_inner_parameters();
		}

		return $this->parameters_definitions;
	}

	protected function add_parameter_analyser_type() {

		$analyser_types   = [];
		$analyser_types[] = [
			self::PARAMETER_LIST_ID    => self::PARAMETER_ANALYSER_TYPE_QUERY,
			self::PARAMETER_LIST_LABEL => 'Query'
		];
		$analyser_types[] = [
			self::PARAMETER_LIST_ID    => self::PARAMETER_ANALYSER_TYPE_INDEX,
			self::PARAMETER_LIST_LABEL => 'Index'
		];

		$this->add_parameter_checkbox( self::PARAMETER_ANALYSER_TYPE, '', $analyser_types, false, self::DESCRIPTION_PARAM_ANALYSER_TYPE );
	}

	/**
	 * Get data parameters
	 *
	 */
	abstract protected function get_inner_parameters();


	/**
	 * Get a parameter by name
	 *
	 * @param string $name
	 *
	 * @return array
	 */
	public function get_parameter_by_name( $name ) {
		$parameters = $this->get_parameters();

		foreach ( $parameters as $parameter ) {
			if ( $name === $parameter[ self::PARAMETER_NAME ] ) {
				return $parameter;
			}
		}

		return [];
	}
}

