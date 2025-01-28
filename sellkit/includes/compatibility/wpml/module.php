<?php

namespace Sellkit\Compatibility\Wpml;

defined( 'ABSPATH' ) || die();

/**
 * Sellkit WPML compatibility module with WPML.
 *
 * @since 2.3.2
 */
class Module {

	/**
	 * Constructor.
	 *
	 * @since 2.3.2
	 */
	public function __construct() {
		add_filter( 'wpml_elementor_widgets_to_translate', [ $this, 'register_widgets_fields' ] );
	}

	/**
	 * Load external classes for repeater fields.
	 *
	 * @since 2.3.2
	 */
	public function load_integration_files() {
		sellkit()->load_files( [
			'compatibility/wpml/modules/checkout-shipping',
			'compatibility/wpml/modules/checkout-billing',
			'compatibility/wpml/modules/optin',
			'compatibility/wpml/modules/order-details',
		] );
	}

	/**
	 * Register widgets fields for translation.
	 *
	 * @since 2.3.2
	 *
	 * @param array $fields Fields to translate.
	 *
	 * @return array
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function register_widgets_fields( $fields ) {
		$this->load_integration_files();

		// Accept Reject Button.
		$fields['sellkit-accept-reject-button'] = [
			'conditions' => [ 'widgetType' => 'sellkit-accept-reject-button' ],
			'fields'     => [
				[
					'field'       => 'title',
					'type'        => esc_html__( 'Sellkit Accept Reject Button: Title', 'sellkit' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'sub_title',
					'type'        => esc_html__( 'Sellkit Accept Reject Button: Sub Title', 'sellkit' ),
					'editor_type' => 'LINE',
				],
			],
		];

		// Checkout.
		$fields['sellkit-checkout'] = [
			'conditions' => [ 'widgetType' => 'sellkit-checkout' ],
			'fields'     => [
				[
					'field'       => 'place_order_btn_txt',
					'type'        => esc_html__( 'Sellkit Checkout: Place Order Button Text', 'sellkit' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'empty_cart',
					'type'        => esc_html__( 'Sellkit Checkout: Empty cart Text', 'sellkit' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'secure_transaction_text',
					'type'        => esc_html__( 'Sellkit Checkout: Payment Description', 'sellkit' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'select_address_text',
					'type'        => esc_html__( 'Sellkit Checkout: Billing Details Description', 'sellkit' ),
					'editor_type' => 'LINE',
				],
			],
			'integration-class' => [
				__NAMESPACE__ . '\Modules\Checkout_Shipping',
				__NAMESPACE__ . '\Modules\Checkout_Billing',
			]
		];

		// Optin.
		$fields['sellkit-optin'] = [
			'conditions' => [ 'widgetType' => 'sellkit-optin' ],
			'fields'     => [
				[
					'field'       => 'submit_button_text',
					'type'        => esc_html__( 'Sellkit Optin: Submit Button Text', 'sellkit' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'submit_button_subtext',
					'type'        => esc_html__( 'Sellkit Optin: Submit Button Sub Text', 'sellkit' ),
					'editor_type' => 'LINE',
				],
				'download_url' => [
					'field'       => 'url',
					'type'        => esc_html__( 'Sellkit Optin: Download URL', 'sellkit' ),
					'editor_type' => 'LINK',
				],
				'redirect_url' => [
					'field'       => 'url',
					'type'        => esc_html__( 'Sellkit Optin: Redirect URL', 'sellkit' ),
					'editor_type' => 'LINK',
				],
				[
					'field'       => 'messages_success',
					'type'        => esc_html__( 'Sellkit Optin: Success Message', 'sellkit' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'messages_error',
					'type'        => esc_html__( 'Sellkit Optin: Error Message', 'sellkit' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'messages_required',
					'type'        => esc_html__( 'Sellkit Optin: Required Message', 'sellkit' ),
					'editor_type' => 'LINE',
				],
			],
			'integration-class' => [
				__NAMESPACE__ . '\Modules\Optin',
			]
		];

		// Order Cart Details.
		$fields['sellkit-order-cart-details'] = [
			'conditions' => [ 'widgetType' => 'sellkit-order-cart-details' ],
			'fields'     => [
				[
					'field'       => 'label',
					'type'        => esc_html__( 'Sellkit Order Cart Details: Label', 'sellkit' ),
					'editor_type' => 'LINE',
				],
			],
		];

		// Order Details.
		$fields['sellkit-order-details'] = [
			'conditions' => [ 'widgetType' => 'sellkit-order-details' ],
			'fields'     => [
				[
					'field'       => 'list_name',
					'type'        => esc_html__( 'Sellkit Order Details: Heading', 'sellkit' ),
					'editor_type' => 'LINE',
				],
			],
			'integration-class' => [
				__NAMESPACE__ . '\Modules\Order_Details',
			]
		];

		return $fields;
	}
}
