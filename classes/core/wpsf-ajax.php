<?php
/**
 * Created by PhpStorm.
 * User: varun
 * Date: 07-02-2018
 * Time: 11:24 AM
 */

/**
 * Class WPSFramework_Ajax
 */
final class WPSFramework_Ajax extends WPSFramework_Abstract {
	/**
	 * _instance
	 *
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * WPSFramework_Ajax constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpsf-ajax', array( &$this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_wpsf-ajax', array( &$this, 'handle_ajax' ) );
	}

	/**
	 * @return null|\WPSFramework_Ajax
	 */
	public static function instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	/**
	 * Handles Ajax Request.
	 */
	public function handle_ajax() {
		if ( isset( $_REQUEST['wpsf-action'] ) ) {
			$action = $_REQUEST['wpsf-action'];
			$action = str_replace( '-', '_', strtolower( $action ) );
			if ( method_exists( $this, $action ) ) {
				$this->$action();
			} elseif ( has_action( 'wpsf_ajax_' . $action ) ) {
				do_action( 'wpsf_ajax_' . $action );
			}
		}

		wp_die();
	}

	/**
	 * Query Select Data.
	 */
	public function query_select_data() {
		$query_args = ( isset( $_REQUEST['query_args'] ) ) ? $_REQUEST['query_args'] : array();
		$data       = WPSFramework_Query::query( $_REQUEST['options'], $query_args, $_REQUEST['s'] );
		wp_send_json( $data );
		wp_die();
	}

	/**
	 * WPSF Gets icons.
	 */
	public function wpsf_get_icons() {
		do_action( 'wpsf_add_icons_before' );
		$jsons = apply_filters( 'wpsf_add_icons_json', glob( WPSF_DIR . '/fields/icon/*.json' ) );

		if ( ! empty( $jsons ) ) {
			foreach ( $jsons as $path ) {
				$object = wpsf_get_icon_fonts( 'fields/icon/' . basename( $path ) );
				if ( is_object( $object ) ) {
					echo ( count( $jsons ) >= 2 ) ? '<h4 class="wpsf-icon-title">' . $object->name . '</h4>' : '';
					foreach ( $object->icons as $icon ) {
						echo '<a class="wpsf-icon-tooltip" data-wpsf-icon="' . $icon . '" data-title="' . $icon . '"><span class="wpsf-icon wpsf-selector"><i class="' . $icon . '"></i></span></a>';
					}
				} else {
					echo '<h4 class="wpsf-icon-title">' . esc_html__( 'Error! Can not load json file.', 'wpsf-framework' ) . '</h4>';
				}
			}
		}

		do_action( 'wpsf_add_icons' );
		do_action( 'wpsf_add_icons_after' );
	}

	/**
	 * WPSF Modal Select.
	 */
	public function wpsf_modal_select() {
		if ( ! isset( $_REQUEST['data'] ) ) {
			wp_send_json_error();
		}

		$data = $_REQUEST['data'];

		$data['query_args']['option_key']   = false;
		$data['query_args']['option_value'] = false;
		$selected                           = ( isset( $_REQUEST['selected'] ) && is_array( $_REQUEST['selected'] ) ) ? $_REQUEST['selected'] : array();

		$post_data = WPSFramework_Query::query( $data['type'], $data['query_args'], $_REQUEST['search'] );
		ob_start();
		new WPSFramework_Modal_Search_Handler( $data['type'], $selected, $post_data, $data );
		$data = ob_get_clean();
		ob_flush();
		wp_send_json_success( $data );
		wp_die();
	}

}

return WPSFramework_Ajax::instance();
