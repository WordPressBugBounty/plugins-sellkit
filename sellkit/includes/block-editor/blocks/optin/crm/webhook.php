<?php
namespace Sellkit\Blocks\Optin;

defined( 'ABSPATH' ) || die();

/**
 * Webhook class.
 *
 * @since 2.3.0
 */
class Webhook {
	/**
	 * Object of helper class.
	 *
	 * @since 2.3.0
	 * @var object
	 */
	private static $helper;

	/**
	 * Run the Drip process.
	 *
	 * @param object $helper The helper class instance.
	 */
	public static function run( $helper ) {
		self::$helper = $helper;

		if ( empty( self::$helper ) ) {
			return;
		}

		self::handle_webhook();
	}

	/**
	 * Handle the webhook.
	 */
	private static function handle_webhook() {
		$webhook_url = isset( self::$helper->attributes['webhookURL'] ) ? self::$helper->attributes['webhookURL'] : '';

		if ( empty( self::$helper->attributes['webhookURL'] ) ) {
			return self::$helper->add_response( 'admin_errors', esc_html__( 'Webhook error: Missing configuration.', 'sellkit' ) );
		}

		$body = self::get_form_data();
		$args = [ 'body' => wp_json_encode( $body ) ];

		$response = wp_remote_post( $webhook_url, $args );

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $response_code < 200 || $response_code >= 300 ) {
			self::$helper->add_response( 'admin_errors', esc_html__( 'Webhook Action: Webhook Error.', 'sellkit' ) );
		}
	}

	/**
	 * Get form data.
	 *
	 * @since 2.3.0
	 * @return array
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private static function get_form_data() {
		$fields    = self::$helper->form_data['fields'];
		$locations = ! empty( self::$helper->attributes['locations'] ) ? self::$helper->attributes['locations'] : [];

		if ( empty( $locations ) || empty( $fields ) ) {
			return [];
		}

		$body = [];

		foreach ( $fields as $key => $value ) {
			if ( ! isset( $locations[ $key ] ) ) {
				continue;
			}

			$label = ! empty( $locations[ $key ]['label'] ) ? $locations[ $key ]['label'] : esc_html__( 'No Label', 'sellkit' ) . ' ' . $key;

			$field_value = empty( $value ) ? '' : $value;

			if ( ! empty( $field_value ) ) {
				$field_value = self::$helper->get_address_field( $fields[ $key ], 'address' );
			}

			if ( 'acceptance' === $locations[ $key ]['type'] ) {
				$field_value = empty( $field_value ) ? esc_html__( 'No', 'sellkit' ) : esc_html__( 'Yes', 'sellkit' );
			}

			$body[ $label ] = $field_value;
		}

		return $body;
	}
}

