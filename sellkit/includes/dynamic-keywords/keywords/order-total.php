<?php

class Order_Total extends Tag_Base {
	/**
	 * Get class id.
	 *
	 * @return string
	 */
	public function get_id() {
		return '_order_total';
	}

	/**
	 * Get class title.
	 *
	 * @param array $atts array of shortcode arguments.
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Order Total', 'sellkit' );
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

		return $order_data['total'];
	}
}
