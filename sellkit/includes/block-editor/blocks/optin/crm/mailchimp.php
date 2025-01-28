<?php
namespace Sellkit\Blocks\Optin;

defined( 'ABSPATH' ) || die();

/**
 * Mailchimp class.
 *
 * @since 2.3.0
 */
class Mailchimp {
	/**
	 * Object of helper class.
	 *
	 * @since 2.3.0
	 * @var object
	 */
	private static $helper;

	/**
	 * The Mailchimp API URL.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	private static $api_key = '';

	/**
	 * The Mailchimp API server.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	private static $api_server = '';

	/**
	 * Run the mailchimp process.
	 *
	 * @param object $helper The helper class instance.
	 */
	public static function run( $helper ) {
		self::$helper = $helper;

		if ( empty( self::$helper ) ) {
			return;
		}

		self::handle_mailchimp();
	}

	/**
	 * Handle Mailchimp.
	 *
	 * @since 2.3.0
	 */
	private static function handle_mailchimp() {
		if ( empty( self::$helper->attributes['mailChimp'] ) ) {
			return self::$helper->add_response( 'admin_errors', esc_html__( 'Mailchimp error: Missing configuration.', 'sellkit' ) );
		}

		self::$helper->check_api_params( 'mailchimp', 'Mailchimp' );

		$mapping_fields = self::$helper->attributes['mailChimp'];
		self::$helper->check_required_fields( $mapping_fields, [ 'audience', 'email' ] );
		self::get_api_params();

		$fields = self::$helper->form_data['fields'];

		$subscriber_data = self::map_fields_to_subscriber( $mapping_fields, $fields );

		$response = self::send_subscriber_to_mailchimp( $subscriber_data, $mapping_fields['audience'] );

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
					'Mailchimp',
					esc_html( $code ),
					wp_remote_retrieve_response_message( $response )
				) . esc_html__( ' (issued by endpoint)', 'sellkit' )
			);
		}
	}

	/**
	 * Map fields to Mailchimp subscriber data.
	 *
	 * @param array $mapping_fields The mapping fields.
	 * @param array $fields The form data fields.
	 * @since 2.3.0
	 * @return array The subscriber data.
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private static function map_fields_to_subscriber( $mapping_fields, $fields ) {
		$subscriber_data = [
			'email_address' => ! empty( $fields[ $mapping_fields['email'] ] ) ? $fields[ $mapping_fields['email'] ] : '',
			'full_name' => ( isset( $mapping_fields['full_name'] ) && ! empty( $fields[ $mapping_fields['full_name'] ] ) ) ? $fields[ $mapping_fields['full_name'] ] : '',
			'status'        => 'subscribed',
			'status_if_new' => 'subscribed',
			'skip_merge_validation' => true,
			'ip_opt' => self::$helper::get_client_ip(),
		];

		foreach ( $mapping_fields as $index => $value ) {
			if ( ! empty( $fields[ $value ] ) ) {
				$fields[ $value ] = self::$helper->get_address_field( $fields[ $value ], 'hidden' );
			}

			if ( ! in_array( $index, [ 'audience', 'email', 'group', 'full_name', 'doubleOptIn' ], true ) && ! empty( $fields[ $value ] ) ) {
				$subscriber_data['merge_fields'][ $index ] = $fields[ $value ];
			}

			if ( 'BIRTHDAY' === $index && ! empty( $fields[ $value ] ) ) {
				$birthday_formatted = date( 'Y/m/d', strtotime( $fields[ $value ] ) );

				$subscriber_data['merge_fields'][ $index ] = $birthday_formatted;
			}

			if ( 'ADDRESS' === $index && ! empty( $fields[ $value ] ) ) {
				$address_data = self::map_address_fields_to_mailchimp( $fields[ $value ] );

				if ( ! empty( array_filter( $address_data ) ) ) {
					$subscriber_data['merge_fields']['ADDRESS'] = $address_data;
				}
			}
		}

		$reactivate = isset( $mapping_fields['doubleOptIn'] ) ? $mapping_fields['doubleOptIn'] : '';

		if ( empty( $reactivate ) ) {
			$email_hash = md5( strtolower( $subscriber_data['email_address'] ) );
			$list_id    = $mapping_fields['audience'];

			$subscriber_info = self::send_get( "lists/{$list_id}/members/{$email_hash}" );

			if ( ! empty( $subscriber_info['body']['status'] ) && 'subscribed' !== $subscriber_info['body']['status'] ) {
				$subscriber_data['status'] = 'pending';
			}
		}

		if ( ! empty( $mapping_fields['tagsValue'] ) ) {
			$subscriber_data['tags'] = [ $mapping_fields['tagsValue'] ];
		}

		if ( ! empty( $mapping_fields['group'] ) ) {
			$subscriber_data['interests'] = array_fill_keys( [ $mapping_fields['group'] ], true );
		}

		return $subscriber_data;
	}

	/**
	 * Send subscriber data to Mailchimp API.
	 *
	 * @param array  $subscriber_data The subscriber data.
	 * @param string $audience_id     The Mailchimp audience ID.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_subscriber_to_mailchimp( $subscriber_data, $audience_id ) {
		$email_hash = md5( strtolower( $subscriber_data['email_address'] ) );

		$endpoint = "lists/{$audience_id}/members/{$email_hash}";
		$args     = [
			'method'  => 'PUT',
			'timeout' => 100,
			'headers' => self::get_headers(),
			'body'    => wp_json_encode( $subscriber_data ),
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
		$api_url  = trailingslashit( 'https://' . self::$api_server . '.api.mailchimp.com/3.0/' ) . $endpoint;
		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			self::$helper->add_response(
				'admin_errors',
				/* translators: %s Error code */
				sprintf( esc_html__( 'Mailchimp: Error in request, code: %s', 'sellkit' ), esc_html( $code ) )
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
		$api_url = trailingslashit( 'https://' . self::$api_server . '.api.mailchimp.com/3.0/' ) . $endpoint;
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
			'Content-Type'  => 'application/json',
			'Authorization' => 'Basic ' . base64_encode( 'user:' . self::$api_key ),
			'User-Agent' => 'sellkit'
		];
	}

	/**
	 * Get Mailchimp API parameters.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	private static function get_api_params() {
		$api_key = sellkit_get_option( 'mailchimp_api_key', '' );

		$parts = explode( '-', $api_key );

		if ( 2 === count( $parts ) ) {
			self::$api_key    = $parts[0];
			self::$api_server = $parts[1];
		}
	}

	/**
	 * Map address fields to Mailchimp format.
	 *
	 * @param string $address_fields The address fields.
	 * @since 2.3.0
	 * @return array The address data.
	 */
	private static function map_address_fields_to_mailchimp( $address_fields ) {
		if ( empty( $address_fields ) ) {
			return [];
		}

		$cleaned_json = stripslashes( $address_fields );

		$addresses = json_decode( $cleaned_json, true );

		$mapping = [
			'addr1'   => [ 'street_number', 'route' ],
			'city'    => 'locality',
			'state'   => 'administrative_area_level_1',
			'zip'     => 'postal_code',
			'country' => 'country'
		];

		$address_data = [];

		foreach ( $mapping as $mailchimp_key => $source_keys ) {
			if ( is_array( $source_keys ) ) {
				$value = '';

				foreach ( $source_keys as $key ) {
					if ( ! empty( $addresses[ $key ] ) ) {
						$value .= $addresses[ $key ] . ' ';
					}
				}

				$address_data[ $mailchimp_key ] = trim( $value );

				continue;
			}

			$address_data[ $mailchimp_key ] = ! empty( $addresses[ $source_keys ] ) ? $addresses[ $source_keys ] : '';
		}

		return $address_data;
	}
}
