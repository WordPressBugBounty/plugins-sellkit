<?php

namespace Sellkit\Blocks\Checkout\Fields;

defined( 'ABSPATH' ) || die();

use Sellkit\Blocks\Checkout\Fields\Base;

/**
 * Class select.
 *
 * @since 2.3.0
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class Select extends Base {
	/**
	 * Type of field.
	 *
	 * @return string
	 * @since 2.3.0
	 */
	public function type() {
		return 'select';
	}

	/**
	 * Adds additional class for fields if required.
	 *
	 * @param array  $args checkout fields options.
	 * @param string $key field key.
	 * @return array
	 * @since 2.3.0
	 */
	protected function additional_class( $args, $key ) {
		$args['class'][] = 'country-state';

		$country_key = 'sellkit-checkout-shipping-cc';

		if ( 'billing_state' === $key ) {
			$country_key = 'sellkit-checkout-billing-cc';
		}

		$country_code = sellkit_htmlspecialchars( INPUT_COOKIE, $country_key );

		$states = WC()->countries->get_states( $country_code );

		if ( ( 'billing_state' === $key || 'shipping_state' === $key ) && ( ! is_array( $states ) || empty( $states ) ) ) {
			return $args;
		}

		$args['class'][] = 'sellkit-checkout-field-select';

		return $args;
	}

	/**
	 * Customized html per field.
	 *
	 * @param string $field field html string.
	 * @param array  $args checkout fields options.
	 * @param string $key key of field.
	 * @return void
	 * @since 2.3.0
	 */
	public function field( $field, $args, $key ) {
		echo '<span class="sellkit-select-appearance"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512"><path d="M201.4 342.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 274.7 86.6 137.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z"/></svg></span>';

		if ( 'shipping_country' === $key || 'billing_country' === $key ) {
			$default = '';
			if ( array_key_exists( 'default', $args ) ) {
				$default = $args['default'];
			}

			$this->woocommerce_field_country( $key, $default );

			return;
		}

		if ( 'shipping_state' === $key || 'billing_state' === $key ) {
			$country_code = filter_input( INPUT_COOKIE, 'sellkit-checkout-shipping-cc' );

			if ( 'billing_state' === $key ) {
				$country_code = filter_input( INPUT_COOKIE, 'sellkit-checkout-billing-cc' );
			}

			if ( empty( $country_code ) ) {
				$country_code = 'US';
			}

			$states = WC()->countries->get_states( $country_code );

			$this->woocommerce_field_state( $key, $args, $states );

			return;
		}

		$placeholder = $this->placeholder_required_value( $args );

		?>
			<p id="<?php echo esc_attr( $key ); ?>_field">
				<span class="woocommerce-input-wrapper">
					<select
						name="<?php echo esc_attr( $key ); ?>"
						id="<?php echo esc_attr( $key ); ?>"
						autocomplete="country"
						data-placeholder="<?php echo esc_attr( $placeholder ); ?>"
						data-label="<?php echo ( array_key_exists( 'label', $args ) ) ? esc_attr( $args['label'] ) : ''; ?>"
					>
						<?php
							$default_value = ( array_key_exists( 'default', $args ) ) ? $args['default'] : '';
						?>
						<?php foreach ( $args['options'] as $value => $label ) : ?>
							<?php
								if ( $value === $default_value ) {
									echo sprintf(
										/** Translators: 1:value 2: label */
										'<option selected value="%1$s">%2$s</option>',
										esc_attr( $value ),
										esc_html( $label )
									);

									continue;
								}

								echo sprintf(
									/** Translators: 1:value 2: label */
									'<option value="%1$s">%2$s</option>',
									esc_attr( $value ),
									esc_html( $label )
								);
							?>
						<?php endforeach; ?>
					</select>
				</span>
			</p>
		<?php
	}

	/**
	 * Customize woocommerce country field.
	 *
	 * @param string $key field key.
	 * @param string $default field default value.
	 * @since 2.3.0
	 * @return void
	 */
	private function woocommerce_field_country( $key, $default ) {
		?>
			<p id="<?php echo esc_attr( $key ); ?>_field">
				<span class="woocommerce-input-wrapper">
					<?php
						$countries = WC()->countries->get_shipping_countries();

						if ( 'billing_country' === $key ) {
							$countries = WC()->countries->get_allowed_countries();
						}

						$field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" class="country_to_state" ><option value="">' . esc_html__( 'Select a country…', 'sellkit' ) . '</option>';

						foreach ( $countries as $country_key => $country_label ) {
							$selected = '';

							if ( $default === $country_key ) {
								$selected = 'selected="selected"';
							}

							$field .= '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $country_key ) . '" >' . esc_html( $country_label ) . '</option>';
						}

						$field .= '</select>';
						$field .= '<noscript><input type="submit" name="woocommerce_checkout_update_totals" value="' . esc_attr__( 'Update country', 'sellkit' ) . '" /></noscript>';

						echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</span>
			</p>
		<?php
	}

	/**
	 * Customize woocommerce state field.
	 *
	 * @param string $key field key.
	 * @param array  $args field options.
	 * @param array  $states country states.
	 * @since 2.3.0
	 * @return void
	 */
	private function woocommerce_field_state( $key, $args, $states ) {
		$placeholder = $this->placeholder_required_value( $args );

		?>
			<p id="<?php echo esc_attr( $key ); ?>_field">
				<span class="woocommerce-input-wrapper">
					<?php
						if ( ! is_array( $states ) || empty( $states ) ) {
							$state = filter_input( INPUT_COOKIE, 'sellkit-checkout-billing-state' );

							if ( 'shipping_state' === $key ) {
								$state = filter_input( INPUT_COOKIE, 'sellkit-checkout-shipping-state' );
							}

							if ( empty( $state ) ) {
								$state = '';
							}

							echo sprintf(
								/* translators: 1: placeholder 2: name 3: id 4: default value */
								'<input type="text" placeholder="%1$s" name="%2$s" id="%3$s" value="%4$s">',
								esc_attr( $placeholder ),
								esc_attr( $key ),
								esc_attr( 'sellkit-' . $key ),
								esc_attr( $state )
							);

							return;
						}

						$field = '<select id="sellkit-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" placeholder="' . esc_attr( $args['label'] ) . '">
						<option value="">' . esc_html__( 'Select a state…', 'sellkit' ) . '</option>';

						foreach ( $states as $state_key => $state_label ) {
							$field .= '<option value="' . esc_attr( $state_key ) . '" >' . esc_html( $state_label ) . '</option>';
						}

						$field .= '</select>';

						echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</span>
			</p>
		<?php
	}
}
