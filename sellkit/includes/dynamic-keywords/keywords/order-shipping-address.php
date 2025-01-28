<?php

class Order_Shipping_Address extends Tag_Base {
	/**
	 * Get class id.
	 *
	 * @return string
	 */
	public function get_id() {
		return '_order_shipping_address';
	}

	/**
	 * Get class title.
	 *
	 * @param array $atts array of shortcode arguments.
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Order Shipping Address', 'sellkit' );
	}

	/**
	 * Render true content.
	 *
	 * @return string
	 */
	public function render_content( $atts ) {
		$this->get_data();

		if ( empty( self::$order ) ) {
			return $this->shortcode_content( $atts );
		}

		$order_data = self::$order->get_data();

		$adresses = [];

		$adress_1 = isset( $order_data['shipping']['address_1'] ) ? $order_data['shipping']['address_1'] : '';
		$adress_2 = isset( $order_data['shipping']['address_2'] ) ? $order_data['shipping']['address_2'] : '';

		$adresses = [ $adress_1, $adress_2 ];

		if ( empty( $adress_1 ) && empty( $adress_2 ) ) {
			return $this->shortcode_content( $atts );
		}

		return implode( ' ', $adresses );
	}
}
