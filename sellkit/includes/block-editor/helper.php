<?php
namespace Sellkit\Blocks;

defined( 'ABSPATH' ) || die();

/**
 * Sellkit Blocks Endpoint.
 *
 * @since 2.3.0
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Sellkit_Blocks_Endpoint {
	/**
	 * Class instance.
	 *
	 * @since 2.3.0
	 * @var Sellkit_Blocks_Endpoint
	 */
	private static $instance = null;

	/**
	 * Get a class instance.
	 *
	 * @since 2.3.0
	 *
	 * @return Sellkit_Blocks_Endpoint Class
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * @since 2.3.0
	 */
	private function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_crm_endpoint' ] );
	}

	/**
	 * Check if user has admin permission.
	 *
	 * @since 2.3.0
	 * @return bool
	 */
	public function admin_permission_callback() {
		return current_user_can( 'administrator' );
	}

	/**
	 * Fetch data from a given API endpoint.
	 *
	 * @since 2.3.0
	 * @param string $url     The API endpoint URL.
	 * @param array  $headers The request headers.
	 * @return array|WP_Error
	 */
	private function fetch_api_data( $url, $headers = [] ) {
		$response = wp_remote_get( $url, [
			'timeout' => 100,
			'sslverify' => false,
			'headers' => $headers,
		] );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'http_request_failed',
				esc_html__( 'HTTP request failed', 'sellkit' ),
				[
					'status' => 500,
					'details' => $response->get_error_message()
				]
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'json_error',
				esc_html__( 'Error parsing JSON response', 'sellkit' ),
				[
					'status' => 500,
					'details' => json_last_error_msg()
				]
			);
		}

		return $data;
	}

	/**
	 * Fetch ActiveCampaign lists, tags, and fields.
	 *
	 * @since 2.3.0
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch_active_campaign_data() {
		$key = sellkit_get_option( 'activecampaign_api_key', '' );
		$url = sellkit_get_option( 'activecampaign_api_url', '' );

		if ( ! $key || ! $url ) {
			return new \WP_Error( 'missing_params', esc_html__( 'API key or URL is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		$headers = [
			'Api-Token' => $key,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
		];

		$responses = [
			'lists' => $this->fetch_api_data( "$url/api/3/lists", $headers ),
			'tags' => $this->fetch_api_data( "$url/api/3/tags", $headers ),
			'fields' => $this->fetch_api_data( "$url/api/3/fields", $headers ),
		];

		if ( is_wp_error( $responses['lists'] ) || is_wp_error( $responses['tags'] ) || is_wp_error( $responses['fields'] ) ) {
			return new \WP_Error( 'api_request_failed', esc_html__( 'Failed to fetch data from ActiveCampaign API', 'sellkit' ), [ 'status' => 500 ] );
		}

		$responses['fields']['fields'] = [
			'email' => esc_html__( 'Email', 'sellkit' ),
			'firstName' => esc_html__( 'First Name', 'sellkit' ),
			'lastName' => esc_html__( 'Last Name', 'sellkit' ),
			'phone' => esc_html__( 'Phone', 'sellkit' ),
		];

		return rest_ensure_response( $responses );
	}

	/**
	 * Fetch ConvertKit data (forms, tags, and custom fields).
	 *
	 * @since 2.3.0
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch_convertkit_data() {
		$api_key = sellkit_get_option( 'convertkit_api_key', '' );

		if ( ! $api_key ) {
			return new \WP_Error( 'missing_params', esc_html__( 'API key is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		$url       = 'https://api.convertkit.com/v3/';
		$responses = [
			'forms' => $this->fetch_api_data( "$url/forms?api_key=$api_key" ),
			'tags' => $this->fetch_api_data( "$url/tags?api_key=$api_key" ),
			'fields' => $this->fetch_api_data( "$url/custom_fields?api_key=$api_key" ),
		];

		if ( is_wp_error( $responses['forms'] ) || is_wp_error( $responses['tags'] ) || is_wp_error( $responses['fields'] ) ) {
			return new \WP_Error( 'api_request_failed', esc_html__( 'Failed to fetch data from ConvertKit API', 'sellkit' ), [ 'status' => 500 ] );
		}

		$custom_fields = [
			'email' => esc_html__( 'Email', 'sellkit' ),
			'first_name' => esc_html__( 'First Name', 'sellkit' ),
		];

		if ( isset( $responses['fields']['custom_fields'] ) && is_array( $responses['fields']['custom_fields'] ) ) {
			$responses['fields']['custom_fields'] = array_merge( $responses['fields']['custom_fields'], $custom_fields );
		} else {
			$responses['fields']['custom_fields'] = $custom_fields;
		}

		return rest_ensure_response( $responses );
	}

	/**
	 * Fetch Drip accounts.
	 *
	 * @since 2.3.0
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch_drip_accounts() {
		$api_key = sellkit_get_option( 'drip_api_key', '' );

		if ( ! $api_key ) {
			return new \WP_Error( 'missing_params', esc_html__( 'API key is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		$url     = 'https://api.getdrip.com/v2/accounts';
		$headers = [
			'Authorization' => 'Basic ' . base64_encode( $api_key ),
			'Content-Type'  => 'application/vnd.api+json',
			'User-Agent'    => 'sellkit',
		];

		$data = $this->fetch_api_data( $url, $headers );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$accounts = array_map( function( $account ) {
			return [
				'id'   => $account['id'],
				'name' => $account['name'],
			];
		}, $data['accounts'] );

		return rest_ensure_response( $accounts );
	}

	/**
	 * Fetch Drip tags and custom fields for a specific account.
	 *
	 * @since 2.3.0
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch_drip_tags_fields( $request ) {
		$api_key    = sellkit_get_option( 'drip_api_key', '' );
		$account_id = $request->get_param( 'account_id' );

		if ( ! $api_key ) {
			return new \WP_Error( 'missing_params', esc_html__( 'API key is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		if ( ! $account_id ) {
			return new \WP_Error( 'missing_params', esc_html__( 'Account ID is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		$url     = "https://api.getdrip.com/v2/$account_id";
		$headers = [
			'Authorization' => 'Basic ' . base64_encode( $api_key ),
			'Content-Type'  => 'application/vnd.api+json',
			'User-Agent'    => 'sellkit',
		];

		$tags   = $this->fetch_api_data( "$url/tags", $headers );
		$fields = $this->fetch_api_data( "$url/custom_field_identifiers", $headers );

		if ( is_wp_error( $tags ) || is_wp_error( $fields ) ) {
			return new \WP_Error( 'api_request_failed', __( 'Failed to fetch data from Drip API', 'sellkit' ), [ 'status' => 500 ] );
		}

		$custom_fields = [
			'email' => esc_html__( 'Email', 'sellkit' ),
			'first_name'  => esc_html__( 'First Name', 'sellkit' ),
			'last_name'   => esc_html__( 'Last Name', 'sellkit' ),
			'address1'    => esc_html__( 'Address 1', 'sellkit' ),
			'address2'    => esc_html__( 'Address 2', 'sellkit' ),
			'city'        => esc_html__( 'City', 'sellkit' ),
			'state'       => esc_html__( 'State', 'sellkit' ),
			'country'     => esc_html__( 'Country', 'sellkit' ),
			'zip'         => esc_html__( 'Zip', 'sellkit' ),
			'phone'       => esc_html__( 'Phone', 'sellkit' ),
			'sms_number'  => esc_html__( 'SMS Number', 'sellkit' ),
			'sms_consent' => esc_html__( 'SMS Consent', 'sellkit' ),
			'time_zone'   => esc_html__( 'Timezone', 'sellkit' ),
		];

		if ( isset( $fields['custom_field_definitions'] ) && is_array( $fields['custom_field_definitions'] ) ) {
			$fields['custom_field_definitions'] = array_merge( $fields['custom_field_definitions'], $custom_fields );
		} else {
			$fields['custom_field_definitions'] = $custom_fields;
		}

		return rest_ensure_response([
			'tags'   => $tags['tags'] ?? [],
			'fields' => $fields['custom_field_definitions'] ?? [],
		]);
	}

	/**
	 * Fetch GetResponse data (campaigns, tags, and custom fields).
	 *
	 * @since 2.3.0
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch_getresponse_data() {
		$api_key = sellkit_get_option( 'getresponse_api_key', '' );

		if ( ! $api_key ) {
			return new \WP_Error( 'missing_params', esc_html__( 'API key is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		$url     = 'https://api.getresponse.com/v3';
		$headers = [
			'X-Auth-Token' => 'api-key ' . $api_key,
			'Content-Type' => 'application/json',
			'User-Agent'   => 'sellkit',
		];

		$responses = [
			'campaigns' => $this->fetch_api_data( "$url/campaigns", $headers ),
			'tags' => $this->fetch_api_data( "$url/tags", $headers ),
			'fields' => $this->fetch_api_data( "$url/custom-fields", $headers ),
		];

		if ( is_wp_error( $responses['campaigns'] ) || is_wp_error( $responses['tags'] ) || is_wp_error( $responses['fields'] ) ) {
			return new \WP_Error( 'api_request_failed', esc_html__( 'Failed to fetch data from GetResponse API', 'sellkit' ), [ 'status' => 500 ] );
		}

		$custom_fields = [
			'email' => esc_html__( 'Email', 'sellkit' ),
			'name' => esc_html__( 'Name', 'sellkit' ),
		];

		foreach ( $responses['fields'] as $field ) {
			if ( is_array( $field ) && isset( $field['customFieldId'] ) ) {
				$custom_fields[ $field['customFieldId'] ] = $field['name'];
			}
		}

		$responses['fields'] = $custom_fields;

		return rest_ensure_response( $responses );
	}

	/**
	 * Fetch Mailchimp audiences.
	 *
	 * @since 2.3.0
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch_mailchimp_audiences() {
		$api_key = sellkit_get_option( 'mailchimp_api_key', '' );

		if ( ! $api_key ) {
			return new \WP_Error( 'missing_params', esc_html__( 'API key is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		$parts = explode( '-', $api_key );

		if ( 2 !== count( $parts ) ) {
			return new \WP_Error( 'invalid_api_key', esc_html__( 'Invalid API key format', 'sellkit' ), [ 'status' => 400 ] );
		}

		$api = [
			'token'  => $parts[0],
			'server' => $parts[1],
		];

		$url     = "https://{$api['server']}.api.mailchimp.com/3.0/lists?count=999";
		$headers = [
			'Authorization' => 'Basic ' . base64_encode( 'user:' . $api['token'] ),
			'Content-Type'  => 'application/json',
			'User-Agent'    => 'sellkit',
		];

		$data = $this->fetch_api_data( $url, $headers );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$lists = [];

		if ( ! empty( $data['lists'] ) ) {
			foreach ( $data['lists'] as $list ) {
				$lists[ $list['id'] ] = [
					'name' => $list['name'],
					'value' => $list['id'],
				];
			}
		}

		return rest_ensure_response( [ 'lists' => $lists ] );
	}

	/**
	 * Fetch Mailchimp data (tags, fields, groups, and double opt-in settings based on audience).
	 *
	 * @since 2.3.0
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response|WP_Error
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function fetch_mailchimp_data( $request ) {
		$api_key     = sellkit_get_option( 'mailchimp_api_key', '' );
		$audience_id = $request->get_param( 'audience_id' );

		if ( ! $api_key || ! $audience_id ) {
			return new \WP_Error( 'missing_params', esc_html__( 'API key or Audience ID is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		$parts = explode( '-', $api_key );

		if ( 2 !== count( $parts ) ) {
			return new \WP_Error( 'invalid_api_key', esc_html__( 'Invalid API key format', 'sellkit' ), [ 'status' => 400 ] );
		}

		$api = [
			'token'  => $parts[0],
			'server' => $parts[1],
		];

		$url_base = "https://{$api['server']}.api.mailchimp.com/3.0/lists/$audience_id";
		$headers  = [
			'Authorization' => 'Basic ' . base64_encode( 'user:' . $api['token'] ),
			'Content-Type'  => 'application/json',
			'User-Agent'    => 'sellkit',
		];

		$tags   = $this->fetch_api_data( "$url_base/tag-search", $headers );
		$fields = $this->fetch_api_data( "$url_base/merge-fields", $headers );
		$groups = $this->fetch_api_data( "$url_base/interest-categories?count=99", $headers );

		if ( is_wp_error( $tags ) || is_wp_error( $fields ) || is_wp_error( $groups ) ) {
			return new \WP_Error( 'api_request_failed', esc_html__( 'Failed to fetch data from Mailchimp API', 'sellkit' ), [ 'status' => 500 ] );
		}

		$custom_fields = [
			'email' => esc_html__( 'Email', 'sellkit' ),
			'full_name' => esc_html__( 'Full Name', 'sellkit' ),
		];

		foreach ( $fields['merge_fields'] as $field ) {
			if ( is_array( $field ) ) {
				$custom_fields[ $field['tag'] ] = $field['name'];
			}
		}

		$responses = [
			'tags' => $tags['tags'] ?? [],
			'fields' => $custom_fields,
			'groups' => [],
		];

		if ( ! empty( $groups['categories'] ) ) {
			foreach ( $groups['categories'] as $category ) {
				$category_data = $this->fetch_api_data( "$url_base/interest-categories/{$category['id']}/interests?count=999", $headers );
				if ( ! is_wp_error( $category_data ) && ! empty( $category_data['interests'] ) ) {
					foreach ( $category_data['interests'] as $interest ) {
						$responses['groups'][ $interest['id'] ] = "{$category['title']}: {$interest['name']}";
					}
				}
			}
		}

		return rest_ensure_response( $responses );
	}

	/**
	 * Fetch MailerLite groups.
	 *
	 * @since 2.3.0
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch_mailerlite_data() {
		$key = sellkit_get_option( 'mailerlite_api_key', '' );

		if ( ! $key ) {
			return new \WP_Error( 'missing_params', esc_html__( 'API key is missing', 'sellkit' ), [ 'status' => 400 ] );
		}

		$url_base = 'https://api.mailerlite.com/api/v2';
		$headers  = [
			'X-MailerLite-ApiKey' => $key,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
		];

		$groups = $this->fetch_api_data( "$url_base/groups", $headers );
		$fields = $this->fetch_api_data( "$url_base/fields", $headers );

		if ( is_wp_error( $groups ) || is_wp_error( $fields ) ) {
			return new \WP_Error( 'api_request_failed', esc_html__( 'Failed to fetch data from MailerLite API', 'sellkit' ), [ 'status' => 500 ] );
		}

		$custom_fields = [
			'email' => esc_html__( 'Email', 'sellkit' ),
			'name' => esc_html__( 'Name', 'sellkit' ),
		];

		foreach ( $fields as $field ) {
			if ( is_array( $field ) && ! array_key_exists( $field['key'], $custom_fields ) ) {
				$custom_fields[ $field['key'] ] = $field['title'];
			}
		}

		return rest_ensure_response( [
			'groups' => $groups,
			'fields' => $custom_fields,
		] );
	}

	/**
	 * Register CRM endpoint.
	 *
	 * @since 2.3.0
	 */
	public function register_crm_endpoint() {
		$routes = [
			[
				'namespace' => 'sellkit/v1',
				'route' => '/activecampaign',
				'method' => 'GET',
				'callback' => [ $this, 'fetch_active_campaign_data' ],
			],
			[
				'namespace' => 'sellkit/v1',
				'route' => '/convertkit',
				'method' => 'GET',
				'callback' => [ $this, 'fetch_convertkit_data' ],
			],
			[
				'namespace' => 'sellkit/v1',
				'route' => '/getResponse',
				'method' => 'GET',
				'callback' => [ $this, 'fetch_getresponse_data' ],
			],
			[
				'namespace' => 'sellkit/v1',
				'route' => '/mailchimp/audiences',
				'method' => 'GET',
				'callback' => [ $this, 'fetch_mailchimp_audiences' ],
			],
			[
				'namespace' => 'sellkit/v1',
				'route' => '/mailchimp/data',
				'method' => 'GET',
				'callback' => [ $this, 'fetch_mailchimp_data' ],
				'args' => [
					'audience_id' => [
						'required' => true,
						'validate_callback' => function( $param ) {
							return is_string( $param );
						}
					]
				]
			],
			[
				'namespace' => 'sellkit/v1',
				'route' => '/drip/accounts',
				'method' => 'GET',
				'callback' => [ $this, 'fetch_drip_accounts' ],
			],
			[
				'namespace' => 'sellkit/v1',
				'route' => '/drip/tags-fields',
				'method' => 'GET',
				'callback' => [ $this, 'fetch_drip_tags_fields' ],
				'args' => [
					'account_id' => [
						'required' => true,
						'validate_callback' => function( $param ) {
							return is_string( $param );
						}
					]
				]
			],
			[
				'namespace' => 'sellkit/v1',
				'route' => '/mailerlite-data',
				'method' => 'GET',
				'callback' => [ $this, 'fetch_mailerlite_data' ],
			],
		];

		foreach ( $routes as $route ) {
			register_rest_route( $route['namespace'], $route['route'], [
				'methods' => $route['method'],
				'callback' => $route['callback'],
				'permission_callback' => [ $this, 'admin_permission_callback' ],
				'args' => isset( $route['args'] ) ? $route['args'] : [],
			] );
		}
	}
}

Sellkit_Blocks_Endpoint::get_instance();
