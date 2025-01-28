<?php

namespace Sellkit\Compatibility\Wpml\Modules;

defined( 'ABSPATH' ) || die();

/**
 * Class Order_Details
 *
 * This class handles the compatibility of the fields in Sellkit
 * with WPML for translation within Elementor modules.
 *
 * @since 2.3.2
 */
class Order_Details extends \WPML_Elementor_Module_With_Items {
	/**
	 * Retrieves the field name that holds the items.
	 *
	 * @since 2.3.2
	 *
	 * @return string The name of the field that contains the items.
	 */
	public function get_items_field() {
		return 'fields';
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
			'label',
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
			case 'label':
				return esc_html__( 'Sellkit Order Details: Items Label', 'sellkit' );

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
			case 'label':
				return 'LINE';

			default:
				return '';
		}
	}
}
