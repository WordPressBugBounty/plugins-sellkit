<?php
namespace Sellkit\Blocks\Optin;

defined( 'ABSPATH' ) || die();

/**
 * Active Campaign class.
 *
 * @since 2.3.0
 */
class ActiveCampaign {
	/**
	 * Object of helper class.
	 *
	 * @since 2.3.0
	 * @var object
	 */
	private static $helper;

	/**
	 * Run the ActiveCampaign process.
	 *
	 * @param object $helper The helper class instance.
	 */
	public static function run( $helper ) {
		self::$helper = $helper;

		if ( empty( self::$helper ) ) {
			return;
		}

		self::handle_active_campaign();
	}

	/**
	 * Handle Active Campaign.
	 *
	 * @since 2.3.0
	 */
	private static function handle_active_campaign() {
		if ( empty( self::$helper->attributes['activeCampaign'] ) ) {
			return self::$helper->add_response( 'admin_errors', esc_html__( 'Active Campaign error: Missing configuration.', 'sellkit' ) );
		}

		self::$helper->check_api_params( 'activecampaign', 'Active Campaign', true );

		$mapping_fields = self::$helper->attributes['activeCampaign'];
		self::$helper->check_required_fields( $mapping_fields, [ 'list', 'Email' ] );

		$fields     = self::$helper->form_data['fields'];
		$subscriber = self::map_fields_to_subscriber( $mapping_fields, $fields );

		$response = self::send_request( 'contacts', $subscriber );

		if ( is_wp_error( $response ) ) {
			return self::$helper->add_response( 'admin_errors', $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 422 === $code ) {
			$response = self::sync_contact( $subscriber );

			if ( is_wp_error( $response ) ) {
				return self::$helper->add_response( 'admin_errors', $response->get_error_message() );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
		}

		if ( $code < 200 || $code >= 300 ) {
			self::$helper->add_response(
				'admin_errors',
				sprintf(
					/* Translators: 1: CRM name 2: Error code 3: Error message */
					esc_html__( '%1$s: Request error-%2$s -- %3$s', 'sellkit' ),
					'Active Campaign',
					esc_html( $code ),
					wp_remote_retrieve_response_message( $response )
				) . esc_html__( ' (issued by endpoint)', 'sellkit' )
			);
		} else {
			$contact = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $contact ) ) {
				$subscriber_id = $contact['contact']['id'];

				self::add_subscriber_to_list( $subscriber_id );
				self::add_tag_to_subscriber( $subscriber_id );
			}
		}
	}

	/**
	 * Map fields to subscriber data.
	 *
	 * @param array $mapping_fields The mapping fields.
	 * @param array $fields The form data fields.
	 * @return array The subscriber data.
	 */
	private static function map_fields_to_subscriber( $mapping_fields, $fields ) {
		$mapped_fields = [];

		foreach ( $mapping_fields as $index => $value ) {
			if ( ! empty( $fields[ $value ] ) ) {
				$fields[ $value ] = self::$helper->get_address_field( $fields[ $value ], 'address' );
			}

			if ( ! empty( $value ) && isset( $fields[ $value ] ) && ! in_array( $index, [ 'list', 'tagsValue' ], true ) ) {
				$mapped_fields[ lcfirst( $index ) ] = $fields[ $value ];
			}
		}

		if ( empty( $mapped_fields['email'] ) && ! empty( $fields[ $mapping_fields['Email'] ] ) ) {
			$mapped_fields['email'] = $fields[ $mapping_fields['Email'] ];
		}

		return [ 'contact' => $mapped_fields ];
	}

	/**
	 * Send POST request to ActiveCampaign API.
	 *
	 * @param string $endpoint The API endpoint.
	 * @param array  $body     The request body.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function send_request( $endpoint, $body ) {
		$args = [
			'method'  => 'POST',
			'timeout' => 100,
			'headers' => [
				'Api-Token'    => self::$helper->api_key,
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( $body ),
		];

		return wp_remote_post( trailingslashit( self::$helper->api_url ) . 'api/3/' . $endpoint, $args );
	}

	/**
	 * Add subscriber to list.
	 *
	 * @param int $subscriber_id The subscriber ID.
	 */
	private static function add_subscriber_to_list( $subscriber_id ) {
		$list_id = self::$helper->attributes['activeCampaign']['list'];
		$body    = [
			'contactList' => [
				'list'    => strval( $list_id ),
				'contact' => strval( $subscriber_id ),
				'status'  => '1',
			],
		];

		$response = self::send_request( 'contactLists', $body );

		if ( is_wp_error( $response ) ) {
			return self::$helper->add_response( 'admin_errors', $response->get_error_message() );
		}

		self::handle_api_response( $response, 'List addition error' );
	}

	/**
	 * Add tag to subscriber.
	 *
	 * @param int $subscriber_id The subscriber ID.
	 */
	private static function add_tag_to_subscriber( $subscriber_id ) {
		$tag = self::$helper->attributes['activeCampaign']['tagsValue'];

		if ( empty( $tag ) ) {
			return;
		}

		$body = [
			'contactTag' => [
				'contact' => $subscriber_id,
				'tag'     => $tag,
			],
		];

		$response = self::send_request( 'contactTags', $body );

		if ( is_wp_error( $response ) ) {
			return self::$helper->add_response( 'admin_errors', $response->get_error_message() );
		}

		self::handle_api_response( $response, 'Tag addition error' );
	}

	/**
	 * Sync the contact using the contact/sync endpoint.
	 *
	 * @param array $subscriber The subscriber data to sync.
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	private static function sync_contact( $subscriber ) {
		return self::send_request( 'contact/sync', $subscriber );
	}

	/**
	 * Handle API response errors.
	 *
	 * @param array  $response The API response.
	 * @param string $context  The context of the error.
	 */
	private static function handle_api_response( $response, $context ) {
		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			self::$helper->add_response(
				'admin_errors',
				sprintf(
					/* Translators: 1: CRM name 2: Error code 3: Error message  4: Rest of message */
					esc_html__( '%1$s: %2$s-%3$s -- %4$s', 'sellkit' ),
					'Active Campaign',
					$context,
					esc_html( $code ),
					wp_remote_retrieve_response_message( $response )
				) . esc_html__( ' (issued by endpoint)', 'sellkit' )
			);
		}
	}
}
