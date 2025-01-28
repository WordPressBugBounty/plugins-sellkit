<?php
namespace Sellkit\Blocks\Optin;

defined( 'ABSPATH' ) || die();

/**
 * Drip class.
 *
 * @since 2.3.0
 */
class Drip {
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

		self::handle_drip();
	}

	/**
	 * Handle Drip.
	 *
	 * @since 2.3.0
	 */
	private static function handle_drip() {
		if ( empty( self::$helper->attributes['drip'] ) ) {
			return self::$helper->add_response( 'admin_errors', esc_html__( 'Drip error: Missing configuration.', 'sellkit' ) );
		}

		self::$helper->check_api_params( 'drip', 'Drip' );

		$mapping_fields = self::$helper->attributes['drip'];

		self::$helper->check_required_fields( $mapping_fields, [ 'account', 'email' ] );
		$fields = self::$helper->form_data['fields'];

		$subscriber_data = self::map_fields_to_subscriber( $mapping_fields, $fields );

		$response = self::send_subscriber_to_drip( $subscriber_data, (int) $mapping_fields['account'] );

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
					'Drip',
					esc_html( $code ),
					wp_remote_retrieve_response_message( $response )
				) . esc_html__( ' (issued by endpoint)', 'sellkit' )
			);
		}
	}

	/**
	 * Map fields to Drip subscriber data.
	 *
	 * @param array $mapping_fields The mapping fields.
	 * @param array $fields The form data fields.
	 * @return array The subscriber data.
	 */
	private static function map_fields_to_subscriber( $mapping_fields, $fields ) {
		$subscriber_data = [
			'email' => isset( $fields[ $mapping_fields['email'] ] ) ? $fields[ $mapping_fields['email'] ] : '',
		];

		foreach ( $mapping_fields as $index => $value ) {
			if ( ! empty( $fields[ $value ] ) ) {
				$fields[ $value ] = self::$helper->get_address_field( $fields[ $value ], 'address' );
			}

			if ( ! in_array( $index, [ 'account', 'email', 'tagsValue' ], true ) && ! empty( $fields[ $value ] ) ) {
				$subscriber_data[ $index ] = $fields[ $value ];
			}
		}

		if ( ! empty( $mapping_fields['tagsValue'] ) ) {
			$subscriber_data['tags'] = [ $mapping_fields['tagsValue'] ];
		}

		$subscriber_data['ip_address'] = self::$helper::get_client_ip();

		return $subscriber_data;
	}

	/**
	 * Send subscriber data to Drip API.
	 *
	 * @param array  $subscriber_data The subscriber data.
	 * @param string $account_id      The Drip account ID.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_subscriber_to_drip( $subscriber_data, $account_id ) {
		$endpoint = $account_id . '/subscribers/';
		$args     = [
			'method'  => 'POST',
			'timeout' => 100,
			'headers' => [
				'Content-Type'  => 'application/vnd.api+json',
				'User-Agent' => 'Sellkit',
				'Authorization' => 'Basic ' . base64_encode( self::$helper->api_key ),
			],
			'body'    => wp_json_encode( [
				'subscribers' => [ $subscriber_data ],
			] ),
		];

		return self::send_post( $endpoint, $args );
	}

	/**
	 * Send a POST request to the specified endpoint.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $args     The request arguments.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_post( $endpoint, $args ) {
		$api_url = 'https://api.getdrip.com/v2/' . $endpoint;
		return wp_remote_post( $api_url, $args );
	}
}
