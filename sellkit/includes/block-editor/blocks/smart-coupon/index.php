<?php
namespace Sellkit\Blocks\Render;

use Sellkit\Blocks\Sellkit_Blocks;

/**
 * Smart Coupon block.
 *
 * @since 2.3.0
 */
class Smart_Coupon {
	/**
	 * Check block activation.
	 *
	 * @since 2.3.0
	 * @return boolean
	 */
	public function is_active() {
		return true;
	}

	/**
	 * Register block from meta.
	 *
	 * @since 2.3.0
	 */
	public function register_block_meta() {
		register_block_type_from_metadata(
			__DIR__,
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Check block has inner blocks.
	 *
	 * @since 2.3.0
	 * @return boolean
	 */
	public function has_inner_blocks() {
		return true;
	}

	/**
	 * Check block has inner blocks.
	 *
	 * @since 2.3.0
	 * @return boolean
	 */
	public function get_inner_block() {
		return [
			'smart-coupon-code' => 'block-editor/blocks/smart-coupon/inner-blocks/smart-coupon-code/class',
			'smart-coupon-expiration' => 'block-editor/blocks/smart-coupon/inner-blocks/smart-coupon-expiration/class',
		];
	}


	/**
	 * Render block in front-end.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param \WP_Block $block Block object.
	 * @since 2.3.0
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$block_html = '';
		$wrapper_attributes = get_block_wrapper_attributes();

		foreach ( $block->inner_blocks as $inner_block ) {
			$block_name    = str_replace( 'sellkit-inner-blocks/', '', $inner_block->parsed_block['blockName'] );
			$block_name    = str_replace( '-', ' ', $block_name );
			$block_name    = ucwords( $block_name );
			$block_name    = str_replace(' ', '_', $block_name );
			$block_content = $inner_block->render();
			$class_name    = 'Sellkit\Blocks\Inner_Block\\' . ucwords( $block_name );

			if ( ! class_exists( $class_name ) ) {
				$block_html .= $inner_block->render();
				continue;
			}
			$new_class  = new $class_name();

			if ( method_exists( $new_class, $block_name ) ) {
				$block_html .= $new_class->$block_name( $block_content );
			}
		}

		$block_html = sprintf(
			'<div %1$s>%2$s</div>',
			wp_kses_data( $wrapper_attributes ),
			wp_kses_post( $block_html )
		);

		return $block_html;
	}
}
