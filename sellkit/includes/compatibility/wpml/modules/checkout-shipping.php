<?php

namespace Sellkit\Compatibility\Wpml\Modules;

defined( 'ABSPATH' ) || die();

/**
 * Class Checkout_Shipping
 *
 * This class handles the compatibility of the fields in Sellkit
 * with WPML for translation within Elementor modules.
 *
 * @since 2.3.2
 */
class Checkout_Shipping extends \WPML_Elementor_Module_With_Items {
	/**
	 * Retrieves the field name that holds the items.
	 *
	 * @since 2.3.2
	 *
	 * @return string The name of the field that contains the items.
	 */
	public function get_items_field() {
		return 'shipping_list';
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
			'shipping_list_placeholder',
			'shipping_custom_value',
			'shipping_custom_options',
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
			case 'shipping_list_placeholder':
				return esc_html__( 'Sellkit Checkout: Shipping Fields Field Label', 'sellkit' );

			case 'shipping_custom_value':
				return esc_html__( 'Sellkit Checkout: Shipping Fields Default Value', 'sellkit' );

			case 'shipping_custom_options':
				return esc_html__( 'Sellkit Checkout: Shipping Fields Options', 'sellkit' );

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
			case 'shipping_list_placeholder':
				return 'LINE';

			case 'shipping_custom_value':
				return 'LINE';

			case 'shipping_custom_options':
				return 'AREA';

			default:
				return '';
		}
	}
}
