<?php
namespace Sellkit\Blocks\Inner_Block;

/**
 * Checkout_Payment_Continue class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Checkout_Payment_Continue {
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
	 * Render payment continue.
	 *
	 * @param string $content Block content.
	 * @param array  $attributes Block attributes.
	 * @since 2.3.0
	 * @return string
	 */
	public function checkout_payment_continue( $content, $attributes ) {
		add_filter( 'sellkit_block_checkout_place_order_btn_text', function() {
			return esc_html( $attributes['paymentContinueButtonText'] );
		} );

		ob_start();
		?>
			<section class="place-order sellkit-one-page-checkout-place-order <?php echo esc_attr( $attributes['customClassName'] ); ?>">
				<noscript>
					<?php
					//phpcs:disable
					/* translators: $1 and $2 opening and closing emphasis tags respectively */
					printf( esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'sellkit' ), '<em>', '</em>' );
					//phpcs:enable
					?>
					<br/><button type="submit" class="button alt sellkit-checkout-widget-primary-button" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'sellkit' ); ?>"><?php esc_html_e( 'Update totals', 'sellkit' ); ?></button>
				</noscript>

				<?php $order_button_text = $attributes['paymentContinueButtonText']; ?>

				<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

				<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt sellkit-checkout-widget-primary-button" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // @codingStandardsIgnoreLine ?>

				<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

				<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
				<?php do_action( 'sellkit_block_checkout_required_hidden_fields' ); ?>
				<?php /* Hide place order button container after ajax call update. we already have it at bottom  */ ?>
				<script>
					jQuery( document ).ajaxComplete( function() {
						jQuery( '#payment > .place-order' ).css( 'display', 'none' ).hide();
					} );
				</script>
			</section>
		<?php
		$final_content = ob_get_clean();

		return str_replace( '</div>', $final_content . '</div>', $content );
	}
}
