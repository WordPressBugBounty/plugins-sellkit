<?php
namespace Sellkit\Blocks\Inner_Block;

use Sellkit\Blocks\Helpers\Checkout\Helper;

/**
 * Billing Details class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Checkout_Billing_Details {
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
	 * Print billing field in frontend using our customized fields.
	 *
	 * @param array  $fields : billing fields.
	 * @param object $checkout : checkout object.
	 * @param array  $attributes Block attributes.
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function sellkit_checkout_billing_field( $fields, $checkout, $attributes = [] ) {
		$default_text = [
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_address_2',
			'billing_postcode',
			'billing_city',
		];

		$default_select = [
			'billing_country',
			'billing_state',
		];

		$default_tel = [
			'billing_phone',
		];

		foreach ( $fields as $key => $details ) {
			if ( in_array( $key, $default_text, true ) ) {
				$fields[ $key ]['type'] = 'text';
			}

			if ( in_array( $key, $default_select, true ) ) {
				$fields[ $key ]['type'] = 'select';
			}

			if ( in_array( $key, $default_tel, true ) ) {
				$fields[ $key ]['type'] = 'tel';
			}
		}

		$block_billing_fields = $attributes['locations'];
		$default_fields       = Helper::assign_settings_per_field( $fields, $block_billing_fields, 'billing' );
		$priority             = array_column( $default_fields, 'priority' );

		array_multisort( $priority, SORT_ASC, $default_fields );

		Helper::register_fields();

		foreach ( $default_fields as $key => $details ) {
			if ( ! array_key_exists( 'type', $details ) ) {
				continue;
			}

			$class = $details['type'];
			$class = '\Sellkit\Blocks\Checkout\Fields\\' . ucfirst( $class );
			$class = new $class();
			$field = '';

			$field = $class->final_html_structure( $field, $details, $key );
		}
	}

	/**
	 * Render billing details.
	 *
	 * @param string $content Block content.
	 * @param array  $attributes Block attributes.
	 * @since 2.3.0
	 * @return string
	 */
	public function checkout_billing_details( $content, $attributes ) {
		add_action( 'sellkit_block_checkout_billing_fields', [ $this, 'sellkit_checkout_billing_field' ], 10, 2 );
		add_filter( 'sellkit_checkout_block_select_address_text', function() {
			return esc_html( $attributes['billingDescriptionText'] );
		} );

		global $woocommerce;

		$checkout = $woocommerce->checkout;

		$shipping_destination = get_option( 'woocommerce_ship_to_destination', true );

		if ( 'billing_only' === $shipping_destination ) {
			$attributes['billingHeadingText']     = esc_html__( 'Billing & Shipping', 'sellkit' );
			$attributes['billingDescriptionText'] = '';
		}

		ob_start();
		?>
		<div class="woocommerce-billing-fields sellkit-one-page-checkout-billing sellkit-checkout-local-fields <?php echo esc_attr( $attributes['customClassName'] ); ?>">
			<div class="sellkit-one-page-checkout-billing-header heading">
				<?php echo esc_html( $attributes['billingHeadingText'] ); ?>
			</div>
			<p class="billing-desc sub-heading">
				<?php echo esc_html( $attributes['billingDescriptionText'] ); ?>
			</p>
			<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

			<?php if ( ! empty( WC()->cart ) && WC()->cart->needs_shipping() && 'billing_only' !== $shipping_destination ) : // Display this section just if we need to ship products. ?>

			<div class="billing-method" >
				<div>
					<label class="wrp method-a">
						<input type="radio" value="same" name="billing-method" class="sellkit-billing-method-a" >
						<span class="checkmark"></span>
						<span class="labels"><?php echo esc_html__( 'Same as shipping address', 'sellkit' ); ?></span>
					</label>
				</div>
				<hr>
				<div>
					<label class="wrp method-b">
						<input type="radio" value="diff" name="billing-method" class="sellkit-billing-method-b" <?php checked( true ); ?> >
						<span class="checkmark"></span>
						<span class="labels"><?php echo esc_html__( 'Use a different billing address', 'sellkit' ); ?></span>
					</label>
				</div>
			</div>

			<?php endif; ?>

			<?php

			$border_top = '';

			if ( 'billing_only' === $shipping_destination ) {
				$border_top = 'border-top';
			}

			?>

			<div class="woocommerce-billing-fields__field-wrapper <?php echo esc_attr( $border_top ); ?>" id="sellkit-checkout-billing-field-wrapper">
				<?php
					$fields = $checkout->get_checkout_fields( 'billing' );

					$this->sellkit_checkout_billing_field( $fields, $checkout, $attributes );
				?>
			</div>

			<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
		</div>
		<?php
		$final_content = ob_get_clean();

		return str_replace( '</div>', $final_content . '</div>', $content );
	}
}
