<?php
namespace Sellkit\Blocks\Inner_Block;

defined( 'ABSPATH' ) || die();

/**
 * Order Products class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Order_Products {
	/**
	 * Order.
	 *
	 * @var \WC_Order
	 * @since 2.3.0
	 * @access private
	 */
	private $order;

	/**
	 * Order_Products constructor.
	 *
	 * @param \WC_Order $order Order.
	 * @since 2.3.0
	 */
	public function __construct( $order ) {
		if ( empty( $order ) ) {
			return;
		}

		$this->order = $order;
	}

	/**
	 * Register block meta.
	 *
	 * @since 2.3.0
	 */
	public function register_block_meta() {
		register_block_type_from_metadata(
			__DIR__
		);
	}

	/**
	 * Render order products item.
	 *
	 * @param string $content Block content.
	 * @param array  $order Order products.
	 * @since 2.3.0
	 * @return string
	 */
	public function order_products( $content, $order ) {
		if ( empty( $order['items'] ) || empty( $this->order ) || ! is_a( $this->order, 'WC_Order' ) ) {
			return '';
		}

		$items = '';

		foreach ( $order['items'] as $id => $item ) {
			if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
				continue;
			}

			$items .= $this->get_items( $id, $item );
		}

		$new_content = preg_replace( '/<\/div>/', '', $content, 1 );

		$html = sprintf(
			'<div class="sellkit-order-cart-details">%1$s<div class="order-products-wrapper">%2$s</div></div></div>',
			$new_content,
			$items,
		);

		return $html;
	}

	/**
	 * Get items.
	 *
	 * @param int   $id Item ID.
	 * @param array $item Item data.
	 * @since 2.3.0
	 * @return string
	 */
	private function get_items( $id, $item ) {
		$qty_display        = $this->get_qty( $id, $item );
		$get_post_thumbnail = get_the_post_thumbnail( $item['product_id'] );

		if ( empty( $get_post_thumbnail ) ) {
			$get_post_thumbnail = wc_placeholder_img_src();
		}

		$qty = '';

		if ( ! empty( $qty_display ) ) {
			$qty = '<span class="order-product-item-quantity">' . wp_kses_post( $qty_display ) . '</span>';
		}

		$title = $this->get_title( $id, $item );
		$price = $this->get_price( $item );

		$classes = apply_filters( 'woocommerce_order_item_class', 'order-product-item', $item, $this->order );

		$item = sprintf(
			'<div class="%1$s"><div class="order-product-item-thumbnail">%2$s %3$s</div>%4$s %5$s</div>',
			esc_attr( $classes ),
			wp_kses_post( $get_post_thumbnail ),
			wp_kses_post( $qty ),
			wp_kses_post( $title ),
			wp_kses_post( $price )
		);

		return $item;
	}

	/**
	 * Get quantity.
	 *
	 * @param int   $id Item ID.
	 * @param array $item Item data.
	 * @since 2.3.0
	 * @return string
	 */
	private function get_qty( $id, $item ) {
		$qty          = $item->get_quantity();
		$refunded_qty = $this->order->get_qty_refunded_for_item( $id );

		$qty_display = esc_html( $qty );

		if ( $refunded_qty ) {
			$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
		}

		return apply_filters( 'woocommerce_order_item_quantity_html', "<span class='product-quantity'>{$qty_display}</span>", $item );
	}

	/**
	 * Get title.
	 *
	 * @param int   $id Item ID.
	 * @param array $item Item data.
	 * @since 2.3.0
	 * @return string
	 */
	private function get_title( $id, $item ) {
		$product = $item->get_product();

		$is_visible        = $product && $product->is_visible();
		$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $this->order );

		ob_start();

		echo wp_kses_post( apply_filters(
			'woocommerce_order_item_name',
			$product_permalink ? sprintf(
				'<a class="order-product-item-title" href="%1$s" data-type="link" >%2$s</a>',
				esc_url( $product_permalink ),
				esc_html( $item->get_name() )
			) :
			$item->get_name(),
			$item,
			$is_visible
		) );

		do_action( 'woocommerce_order_item_meta_start', $id, $item, $this->order, false );

		wc_display_item_meta( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action( 'woocommerce_order_item_meta_end', $id, $item, $this->order, false );

		return ob_get_clean();
	}

	/**
	 * Get product price.
	 *
	 * @param array $item Item data.
	 * @since 2.3.0
	 * @return string
	 */
	private function get_price( $item ) {
		$price = get_woocommerce_currency_symbol( $this->order->get_currency() ) . $this->order->get_line_subtotal( $item, true );

		return '<div class="order-product-item-price">' . esc_html( $price ) . '</div>';
	}
}
