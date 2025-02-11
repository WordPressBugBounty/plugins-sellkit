<?php

defined( 'ABSPATH' ) || die();

class Sellkit_Elementor_Optin_Field_Radio extends Sellkit_Elementor_Optin_Field_Base {

	public static function get_field_type() {
		return 'radio';
	}

	public function get_input_type() {
		return 'radio';
	}

	public function render_content() {
		$field   = $this->field;
		$options = preg_split( "/(\r\n|\n|\r)/", $field['field_options'], -1, PREG_SPLIT_NO_EMPTY );

		if ( empty( $options ) ) {
			return;
		}

		?>
		<div class="sellkit-field-subgroup <?php echo esc_attr( $field['inline_list'] ); ?>">
			<?php $this->render_options( $options, $field['required'] ); ?>
		</div>
		<?php
	}

	private function render_options( $options, $required ) {
		foreach ( $options as $key => $option ) {
			$id              = $this->get_id();
			$option_id       = 'optin-field-' . $id . '-' . $key;
			$option_label    = $option;
			$option_value    = $option;
			$option_name     = "fields[{$id}]";
			$option_required = $required ? 'required' : '';

			if ( false !== strpos( $option, '|' ) ) {
				list( $option_label, $option_value ) = explode( '|', $option );
			}

			?>
			<span class="sellkit-field-option sellkit-field-option-radio">
				<input
					type="radio"
					id="<?php echo esc_attr( $option_id ); ?>"
					class="sellkit-field"
					name="<?php echo esc_attr( $option_name ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					<?php echo esc_attr( $option_required ); ?>>
				<label
					for="<?php echo esc_attr( $option_id ); ?>"
					class="sellkit-field-label sellkit-field-option-label">
					<?php echo esc_html( $option_label ); ?>
				</label>
			</span>
			<?php
		}
	}

	public static function get_additional_controls() {
		$commons = parent::get_common_controls();

		return [
			'label' => $commons['label'],
			'field_options' => [
				'label'      => esc_html__( 'Options', 'sellkit' ),
				'type'       => 'textarea',
				'conditions' => [ 'terms' => [ parent::get_type_condition() ] ],
				'default'    => '',
				'description' => esc_html__( 'Enter each option in a separate line. To differentiate between label and value, separate them with a pipe char ("|"). For example: First Name|f_name', 'sellkit' ),
			],
			'inline_list' => [
				'label'        => esc_html__( 'Inline List', 'sellkit' ),
				'type'         => 'switcher',
				'return_value' => 'sellkit-subgroup-inline',
				'conditions' => [ 'terms' => [ parent::get_type_condition() ] ],
				'default'      => '',
			],
			'required' => $commons['required'],
			'width_responsive' => $commons['width_responsive'],
		];
	}
}
