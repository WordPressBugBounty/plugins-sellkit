<?php
namespace Sellkit\Blocks\Render;
defined( 'ABSPATH' ) || die();

use Sellkit\Blocks\Helpers\Optin\Helper;
use Sellkit\Blocks\Sellkit_Blocks;

/**
 * Optin block.
 *
 * @since 2.3.0
 */
class Optin {
	/**
	 * Post ID.
	 *
	 * @var int
	 * @since 2.3.0
	 * @access private
	 */
	private $post_id;

	/**
	 * Optin constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Check block activation.
	 *
	 * @since 2.3.0
	 * @return boolean
	 */
	public function is_active() {
		return true;
	}

	/**
	 * Register block from meta.
	 *
	 * @since 2.3.0
	 */
	public function register_block_meta() {
		if ( ! $this->is_active() ) {
			return;
		}

		register_block_type_from_metadata(
			__DIR__,
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Get localize data.
	 *
	 * @since 2.3.0
	 * @return array
	 */
	private function get_localize_data() {
		$messages =
			class_exists( 'Sellkit\Blocks\Helpers\Optin\Helper' ) ?
			( new Helper )->get_messages() :
			[
				'success'  => esc_html__( 'The form was sent successfully!', 'sellkit' ),
				'error'    => esc_html__( 'Please check the errors.', 'sellkit' ),
				'required' => esc_html__( 'Required', 'sellkit' ),
			];

		return [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'sellkit_block' ),
			'sellkitOptinValidationsTranslations' => [
				'general' => [
					'errorExists'     => $messages['error'],
					'required'        => $messages['required'],
					'invalidEmail'    => esc_html__( 'Invalid Email address.', 'sellkit' ),
					'invalidPhone'    => esc_html__( 'The value should only consist numbers and phone characters (-, +, (), etc).', 'sellkit' ),
					'invalidNumber'   => esc_html__( 'Invalid number.', 'sellkit' ),
					'invalidMaxValue' => esc_html__( 'Value must be less than or equal to MAX_VALUE.', 'sellkit' ),
					'invalidMinValue' => esc_html__( 'Value must be greater than or equal to MIN_VALUE.', 'sellkit' ),
				],
				// Validation messages specific to Intelligent Tel Input plugin.
				'itiValidation' => [
					'invalidCountryCode' => esc_html__( 'Invalid country code.', 'sellkit' ),
					'tooShort'           => esc_html__( 'Phone number is too short.', 'sellkit' ),
					'tooLong'            => esc_html__( 'Phone number is too long.', 'sellkit' ),
					'areaCodeMissing'    => esc_html__( 'Area code is required..', 'sellkit' ),
					'invalidLength'      => esc_html__( 'Phone number has an invalid length.', 'sellkit' ),
					'invalidGeneral'     => esc_html__( 'Invalid phone number.', 'sellkit' ),
					'typeMismatch'       => [
						'0'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Fixed Line', 'sellkit' ) . '.',
						'1'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Mobile', 'sellkit' ) . '.',
						'2'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Fixed Line or Mobile', 'sellkit' ) . '.',
						'3'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Toll Free', 'sellkit' ) . '.',
						'4'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Premium Rate', 'sellkit' ) . '.',
						'5'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Shared Cost', 'sellkit' ) . '.',
						'6'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'VOIP', 'sellkit' ) . '.',
						'7'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Personal Number', 'sellkit' ) . '.',
						'8'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Pager', 'sellkit' ) . '.',
						'9'  => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'UAN', 'sellkit' ) . '.',
						'10' => esc_html__( 'Phone number must be of type: ', 'sellkit' ) . esc_html__( 'Voicemail', 'sellkit' ) . '.',
					],
				],
			]
		];
	}

	/**
	 * Render block in front-end.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param \WP_Block $block Block object.
	 * @since 2.3.0
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		if ( ! is_admin() ) {
			$localize_data = $this->get_localize_data();

			$blocks = new Sellkit_Blocks();

			$google_map_api_key = sellkit_get_option( 'google_api_key', '' );

			if ( ! empty( $google_map_api_key ) ) {
				$blocks->load_google_map( $google_map_api_key );
			}

			$blocks->load_flatpicker();
			Sellkit_Blocks::load_scripts( 'optin', 'optin-frontend', [ 'sellkit-flatpickr' ], $localize_data );
		}

		return $content;
	}
}

