<?php

defined( 'ABSPATH' ) || die();
/**
 * Tag Base.
 *
 * @since 1.1.0
 */
abstract class Tag_Base {

	/**
	 * Order data.
	 *
	 * @var object
	 * @since 1.1.0
	 */
	public static $order = null;

	/**
	 * Get order data.
	 *
	 * @since 1.1.0
	 */
	public function get_keyword_content() {
		return $this->render_content();
	}

	/**
	 * Get shortcode default data.
	 *
	 * @since 1.1.0
	 * @param array $atts shortcode attributes.
	 */
	public function shortcode_content( $atts ) {
		$atts = shortcode_atts( [
			'fallback' => '',
		], $atts );

		return $atts['fallback'];
	}

	/**
	 * Get shortcode default data.
	 *
	 * @since 1.1.0
	 */
	public function get_data() {
		if ( ! empty( $this::$order ) ) {
			return $this::$order;
		}

		$order_key = ! empty( sellkit_htmlspecialchars( INPUT_GET, 'order-key' ) ) ? sellkit_htmlspecialchars( INPUT_GET, 'order-key' ) : sellkit_htmlspecialchars( INPUT_GET, 'key' );

		if ( empty( $order_key ) ) {
			return;
		}

		$order_key = sanitize_text_field( $order_key );
		$order     = wc_get_order( wc_get_order_id_by_order_key( $order_key ) );

		if ( ! $order instanceof \WC_Order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
			return;
		}

		// Orders of registered customers are only shown to that customer. Guest orders are
		// shown to whoever holds the order key, like WooCommerce's order-received page.
		$customer_id = absint( $order->get_customer_id() );

		if ( $customer_id > 0 && get_current_user_id() !== $customer_id ) {
			return;
		}

		$this::$order = $order;

		return $this::$order;
	}

	/**
	 * Get Keywords type.
	 *
	 * @since 1.1.0
	 */
	public static function get_keywords_type() {
		return 'order_keyword';
	}

	/**
	 * Get keyword id.
	 *
	 * @since 1.1.0
	 * @access public
	 * @abstract
	 *
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Get keyword title.
	 *
	 * @since 1.1.0
	 * @access public
	 * @abstract
	 *
	 * @return string
	 */
	abstract public function get_title();

	/**
	 * Render content.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $atts array of shortcode arguments.
	 * @abstract
	 *
	 * @return string
	 */
	abstract public function render_content( $atts );
}
