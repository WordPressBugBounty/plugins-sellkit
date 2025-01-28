<?php
namespace Sellkit\Blocks\Optin;

defined( 'ABSPATH' ) || die();

/**
 * ConvertKit class.
 *
 * @since 2.3.0
 */
class ConvertKit {
	/**
	 * Object of helper class.
	 *
	 * @since 2.3.0
	 * @var object
	 */
	private static $helper;

	/**
	 * Run the ConvertKit process.
	 *
	 * @param object $helper The helper class instance.
	 */
	public static function run( $helper ) {
		self::$helper = $helper;

		if ( empty( self::$helper ) ) {
			return;
		}

		self::handle_convertkit();
	}

	/**
	 * Handle convertKit.
	 *
	 * @since 2.3.0
	 */
	private static function handle_convertkit() {
		if ( empty( self::$helper->attributes['convertKit'] ) ) {
			return self::$helper->add_response( 'admin_errors', esc_html__( 'ConvertKit error: Missing configuration.', 'sellkit' ) );
		}

		self::$helper->check_api_params( 'convertkit', 'ConvertKit' );

		$mapping_fields = self::$helper->attributes['convertKit'];
		self::$helper->check_required_fields( $mapping_fields, [ 'form', 'email' ] );

		$fields = self::$helper->form_data['fields'];

		$subscriber_data = self::map_fields_to_subscriber( $mapping_fields, $fields );

		$response = self::send_subscriber_to_convertkit( $subscriber_data, $mapping_fields['form'] );

		if ( is_wp_error( $response ) ) {
			return self::$helper->add_response( 'admin_errors', $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			return self::$helper->add_response(
				'admin_errors',
				sprintf(
					/* Translators: 1: CRM name 2: Error code 3: Error message */
					esc_html__( '%1$s: Request error-%2$s -- %3$s', 'sellkit' ),
					'ConvertKit',
					esc_html( $code ),
					wp_remote_retrieve_response_message( $response )
				) . esc_html__( ' (issued by endpoint)', 'sellkit' )
			);
		}
	}

	/**
	 * Send subscriber data to ConvertKit API.
	 *
	 * @param array  $subscriber_data The subscriber data.
	 * @param string $form_id         The ConvertKit form ID.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_subscriber_to_convertkit( $subscriber_data, $form_id ) {
		$endpoint = 'forms/' . $form_id . '/subscribe?api_key=' . self::$helper->api_key;
		$args     = [
			'method'  => 'POST',
			'timeout' => 100,
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( $subscriber_data ),
		];

		return self::send_post( $endpoint, $args );
	}

	/**
	 * Map fields to ConvertKit subscriber data.
	 *
	 * @param array $mapping_fields The mapping fields.
	 * @param array $fields The form data fields.
	 * @return array The subscriber data.
	 */
	private static function map_fields_to_subscriber( $mapping_fields, $fields ) {
		$subscriber_data = [
			'form_id' => $mapping_fields['form'],
			'email'   => isset( $fields[ $mapping_fields['email'] ] ) ? $fields[ $mapping_fields['email'] ] : '',
			'tags'    => [],
		];

		foreach ( $mapping_fields as $index => $value ) {
			if ( ! empty( $fields[ $value ] ) ) {
				$fields[ $value ] = self::$helper->get_address_field( $fields[ $value ], 'address' );
			}

			if ( ! in_array( $index, [ 'form', 'email', 'tags' ], true ) && ! empty( $fields[ $value ] ) ) {
				$subscriber_data[ $index ] = $fields[ $value ];
			}
		}

		if ( ! empty( $mapping_fields['tagsValue'] ) ) {
			$subscriber_data['tags'] = [ $mapping_fields['tagsValue'] ];
		}

		$subscriber_data['api_key'] = self::$helper->api_key;

		return $subscriber_data;
	}

	/**
	 * Send a POST request to the specified endpoint.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $args     The request arguments.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_post( $endpoint, $args ) {
		$api_url = 'https://api.convertkit.com/v3/' . $endpoint;
		return wp_remote_post( $api_url, $args );
	}
}
