<?php

defined( 'ABSPATH' ) || die();

class Sellkit_Elementor_Optin_Field_Address extends Sellkit_Elementor_Optin_Field_Base {

	public static function get_field_type() {
		return 'address';
	}

	public function get_input_type() {
		return 'text';
	}

	public function render_content() {
		?>
		<input <?php echo $this->widget->get_render_attribute_string( 'field-' . esc_attr( $this->get_id() ) ); ?>>
		<?php

		$attributes = [
			'type'        => 'hidden',
			'name'        => 'fields[hidden_' . $this->get_id() . ']',
			'id'          => 'optin-field-hidden-' . $this->get_id(),
			'class'       => $this->get_class(),
			'data-type'   => 'hidden',
		];

		$this->widget->add_render_attribute( 'field-hidden-' . $this->get_id(), $attributes );
		?>
		<input <?php echo $this->widget->get_render_attribute_string( 'field-hidden-' . $this->get_id() ); ?>>
		<?php
	}

	public static function get_additional_controls() {
		$commons = parent::get_common_controls();

		$subtext1    = esc_html__( 'Set your google API key in ', 'sellkit' );
		$subtext2    = esc_html__( 'Sellkit Settings ', 'sellkit' );
		$setting_url = admin_url() . 'admin.php?page=sellkit-settings#/';

		return [
			'address_google_api_key_help' => [
				'type'      => 'raw_html',
				'conditions' => [ 'terms' => [ parent::get_type_condition() ] ],
				'raw'       => sprintf(
					'<small>%1$s<a target="_blank" href="%2$s">%3$s<i class="fa fa-external-link-square"></i></a></small>.',
					$subtext1,
					$setting_url,
					$subtext2
				),
			],
			'label' => $commons['label'],
			'field_value' => $commons['field_value'],
			'placeholder' => $commons['placeholder'],
			'required' => $commons['required'],
			'width_responsive' => $commons['width_responsive'],
		];
	}
}
