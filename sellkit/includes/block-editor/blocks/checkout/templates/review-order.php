<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit; ?>

<?php
if ( ! function_exists( 'checkout_order_hidden_quantity_review_order' ) ) {
	/**
	 * Hide checkout order product quantity input and show raw quantity.
	 *
	 * @param string $input_html default quantity input.
	 * @param int    $quantity product quantity in cart.
	 * @since 2.3.0
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	function checkout_order_hidden_quantity_review_order( $input_html, $quantity ) {
		$html = $input_html;
		echo sprintf(
			/** Translators: %s: product quantity */
			'<strong class="product-quantity">%s</strong>',
			esc_html( $quantity )
		);
	}
}

if ( isset( $review_order_attributes['disableCartEditing'] ) && $review_order_attributes['disableCartEditing'] ) {
	add_filter( 'sellkit-checkout-block-disable-quantity', 'checkout_order_hidden_quantity_review_order', 10, 2 );
}
?>
<table class="shop_table woocommerce-checkout-review-order-table" cellspacing="0">
	<thead>
		<?php ob_start(); ?>
		<tr>
			<th class="product-name"><?php esc_html_e( 'Product', 'sellkit' ); ?></th>
			<th class="product-total"><?php esc_html_e( 'Subtotal', 'sellkit' ); ?></th>
		</tr>
		<?php ob_get_clean(); ?>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				ob_start();
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<td class="product-name">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ) . '&nbsp;'; ?>
						<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
					<td class="product-total">
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<?php
				$row = ob_get_clean();

				do_action( 'sellkit-block-one-page-checkout-custom-order-item', $row, $_product, $cart_item, $cart_item_key );
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		do_action( 'sellkit-block-checkout-custom-coupon-form' );
		?>

	</tbody>

	<tfoot class="sellkit-checkout-widget-order-summary-tfoot">
		<tr class="cart-subtotal">
			<th class="sellkit-checkout-widget-divider"><?php esc_html_e( 'Subtotal', 'sellkit' ); ?></th>
			<td class="sellkit-checkout-widget-divider"><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php do_action( 'sellkit-block-checkout-display-shipping-price' ); ?>

		<?php
			add_filter( 'woocommerce_cart_totals_coupon_label', function( $label, $coupon ) {
				return sprintf(
					'%s<span class="sellkit-coupon-label">%s</span>',
					esc_html__( 'Discount', 'sellkit' ),
					esc_html( $coupon->get_code() )
				);
			}, 10, 2 );
		?>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th>
					<?php wc_cart_totals_coupon_label( $coupon ); ?>
					<?php
						$coupon = new \WC_Coupon( $coupon );
						$link   = esc_url( add_query_arg( 'remove_coupon', urlencode( $coupon->get_code() ), defined( 'WOOCOMMERCE_CHECKOUT' ) ? wc_get_checkout_url() : wc_get_cart_url() ) ); // phpcs:ignore
					?>
					<span
						class="woocommerce-remove-coupon"
						data-coupon="<?php echo esc_attr( $code ); ?>"
						data-link="<?php echo esc_attr( $link ); ?>"
					>
						<span class="woo-remove-coupon" title="<?php echo esc_attr__( 'Remove coupon', 'sellkit' ); ?>"></span>
					</span>
				</th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php /* ! shipping method moved. */ ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : /* phpcs:ignore */ ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<!-- Discount are hooked here -->
		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th class="sellkit-checkout-widget-divider sellkit-order-total"><?php esc_html_e( 'Total', 'sellkit' ); ?></th>
			<td class="sellkit-checkout-widget-divider sellkit-order-total"><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</tfoot>
</table>
<script>
	if ( typeof window.sellkitCheckoutMakeSureJsWorks === 'function' ) {
		window.sellkitCheckoutMakeSureJsWorks();
	}
</script>
<?php if ( ! wp_doing_ajax() ) : ?>
	<?php do_action( 'sellkit-block-checkout-after-order-summary' ); ?>
<?php endif; ?>
