<?php
namespace Sellkit\Blocks\Inner_Block;

use Sellkit\Blocks\Helpers\Checkout\Helper;

/**
 * Form Shipping class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Checkout_Form_Shipping {
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
	 * Print shipping field in frontend using our customized fields.
	 *
	 * @param array  $fields : shipping fields.
	 * @param object $checkout : checkout object.
	 * @param array  $attributes Block attributes.
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function sellkit_checkout_shipping_field( $fields, $checkout, $attributes = [] ) {
		$default_text = [
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_postcode',
			'shipping_city',
		];

		$default_select = [
			'shipping_country',
			'shipping_state',
		];

		$default_tel = [
			'shipping_phone',
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

		$block_shipping_fields = $attributes['locations'];
		$default_fields        = Helper::assign_settings_per_field( $fields, $block_shipping_fields, 'shipping' );
		$priority              = array_column( $default_fields, 'priority' );

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
	 * Render form shipping.
	 *
	 * @param string $content Block content.
	 * @param array  $attributes Block attributes.
	 * @since 2.3.0
	 * @return string
	 */
	public function checkout_form_shipping( $content, $attributes ) {
		add_action( 'sellkit_block_checkout_shipping_fields', [ $this, 'sellkit_checkout_shipping_field' ], 10, 3 );

		global $woocommerce;

		$checkout = $woocommerce->checkout;

		$show_title = apply_filters( 'sellkit-checkout-disable-shipping-fields-title', true );

		ob_start();

		if ( ! empty( WC()->cart ) && WC()->cart->needs_shipping() ) : ?>
			<div class="woocommerce-shipping-fields sellkit-one-page-checkout-shipping sellkit-checkout-local-fields <?php echo esc_attr( $attributes['customClassName'] ); ?>">
				<div class="shipping_address">
					<?php if ( true === $show_title ) : ?>
					<div id="shipping_text_title" class="header heading" style="width:100%">
						<?php echo esc_html( $attributes['shippingHeadingText'] ); ?>
					</div>
					<?php endif; ?>

					<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

					<div class="woocommerce-shipping-fields__field-wrapper" id="sellkit-checkout-widget-shipping-fields">
						<?php
							$fields = $checkout->get_checkout_fields( 'shipping' );

							$this->sellkit_checkout_shipping_field( $fields, $checkout, $attributes );
						?>
					</div>

					<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="sellkit-woocommerce-additional-fields">
			<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>
			<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
		</div>
		<?php do_action( 'sellkit_block_checkout_after_shipping_section' );
		?>
		<section class="sellkit-one-page-shipping-methods">
		<?php if ( ! empty( WC()->cart ) && WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
				<?php WC()->cart->calculate_totals(); ?>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>
		</section>
		<?php
		$final_content = ob_get_clean();

		return str_replace( '</div>', $final_content . '</div>', $content );
	}
}
