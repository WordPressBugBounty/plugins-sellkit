<?php
namespace Sellkit\Blocks\Inner_Block;

use Sellkit\Blocks\Sellkit_Blocks;

/**
 * Smart Coupon Expiration class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Smart_Coupon_Expiration {
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
	 * Render smart coupon expiration date.
	 *
	 * @param string $content Block content.
	 * @since 2.3.0
	 * @return string
	 */
	public function smart_coupon_expiration( $content ) {
		return $content;
	}
}
