<?php
namespace Sellkit\Blocks\Inner_Block;

use Sellkit\Blocks\Sellkit_Blocks;

/**
 * Smart Coupon Code class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Smart_Coupon_Code {
	/**
	 * Smart_Coupon_Code constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}
	}

	/**
	 * Enqueue smart coupon code script.
	 *
	 * @since 2.3.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'sellkit-block-smart-coupon-code-script',
			sellkit()->plugin_url() . 'assets/dist/blocks/smart-coupon-code.js',
			[ 'jquery', 'wp-util' ],
			sellkit()->version(),
			true
		);

		wp_localize_script('sellkit-block-smart-coupon-code-script', 'sellkit_smart_coupon_code', [
			'nonce' => wp_create_nonce( 'sellkit_smart_coupon_code' )
		] );
	}

	/**
	 * Register block meta.
	 *
	 * @since 2.3.0
	 */
	public function register_block_meta() {
		register_block_type_from_metadata(
			__DIR__
		);
	}

	/**
	 * Render smart coupon code.
	 *
	 * @param string $content Block content.
	 * @since 2.3.0
	 * @return string
	 */
	public function smart_coupon_code( $content ) {
		return $content;
	}
}
