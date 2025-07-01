<?php

namespace Sellkit\Blocks\Checkout\Fields;

defined( 'ABSPATH' ) || die();

use Sellkit\Blocks\Checkout\Fields\Base;

/**
 * Class hidden.
 *
 * @since 2.3.5
 */
class Hidden extends Base {
	/**
	 * Type of field.
	 *
	 * @return string
	 * @since 2.3.5
	 */
	public function type() {
		return 'hidden';
	}

	/**
	 * Customized html per field.
	 *
	 * @param string $field field html string.
	 * @param array  $args checkout fields options.
	 * @param string $key key of field.
	 * @return void
	 * @since 2.3.5
	 */
	public function field( $field, $args, $key ) {
		$placeholder = $this->placeholder_required_value( $args );

		?>
			<p id="<?php echo esc_attr( $key ); ?>_field" data-priority="">
				<span class="woocommerce-input-wrapper">
					<input
						type="hidden"
						class="input-hidden empty"
						name="<?php echo esc_attr( $key ); ?>"
						id="<?php echo esc_attr( $key ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						value="<?php echo ( array_key_exists( 'default', $args ) ) ? esc_attr( $args['default'] ) : ''; ?>"
					>
				</span>
			</p>
		<?php
	}
}
