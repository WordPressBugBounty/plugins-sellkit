<?php

namespace Sellkit\Compatibility\Wpml\Modules;

defined( 'ABSPATH' ) || die();

/**
 * Class Checkout_Billing
 *
 * This class handles the compatibility of the fields in Sellkit
 * with WPML for translation within Elementor modules.
 *
 * @since 2.3.2
 */
class Checkout_Billing extends \WPML_Elementor_Module_With_Items {
	/**
	 * Retrieves the field name that holds the items.
	 *
	 * @since 2.3.2
	 *
	 * @return string The name of the field that contains the items.
	 */
	public function get_items_field() {
		return 'billing_list';
	}

	/**
	 * Retrieves the fields that are translatable.
	 *
	 * @since 2.3.2
	 *
	 * @return array List of fields that support translations.
	 */
	public function get_fields() {
		return [
			'billing_list_placeholder',
			'billing_custom_value',
			'billing_custom_options',
		];
	}

	/**
	 * Retrieves the translation title for each field.
	 *
	 * @since 2.3.2
	 *
	 * @param string $field The field name.
	 *
	 * @return string The title for the translation of the field.
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'billing_list_placeholder':
				return esc_html__( 'Sellkit Checkout: Billing Fields Field Label', 'sellkit' );

			case 'billing_custom_value':
				return esc_html__( 'Sellkit Checkout: Billing Fields Default Value', 'sellkit' );

			case 'billing_custom_options':
				return esc_html__( 'Sellkit Checkout: Billing Fields Options', 'sellkit' );

			default:
				return '';
		}
	}

	/**
	 * Retrieves the editor type for each field.
	 *
	 * @since 2.3.2
	 *
	 * @param string $field The field name.
	 *
	 * @return string The editor type for the translation.
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'billing_list_placeholder':
				return 'LINE';

			case 'billing_custom_value':
				return 'LINE';

			case 'billing_custom_options':
				return 'AREA';

			default:
				return '';
		}
	}
}
