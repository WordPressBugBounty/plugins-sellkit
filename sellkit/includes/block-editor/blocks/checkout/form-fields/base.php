<?php

namespace Sellkit\Blocks\Checkout\Fields;

defined( 'ABSPATH' ) || die();

/**
 * Class base.
 *
 * @since 2.3.0
 */
abstract class Base {
	/**
	 * Type of supported fields.
	 *
	 * @return array
	 * @since 2.3.0
	 */
	public function supported_fields() {
		return [
			'text',
			'select',
			'tel',
		];
	}

	/**
	 * Return type of field.
	 *
	 * @return string
	 * @since 2.3.0
	 */
	abstract public function type();

	/**
	 * Print out field label using placeholder argument.
	 *
	 * @param array $args checkout fields options.
	 * @return void
	 * @since 2.3.0
	 */
	protected function placeholder( $args ) {
		if ( ! array_key_exists( 'label', $args ) ) {
			return;
		}

		$label = $args['label'];

		?>
			<div>
				<span class="mini-title">
					<?php echo esc_html( $label ); ?>
				</span>
			</div>
		<?php
	}

	/**
	 * Print out field label for those fields without placeholder attribute.
	 *
	 * @param array $args checkout fields options.
	 * @return void
	 * @since 2.3.0
	 */
	protected function label( $args ) {
		$label = $this->placeholder_required_value( $args );

		?>
			<label class="free-label"><?php echo esc_html( $label ); ?></label>
		<?php
	}

	/**
	 * Display error if required field is empty.
	 *
	 * @param array $args checkout fields options.
	 * @return void
	 * @since 2.3.0
	 */
	protected function required_validation( $args ) {
		if ( empty( $args['required'] ) ) {
			return;
		}

		?>
			<div class="sellkit-required-validation">
				<span class="required-alarm">
					<?php echo esc_html__( 'This field is required.', 'sellkit' ); ?>
				</span>
			</div>
		<?php
	}

	/**
	 * Empty element, is used to display postcode, phone etc validation errors.
	 *
	 * @param bool $enable enable or disable this element per field.
	 * @return void
	 * @since 2.3.0
	 */
	protected function global_errors( $enable ) {
		if ( false === $enable ) {
			return;
		}
		?>
			<div
				class="sellkit-checkout-widget-d-none sellkit-checkout-field-global-errors"
			>
			</div>
		<?php
	}


	/**
	 * Adds additional class for fields if required
	 *
	 * @param array  $args checkout fields options.
	 * @param string $key field key.
	 * @return array
	 * @since 2.3.0
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function additional_class( $args, $key ) {
		return $args;
	}

	/**
	 * Adds required validation class for fields.
	 *
	 * @param array $args checkout fields options.
	 * @return array
	 * @since 2.3.0
	 */
	private function set_required_class( $args ) {
		if ( ! empty( $args['required'] ) && ( true === $args['required'] || 1 === $args['required'] ) ) {
			if ( ! isset( $args['class'] ) || ! is_array( $args['class'] ) ) {
				$args['class'] = [];
			}
			$args['class'][] = 'validate-required';
		}

		return $args;
	}

	/**
	 * Check if this field is required.
	 *
	 * @param array $args field arguments.
	 * @since 2.3.0
	 * @return boolean
	 */
	protected function is_this_required( $args ) {
		if ( ! empty( $args['required'] ) && ( true === $args['required'] || 1 === $args['required'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare placeholder value and add * to it for required fields.
	 *
	 * @param array $args fields arguments.
	 * @since 2.3.0
	 * @return string
	 */
	protected function placeholder_required_value( $args ) {
		$placeholder = ( ! empty( $args['label'] ) ) ? $args['label'] : '';

		if ( ! empty( $args ) && $this->is_this_required( $args ) ) {
			$placeholder .= ' *';
		}

		return $placeholder;
	}

	/**
	 * We make sure our fields basic class exists in class array, in order to not have any style issue.
	 *
	 * @param array $args checkout field option.
	 * @since 2.3.0
	 * @return array
	 */
	private function make_sure_sellkit_native_classes_exists( $args ) {
		$classes     = $args['class'];
		$defaults    = [ 'sellkit-widget-checkout-fields', 'sellkit-checkout-fields-wrapper' ];
		$new_classes = [];
		$is_width    = true;

		if ( ! is_array( $classes ) ) {
			$classes = [];
		}

		foreach ( $defaults as $def ) {
			if ( ! in_array( $def, $classes, true ) ) {
				$new_classes[] = $def;
			}
		}

		// Assign w-100 class to those fields that have not width class.
		foreach ( $classes as $class ) {
			if ( 'form-row-wide' === $class ) {
				continue;
			}

			if ( false !== strpos( $class, 'w-' ) ) {
				$is_width = false;
			}

			$new_classes[] = $class;
		}

		if ( true === $is_width ) {
			$new_classes[] = 'w-100';
		}

		$args['class'] = $new_classes;

		return $args;
	}

	/**
	 * Gets fields default value from database if exists.
	 *
	 * @param array  $args checkout fields options.
	 * @param string $key checkout field key.
	 * @since 2.3.0
	 * @return array
	 */
	private function try_to_set_default_value( $args, $key ) {
		$user = get_current_user_id();

		$default = get_user_meta( $user, $key, true );

		if ( ! empty( $default ) ) {
			$args['default'] = $default;
		}

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
	abstract public function field( $field, $args, $key );

	/**
	 * Field final html structure.
	 *
	 * @param string $field html string.
	 * @param array  $args checkout fields options.
	 * @param string $key key of field.
	 * @return void
	 * @since 2.3.0
	 */
	public function final_html_structure( $field, $args, $key ) {
		$args  = $this->additional_class( $args, $key );
		$args  = $this->set_required_class( $args );
		$args  = $this->make_sure_sellkit_native_classes_exists( $args );
		$args  = $this->try_to_set_default_value( $args, $key );
		$class = implode( ' ', $args['class'] );

		// Remove all classes of fields.
		$field = preg_replace( '/class=".*?"/', '', $field, 1 );
		// Remove fields label.
		$field = preg_replace( '~<label(.*?)</label>~Usi', '', $field );

		?>
			<div class="<?php echo esc_attr( $class ); ?> sellkit-checkout-fields-wrapper" id="wrapper-<?php echo esc_attr( $key ); ?>">
				<?php
					$this->label( $args );
					$this->field( $field, $args, $key );
					$this->required_validation( $args );
					$this->global_errors( true );
				?>
			</div>
		<?php
	}
}
