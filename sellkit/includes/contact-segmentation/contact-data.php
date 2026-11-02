<?php

namespace Sellkit\Contact_Segmentation;

use Sellkit\Database;

defined( 'ABSPATH' ) || die();

/**
 * Class Contact Data
 *
 * @package Sellkit\Contact_Segmentation
 * @since 1.1.0
 */
class Contact_Data {

	/**
	 * Guest segmentation data cookie name.
	 *
	 * @since 2.8.1
	 */
	const COOKIE_NAME = 'sellkit_contact_segmentation';

	/**
	 * Cookie that holds the signature of the guest segmentation data cookie.
	 *
	 * @since 2.8.1
	 */
	const COOKIE_SIGNATURE_NAME = 'sellkit_contact_segmentation_sig';

	/**
	 * Class instance.
	 *
	 * @since 1.1.0
	 * @var Contact_Data
	 */
	private static $instance = null;

	/**
	 * Contact data.
	 *
	 * @since 1.1.0
	 * @var array Contact data.
	 */
	public $data = [];

	/**
	 * Historical data.
	 *
	 * @since 1.1.0
	 * @var array Contact historical data.
	 */
	public static $historical_data = [];

	/**
	 * Contact_Data constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->force_update_hooks();

		if ( ! function_exists( 'wc' ) || ( is_admin() && ! wp_doing_ajax() ) ) {
			return;
		}

		$this->get_contact_data();

		$this->update_user_info();

		$this->data = array_merge( $this->data, self::$historical_data );

		if ( ! class_exists( 'Contact_Data_Updater' ) ) {
			require_once __DIR__ . '/contact-data-updater.php';
		}

		Contact_Data_Updater::get_instance();
	}

	/**
	 * Update user info.
	 *
	 * @since 1.1.0
	 */
	private function update_user_info() {
		$current_user = wp_get_current_user();

		$this->data['signup_date'] = strtotime( $current_user->user_registered );
		$this->data['user_role']   = $current_user->roles;
	}

	/**
	 * Get the class instance.
	 *
	 * @since 1.1.0
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Gets data.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_data() {
		return array_replace( $this->data, Contact_Data_Updater::$new_data );
	}

	/**
	 * Gets cart terms.
	 *
	 * @since 1.1.0
	 * @param string $taxonomy Taxonomy.
	 */
	public static function get_cart_terms( $taxonomy = 'product_cat' ) {
		$product_ids = [];

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_ids[] = $cart_item['product_id'];
		}

		$terms = get_terms( [
			'taxonomy' => $taxonomy,
			'object_ids' => $product_ids,
		] );

		$term_ids = [];

		foreach ( $terms as $category ) {
			$term_ids[] = $category->term_id;
		}

		return $term_ids;
	}

	/**
	 * Gets cart items.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public static function get_cart_items() {
		$product_ids = [];

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_ids[] = $cart_item['product_id'];
		}

		return $product_ids;
	}

	/**
	 * Gets contact data
	 *
	 * @since 1.1.0
	 */
	public function get_contact_data() {
		if ( ! is_user_logged_in() ) {
			$cookie_json = self::get_verified_cookie_json();

			// Unsigned or tampered data is ignored; it gets rebuilt and signed on this request.
			if ( empty( $cookie_json ) ) {
				return;
			}

			$cookie_data = json_decode( $cookie_json, true );

			// The cookie is visitor-controlled: sanitize every value before use.
			self::$historical_data = is_array( $cookie_data ) ? map_deep( $cookie_data, function ( $value ) {
				return is_string( $value ) ? sanitize_text_field( $value ) : $value;
			} ) : [];

			return;
		}

		global $wpdb;

		$sellkit_prefix = Database::DATABASE_PREFIX;

		$results = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$sellkit_prefix}contact_segmentation WHERE email=%s", self::get_user_email() ),// phpcs:ignore
			ARRAY_A ); // phpcs:ignore

		if ( is_wp_error( $results ) ) {
			new \WP_Error( __( 'Somethings went wrong', 'sellkit' ) );
		}

		if ( empty( $results[0] ) || ! is_array( $results[0] ) ) {
			return;
		}

		$output = [];

		foreach ( $results[0] as $key => $result ) {
			$output[ $key ] = maybe_unserialize( $result );
		}

		self::$historical_data = $output;
	}

	/**
	 * Gets the guest segmentation cookie JSON, only if its signature is valid.
	 *
	 * Guest data (purchase history, RFM, country, ...) is stored in a cookie, so it is signed
	 * to stop visitors from editing it to match conditions.
	 *
	 * @since 2.8.1
	 * @return string Empty string when the cookie is missing or its signature doesn't match.
	 */
	public static function get_verified_cookie_json() {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) || empty( $_COOKIE[ self::COOKIE_SIGNATURE_NAME ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verified against its signature below and sanitized after decoding.
		$cookie_json = wp_unslash( $_COOKIE[ self::COOKIE_NAME ] );
		$signature   = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_SIGNATURE_NAME ] ) );

		if ( ! is_string( $cookie_json ) || ! hash_equals( self::sign_cookie_json( $cookie_json ), $signature ) ) {
			return '';
		}

		return $cookie_json;
	}

	/**
	 * Signs the guest segmentation cookie JSON.
	 *
	 * @since 2.8.1
	 * @param string $cookie_json Cookie JSON.
	 * @return string
	 */
	public static function sign_cookie_json( $cookie_json ) {
		return hash_hmac( 'sha256', $cookie_json, wp_salt( 'auth' ) );
	}

	/**
	 * Gets use email
	 *
	 * @since 1.1.0
	 */
	public static function get_user_email() {
		global $current_user;

		wp_get_current_user();

		$email = $current_user->user_email;

		return $email;
	}

	/**
	 * Force update hooks.
	 *
	 * @since 1.1.0
	 */
	public function force_update_hooks() {
		add_action( 'woocommerce_order_status_changed', function ( $order_id, $old_status, $new_status ) {
			if ( 'completed' === $old_status ) {
				Contact_Data_Updater::force_update_data( $order_id );
			}

			if ( 'completed' === $new_status ) {
				Contact_Data_Updater::force_update_data( $order_id );
			}
		}, 10, 3 );
	}
}
