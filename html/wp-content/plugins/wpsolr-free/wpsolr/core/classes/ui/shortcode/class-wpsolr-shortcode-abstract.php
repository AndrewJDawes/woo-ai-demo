<?php

namespace wpsolr\core\classes\ui\shortcode;

/**
 * Class WPSOLR_Shortcode_Builder
 */
abstract class WPSOLR_Shortcode_Abstract {

	const INSTANCE_ID = 'id';
	const INSTANCE_LABEL = 'label';

	public static function get_shortcode_html( $instance_id, $instance_label, $is_escape = false ): string {
		$result = sprintf( '[%s %s="%s" %s="%s" /]',
			static::SHORTCODE_NAME,
			static::INSTANCE_ID,
			$instance_id,
			static::INSTANCE_LABEL,
			$instance_label );

		return $is_escape ? esc_html( $result ) : $result;
	}

}