<?php
namespace Sellkit\Blocks\Optin;

defined( 'ABSPATH' ) || die();

/**
 * MailerLite class.
 *
 * @since 2.3.0
 */
class Mailerlite {
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

		self::handle_mailerlite();
	}

	/**
	 * Handle MailerLite subscription.
	 */
	private static function handle_mailerlite() {
		if ( empty( self::$helper->attributes['mailerLite'] ) ) {
			return self::$helper->add_response( 'admin_errors', esc_html__( 'MailerLite error: Missing configuration.', 'sellkit' ) );
		}

		self::$helper->check_api_params( 'mailerlite', 'MailerLite' );

		$mapping_fields = self::$helper->attributes['mailerLite'];
		self::$helper->check_required_fields( $mapping_fields, [ 'group', 'email' ] );

		$fields = self::$helper->form_data['fields'];

		$subscriber_data = self::map_fields_to_subscriber( $mapping_fields, $fields );

		$response = self::send_subscriber_to_mailerlite( $subscriber_data, $mapping_fields['group'] );

		if ( is_wp_error( $response ) ) {
			return self::$helper->add_response( 'admin_errors', $response->get_error_message() );
		}

		$code = $response['code'];

		if ( $code < 200 || $code >= 300 ) {
			return self::$helper->add_response(
				'admin_errors',
				sprintf(
					/* Translators: 1: CRM name 2: Error code 3: Error message */
					esc_html__( '%1$s: Request error-%2$s -- %3$s', 'sellkit' ),
					'MailerLite',
					esc_html( $code ),
					wp_remote_retrieve_response_message( $response )
				) . esc_html__( ' (issued by endpoint)', 'sellkit' )
			);
		}
	}

	/**
	 * Map fields to MailerLite subscriber data.
	 *
	 * @param array $mapping_fields The mapping fields.
	 * @param array $fields The form data fields.
	 * @return array The subscriber data.
	 */
	private static function map_fields_to_subscriber( $mapping_fields, $fields ) {
		$subscriber_data = [
			'email' => ! empty( $fields[ $mapping_fields['email'] ] ) ? $fields[ $mapping_fields['email'] ] : '',
			'name' => ( isset( $mapping_fields['name'] ) && ! empty( $fields[ $mapping_fields['name'] ] ) ) ? $fields[ $mapping_fields['name'] ] : '',
			'resubscribe' => false,
		];

		foreach ( $mapping_fields as $index => $value ) {
			if ( ! empty( $fields[ $value ] ) ) {
				$fields[ $value ] = self::$helper->get_address_field( $fields[ $value ], 'address' );
			}

			if ( ! in_array( $index, [ 'email', 'name', 'group' ], true ) && ! empty( $fields[ $value ] ) ) {
				$subscriber_data['fields'][ $index ] = $fields[ $value ];
			}
		}

		if ( ! empty( $mapping_fields['doubleOptIn'] ) ) {
			$subscriber_data['resubscribe'] = true;
		}

		return $subscriber_data;
	}

	/**
	 * Send subscriber data to MailerLite API.
	 *
	 * @param array  $subscriber_data The subscriber data.
	 * @param string $group_id        The MailerLite group ID.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_subscriber_to_mailerlite( $subscriber_data, $group_id ) {
		$endpoint = "groups/{$group_id}/subscribers";
		$args     = [
			'method'    => 'POST',
			'timeout'   => 100,
			'headers'   => self::get_headers(),
			'body'      => wp_json_encode( $subscriber_data ),
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
		$api_url  = 'https://api.mailerlite.com/api/v2/' . $endpoint;
		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			self::$helper->add_response(
				'admin_errors',
				/* translators: %s Error code */
				sprintf( esc_html__( 'MailerLite: Error in request, code: %s', 'sellkit' ), esc_html( $code ) )
			);
		}

		return [
			'code' => $code,
			'body' => json_decode( wp_remote_retrieve_body( $response ), true ),
		];
	}

	/**
	 * Get headers for the API request.
	 *
	 * @return array The headers for the API request.
	 */
	private static function get_headers() {
		return [
			'Content-Type'  => 'application/json',
			'X-MailerLite-ApiKey' => self::$helper->api_key,
		];
	}
}
