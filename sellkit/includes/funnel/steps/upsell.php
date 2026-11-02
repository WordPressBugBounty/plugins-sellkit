<?php

namespace Sellkit\Funnel\Steps;

use Sellkit\Funnel\Analytics\Data_Updater;
use Sellkit\Funnel\Contacts\Base_Contacts;
use Sellkit_Funnel;

defined( 'ABSPATH' ) || die();

/**
 * Class Sellkit_Upsell.
 *
 * @since 1.1.0
 */
class Upsell {
	/**
	 * Current funnel.
	 *
	 * @since 1.8.6
	 * @var Sellkit_Funnel
	 */
	public $funnel;

	/**
	 * Upsell constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->funnel = Sellkit_Funnel::get_instance();

		add_action( 'wp_ajax_sellkit_upsell_operations', [ $this, 'upsell_handler' ] );
		add_action( 'wp_ajax_nopriv_sellkit_upsell_operations', [ $this, 'upsell_handler' ] );
	}

	/**
	 * Ajax function to handle accepted offer actions.
	 *
	 * @since 1.1.0
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function upsell_handler() {
		check_ajax_referer( 'sellkit_elementor', 'nonce' );

		$order_key  = sellkit_htmlspecialchars( INPUT_POST, 'order_key' );
		$offer_type = sellkit_htmlspecialchars( INPUT_POST, 'offer_type' );

		if ( empty( $order_key ) ) {
			wp_send_json_error( __( 'Each upsell needs a main order.', 'sellkit' ) );
		}

		$order_key  = sanitize_text_field( $order_key );
		$main_order = wc_get_order( wc_get_order_id_by_order_key( $order_key ) );

		if ( ! $main_order instanceof \WC_Order || ! hash_equals( $main_order->get_order_key(), $order_key ) ) {
			wp_send_json_error( __( 'Invalid order.', 'sellkit' ) );
		}

		$current_step_id   = ! empty( $this->funnel->current_step_data['page_id'] ) ? absint( $this->funnel->current_step_data['page_id'] ) : 0;
		$current_step_type = ! empty( $this->funnel->current_step_data['type']['key'] ) ? $this->funnel->current_step_data['type']['key'] : '';

		// The step must be a published upsell/downsell step of the same funnel as the order.
		if (
			empty( $current_step_id ) ||
			! in_array( $current_step_type, [ 'upsell', 'downsell' ], true ) ||
			! $this->funnel->is_accessible() ||
			absint( $main_order->get_meta( 'sellkit_funnel_id' ) ) !== absint( $this->funnel->funnel_id )
		) {
			wp_send_json_error( __( 'Invalid funnel step.', 'sellkit' ) );
		}

		if (
			empty( $this->funnel->next_no_step_data['page_id'] ) &&
			empty( $this->funnel->next_step_data['page_id'] ) &&
			! empty( $this->funnel->end_node_step_data['page_id'] )
		) {
			$next_step_link = add_query_arg( [ 'order-key' => $main_order->get_order_key() ], get_permalink( $this->funnel->end_node_step_data['page_id'] ) );

			wp_send_json_success( $next_step_link );
		}

		if ( 'reject' === $offer_type && ! empty( $this->funnel->next_no_step_data['page_id'] ) ) {
			$next_step_link = add_query_arg( [ 'order-key' => $main_order->get_order_key() ], get_permalink( $this->funnel->next_no_step_data['page_id'] ) );

			wp_send_json_success( $next_step_link );
		}

		// Only the visitor who placed the order can accept an offer on it. Rejecting only redirects, so it's not checked above.
		if ( ! self::is_order_owned_by_visitor( $main_order ) ) {
			wp_send_json_error( __( 'Invalid order.', 'sellkit' ) );
		}

		// Prevent replaying the same offer: one upsell order per main order and step,
		// unless the previous attempt was not paid (pending, failed or cancelled).
		$accepted_steps = $main_order->get_meta( 'sellkit_upsell_accepted_steps' );
		$accepted_steps = is_array( $accepted_steps ) ? $accepted_steps : [];

		if ( ! empty( $accepted_steps[ $current_step_id ] ) ) {
			$previous_order = wc_get_order( $accepted_steps[ $current_step_id ] );

			if ( $previous_order && ! $previous_order->has_status( [ 'pending', 'failed', 'cancelled' ] ) ) {
				wp_send_json_error( __( 'This offer has already been accepted.', 'sellkit' ) );
			}
		}

		$gateways       = WC()->payment_gateways->payment_gateways();
		$payment_method = $main_order->get_payment_method();

		if ( 'ppcp-gateway' !== $payment_method && empty( $gateways[ $payment_method ] ) ) {
			wp_send_json_error( __( 'Payment method is not available.', 'sellkit' ) );
		}

		$products = ! empty( $this->funnel->current_step_data['data']['products']['list'] ) ? $this->funnel->current_step_data['data']['products']['list'] : [];

		if ( empty( $products ) || ! is_array( $products ) ) {
			wp_send_json_error( __( 'Something went wrong', 'sellkit' ) );
		}

		Base_Contacts::step_is_passed();

		$billing_address  = $main_order->get_address();
		$shipping_address = $main_order->get_address( 'shipping' );

		$order = wc_create_order();

		$order_total = 0;

		foreach ( $products as $product_id => $product ) {
			$product_object = wc_get_product( $product_id );

			if ( ! $product_object ) {
				continue;
			}

			$regular_price  = $product_object->get_regular_price();
			$quantity       = ! empty( $product['quantity'] ) ? $product['quantity'] : 1;
			$discount       = ! empty( $product['discount'] ) ? $product['discount'] : 0;
			$sale_price     = self::calculate_sale_price( $regular_price, $discount, $product['discountType'] );
			$order_total    = floatval( $order_total ) + intval( $quantity ) * floatval( $sale_price );

			$product_object->set_price( $sale_price );
			$order->add_product( $product_object, $quantity );
		}

		$order->set_address( $billing_address );
		$order->set_address( $shipping_address, 'shipping' );
		$order->set_payment_method( $main_order->get_payment_method() );
		$order->set_total( $order_total );
		$order->set_customer_id( get_current_user_id() );
		$order->update_meta_data( 'sellkit_funnel_id', $this->funnel->funnel_id );
		$order->update_meta_data( 'sellkit_main_order_id', $main_order->get_id() );

		if ( ! empty( $this->funnel->next_step_data ) ) {
			$order->update_meta_data( 'sellkit_funnel_next_step_data', $this->funnel->next_step_data );
		}

		if (
			empty( $this->funnel->next_step_data ) &&
			! empty( $this->funnel->current_step_data['targets'][0]['nodeId'] ) &&
			'none' === $this->funnel->current_step_data['targets'][0]['nodeId'] &&
			! empty( $this->funnel->end_node_step_data )
		) {
			$order->update_meta_data( 'sellkit_funnel_next_step_data', $this->funnel->end_node_step_data );
		}

		$order->save();

		$analytics_updater = new Data_Updater();

		// Adding order logs.
		$analytics_updater->set_funnel_id( $this->funnel->funnel_id );
		$analytics_updater->add_new_order_log( $order, 'upsell' );

		$accepted_steps[ $current_step_id ] = $order->get_id();

		$main_order->update_meta_data( 'sellkit_upsell_order_id', $order->get_id() );
		$main_order->update_meta_data( 'sellkit_upsell_accepted_steps', $accepted_steps );
		$main_order->save();

		// The upsell order's key is used by the next funnel steps.
		self::remember_order( $order->get_id() );

		$payment_process = false;

		if ( 'ppcp-gateway' !== $payment_method ) {
			$payment_process = $gateways[ $payment_method ]->process_payment( $order->get_id() );
		}

		if ( 'ppcp-gateway' === $payment_method ) {
			$payment_process = self::ppcp_process_payment( $order->get_id() );
		}

		if ( ! empty( $payment_process['result'] ) && 'success' === $payment_process['result'] && ! empty( $payment_process['redirect'] ) ) {
			wp_send_json_success( $payment_process['redirect'] );
		}

		wp_send_json_error( __( 'Something went wrong', 'sellkit' ) );
	}

	/**
	 * Remembers an order id in the visitor's WC session.
	 *
	 * @since 2.8.1
	 * @param int $order_id Order id.
	 */
	public static function remember_order( $order_id ) {
		if ( ! function_exists( 'WC' ) || ! is_object( WC()->session ) ) {
			return;
		}

		$order_ids = (array) WC()->session->get( 'sellkit_funnel_order_ids', [] );
		$order_ids = array_slice( array_unique( array_merge( array_map( 'absint', $order_ids ), [ absint( $order_id ) ] ) ), -20 );

		WC()->session->set( 'sellkit_funnel_order_ids', $order_ids );
	}

	/**
	 * Checks the order belongs to the current visitor.
	 *
	 * Customer orders must belong to the logged-in user; guest orders must have been
	 * placed in the visitor's current WC session.
	 *
	 * @since 2.8.1
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	public static function is_order_owned_by_visitor( $order ) {
		$customer_id = absint( $order->get_customer_id() );

		if ( $customer_id > 0 ) {
			return get_current_user_id() === $customer_id;
		}

		if ( ! function_exists( 'WC' ) || ! is_object( WC()->session ) ) {
			return false;
		}

		$order_ids = array_map( 'absint', (array) WC()->session->get( 'sellkit_funnel_order_ids', [] ) );

		return in_array( absint( $order->get_id() ), $order_ids, true );
	}

	/**
	 * Calculate the sale price.
	 *
	 * @param string $price Main price.
	 * @param string $discount Discount value.
	 * @param string $discount_type Discount type.
	 */
	public static function calculate_sale_price( $price, $discount, $discount_type ) {
		if ( $discount <= 0 ) {
			return $price;
		}

		if ( 'fixed' === $discount_type ) {
			return floatval( $price ) - floatval( $discount );
		}

		if ( 'percentage' === $discount_type ) {
			return floatval( $price ) - ( ( floatval( $price ) * floatval( $discount ) ) / 100 );
		}
	}

	/**
	 * Upsell actions.
	 *
	 * @since 1.1.0
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function do_actions() {
		$conditions = ! empty( $this->funnel->current_step_data['data']['conditions'] ) ? $this->funnel->current_step_data['data']['conditions'] : [];
		$is_valid   = sellkit_conditions_validation( $conditions );

		$args = [];

		if ( ! empty( $_GET['order-key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public funnel navigation.
			$args['order-key'] = rawurlencode( sanitize_text_field( wp_unslash( $_GET['order-key'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public funnel navigation.
		}

		if ( ! $is_valid && ! empty( $this->funnel->next_step_data['page_id'] ) ) {
			wp_safe_redirect( add_query_arg( $args, get_permalink( $this->funnel->next_step_data['page_id'] ) ) );
			exit;
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @since 1.1.0
	 */
	public static function ppcp_process_payment( $order_id ) {
		if ( ! class_exists( 'WC_Gateway_Paypal' ) ) {
			return false;
		}

		$paypal = new \WC_Gateway_Paypal();

		return $paypal->process_payment( $order_id );
	}
}
