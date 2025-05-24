<?php

namespace wpsolr\core\classes\engines\configuration\builder;

use DirectoryIterator;

trait WPSOLR_Configuration_utils {


	/**
	 * Retrieve all class names in all files of a relative directory
	 *
	 * @param string[] $relative_dirs Relative dir
	 *
	 * @return array
	 */
	static public function get_class_names() {

		$class_names = [];

		foreach ( static::DIRS as $relative_dir ) {

			$full_path = __DIR__ . '/' . $relative_dir;
			foreach ( new DirectoryIterator( $full_path ) as $fileInfo ) {

				if ( $fileInfo->isDot() || ( false !== strpos( $fileInfo->getFilename(), 'abstract' ) ) ) {
					continue;
				}

				$classe_names = self::get_class_name_from_php_content( file_get_contents( $fileInfo->getPathname() ) );

				$class_name = implode( '\\', [
					__NAMESPACE__,
					str_replace( '/', '\\', $relative_dir ),
					$classe_names[0]
				] );

				if ( empty( $class_names[ $class_name ] ) ) {

					$class_names[] = $class_name;
				}
			}
		}

		return $class_names;
	}

	/**
	 * @param string $php_code
	 *
	 * @return string[]
	 */

	static function get_class_name_from_php_content( $php_code ) {
		$classes = array();
		$tokens  = token_get_all( $php_code );
		$count   = count( $tokens );
		for ( $i = 2; $i < $count; $i ++ ) {
			if ( $tokens[ $i - 2 ][0] == T_CLASS && $tokens[ $i - 1 ][0] == T_WHITESPACE && $tokens[ $i ][0] == T_STRING && ! ( $tokens[ $i - 3 ] && $i - 4 >= 0 && $tokens[ $i - 4 ][0] == T_ABSTRACT ) ) {
				$class_name = $tokens[ $i ][1];
				$classes[]  = $class_name;
			}
		}

		return $classes;
	}

}

