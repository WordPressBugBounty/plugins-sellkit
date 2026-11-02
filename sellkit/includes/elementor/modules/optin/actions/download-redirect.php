<?php

defined( 'ABSPATH' ) || die();

use Sellkit_Elementor_Optin_Ajaxhandler as AjaxHandler;

/**
 * Initializing the Download and Redirect action by extending Action base..
 *
 * @since 1.5.0
 */
class Sellkit_Elementor_Optin_Action_Download_Redirect extends Sellkit_Elementor_Optin_Action_Base {

	public static $instance;

	public static function get_instance() {
		if ( empty( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	public function __construct() {
		// Register download hooks.
		add_action( 'admin_post_sellkit_download_file', 'sellkit_handle_optin_file_download' );
		add_action( 'admin_post_nopriv_sellkit_download_file', 'sellkit_handle_optin_file_download' );
	}

	public function get_name() {
		return 'download_redirect';
	}

	// Just for the sake of override!
	public function get_title() {}

	// Just for the sake of override!
	public function add_controls( $widget ) {}

	// Just for the sake of override!
	public function add_action_section_controls( $widget ) {}

	public function run( AjaxHandler $ajax_handler ) {

		// Proceed only if all other actions are done.
		if ( ! $ajax_handler->is_success ) {
			return;
		}

		$settings        = $ajax_handler->form['settings'];
		$download_source = $settings['download_source'];

		switch ( $download_source ) {
			case 'file':
				$this->download_file( $ajax_handler, $settings['download_file'] );
				break;
			case 'url':
				$this->download_url( $ajax_handler, $settings['download_url'] );
				break;
		}

		$this->redirect( $ajax_handler, $settings['redirect_to'], $settings['redirect_url'] );
	}

	private function download_file( AjaxHandler $ajax_handler, $file ) {
		if ( empty( $file['files'] ) ) {
			return;
		}

		$file = $file['files'][0];

		$file_path = ! empty( $file['path'] ) ? sellkit_get_optin_download_real_path( $file['path'] ) : false;

		if ( false === $file_path ) {
			$admin_error = esc_html__( 'Download error: The file doesn\'t exist anymore.', 'sellkit' );
			return $ajax_handler->add_response( 'admin_errors', $admin_error );
		}

		return $ajax_handler->add_response( 'downloadURL', sellkit_get_optin_download_url( $file_path ) );
	}

	private function download_url( AjaxHandler $ajax_handler, $url ) {
		if ( empty( $url ) || empty( $url['url'] ) ) {
			return;
		}

		if ( ! sellkit_is_http_url( $url['url'] ) ) {
			$admin_error = esc_html__( 'Download error: Invalid file URL.', 'sellkit' );
			return $ajax_handler->add_response( 'admin_errors', $admin_error );
		}

		return $ajax_handler->add_response( 'downloadURL', $url['url'] );
	}

	/**
	 * Handles decision node if there is no page on the decision in funnel.
	 *
	 * @param object $funnel_old_data The old funnel data.
	 * @since 1.9.2
	 * @return array|null
	 */
	private function handle_decisiton( $funnel_old_data ) {
		if ( ! empty( $funnel_old_data->next_step_data['page_id'] ) ) {
			return;
		}

		if ( empty( $funnel_old_data->current_step_data['page_id'] ) ) {
			return;
		}

		$current_step_id = $funnel_old_data->current_step_data['page_id'];

		$funnel        = new Sellkit_Funnel( $current_step_id );
		$decicion_data = $funnel->next_step_data;
		$conditions    = $decicion_data['data']['conditions'];
		$funnel_data   = get_post_meta( $funnel->funnel_id, 'nodes', true );
		$next_no       = $funnel_data[ $decicion_data['targets'][1]['nodeId'] ];
		$next_yes      = $funnel_data[ $decicion_data['targets'][0]['nodeId'] ];
		$is_valid      = sellkit_conditions_validation( $conditions );
		$next_step     = $next_no;

		if ( $is_valid ) {
			$next_step = $next_yes;
		}

		return $next_step;
	}

	private function redirect( AjaxHandler $ajax_handler, $target, $url ) {
		// When redirect target is funnel next step.
		if ( 'funnel' === $target ) {
			$funnel = sellkit_funnel();

			if ( empty( $funnel->next_step_data ) || empty( $funnel->next_step_data['page_id'] ) ) {
				$next_step_data = $this->handle_decisiton( $funnel );

				if ( empty( $next_step_data ) ) {
					$ajax_handler
						->set_success( false )
						->add_response( 'errors', esc_html__( 'Internal server error: failed to find next funnel step.', 'sellkit' ) );
					return;
				}

				$funnel->next_step_data = $next_step_data;
			}

			$next_page_id  = sellkit_funnel()->next_step_data['page_id'];
			$current_url   = filter_input( INPUT_POST, 'referrer', FILTER_SANITIZE_URL );
			$current_query = wp_parse_url( $current_url, PHP_URL_QUERY );

			wp_parse_str( $current_query, $params );
			unset( $params['sellkit_step'] );

			// Nonce is already checked in AJAX handler class, so we ignore its phpcs warning.
			$next_step_url = add_query_arg( $params, get_permalink( intval( $next_page_id ) ) ); //phpcs:ignore WordPress.Security.NonceVerification
			return $ajax_handler->add_response( 'redirectURL', $next_step_url );
		}

		// When redirect target is a custom URL.
		if ( empty( $url ) || empty( $url['url'] ) ) {
			return;
		}

		if ( ! sellkit_is_http_url( $url['url'] ) ) {
			$admin_error = esc_html__( 'Redirect error: Invalid URL.', 'sellkit' );
			return $ajax_handler->add_response( 'admin_errors', $admin_error );
		}

		return $ajax_handler->add_response( 'redirectURL', $url['url'] );
	}
}
