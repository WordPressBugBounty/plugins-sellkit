<?php
namespace Sellkit\Blocks\Optin;

defined( 'ABSPATH' ) || die();

/**
 * GetResponse class.
 *
 * @since 2.3.0
 */
class GetResponse {
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

		self::handle_getresponse();
	}

	/**
	 * Handle getResponse.
	 *
	 * @since 2.3.0
	 */
	private static function handle_getresponse() {
		if ( empty( self::$helper->attributes['getResponse'] ) ) {
			return self::$helper->add_response( 'admin_errors', esc_html__( 'GetResponse error: Missing configuration.', 'sellkit' ) );
		}

		self::$helper->check_api_params( 'getresponse', 'GetResponse' );

		$mapping_fields = self::$helper->attributes['getResponse'];

		self::$helper->check_required_fields( $mapping_fields, [ 'campaign', 'email' ] );

		$fields = self::$helper->form_data['fields'];

		$subscriber_data = self::map_fields_to_subscriber( $mapping_fields, $fields );

		$response = self::subscribe( $subscriber_data );

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
					'GetResponse',
					esc_html( $code ),
					wp_remote_retrieve_response_message( $response )
				) . esc_html__( ' (issued by endpoint)', 'sellkit' )
			);
		}
	}

	/**
	 * Subscribe a contact to GetResponse.
	 *
	 * @param array $subscriber_data The subscriber data.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function subscribe( $subscriber_data ) {
		$endpoint = 'contacts';
		$args     = [
			'method'    => 'POST',
			'timeout'   => 100,
			'sslverify' => false,
			'headers'   => self::get_headers(),
			'body'      => wp_json_encode( $subscriber_data ),
		];

		$result = self::send_post( $endpoint, $args, 'temp_getresponse' );

		if ( is_array( $result ) && 409 === $result['code'] ) {
			unset( self::$helper->ajax_handler->response['admin_errors']['temp_getresponse'] );

			$_result = self::send_get( "contacts?query[email]={$subscriber_data['email']}" );

			if ( $_result['code'] < 200 || $_result['code'] >= 300 ) {
				return self::$helper->add_response( 'admin_errors', esc_html__( 'GetResponse: Contact already exists, but cannot retrieve its ID.', 'sellkit' ) );
			}

			$contact_id = $_result['body'][0]['contactId'];

			return self::send_post( "contacts/{$contact_id}", $args );
		}

		return $result;
	}

	/**
	 * Send a POST request to the specified endpoint.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $args     The request arguments.
	 * @param string $context  A context identifier for error handling.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_post( $endpoint, $args, $context = '' ) {
		$api_url  = 'https://api.getresponse.com/v3/' . $endpoint;
		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			self::$helper->add_response(
				'admin_errors',
				/* translators: %s Error code */
				sprintf( esc_html__( 'GetResponse: Error in request, code: %s', 'sellkit' ), esc_html( $code ) ),
				$context
			);
		}

		return [
			'code' => $code,
			'body' => json_decode( wp_remote_retrieve_body( $response ), true ),
		];
	}

	/**
	 * Send a GET request to the specified endpoint.
	 *
	 * @param string $endpoint The API endpoint.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_get( $endpoint ) {
		$api_url = 'https://api.getresponse.com/v3/' . $endpoint;
		$args    = [
			'method'    => 'GET',
			'timeout'   => 100,
			'sslverify' => false,
			'headers'   => self::get_headers(),
		];

		$response = wp_remote_get( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

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
			'Content-Type' => 'application/json',
			'User-Agent'   => 'sellkit',
			'X-Auth-Token' => 'api-key ' . self::$helper->api_key,
		];
	}


	/**
	 * Map fields to GetResponse subscriber data.
	 *
	 * @param array $mapping_fields The mapping fields.
	 * @param array $fields         The form data fields.
	 * @return array The subscriber data.
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private static function map_fields_to_subscriber( $mapping_fields, $fields ) {
		$subscriber_data = [
			'email' => isset( $fields[ $mapping_fields['email'] ] ) ? $fields[ $mapping_fields['email'] ] : '',
			'campaign' => [ 'campaignId' => $mapping_fields['campaign'] ],
			'dayOfCycle' => ! empty( $mapping_fields['dayOfCycle'] ) ? intval( $mapping_fields['dayOfCycle'] ) : 0,
			'ipAddress' => self::$helper->get_client_ip(),
		];

		foreach ( $mapping_fields as $index => $value ) {
			if ( strpos( $index, 'name_' ) === 0 ) {
				continue;
			}

			if ( ! empty( $fields[ $value ] ) ) {
				$fields[ $value ] = self::$helper->get_address_field( $fields[ $value ], 'address' );
			}

			if ( 'name' === $index ) {
				$subscriber_data['name'] = $fields[ $value ];
				continue;

			}

			if ( ! in_array( $index, [ 'campaign', 'email', 'tags', 'name', 'dayOfCycle' ], true ) && ! empty( $fields[ $value ] ) ) {
				$field_name = "name_{$index}";

				$custom_value_name = '';

				if ( ! empty( $mapping_fields[ $field_name ] ) ) {
					$custom_value_name = $mapping_fields[ $field_name ];
				}

				$subscriber_data['customFieldValues'][] = [
					'customFieldId' => $index,
					'name'          => $custom_value_name,
					'value'         => [ $fields[ $value ] ],
					'type'          => 'single_select',
					'fieldType'     => 'single_select',
					'type'          => 'string'
				];
			}
		}

		if ( ! empty( $mapping_fields['tagsValue'] ) ) {
			$subscriber_data['tags'] = [ $mapping_fields['tagsValue'] ];
		}

		return $subscriber_data;
	}
}
