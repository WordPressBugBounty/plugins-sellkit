<?php

namespace Sellkit\Admin\Settings\Integration;

defined( 'ABSPATH' ) || die();

use Sellkit\Global_Checkout\Checkout as Global_Checkout;

/**
 * Class Google Analytics and Facebook Pixel integration.
 *
 * @package Sellkit\Admin\Settings\Integration\Settings_Integration
 * @since 1.1.0
 */
class Settings_Integration {
	/**
	 * The class instance.
	 *
	 * @var Object Class instance.
	 * @since 1.1.0
	 */
	public static $instance = null;

	/**
	 * Post data.
	 *
	 * @var array
	 * @since 1.1.0
	 */
	public static $post = [];

	/**
	 * Order key.
	 *
	 * @var string
	 * @since 1.1.0
	 */
	public static $order_key = [];

	/**
	 * Localized data.
	 *
	 * @var array localized data.
	 * @since 1.1.0
	 */
	public static $localized_data = [];

	/**
	 * Class Instance.
	 *
	 * @since 1.1.0
	 * @return Sellkit_Funnel|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'google_facebook_analytics' ] );
	}

	/**
	 * Get google analyticts scripts.
	 *
	 * @since 1.1.0
	 */
	public function google_facebook_analytics() {
		global $post;

		$step_meta = $this->get_step_meta( $post );

		if (
			empty( $post ) ||
			empty( $step_meta ) ||
			$this->is_elementor_preview()
		) {
			return;
		}

		self::$post      = $step_meta;
		self::$order_key = sellkit_htmlspecialchars( INPUT_GET, 'order-key' );

		if ( empty( self::$order_key ) ) {
			self::$order_key = sellkit_htmlspecialchars( INPUT_GET, 'key' );
		}

		if ( ! sellkit()->has_valid_dependencies() ) {
			self::$order_key = null;
		}

		sellkit()->load_files(
			[
				'admin/settings/integration/google-integration',
				'admin/settings/integration/facebook-integration',
			]
		);

		wp_localize_script( 'funnel-settings-variables', 'sellkitSettings', self::$localized_data );
	}

	/**
	 * Get step meta.
	 *
	 * @param object $post post object.
	 * @since 2.3.0
	 * @return array|void
	 */
	private function get_step_meta( $post ) {
		if ( empty( $post ) ) {
			return;
		}

		$step_meta = get_post_meta( $post->ID, 'step_data' );

		if ( ! empty( $step_meta ) ) {
			return $step_meta;
		}

		$global_checkout_id = get_option( Global_Checkout::SELLKIT_GLOBAL_CHECKOUT_OPTION, 0 );

		if ( empty( $global_checkout_id ) ) {
			return;
		}

		if ( 'publish' !== get_post_status( $global_checkout_id ) ) {
			return;
		}

		$global_chekout_nodes = get_post_meta( $global_checkout_id, 'nodes' );

		if ( empty( $global_chekout_nodes ) ) {
			return;
		}

		$global_chekout_nodes = $global_chekout_nodes[0];
		$global_chekout_nodes = $this->check_global_checkout_steps( $global_chekout_nodes, $post->ID );

		if ( empty( $global_chekout_nodes ) ) {
			return;
		}

		return $global_chekout_nodes;
	}

	/**
	 * Check global checkout steps.
	 *
	 * @param array $global_chekout_nodes global checkout nodes.
	 * @param int   $post_id post id.
	 * @since 2.3.0
	 * @return array
	 */
	private function check_global_checkout_steps( $global_chekout_nodes, $post_id ) {
		$default_checkout_page = intval( get_option( 'woocommerce_checkout_page_id', 0 ) );
		$default_thankyou_page = is_checkout() && ! empty( is_wc_endpoint_url( 'order-received' ) );
		$is_checkout_page      = is_checkout() && empty( is_wc_endpoint_url( 'order-received' ) );

		$new_nodes = [];

		if ( $default_checkout_page !== $post_id && $default_thankyou_page ) {
			return [];
		}

		foreach ( $global_chekout_nodes as $node ) {
			if ( 'checkout' === $node['type']['key'] && ! empty( $default_checkout_page ) && $is_checkout_page ) {
				$new_nodes[ $default_checkout_page ] = $node;
				continue;
			}

			if ( 'thankyou' === $node['type']['key'] && ! empty( $default_thankyou_page ) ) {
				$new_nodes[ $post_id ] = $node;
				continue;
			}
		}

		if ( ! isset( $new_nodes[ $post_id ] ) ) {
			return [];
		}

		return $new_nodes;
	}

	/**
	 * Check is elementor preview.
	 *
	 * @since 1.1.0
	 */
	private function is_elementor_preview() {
		if ( class_exists( '\Elementor\Plugin' ) ) {

			if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get product data.
	 *
	 * @since 1.1.0
	 * @param array  $products_list list of the products.
	 * @param string $event event type name.
	 */
	public function get_products_data( $products_list, $event ) {
		if ( empty( $products_list ) ) {
			return;
		}

		$products         = [];
		$products_id      = [];
		$products_name    = '';
		$categories_names = '';

		foreach ( $products_list as $product ) {
			$product_data = wc_get_product( $product['product_id'] );

			if ( $product_data->is_type( 'variable' ) && isset( $product['variation_id'] ) ) {
				$product_data = wc_get_product( $product['variation_id'] );
			}

			if ( ! empty( $product_data ) ) {
				$products_id[]    = (string) $product_data->get_id();
				$products_name    = $products_name . ', ' . $product_data->get_name();
				$categories_names = $categories_names . ', ' . wp_strip_all_tags( wc_get_product_category_list( $product_data->get_id() ) );

				$products[] = [
					'id'       => $product_data->get_id(),
					'name'     => $product_data->get_name(),
					'sku'      => $product_data->get_sku(),
					'category' => wp_strip_all_tags( wc_get_product_category_list( $product_data->get_id() ) ),
					'quantity' => $product['quantity'],
				];
			}
		}

		if ( 'fb' === $event ) {
			$fb_products = [
				'cart_contents'   => $products,
				'content_ids'     => $products_id,
				'products_name'   => $products_name,
				'categories_name' => $categories_names,
			];

			return $fb_products;
		}

		return $products;
	}
}

Settings_Integration::get_instance();
