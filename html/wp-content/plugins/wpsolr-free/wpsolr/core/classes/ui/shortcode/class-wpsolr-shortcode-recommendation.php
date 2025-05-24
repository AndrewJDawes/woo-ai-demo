<?php

namespace wpsolr\core\classes\ui\shortcode;

use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\recommendations\WPSOLR_Option_Recommendations;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\models\post\WPSOLR_Model_Meta_Type_Post;
use wpsolr\core\classes\models\taxonomy\WPSOLR_Model_Meta_Type_Taxonomy;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Shortcode recommendations
 * Built with Ajax to prevent being cached if the HTML page is cached (CDN, cache plugin, ...)
 */
class WPSOLR_Shortcode_Recommendation extends WPSOLR_Shortcode_Abstract {

	const SHORTCODE_NAME = 'wpsolr_recommendation';

	const AJAX_ACTION = 'wpsolr_recommendations';
	private static $_cache;
	/**
	 * @var array|mixed
	 */
	protected static array $_attributes;

	/**
	 * Ajax
	 */
	public static function init_ajax() {
		add_action( sprintf( 'wp_ajax_nopriv_%s', static::AJAX_ACTION ), [ static::class, 'ajax_call' ] );
		add_action( sprintf( 'wp_ajax_%s', static::AJAX_ACTION ), [ static::class, 'ajax_call' ] );
	}

	public static function ajax_call() {
		if ( empty( $_POST['security'] ) or ! wp_verify_nonce( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'security' ] ), 'nonce_for_autocomplete' ) ) {
			global $wp;
			$url = add_query_arg( $wp->query_vars, home_url( $wp->request ) );

			// Write error in debug.log
			WPSOLR_Escape::error_log( sprintf( 'WPSOLR message: %s nonce security check failed on url %s', WPSOLR_PLUGIN_SHORT_NAME, $url ) );
			?>

            <script>
                console.error("<?php WPSOLR_Escape::echo_esc_html( WPSOLR_PLUGIN_SHORT_NAME ); ?> : a nonce security error prevented the recommendation query to be executed. " +
                    "Please check the error details in your debug.log file. See https://codex.wordpress.org/Debugging_in_WordPress.");
            </script>

			<?php
			die();
		}

		static::ajax_call_no_die( WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'recommendation_uuid' ], '' ),
			WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'context', 'object_id' ], '' ),
			WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'context', 'meta_type' ], '' ),
			WPSOLR_Sanitize::sanitize_text_field( $_POST, [ 'context', 'type' ], '' ),
		);
		die();
	}

	public static function ajax_call_no_die(
		string $recommendation_uuid, string $object_id,
		string $meta_type, string $type
	): void {

		WPSOLR_Escape::echo_escaped(
			static::get_html_content( [
				static::INSTANCE_ID => $recommendation_uuid,
				'object_id'         => $object_id,
				'meta_type'         => $meta_type,
				'type'              => $type
			] ) );
	}

	/**
	 * @inheritdoc
	 */
	public static function get_html( $attributes = [] ) {

		$is_in_suggestion = ( WPSOLR_AJAX_AUTO_COMPLETE_ACTION === ( WPSOLR_Sanitize::sanitize_text_field( $_REQUEST, [ 'action' ], '' ) ) );
		$is_admin         = is_admin() || isset( $_GET['elementor-preview'] );
		if ( ( ! $is_in_suggestion ) && $is_admin ) {
			return;
		}

		$recommendation_uuid = $attributes[ static::INSTANCE_ID ] ?? '';
		if ( empty( $recommendation_uuid ) ) {
			return;
		}

		try {
			$recommendation = WPSOLR_Option_Recommendations::get_recommendation( $recommendation_uuid );
			$is_not_ajax    = $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IS_NOT_AJAX ] ?? false;
		} catch ( \Exception $e ) {
			// Bad recommendation: inactive or missing
			return;
		}

		$container_class = sprintf( 'wpsolr_container_recommendation_c%s', $recommendation_uuid );

		list( $meta_type, $type ) = self::_get_object_infos();
		?>

        <div class="<?php WPSOLR_Escape::echo_esc_attr( $container_class ); ?>">
            <!-- Loading Ajax here... -->
			<?php
			if ( $is_not_ajax ) {
				static::ajax_call_no_die( $recommendation_uuid, get_queried_object_id() ?? '', $meta_type, $type );
			}
			?>
        </div>

		<?php
		if ( $is_not_ajax ) {
			return;
		}

		list( $meta_type, $type ) = self::_get_object_infos();
		?>

        <script>
            jQuery(document).ready(function ($) {
                const container_el = $('.<?php WPSOLR_Escape::echo_esc_attr( $container_class ); ?>');

                if (container_el.find('.wpsolr_recommendations').length > 0) {
                    return;
                }

                $.ajax({
                    url: '<?php WPSOLR_Escape::echo_esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    type: "post",
                    data: {
                        action: '<?php WPSOLR_Escape::echo_esc_attr( static::AJAX_ACTION ); ?>',
                        recommendation_uuid: '<?php WPSOLR_Escape::echo_esc_attr( $recommendation_uuid ); ?>',
                        context: {
                            object_id: '<?php WPSOLR_Escape::echo_esc_attr( get_queried_object_id() ?? '' ); ?>',
                            meta_type: '<?php WPSOLR_Escape::echo_esc_attr( $meta_type ); ?>',
                            type: '<?php WPSOLR_Escape::echo_esc_attr( $type ); ?>'
                        },
                        security: '<?php WPSOLR_Escape::echo_esc_attr( wp_create_nonce( 'nonce_for_autocomplete' ) ); ?>'
                        //view_uuid: view_uuid
                    },
                    dataType: 'html',
                    success: function (response) {
                        container_el.html(response);

                        $(document).trigger('wpsolr_on_ajax_recommendations_success', {
                            me: $(this),
                            recommendations: []
                        });
                    },
                    error: function () {
                        // Not called.
                    },
                    always: function () {
                        // Not called.
                    }
                });

            });
        </script>

		<?php
	}

	/**
	 * @inheritdoc
	 */
	public static function get_html_content( $context = [] ) {

		try {
			$recommendation_uuid = $context[ static::INSTANCE_ID ] ?? '';

			$recommendation = WPSOLR_Option_Recommendations::get_recommendation( $recommendation_uuid );

			/**
			 * Prevent calling the Recommender API by detecting problems early
			 */
			WPSOLR_Option_Recommendations::check_recommendation( $recommendation, $context );

			$index_uuid = $recommendation[ WPSOLR_Option_View::INDEX_UUID ];
			$view_uuid  = $recommendation[ WPSOLR_Option_View::VIEW_UUID ];
			WPSOLR_Option_View::set_current_index_uuid( $index_uuid );
			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

			$wpsolr_query = WPSOLR_Query_Parameters::CreateQuery();
			$wpsolr_query->wpsolr_set_view_uuid( $view_uuid );
			$wpsolr_query->set_wpsolr_image_size( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_IMAGE_SIZE ] ?? '' );

			$wpsolr_query->set_wpsolr_ajax_context( $context );
			$search_engine_client = WPSOLR_Service_Container::get_solr_client( false, $index_uuid );

			// Add filters
			add_action( WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY, [
				static::class,
				'wpsolr_action_query',
			], 10, 1 );

			/**
			 * Get recommendations
			 */
			$html = $search_engine_client->get_recommendations_html( $recommendation, $wpsolr_query );
			WPSOLR_Option_View::restore_current_view_uuid();

			return ( static::$_cache[ $recommendation_uuid ] = $html );

		} catch ( \Exception $e ) {

			global $wp;
			$url = add_query_arg( $wp->query_vars, home_url( $wp->request ) );

			// Write error in debug.log
			WPSOLR_Escape::error_log( sprintf( 'WPSOLR message: recommendation throws %s on url %s', $e->getMessage(), $url ) );
			WPSOLR_Escape::error_log( sprintf( 'WPSOLR trace: recommendation throws %s on url %s', $e->getTraceAsString(), $url ) );
			?>

            <script>
                console.error("<?php WPSOLR_Escape::echo_esc_html( WPSOLR_PLUGIN_SHORT_NAME ); ?> : an error prevented the recommendation query to be executed. " +
                    "Please check the error details in your debug.log file. See https://codex.wordpress.org/Debugging_in_WordPress.");
            </script>

			<?php
			return '';
		}

	}

	/**
	 *
	 * Add filters
	 *
	 * @param array $parameters
	 *
	 */
	static public function wpsolr_action_query( $parameters ) {

		/* @var WPSOLR_Query $wpsolr_query */
		$wpsolr_query = $parameters[ WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_WPSOLR_QUERY ];
		/* @var WPSOLR_AbstractSearchClient $search_engine_client */
		$search_engine_client = $parameters[ WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_CLIENT ];

		$object_id = $wpsolr_query->get_wpsolr_ajax_context()['object_id'] ?? '';
		if ( ! empty( $object_id ) ) {
			$recommendation = $wpsolr_query->wpsolr_get_recommendation();

			/**
			 * Filter: not same post id
			 */
			if ( isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_NOT_SAME_OBJECT ] ) ) {
				$search_engine_client->search_engine_client_add_filter( 'not same id',
					$search_engine_client->search_engine_client_create_filter_not_in_terms(
						WpSolrSchema::_FIELD_NAME_ID, [ $object_id ] )
				);
			}

			/**
			 * Filter: same type
			 */
			if ( isset( $recommendation[ WPSOLR_Option::OPTION_RECOMMENDATION_FILTER_IS_SAME_OBJECT_TYPE ] ) &&
			     ( ! empty( $post_type = get_post_type( $object_id ) ) ) ) {
				$search_engine_client->search_engine_client_add_filter( 'same type',
					$search_engine_client->search_engine_client_create_filter_in_terms(
						WpSolrSchema::_FIELD_NAME_TYPE, [ $post_type ] )
				);
			}

		}
	}

	/**
	 * @return array
	 */
	protected static function _get_object_infos(): array {
		$meta_type = '';
		$type      = '';
		if ( get_post_type() ) {
			$meta_type = WPSOLR_Model_Meta_Type_Post::META_TYPE;
			$type      = get_post_type();
		} else if ( is_tax() ) {
			$meta_type = WPSOLR_Model_Meta_Type_Taxonomy::META_TYPE;
			$type      = get_queried_object()->taxonomy;
		}

		return array( $meta_type, $type );
	}

}