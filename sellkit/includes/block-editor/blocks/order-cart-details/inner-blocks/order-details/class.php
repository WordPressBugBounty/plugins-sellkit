<?php
namespace Sellkit\Blocks\Inner_Block;

defined( 'ABSPATH' ) || die();

/**
 * Order Details class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Order_Details {
	/**
	 * Order.
	 *
	 * @var \WC_Order
	 * @since 2.3.0
	 * @access private
	 */
	private $order;

	/**
	 * Order_Details constructor.
	 *
	 * @param \WC_Order $order Order.
	 * @since 2.3.0
	 */
	public function __construct( $order ) {
		if ( empty( $order ) ) {
			return;
		}

		$this->order = $order;
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
	 * Render order details item.
	 *
	 * @param string $content Block content.
	 * @param array  $order   Order products.
	 * @since 2.3.0
	 * @return string
	 */
	public function order_details( $content, $order ) {
		if ( empty( $order['prices'] ) || empty( $this->order ) || ! is_a( $this->order, 'WC_Order' ) ) {
			return '';
		}

		$new_content = preg_replace( '/<\/div>/', '', $content, 1 );

		$html = sprintf(
			'<div class="sellkit-order-cart-details">%1$s %2$s</div></div>',
			$new_content,
			wp_kses_post( $this->get_content( $order['prices'] ) )
		);

		return $html;
	}

	/**
	 * Get order content.
	 *
	 * @param array $prices Prices.
	 * @since 2.3.0
	 * @return string
	 */
	private function get_content( $prices ) {
		ob_start();
		?>
		<div class="sellkit-order-cart-details-order-details-block-frontend">
			<div class="order-details-wrapper">
			<?php
			foreach ( $prices as $key => $total ) {
				if ( 'order_total' === $key ) {
					?>
					<div class="wc-block-components-totals-wrapper">
						<div class="order-total wc-block-components-totals-footer-item">
							<span class="wc-block-components-totals-item__label"><?php echo esc_html( $total['label'] ); ?></span>
							<div class="wc-block-components-totals-item__value">
								<span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-footer-item-tax-value"><?php echo ( 'payment_method' === $key ) ? esc_html( $total['value'] ) : wp_kses_post( $total['value'] ); ?></span>
							</div>
						</div>
					</div>
					<?php
					continue;
				}
				?>
				<div class="order-details-item">
					<span class="wc-block-components-totals-item__label"><?php echo esc_html( $total['label'] ); ?></span>
					<span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-item__value"><?php echo ( 'payment_method' === $key ) ? esc_html( $total['value'] ) : wp_kses_post( $total['value'] ); ?></span>
				</div>
				<?php
			}
			?>
			</div>
		<?php

		if ( $this->order && $this->order->get_customer_note() ) :
			?>
			<div class="wc-block-components-totals-wrapper">
				<div class="order-total wc-block-components-totals-footer-item">
					<span class="wc-block-components-totals-item__label"><?php esc_html_e( 'Note:', 'sellkit' ); ?></span>
					<div class="wc-block-components-totals-item__value">
						<span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-footer-item-tax-value"><?php echo wp_kses_post( nl2br( wptexturize( $this->order->get_customer_note() ) ) ); ?></span>
					</div>
				</div>
			</div>
			<?php
		endif;
		if ( is_a( $this->order, 'WC_Order' ) ) {
			do_action( 'woocommerce_order_details_after_order_table', $this->order );
		}
		?>
		</div>
		<?php
		return ob_get_clean();
	}
}
