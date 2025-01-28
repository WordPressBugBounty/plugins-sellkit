<?php
namespace Sellkit\Blocks\Render;
defined( 'ABSPATH' ) || die();

use Sellkit\Blocks\Sellkit_Blocks;

/**
 * Order cart details block.
 *
 * @since 2.3.0
 */
class Order_Cart_Details {
	/**
	 * Post ID.
	 *
	 * @var int
	 * @since 2.3.0
	 * @access private
	 */
	private $post_id;

	/**
	 * Order.
	 *
	 * @var \WC_Order
	 * @since 2.3.0
	 * @access private
	 */
	private $order;

	/**
	 * Order_Cart_Details constructor.
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
		if ( empty( $this->post_id ) ) {
			return false;
		}

		$step_data = get_post_meta( $this->post_id, 'step_data', true );

		if ( ! empty( $step_data['type']['key'] ) && 'thankyou' === $step_data['type']['key'] ) {
			return true;
		}

		if (
			function_exists( 'is_checkout' ) && function_exists( 'is_wc_endpoint_url' ) &&
			is_checkout() && ! empty( is_wc_endpoint_url( 'order-received' ) )
		) {
			return true;
		}

		return false;
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
	 * Check block has inner blocks.
	 *
	 * @since 2.3.0
	 * @return boolean
	 */
	public function has_inner_blocks() {
		return true;
	}

	/**
	 * Get inner blocks.
	 *
	 * @since 2.3.0
	 * @return boolean
	 */
	public function get_inner_block() {
		return [
			'order-products' => 'block-editor/blocks/order-cart-details/inner-blocks/order-products/class',
			'order-details' => 'block-editor/blocks/order-cart-details/inner-blocks/order-details/class',
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
		$block_html = '';
		$wrapper_attributes = get_block_wrapper_attributes();

		$order = $this->generate_cart_items();

		if ( empty( $order ) ) {
			return $block_html;
		}

		foreach ( $block->inner_blocks as $inner_block ) {
			$block_name = $inner_block->parsed_block['blockName'];

			if ( 'core/heading' !== $block_name ) {
				$block_name = str_replace( 'sellkit-inner-blocks/', '', $block_name );
				$block_name = str_replace( '-', '_', $block_name );
			}

			$block_content = $inner_block->render();
			$class_name    = 'Sellkit\Blocks\Inner_Block\\' . ucwords( $block_name );

			if ( ! class_exists( $class_name ) ) {
				$block_html .= $inner_block->render();

				continue;
			}

			$new_class = new $class_name( $this->order );

			if ( method_exists( $new_class, $block_name ) ) {
				$block_html .= $new_class->$block_name( $block_content, $order );
			}
		}

		if ( empty( $block_html ) ) {
			return '';
		}

		$block_html = sprintf(
			'<div %1$s>%2$s</div>',
			wp_kses_data( $wrapper_attributes ),
			wp_kses_post( $block_html )
		);

		return $block_html;
	}


	/**
	 * Generate cart items.
	 *
	 * @since 2.3.0
	 */
	private function generate_cart_items() {
		$order = $this->get_order_products();

		if ( empty( $order ) ) {
			return;
		}

		$order_items       = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
		$order_items_total = $order->get_order_item_totals();

		return [
			'items'  => $order_items,
			'prices' => $order_items_total,
		];
	}

	/**
	 * Get order products.
	 *
	 * @since 2.3.0
	 * @return mixed
	 */
	private function get_order_products() {
		$key = filter_input( INPUT_GET, 'order-key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $key ) ) {
			$key = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		$order_id = null;

		if ( $key ) {
			$order_id = wc_get_order_id_by_order_key( $key );
		}

		if ( ! empty( $order_id ) ) {
			$this->order = $this->get_order_object( $order_id );
		}

		return $this->order;
	}

	/**
	 * Get order object.
	 *
	 * @return mixed
	 * @param string $order_id Order id.
	 * @since 2.3.0
	 */
	public function get_order_object( $order_id ) {
		$order         = wc_get_order( $order_id );
		$main_order_id = $order->get_meta( 'sellkit_main_order_id' );

		if ( empty( $main_order_id ) ) {
			return $order;
		}

		$main_order  = new \WC_Order( $main_order_id );
		$total_order = new \WC_Order();

		foreach ( $order->get_items() as $item ) {
			$total_order->add_item( $item );
		}

		foreach ( $main_order->get_items() as $item ) {
			$total_order->add_item( $item );
		}

		$total_price = $order->get_total() + $main_order->get_total();

		$total_order->set_total( $total_price );
		$total_order->set_payment_method( $main_order->get_payment_method() );
		$total_order->set_address( $order->get_address() );
		$total_order->set_address( $order->get_address( 'shipping' ), 'shipping' );

		return $total_order;
	}
}

