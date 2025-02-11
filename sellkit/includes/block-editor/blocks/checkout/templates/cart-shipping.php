<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

$formatted_destination    = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
$show_shipping_calculator = ! empty( $show_shipping_calculator );
$calculator_text          = '';

$chosen_method_2 = apply_filters( 'sellkit-shipping-methods-choosen-method', $chosen_method );

?>
<tr class="woocommerce-shipping-totals shipping">
	<th><?php echo wp_kses_post( $package_name ); ?></th>
	<td data-title="<?php echo esc_attr( $package_name ); ?>">
		<?php if ( $available_methods ) : ?>
			<table id="shipping_method" class="woocommerce-shipping-methods sellkit-shipping-methods-one-page sellkit-checkout-widget-divider">
				<?php foreach ( $available_methods as $method ) : ?>
					<tr class="sellkit-checkout-widget-divider">
						<td class="sellkit-shipping-method-t1 sellkit-checkout-widget-divider">
							<label class="wrp">
								<?php
								if ( 1 < count( $available_methods ) ) {
									printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', esc_attr( $index ), esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); // WPCS: XSS ok.
								} else {
									printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', esc_attr( $index ), esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ) ); // WPCS: XSS ok.
								}
								?>
								<span class="checkmark"></span>
								<span class="labels"><?php echo esc_html( $method->label ); ?></span>
							</label>
						</td>
						<td class="sellkit-shipping-method-t3 sellkit-checkout-widget-divider">
						<?php
							add_filter( 'woocommerce_cart_shipping_method_full_label', function( $label, $method ) {
								if ( WC()->cart->display_prices_including_tax() ) {
									return wc_price( $method->cost + $method->get_shipping_tax() );
								}

								return wc_price( $method->cost );
							}, 999, 2 );

							echo wc_cart_totals_shipping_method_label( $method ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
						</td>
					</tr>
				<?php endforeach; ?>
					</table>
			<?php if ( is_cart() ) : ?>
				<p class="woocommerce-shipping-destination">
					<?php
					if ( $formatted_destination ) {
						// Translators: $s shipping destination.
						printf( esc_html__( 'Shipping to %s.', 'sellkit' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' );
						$calculator_text = esc_html__( 'Change address', 'sellkit' );
					} else {
						echo wp_kses_post( apply_filters( 'woocommerce_shipping_estimate_html', esc_html__( 'Shipping options will be updated during checkout.', 'sellkit' ) ) );
					}
					?>
				</p>
			<?php endif; ?>
			<?php
		elseif ( ! $has_calculated_shipping || ! $formatted_destination ) :
			if ( is_cart() && 'no' === get_option( 'woocommerce_enable_shipping_calc' ) ) {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_not_enabled_on_cart_html', esc_html__( 'Shipping costs are calculated during checkout.', 'sellkit' ) ) );
			} else {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', esc_html__( 'Enter your address to view shipping options.', 'sellkit' ) ) );
			}
		elseif ( ! is_cart() ) :
			echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', esc_html__( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'sellkit' ) ) );
		else :
			// Translators: $s shipping destination.
			echo wp_kses_post( apply_filters( 'woocommerce_cart_no_shipping_available_html', sprintf( esc_html__( 'No shipping options were found for %s.', 'sellkit' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' ) ) );
			$calculator_text = esc_html__( 'Enter a different address', 'sellkit' );
		endif;
		?>

		<?php if ( $show_package_details ) : ?>
			<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
		<?php endif; ?>

		<?php if ( $show_shipping_calculator ) : ?>
			<?php woocommerce_shipping_calculator( $calculator_text ); ?>
		<?php endif; ?>
	</td>
</tr>

<?php if ( $chosen_method !== $chosen_method_2 || 1 === count( $available_methods ) ) : ?>
	<script>
		jQuery( document ).ready( function() {
			jQuery( "input[value='<?php echo esc_js( $chosen_method_2 ); ?>']" ).prop( 'checked', true ).trigger( 'change' );
		} );
	</script>
<?php endif; ?>
