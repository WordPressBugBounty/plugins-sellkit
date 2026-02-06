<?php

namespace Sellkit\Funnel\Steps;

defined( 'ABSPATH' ) || die();

use Sellkit\Global_Checkout\Checkout as Global_Checkout;
use Elementor\Plugin as Elementor;

/**
 * Class Sellkit_Thankyou.
 *
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @since 1.1.0
 */
class Thankyou extends Base_Step {

	/**
	 * Post ID.
	 *
	 * @var int
	 * @since 2.3.0
	 */
	private $post_id;

	/**
	 * Thankyou constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'template_redirect', [ $this, 'redirect_after_purchase' ], 10 );
	}

	/**
	 * Redirects after purchasing.
	 *
	 * @since 1.1.0
	 */
	public function redirect_after_purchase() {
		if ( ! class_exists( 'woocommerce' ) ) {
			return;
		}

		global $wp;

		$funnel = sellkit_funnel();

		if ( ! empty( $funnel->funnel_id ) && 'thankyou' === $funnel->current_step_data['type']['key'] ) {
			return;
		}

		if ( ! function_exists( 'is_checkout' ) ) {
			return;
		}

		$order_key = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! empty( $order_key ) ) {
			$order_id = wc_get_order_id_by_order_key( $order_key );
			$order    = wc_get_order( $order_id );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}

			$next_step = ! empty( $order ) ? $order->get_meta( 'sellkit_funnel_next_step_data' ) : '';
			$funnel_id = (int) $order->get_meta( 'sellkit_funnel_id' );

			if ( empty( $next_step ) ) {
				return;
			}

			if ( empty( $funnel_id ) ) {
				$step_id   = intval( $next_step['page_id'] );
				$step_data = get_post_meta( $step_id, 'step_data', true );
				$funnel_id = (int) $step_data['funnel_id'];
			}

			// For the new method( upsell popup ) we show thakyou page right away after checkout step.
			$thankyou_id    = $this->find_funnel_thankyou_page( $funnel_id );
			$next_step_link = add_query_arg( [ 'order-key' => $order_key ], get_permalink( $thankyou_id ) );

			if ( class_exists( 'SitePress' ) ) {
				$thankyou_data = $this->wpml_compatibility_get_thank_you_page_data( $thankyou_id );
				$thankyou_id   = isset( $thankyou_data['id'] ) ? $thankyou_data['id'] : $thankyou_id;

				if ( isset( $thankyou_data['lang'] ) ) {
					$next_step_link = $this->wpml_compatibility_get_next_step_link( $thankyou_id, $order_key, $thankyou_data['lang'] );
				}
			}

			$last_price = $order->get_total() - $order->get_total_discount() - $order->get_total_tax();

			$this->contacts->add_total_spent( $last_price, $funnel_id );

			$global_funnel_id = (int) get_option( Global_Checkout::SELLKIT_GLOBAL_CHECKOUT_OPTION );
			if ( $global_funnel_id === $funnel_id ) {
				$this->global_thankyou( $thankyou_id );
				exit();
			}

			wp_safe_redirect( $next_step_link );
			exit();
		}
	}

	/**
	 * Retrieves the translated thank you page data for WPML compatibility.
	 *
	 * This function checks the referring page to determine the current language
	 * and retrieves the translated thank you page ID for that language.
	 *
	 * @since 2.3.2
	 *
	 * @param int $thankyou_id The ID of the default thank you page.
	 *
	 * @return array {
	 *     An associative array containing the translated thank you page ID and language code.
	 *
	 *     @type int    $id   The translated thank you page ID or the original if no translation is found.
	 *     @type string $lang The language code for the thank you page.
	 * }
	 */
	private function wpml_compatibility_get_thank_you_page_data( $thankyou_id ) {
		$referrer_url     = wp_get_referer();
		$previous_page_id = 0;

		$data = [
			'id' => $thankyou_id,
			'lang' => '',
		];

		if ( $referrer_url ) {
			$previous_page_id = url_to_postid( $referrer_url );
		}

		if ( $previous_page_id ) {
			$order_language_details = apply_filters( 'wpml_post_language_details', null, $previous_page_id );
			$current_language       = $order_language_details['language_code'];

			if ( ! empty( $current_language ) ) {
				$thankyou_id_translated = apply_filters( 'wpml_object_id', $thankyou_id, 'sellkit_step', true, $current_language );

				if ( ! empty( $thankyou_id_translated ) ) {
					$data['id']   = $thankyou_id_translated;
					$data['lang'] = $current_language;

					return $data;
				}
			}

			return $data;
		}

		return $data;
	}

	/**
	 * Generates the next step link with WPML compatibility.
	 *
	 * This function generates the next step link by retrieving the translated permalink
	 * for the thank you page in the specified language and appending the order key as a query argument.
	 *
	 * @since 2.3.2
	 *
	 * @param int    $thankyou_id The ID of the thank you page.
	 * @param string $order_key   The order key used for generating the next step link.
	 * @param string $lang        The language code for the thank you page.
	 *
	 * @return string The next step link URL, including the translated permalink and order key.
	 */
	private function wpml_compatibility_get_next_step_link( $thankyou_id, $order_key, $lang ) {
		$permalink      = apply_filters( 'wpml_permalink', get_permalink( $thankyou_id ), $lang );
		$next_step_link = add_query_arg( [ 'order-key' => $order_key ], $permalink );

		return $next_step_link;
	}

	/**
	 * Find funnel thankyou page using one of steps data.
	 *
	 * @param array $funnel_id funnel id.
	 * @since 1.6.2
	 */
	private function find_funnel_thankyou_page( $funnel_id ) {
		$funnel_data = get_post_meta( $funnel_id, 'nodes', true );
		$id          = 0;

		foreach ( $funnel_data as $step ) {
			$step['type'] = (array) $step['type'];

			if ( 'thankyou' === $step['type']['key'] ) {
				$id = $step['page_id'];
			}
		}

		return $id;
	}

	/**
	 * Show global thankyou page.
	 *
	 * @param int $thankyou_id thankyou page id.
	 * @since 1.8.6
	 */
	private function global_thankyou( $thankyou_id ) {
		// Remove previous content.
		remove_all_filters( 'the_content' );

		add_filter( 'sellkit_global_thankyou', '__return_true' );

		if ( 'gutenberg' === sellkit()->page_builder() ) {
			$thankyou_id = get_post( $thankyou_id );

			$this->load_order_cart_details_block_frontend();

			$thankyou_post = get_post( $thankyou_id );

			global $post;
			$post = $thankyou_post; // phpcs:ignore:WordPress.WP.GlobalVariablesOverride.OverrideProhibited
			setup_postdata( $post );

			$content = do_blocks( $post->post_content );

			$content = apply_filters( 'the_content', $content );

			add_filter( 'the_content', function() use ( $content ) {
				ob_Start();

				echo $content; // phpcs:ignore:WordPress.Security.EscapeOutput.OutputNotEscaped

				return ob_get_clean();
			}, 5 );

			sellkit()->load_files( [
				'templates/default-canvas'
			] );
		}

		if ( defined( 'ELEMENTOR_VERSION' ) && 'elementor' === sellkit()->page_builder() ) {
			// Add new content.
			add_filter( 'the_content', function() use ( $thankyou_id ) {
				ob_Start();
				$content = Elementor::instance()->frontend->get_builder_content_for_display( (int) $thankyou_id, true );
				echo do_shortcode( $content );
				return ob_get_clean();
			}, 5 );

			sellkit()->load_files( [
				'templates/canvas'
			] );
		}
	}

	/**
	 * Load order cart details block on frontend.
	 *
	 * @since 2.3.0
	 */
	public function load_order_cart_details_block_frontend() {
		global $post;

		if ( empty( $post->post_content ) ) {
			return;
		}

		$this->post_id = $post->ID;

		$block = 'blocks/order-cart-details';

		$block_data = explode( '/', $block );
		$block_name = $block_data[1];

		$class_name = str_replace( '-', ' ', $block_name );
		$class_name = str_replace( ' ', '_', ucwords( $class_name ) );
		$class_name = "Sellkit\blocks\Render\\{$class_name}";
		$class_path = 'block-editor/' . $block . '/index';

		sellkit()->load_files( [
			$class_path,
		] );

		$new_class = new $class_name( $this->post_id );
		$this->register_inner_blocks_by_parent( $new_class );
		$new_class->register_block_meta();
	}

	/**
	 * Register inner blocks by parent.
	 *
	 * @param Object $parent_class Parent class name.
	 * @since 2.3.0
	 * @return void
	 */
	private function register_inner_blocks_by_parent( $parent_class ) {
		if ( ! method_exists( $parent_class, 'has_inner_blocks' ) ) {
			return;
		}

		$inner_blocks = $parent_class->get_inner_block();

		sellkit()->load_files( $inner_blocks );

		foreach ( $inner_blocks as $key => $value ) {
			if ( isset( $this->inner_blocks[ "blocks/{$key}" ] ) ) {
				continue;
			}

			$inner_block_class = 'Sellkit\Blocks\Inner_Block\\' . str_replace( '-', '_', ucwords( $key ) );

			if ( ! class_exists( $inner_block_class ) ) {
				continue;
			}

			$inner_block_instance = new $inner_block_class( $this->post_id );

			$inner_block_instance->register_block_meta();
		}
	}
}
