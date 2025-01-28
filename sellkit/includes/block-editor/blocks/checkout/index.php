<?php
namespace Sellkit\Blocks\Render;

use Sellkit\Blocks\Sellkit_Blocks;
use Sellkit\Global_Checkout\Checkout as Global_Checkout;

/**
 * Checkout block.
 *
 * @since 2.3.0
 */
class Checkout {
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
		$shipping_destination = get_option( 'woocommerce_ship_to_destination', true );

		$inner_blocks = [
			'checkout-contact-information' => 'block-editor/blocks/checkout/inner-blocks/contact-information/class',
			'checkout-form-shipping' => 'block-editor/blocks/checkout/inner-blocks/form-shipping/class',
			'checkout-cart-shipping' => 'block-editor/blocks/checkout/inner-blocks/cart-shipping/class',
			'checkout-review-order' => 'block-editor/blocks/checkout/inner-blocks/review-order/class',
			'checkout-billing-details' => 'block-editor/blocks/checkout/inner-blocks/billing-details/class',
			'checkout-payment' => 'block-editor/blocks/checkout/inner-blocks/payment/class',
			'checkout-payment-continue' => 'block-editor/blocks/checkout/inner-blocks/payment-continue/class',
		];

		if ( 'billing_only' === $shipping_destination ) {
			$inner_blocks = [
				'checkout-contact-information' => 'block-editor/blocks/checkout/inner-blocks/contact-information/class',
				'checkout-billing-details' => 'block-editor/blocks/checkout/inner-blocks/billing-details/class',
				'checkout-cart-shipping' => 'block-editor/blocks/checkout/inner-blocks/cart-shipping/class',
				'checkout-review-order' => 'block-editor/blocks/checkout/inner-blocks/review-order/class',
				'checkout-payment' => 'block-editor/blocks/checkout/inner-blocks/payment/class',
				'checkout-payment-continue' => 'block-editor/blocks/checkout/inner-blocks/payment-continue/class',
			];
		}
		
		return $inner_blocks;
	}

	/**
	 * Clears all custom actions and filters related to SellKit checkout process.
	 *
	 * @since 2.3.0
	 */
	public function clear_sellkit_checkout_custom_hooks() {
		remove_all_actions( 'sellkit_block_checkout_shipping_fields' );
		remove_all_actions( 'sellkit_block_checkout_billing_fields' );
		remove_all_actions( 'sellkit_block_checkout_after_shipping_section' );
		remove_all_actions( 'sellkit-checkout-widget-custom-coupon-form' );
		remove_all_actions( 'sellkit-checkout-widget-custom-coupon-form' );
		remove_all_actions( 'woocommerce_checkout_update_order_review' );
		remove_all_filters( 'woocommerce_locate_template' );
	}

	/**
	 * Render block in front-end.
	 *
	 * @param array $attributes Block attributes.
	 * @param string $content Block content.
	 * @param \WP_Block $block Block object.
	 * @since 2.3.0
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		if ( ! function_exists( 'wc' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			Sellkit_Blocks::load_scripts( 'checkout' );
			Sellkit_Blocks::load_scripts( 'checkout', 'checkout-external', [ 'wc-checkout', 'wc-country-select', 'wp-api' ] );

			wp_localize_script('sellkit-block-checkout-script', 'sellkit_block_checkout', [
				'nonce' => wp_create_nonce( 'sellkit_block_checkout' ),
				'wcNeedShipping' => ( function_exists( 'WC' ) ) ? WC()->cart->needs_shipping() : '',
				'url' => [
					'assets' => sellkit()->plugin_url() . 'assets/',
				],
			] );
		}

		$block_html = '';
		$wrapper_attributes = get_block_wrapper_attributes( [
			'class' => 'sellkit-checkout-widget-one-page-build sellkit-block-checkout-main-wrapper',
		] );

		do_action( 'sellkit_block_before_checkout_form' );

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
				$block_html .= $new_class->$block_name( $block_content, $inner_block->attributes );
			}
		}

		global $woocommerce;

		$checkout = $woocommerce->checkout;

		do_action( 'woocommerce_before_checkout_form', $checkout );

		// If checkout registration is disabled and not logged in, the user cannot checkout.
		if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
			echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', esc_html__( 'You must be logged in to checkout.', 'sellkit' ) ) );
			return;
		}

		$extra_class = '';

		if ( WC()->cart && ! WC()->cart->needs_shipping() ) {
			$extra_class = 'sellkit-checkout-virtual-session';
		}

		$block_html = sprintf(
			'
			<div %1$s>
				<div class="woocommerce">
					<div id="sellkit-checkout-widget-id">
						<form name="checkout" method="post" class="checkout woocommerce-checkout %2$s" action="%3$s" enctype="multipart/form-data">
							%4$s
						</form>
						%5$s
					</div>
				</div>
			</div>
			',
			wp_kses_data( $wrapper_attributes ),
			esc_attr( $extra_class ),
			esc_url( wc_get_checkout_url() ),
			stripslashes( $block_html ),
			do_action( 'woocommerce_after_checkout_form', $checkout )
		);

		$checkout_id        = get_the_ID();
		$step_data          = get_post_meta( $checkout_id, 'step_data', true );
		$global_checkout    = apply_filters( 'sellkit_global_checkout_activated', false );
		$global_checkout_id = get_option( Global_Checkout::SELLKIT_GLOBAL_CHECKOUT_OPTION, 0 );
		$current_funnel_id  = ( ! empty( $step_data ) ) ? $step_data['funnel_id'] : -1;
		$empty_cart_msg     = esc_html( $attributes['emptyCartMessage'] );

		$order_key = ! empty( sellkit_htmlspecialchars( INPUT_GET, 'order-key' ) ) ? sellkit_htmlspecialchars( INPUT_GET, 'order-key' ) : sellkit_htmlspecialchars( INPUT_GET, 'key' );

		if (
			$global_checkout &&
			is_wc_endpoint_url( 'order-received' ) &&
			! empty( $order_key )
		) {
			$order_id = wc_get_order_id_by_order_key( $order_key );

			if ( empty( $order_id ) ) {
				$block_html = sprintf(
					'
					<div %1$s>
						<div class="woocommerce">
							<div id="sellkit-checkout-widget-id">
								<div class="woocommerce-info">%2$s</div>
							</div>
						</div>
					</div>'
					,
					wp_kses_data( $wrapper_attributes ),
					wp_kses_post( $empty_cart_msg )
				);

				return $block_html;
			}
		}

		if (
			$global_checkout &&
			0 === WC()->cart->get_cart_contents_count() &&
			! is_wc_endpoint_url( 'order-received' )
		) {
			$block_html = sprintf(
				'
				<div %1$s>
					<div class="woocommerce">
						<div id="sellkit-checkout-widget-id">
							<div class="woocommerce-info">%2$s</div>
						</div>
					</div>
				</div>'
				,
				wp_kses_data( $wrapper_attributes ),
				wp_kses_post( $empty_cart_msg )
			);

			return $block_html;
		}

		if (
			false === $global_checkout &&
			intval( $global_checkout_id ) !== intval( $current_funnel_id ) &&
			is_array( $step_data ) &&
			(
				! array_key_exists( 'data', $step_data ) ||
				! array_key_exists( 'list', $step_data['data']['products'] )
			)
		) {
			$block_html = sprintf(
				'
				<div %1$s>
					<div class="woocommerce">
						<div id="sellkit-checkout-widget-id">
							<div class="woocommerce-info">%2$s</div>
						</div>
					</div>
				</div>'
				,
				wp_kses_data( $wrapper_attributes ),
				wp_kses_post( $empty_cart_msg )
			);

			return $block_html;
		}

		add_filter( 'woocommerce_is_checkout', function() {
			return true;
		}, 10 );

		$this->clear_sellkit_checkout_custom_hooks();

		return $block_html;
	}
}
