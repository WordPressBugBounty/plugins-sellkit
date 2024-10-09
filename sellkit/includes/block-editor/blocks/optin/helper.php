<?php
namespace Sellkit\Blocks\Helpers\Optin;

defined( 'ABSPATH' ) || die();

use Sellkit\Funnel\Contacts\Base_Contacts;

/**
 * Optin block helper class.
 *
 * @since 2.3.0
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Helper {
	/**
	 * Current post id.
	 *
	 * @since 2.3.0
	 * @var integer|null
	 */
	private $post_id = null;

	/**
	 * Current block client id.
	 *
	 * @since 2.3.0
	 * @var string|null
	 */
	private $client_id = null;

	/**
	 * Current block attributes.
	 *
	 * @since 2.3.0
	 * @var array
	 */
	public $attributes = [];

	/**
	 * Holds all the responses.
	 *
	 * @access public
	 * @var array
	 */
	public $response = [
		'message' => [],
		'errors' => [],
		'admin_errors' => [],
	];

	/**
	 * Holds all the messages.
	 *
	 * @access private
	 * @var array
	 */
	private $messages = [];

	/**
	 * Holds the reponse state.
	 *
	 * @access public
	 * @var bool
	 */
	public $is_success = true;

	/**
	 * Holds a record of the user-filled form.
	 *
	 * @access public
	 * @var array
	 */
	public $form_data;

	/**
	 * Holds the API key.
	 *
	 * @access private
	 * @var string
	 */
	public $api_key;

	/**
	 * Holds the API URL.
	 *
	 * @access private
	 * @var string
	 */
	public $api_url;

	/**
	 * Class constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_submit_optin_block_form', [ $this, 'handle_frontend' ] );
		add_action( 'wp_ajax_nopriv_submit_optin_block_form', [ $this, 'handle_frontend' ] );

		add_action( 'admin_post_sellkit_download_file', [ $this, 'handle_file_download' ] );
		add_action( 'admsellkitin_post_nopriv_sellkit_download_file', [ $this, 'handle_file_download' ] );
	}

	/**
	 * Handle form submission.
	 *
	 * @since 2.3.0
	 */
	public function handle_frontend() {
		if ( false === check_ajax_referer( 'sellkit_block', 'nonce', false ) ) {
			wp_send_json_error( esc_html__( 'Error: Nonce mismatch or expired. Please reload the page and retry.', 'sellkit' ) );
		}

		$this->post_id   = (int) filter_input( INPUT_POST, 'post_id' );
		$this->client_id = filter_input( INPUT_POST, 'client_id' );
		$funnel_page_id  = filter_input( INPUT_POST, 'sellkit_current_page_id', FILTER_SANITIZE_NUMBER_INT );

		$this->form_data = $_POST;

		$funnel = \Sellkit_Funnel::get_instance( $funnel_page_id );

		if ( ! empty( $funnel->current_step_data['type']['key'] ) && 'sales-page' === $funnel->current_step_data['type']['key'] ) {
			$this
				->add_response( 'errors', esc_html__( 'Opt-in widget should not be used in the sales page step.', 'sellkit' ) )
				->set_success( false );

			wp_send_json_error( $this->response );
		}

		$this->get_optin_block_data();

		if ( empty( $this->form_data['fields'] ) ) {
			$this
				->add_response( 'errors', esc_html__( 'There is no field available to submit the opt-in form.', 'sellkit' ) )
				->set_success( false );

			wp_send_json_error( $this->response );
		}

		$this
			->validate_form()
			->set_custom_messages()
			->run_actions()
			->send_response();
	}

	/**
	 * Get messages.
	 *
	 * @since 2.3.0
	 * @access public
	 * @return array
	 */
	public function get_messages() {
		$this->set_custom_messages();

		return $this->messages;
	}

	/**
	 * Validate the form based on form ID.
	 *
	 * @since 1.5.0
	 * @access public
	 */
	public function set_custom_messages() {
		$this->messages = [
			'success'  => esc_html__( 'The form was sent successfully!', 'sellkit' ),
			'error'    => esc_html__( 'Please check the errors.', 'sellkit' ),
			'required' => esc_html__( 'Required', 'sellkit' ),
		];

		if ( ! $this->attributes ) {
			return $this;
		}

		$enable = isset( $this->attributes['customMessagesEnabled'] ) ? $this->attributes['customMessagesEnabled'] : '';

		if ( ! $enable ) {
			return $this;
		}

		$success  = isset( $this->attributes['successCustomMessage'] ) ? $this->attributes['successCustomMessage'] : $this->messages['success'];
		$errors   = isset( $this->attributes['errorsCustomMessage'] ) ? $this->attributes['errorsCustomMessage'] : $this->messages['error'];
		$required = isset( $this->attributes['requiredCustomMessage'] ) ? $this->attributes['requiredCustomMessage'] : $this->messages['required'];

		$this->messages = [
			'success'  => $success,
			'error'    => $errors,
			'required' => $required,
		];

		return $this;
	}

	/**
	 * Get optin block data.
	 *
	 * @since 2.3.0
	 * @access private
	 * @return void
	 */
	private function get_optin_block_data() {
		if ( empty( $this->post_id ) || ! has_block( 'sellkit-blocks/optin', $this->post_id ) ) {
			return;
		}

		$post_content = get_post_field( 'post_content', $this->post_id );
		$blocks       = parse_blocks( $post_content );

		foreach ( $blocks as $block ) {
			if ( 'sellkit-blocks/optin' === $block['blockName'] && $block['attrs']['clientId'] === $this->client_id ) {
				$this->attributes = $block['attrs'];
				return;
			}
		}
	}

	/**
	 * Validate the form based on form ID.
	 *
	 * @since 2.3.0
	 * @access public
	 */
	public function validate_form() {
		if ( ! empty( $this->attributes ) ) {
			return $this;
		}

		$this
			->add_response( 'message', esc_html__( 'There\'s something wrong. The form is not valid.', 'sellkit' ) )
			->set_success( false )
			->send_response();
	}

	/**
	 * Run all the specified actions.
	 *
	 * @since 2.3.0
	 * @access public
	 */
	public function run_actions() {
		$selected_crm = isset( $this->attributes['selectedCRM'] ) ? $this->attributes['selectedCRM'] : [];

		if ( ! empty( $selected_crm ) ) {
			foreach ( $selected_crm as $crm ) {
				if ( ! isset( $this->attributes[ $crm ] ) && 'webHook' !== $crm ) {
					return $this->add_response( 'admin_errors', "{$crm}: " . esc_html__( 'Missing configuration.', 'sellkit' ) );
				}

				$crm_name   = strtolower( $crm );
				$class_path = "block-editor/blocks/optin/crm/{$crm_name}";

				sellkit()->load_files( [
					$class_path,
				] );

				$class_name = str_replace( ' ', '_', ucwords( $crm ) );
				$class_name = "Sellkit\Blocks\Optin\\{$class_name}";

				if ( class_exists( $class_name ) ) {
					$class_name::run( $this );
				}
			}
		}

		$this->handle_download_redirect();

		return $this;
	}

	/**
	 * Handle download redirect.
	 *
	 * @since 2.3.0
	 */
	private function handle_download_redirect() {
		if ( ! $this->is_success ) {
			return;
		}

		$download_source = ! empty( $this->attributes['afterSubmitDownload'] ) ? $this->attributes['afterSubmitDownload'] : '';

		switch ( $download_source ) {
			case 'file':
				$this->download_file();
				break;
			case 'url':
				$this->download_url();
				break;
		}

		$this->redirect();
	}

	/**
	 * Handle file download.
	 *
	 * @since 2.3.0
	 * @access private
	 * @return void
	 */
	private function download_file() {
		$file = ! empty( $this->attributes['afterSubmitDownloadMedia'] ) ? $this->attributes['afterSubmitDownloadMedia'] : '';

		if ( empty( $file['filename'] ) ) {
			return;
		}

		if ( ! file_exists( $file['path'] ) ) {
			$admin_error = esc_html__( 'Download error: The file doesn\'t exist anymore.', 'sellkit' );
			return $this->add_response( 'admin_errors', $admin_error );
		}

		$args = [
			'action'   => 'sellkit_download_file',
			'file'     => base64_encode( $file['path'] ),
			'_wpnonce' => wp_create_nonce(),
		];

		$url = add_query_arg( $args, admin_url( 'admin-post.php' ) );
		return $this->add_response( 'downloadURL', $url );
	}

	/**
	 * Handle file download.
	 *
	 * @since 2.3.0
	 * @access private
	 * @return void
	 */
	private function download_url() {
		$url = ! empty( $this->attributes['afterSubmitDownloadURL'] ) ? $this->attributes['afterSubmitDownloadURL'] : '';

		if ( empty( $url ) ) {
			return;
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$admin_error = esc_html__( 'Download error: Invalid file URL.', 'sellkit' );
			return $this->add_response( 'admin_errors', $admin_error );
		}

		return $this->add_response( 'downloadURL', $url );
	}

	/**
	 * Handles decision node if there is no page on the decision in funnel.
	 *
	 * @param object $funnel_old_data The old funnel data.
	 * @since 2.3.0
	 * @return array|null
	 */
	private function handle_decisiton( $funnel_old_data ) {
		if ( ! empty( $funnel_old_data->next_step_data['page_id'] ) ) {
			return;
		}

		if ( empty( $funnel_old_data->current_step_data['page_id'] ) ) {
			return;
		}

		$current_step_id = $funnel_old_data->current_step_data['page_id'];

		$funnel        = new \Sellkit_Funnel( $current_step_id );
		$decicion_data = $funnel->next_step_data;
		$conditions    = $decicion_data['data']['conditions'];
		$funnel_data   = get_post_meta( $funnel->funnel_id, 'nodes', true );
		$next_no       = $funnel_data[ $decicion_data['targets'][1]['nodeId'] ];
		$next_yes      = $funnel_data[ $decicion_data['targets'][0]['nodeId'] ];
		$is_valid      = sellkit_conditions_validation( $conditions );
		$next_step     = $next_no;

		if ( $is_valid ) {
			$next_step = $next_yes;
		}

		return $next_step;
	}

	/**
	 * Redirect to the next step.
	 *
	 * @since 2.3.0
	 * @access private
	 * @return void
	 */
	private function redirect() {
		$target = ! empty( $this->attributes['afterSubmitRedirect'] ) ? $this->attributes['afterSubmitRedirect'] : 'funnel';

		// When redirect target is funnel next step.
		if ( 'funnel' === $target ) {
			$funnel = sellkit_funnel();

			if ( empty( $funnel->next_step_data ) || empty( $funnel->next_step_data['page_id'] ) ) {
				$next_step_data = $this->handle_decisiton( $funnel );

				if ( empty( $next_step_data ) ) {
					$this
						->set_success( false )
						->add_response( 'errors', esc_html__( 'Internal server error: failed to find next funnel step.', 'sellkit' ) );
					return;
				}

				$funnel->next_step_data = $next_step_data;
			}

			$next_page_id  = sellkit_funnel()->next_step_data['page_id'];
			$current_url   = filter_input( INPUT_POST, 'referrer', FILTER_SANITIZE_URL );
			$current_query = wp_parse_url( $current_url, PHP_URL_QUERY );

			wp_parse_str( $current_query, $params );
			unset( $params['sellkit_step'] );

			// Nonce is already checked in AJAX handler class, so we ignore its phpcs warning.
			$next_step_url = add_query_arg( $params, get_permalink( intval( $next_page_id ) ) ); //phpcs:ignore WordPress.Security.NonceVerification
			return $this->add_response( 'redirectURL', $next_step_url );
		}

		$url = ! empty( $this->attributes['afterSubmitRedirectURL'] ) ? $this->attributes['afterSubmitRedirectURL'] : '';

		// When redirect target is a custom URL.
		if ( empty( $url ) ) {
			return;
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$admin_error = esc_html__( 'Redirect error: Invalid URL.', 'sellkit' );
			return $this->add_response( 'admin_errors', $admin_error );
		}

		return $this->add_response( 'redirectURL', $url );
	}

	/**
	 * Set form state to success/error.
	 *
	 * @param boolean $bool True or false.
	 *
	 * @since 1.5.0
	 * @access public
	 */
	public function set_success( $bool ) {
		$this->is_success = $bool;
		return $this;
	}

	/**
	 * Add response to ajax response.
	 *
	 * @param string $type Response type.
	 * @param string $text Response text.
	 * @param string $text_key Response text key.
	 *
	 * @since 2.3.0
	 * @access public
	 */
	public function add_response( $type, $text = '', $text_key = '' ) {
		if ( ! empty( $text_key ) ) {
			$this->response[ $type ][ $text_key ] = $text;
			return $this;
		}

		$this->response[ $type ][] = $text;
		return $this;
	}

	/**
	 * Send success/fail response.
	 *
	 * @since 2.3.0
	 * @access public
	 */
	public function send_response() {
		if ( ! current_user_can( 'administrator' ) ) {
			unset( $this->response['admin_errors'] );
		} else {
			// Flatten admin_errors.
			$this->response['admin_errors'] = array_values( $this->response['admin_errors'] );
		}

		if ( $this->is_success ) {
			if ( empty( $this->response['message'] ) ) {
				$this->add_response( 'message', $this->messages['success'] );
			}

			Base_Contacts::step_is_passed();

			wp_send_json_success( $this->response );
		}

		if ( ! empty( $this->response['errors'] ) ) {
			$this->add_response( 'message', $this->messages['error'] );
		}

		wp_send_json_error( $this->response );
	}

	/**
	 * Called by hook and handles file download.
	 *
	 * @since 2.3.0
	 * @access public
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function handle_file_download() {
		$file  = filter_input( INPUT_GET, 'file' );
		$nonce = filter_input( INPUT_GET, '_wpnonce' );

		// Validate nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce ) ) {
			wp_die( '<script>window.close();</script>' );
		}

		$file       = base64_decode( $file ); // phpcs:ignore
		$upload_dir = wp_get_upload_dir();

		// Make sure file exists.
		if ( empty( $file ) || ! file_exists( $file ) ) {
			wp_die( '<script>window.close();</script>' );
		}

		$file_name = pathinfo( $file, PATHINFO_BASENAME );
		$file_info = wp_check_filetype_and_ext( $file, $file_name );

		$real_file_path = realpath( $file );

		// Make sure the file exists and is inside the allowed directory.
		if (
			false === $real_file_path ||
			! file_exists( $real_file_path ) ||
			0 !== strpos( wp_normalize_path( $file ), wp_normalize_path( $upload_dir['basedir'] ) )
		) {
			wp_die( '<script>window.close();</script>' );
		}

		// Validate file extension and MIME type.
		if ( empty( $file_info['ext'] ) || empty( $file_info['type'] ) ) {
			wp_die( '<script>window.close();</script>' );
		}

		// Validate file path.
		if (
			strpos( $file, wp_normalize_path( WP_CONTENT_DIR . '/uploads/' ) ) === false ||
			strpos( $file, wp_normalize_path( WP_CONTENT_DIR . '/uploads/' ) ) !== 0
		) {
			wp_die( '<script>window.close();</script>' );
		}

		// Restrict the download to WP upload directory.
		if (
			strpos( $file, $upload_dir['basedir'] ) === false ||
			strpos( $file, $upload_dir['basedir'] ) !== 0
		) {
			wp_die( '<script>window.close();</script>' );
		}

		$file_ext = pathinfo( $file, PATHINFO_EXTENSION );

		// Strip hash.
		$file_name  = str_replace( $file_ext, '', $file_name );
		$file_parts = explode( '__', $file_name );
		$file_name  = array_shift( $file_parts );
		$file_name .= '.' . $file_ext;

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions
		readfile( $file );
	}

	/**
	 * Get API params.
	 *
	 * @param string $crm The CRM name.
	 * @param string $title The CRM title.
	 * @param bool   $has_url Whether the CRM has URL.
	 * @return void|object
	 */
	public function check_api_params( $crm, $title, $has_url = false ) {
		$option_name = "{$crm}_api_key";

		$this->api_key = sellkit_get_option( $option_name, '' );

		if ( $has_url ) {
			$option_name   = "{$crm}_api_url";
			$this->api_url = sellkit_get_option( $option_name, '' );

			if ( empty( $this->api_url ) ) {
				return $this->add_response( 'admin_errors', "{$title}: " . esc_html__( 'Missing API URL.', 'sellkit' ) );
			}
		}

		if ( empty( $this->api_key ) ) {
			return $this->add_response( 'admin_errors', "{$title}: " . esc_html__( 'Missing API credentials.', 'sellkit' ) );
		}
	}

	/**
	 * Creates and returns a text that notifies the user that a field should be made required.
	 *
	 * @param string $title Title of the CRM.
	 * @param string $field_label Label of the field.
	 * @return string
	 *
	 * @access protected
	 * @since 2.3.0
	 */
	protected function get_make_require_notice( $title, $field_label ) {
		return sprintf(
			/* translators: 1: Action name 2: Field name */
			esc_attr__( '%1$s: %2$s is required by api endpoint, but the corresponding field is not made required in your form.', 'sellkit' ),
			$title,
			$field_label
		);
	}

	/**
	 * Creates and returns a text that notifies the user that a field should be made required.
	 *
	 * @param string $title Title of the CRM.
	 * @param string $field_label Label of the field.
	 * @return string
	 *
	 * @access protected
	 * @since 2.3.0
	 */
	protected function make_missing_field( $title, $field_label ) {
		return sprintf(
			/* translators: 1: Action name 2: Field name */
			esc_attr__( '%1$s: %2$s field is missing, please check the created form fields.', 'sellkit' ),
			$title,
			$field_label
		);
	}

	/**
	 * Check required fields.
	 *
	 * @param array $fields The fields.
	 * @param array $requird_fields The required fields.
	 * @return mixed
	 */
	public function check_required_fields( $fields, $requird_fields ) {
		foreach ( $requird_fields as $field ) {
			$value = isset( $fields[ $field ] ) ? $fields[ $field ] : null;
			if ( ! is_null( $value ) ) {
				continue;
			}

			return $this->add_response( 'admin_errors', $this->get_make_require_notice( 'Active Campaign', strtoupper( $field ) ) );
		}
	}

	/**
	 * Get Client IP Address.
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 * @access public
	 * @static
	 */
	public static function get_client_ip() {
		$ip_address     = '';
		$server_headers = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		foreach ( $server_headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				$ip_address = $_SERVER[ $header ];
				break;
			}
		}
		// phpcs:enable

		return $ip_address;
	}

	/**
	 * Get address field.
	 *
	 * @param mixed  $field The field.
	 * @param string $index The index.
	 * @return mixed
	 */
	public function get_address_field( $field, $index ) {
		if ( ! is_array( $field ) ) {
			return $field;
		}

		if ( ! isset( $field['address'] ) ) {
			return $field;
		}

		if ( isset( $field[ $index ] ) ) {
			return $field[ $index ];
		}

		return $field;
	}
}

