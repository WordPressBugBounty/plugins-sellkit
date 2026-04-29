<?php

defined( 'ABSPATH' ) || die();

use Sellkit\Global_Checkout\Checkout;

/**
 * Sellkit global checkout.
 *
 * @since 1.7.4
 */
class Sellkit_Global_Checkout {
	/**
	 * Class construct.
	 *
	 * @since 1.7.4
	 */
	public function __construct() {
		add_action( 'wp_ajax_sellkit_global_checkout_id', [ $this, 'get_global_checkout_funnel_id' ] );
		add_action( 'wp_ajax_sellkit_global_checkout_toggle_status', [ $this, 'change_status' ] );
	}

	/**
	 * Get global checkout id.
	 *
	 * @since 1.7.4
	 */
	public function get_global_checkout_funnel_id() {
		check_ajax_referer( 'sellkit', 'nonce' );

		$global_checkout_id = get_option( Checkout::SELLKIT_GLOBAL_CHECKOUT_OPTION, 0 );

		// Post not exists.
		if ( false === get_post_status( $global_checkout_id ) ) {
			wp_send_json_success( 0 );
		}

		wp_send_json_success( $global_checkout_id );
	}

	/**
	 * Set global checkout funnel post status (activate / deactivate).
	 *
	 * Expects POST `status` as the target status: `publish` or `draft`.
	 *
	 * @since 1.7.4
	 */
	public function change_status() {
		check_ajax_referer( 'sellkit', 'nonce' );

		$target_status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$post_id       = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! in_array( $target_status, [ 'publish', 'draft' ], true ) || ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', 'sellkit' ) ] );
		}

		$post = get_post( $post_id );

		if ( ! $post || 'sellkit-funnels' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Invalid funnel.', 'sellkit' ) ] );
		}

		$global_checkout_id = (int) get_option( Checkout::SELLKIT_GLOBAL_CHECKOUT_OPTION, 0 );

		if ( $global_checkout_id !== $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Not the Global Checkout funnel.', 'sellkit' ) ] );
		}

		$updated = wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => $target_status,
			],
			true
		);

		if ( is_wp_error( $updated ) || ! $updated ) {
			wp_send_json_error( [ 'message' => __( 'Could not update status.', 'sellkit' ) ] );
		}

		$saved_status = get_post_status( $post_id );

		wp_send_json_success( $saved_status ? $saved_status : $target_status );
	}
}

new Sellkit_Global_Checkout();
