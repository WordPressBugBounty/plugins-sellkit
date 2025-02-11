<?php

defined( 'ABSPATH' ) || die();

class Sellkit_Elementor_Optin_Field_Hidden extends Sellkit_Elementor_Optin_Field_Base {

	public static function get_field_type() {
		return 'hidden';
	}

	public function get_input_type() {
		return 'hidden';
	}

	public function render_content() {
		?>
		<input <?php echo $this->widget->get_render_attribute_string( 'field-' . esc_attr( $this->get_id() ) ); ?>>
		<?php
	}

	public static function get_additional_controls() {
		$commons = parent::get_common_controls();

		return [
			'label' => $commons['label'],
			'field_value' => $commons['field_value'],
		];
	}
}
