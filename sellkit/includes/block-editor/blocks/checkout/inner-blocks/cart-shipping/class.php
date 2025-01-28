<?php
namespace Sellkit\Blocks\Inner_Block;

/**
 * Cart Shipping class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Checkout_Cart_Shipping {
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
	 * Update shipping label.
	 *
	 * @param string $label Shipping label.
	 * @param string $method Shipping method.
	 * @since 2.3.0
	 * @return string
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function cart_shipping_method_full_label( $label, $method ) {
		if ( WC()->cart->display_prices_including_tax() ) {
			return wc_price( $method->cost + $method->get_shipping_tax() );
		}

		return wc_price( $method->cost );
	}

	/**
	 * Render cart shipping.
	 *
	 * @param string $content Block content.
	 * @param array  $attributes Block attributes.
	 * @since 2.3.0
	 * @return string
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function checkout_cart_shipping( $content, $attributes ) {
		$packages = WC()->shipping()->get_packages();
		ob_start();

		foreach ( $packages as $index => $package ) {
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $index ] ) ? WC()->session->chosen_shipping_methods[ $index ] : '';

			$formatted_destination    = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
			$has_calculated_shipping  = isset( WC()->customer ) && ! empty( WC()->customer->has_calculated_shipping() );
			$show_shipping_calculator = ! empty( is_cart() && apply_filters( 'woocommerce_shipping_show_shipping_calculator', true, $index, $package ) );
			$calculator_text          = '';

			$chosen_method_2 = apply_filters( 'sellkit-block-shipping-methods-choosen-method', $chosen_method );

			$package_name = apply_filters(
				'woocommerce_shipping_package_name',
				// Translators: %d is the shipping package number.
				( ( esc_attr( $index ) + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'sellkit' ), esc_attr( $index + 1 ) ) : _x( 'Shipping', 'shipping packages', 'sellkit' ),
				esc_attr( $index ),
				$package
			);

			$available_methods    = $package['rates'];
			$show_package_details = count( $packages ) > 1;
			$product_names        = array();

			if ( count( $packages ) > 1 ) {
				foreach ( $package['contents'] as $item_id => $values ) {
					$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
				}
				$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
			}

			$package_details = implode( ', ', $product_names );

			?>
			<tr class="woocommerce-shipping-totals shipping <?php echo esc_attr( $attributes['customClassName'] ); ?>">
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
										add_filter( 'woocommerce_cart_shipping_method_full_label', [ $this, 'cart_shipping_method_full_label' ], 999, 2 );

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
					<?php elseif ( ! $has_calculated_shipping || ! $formatted_destination ) : ?>
						<?php
						if ( is_cart() && 'no' === get_option( 'woocommerce_enable_shipping_calc' ) ) {
							echo wp_kses_post( apply_filters( 'woocommerce_shipping_not_enabled_on_cart_html', esc_html__( 'Shipping costs are calculated during checkout.', 'sellkit' ) ) );
						} else {
							echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', esc_html__( 'Enter your address to view shipping options.', 'sellkit' ) ) );
						}
						?>
					<?php elseif ( ! is_cart() ) : ?>
						<?php echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', esc_html__( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'sellkit' ) ) ); ?>
					<?php else : ?>
						<?php
						// Translators: $s shipping destination.
						echo wp_kses_post( apply_filters( 'woocommerce_cart_no_shipping_available_html', sprintf( esc_html__( 'No shipping options were found for %s.', 'sellkit' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' ) ) );
						$calculator_text = esc_html__( 'Enter a different address', 'sellkit' );
						?>
					<?php endif; ?>

					<?php if ( $show_package_details ) : ?>
						<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
					<?php endif; ?>

					<?php if ( $show_shipping_calculator ) : ?>
						<?php woocommerce_shipping_calculator( $calculator_text ); ?>
					<?php endif; ?>
				</td>
			</tr>

			<?php if ( $chosen_method !== $chosen_method_2 || ( is_array( $available_methods ) && 1 === count( $available_methods ) ) ) : ?>
				<script>
					jQuery( document ).ready( function() {
						jQuery( "input[value='<?php echo esc_js( $chosen_method_2 ); ?>']" ).prop( 'checked', true ).trigger( 'change' );
					} );
				</script>
			<?php endif; ?>
			<?php
		}

		$final_content = ob_get_clean();

		return str_replace( '</div>', $final_content . '</div>', $content );
	}
}
