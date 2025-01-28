<?php
namespace Sellkit\Blocks\Inner_Block;

/**
 * Payment class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Checkout_Payment {
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
	 * Render payment.
	 *
	 * @param string $content Block content.
	 * @param array  $attributes Block attributes.
	 * @since 2.3.0
	 * @return string
	 */
	public function checkout_payment( $content, $attributes ) {
		add_filter( 'sellkit_checkout_block_secure_transaction_text', function() {
			return esc_html( $attributes['paymentDescriptionText'] );
		} );

		ob_start();

		if ( ! wp_doing_ajax() ) {
			do_action( 'woocommerce_review_order_before_payment' );

			$custom_class = ! empty( $attributes['customClass'] ) ? esc_attr( $attributes['customClassName'] ) : '';

			echo '<section class="' . esc_attr( $custom_class ) . '">';
		}

		if ( ! wp_doing_ajax() ) {
			?>
				<div id="payment_method_title" class="sellkit-one-page-checkout-payment-heading heading"><?php echo esc_html( $attributes['paymentHeadingText'] ); ?></div>
				<p class="sellkit-one-page-checkout-payment-desc sub-heading">
					<?php echo esc_html( $attributes['paymentDescriptionText'] ); ?>
				</p>
			<?php
		}
			?>
		<div id="payment" class="woocommerce-checkout-payment sellkit-one-page-checkout-payment-methods sellkit-checkout-widget-divider">
			<?php if ( ! empty( WC()->cart ) && WC()->cart->needs_payment() ) : ?>
				<ul class="wc_payment_methods payment_methods methods">
					<?php
					$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

					if ( ! empty( $available_gateways ) ) {
						foreach ( $available_gateways as $gateway ) {
							wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
							echo '<hr class="sellkit-checkout-widget-divider">';
						}
					} else {
						$wc_no_available_payment = apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'sellkit' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'sellkit' ) );

						echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . wp_kses_post( $wc_no_available_payment ) . '</li>';
					}
					?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		if ( ! wp_doing_ajax() ) {
			?>
				<div class="sellkit-one-page-checkout-payment-desc sub-heading">
					<?php wc_get_template( 'checkout/terms.php' ); ?>
				</div>
				<?php do_action( 'sellkit_block_checkout_after_term_and_condition' ); ?>
			<?php
			echo '</section>';
			do_action( 'woocommerce_review_order_after_payment' );
		}

		$final_content = ob_get_clean();

		return str_replace( '</div>', $final_content . '</div>', $content );
	}
}
