<?php
namespace Sellkit\Blocks\Helpers\Checkout;

use Sellkit\Funnel\Analytics\Data_Updater;
use Sellkit\Funnel\Contacts\Base_Contacts;
use Sellkit\Funnel\Steps\Checkout;
use Sellkit_Funnel;
use Sellkit\Global_Checkout\Checkout as Global_Checkout;

defined( 'ABSPATH' ) || die();

/**
 * Checkout block helper class.
 *
 * @since 2.3.0
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(ExcessiveClassLength)
 * @SuppressWarnings(ExcessivePublicCount)
 * @SuppressWarnings(TooManyMethods)
 */
class Helper {
	/**
	 * Current post ID.
	 *
	 * @since 2.3.0
	 * @var null|integer
	 */
	public $post_id = null;

	/**
	 * Checks if bumps applied.
	 *
	 * @since 2.3.0
	 * @var bool
	 */
	public $is_bumps_applied = false;

	/**
	 * Array of added products to cart.
	 *
	 * @since 2.3.0
	 * @var array
	 */
	public $in_cart = [];

	/**
	 * Helper id during checkout process.
	 *
	 * @var int
	 */
	private $helper_id = 0;

	/**
	 * Accept and reject button registration flag.
	 *
	 * @var bool
	 */
	private $is_accept_reject_button_registered = false;

	/**
	 * List of upsell product with price
	 *
	 * @var array
	 */
	private static $sellkit_upsell_products;

	/**
	 * Class constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		// Create empty shortcode to simulate our block page as checkout page.
		add_shortcode( 'sellkit_block_checkout_simulated', function() {
			return '';
		} );

		add_action( 'wp_ajax_sellkit_block_checkout_ajax_handler', [ $this, 'ajax_handler' ] );
		add_action( 'wp_ajax_nopriv_sellkit_block_checkout_ajax_handler', [ $this, 'ajax_handler' ] );

		// Add shipping total to review order by custom hook.
		add_action( 'sellkit-block-checkout-display-shipping-price', [ $this, 'add_shipping_total_to_review_order' ] );

		// Custom coupon form.
		add_action( 'sellkit-block-checkout-custom-coupon-form', [ $this, 'coupon_form' ] );

		// Modify checkout order-review item.
		add_action( 'sellkit-block-one-page-checkout-custom-order-item', [ $this, 'modify_checkout_order_item' ], 1, 4 );

		// Validate user defined fields.
		add_action( 'woocommerce_checkout_process', [ $this, 'validate_user_defined_fields' ] );

		// Save user defined fields.
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_user_defined_fields' ] );

		// Filter checkout ajax fragment.
		add_filter( 'woocommerce_update_order_review_fragments', [ $this, 'checkout_fragment' ] );

		// Simulate checkout page for our block.
		add_action( 'wp', [ $this, 'simulate_our_block_page_as_checkout' ] );

		// Fix shipping method price change on ajax.
		// Manage bump order discounts.
		// Template replacement.
		add_action( 'woocommerce_checkout_update_order_review', [ $this, 'sellkit_checkout_during_ajax' ], 10, 1 );

		add_filter( 'sellkit-block-shipping-methods-choosen-method', [ $this, 'sellkit_default_shipping_method' ], 10, 1 );

		// Modify discounted products.
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'before_cart_calculate' ], 999 );

		// Add order notes to checkout form to desired location.
		add_action( 'sellkit_block_checkout_after_term_and_condition', [ $this, 'order_notes' ] );

		// Order Bump.
		add_action( 'woocommerce_checkout_init', [ $this, 'order_bump' ] );

		// Trigger for upsell steps popup.
		add_action( 'sellkit_block_checkout_required_hidden_fields', [ $this, 'add_trigger_field_for_upsell_steps' ] );

		// Add upsell templates at the end of page.
		add_action( 'wp_footer', [ $this, 'sellkit_funnel_display_upsell_as_popup' ] );

		// Bundle products.
		add_action( 'sellkit_block_before_checkout_form', [ $this, 'bundled_products' ] );

		// Remove coupon from it's default location. will add custom coupon form based on design at desired location.
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		// Rename Shipping text to Shipping Method.
		add_filter( 'woocommerce_shipping_package_name', [ $this, 'rename_shipping_text' ] );
	}

	/**
	 * Handle ajax calls.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	public function ajax_handler() {
		check_ajax_referer( 'sellkit_block_checkout', 'nonce' );

		$sub_action = filter_input( INPUT_POST, 'sub_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( method_exists( $this, $sub_action ) ) {
			call_user_func( [ $this, $sub_action ] );
			return;
		}

		wp_send_json_error();
	}

	/**
	 * Modify checkout ajax response HTML.
	 *
	 * @param array $response woocommerce checkout page ajax response.
	 * @return array
	 * @since 2.3.0
	 */
	public function checkout_fragment( $response ) {
		// Get posted data and identify if it's been sent by jupiter block or not.
		$checkout_form_data = filter_input( INPUT_POST, 'post_data', FILTER_DEFAULT );
		$checkout_form_data = explode( '&', $checkout_form_data );
		$posted_data        = [];

		foreach ( $checkout_form_data as $input ) {
			$item = explode( '=', urldecode( $input ) );

			$posted_data[ trim( $item[0] ) ] = $item[1];
		}

		if ( ! array_key_exists( 'sellkit_current_page_id', $posted_data ) ) {
			return $response;
		}

		// Order review fragment.
		ob_start();
		woocommerce_order_review();
		$order_review = ob_get_clean();

		// Payment fragment.
		ob_start();
		woocommerce_checkout_payment();
		$payment = ob_get_clean();

		$response['.woocommerce-checkout-review-order-table'] = $order_review;
		$response['.woocommerce-checkout-payment']            = $payment;

		return $response;
	}

	/**
	 * Apply coupon code using ajax.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function apply_coupon() {
		$code = filter_input( INPUT_POST, 'code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $code ) ) {
			wp_send_json_error();
		}

		$result = WC()->cart->add_discount( $code );
		wp_send_json_success( $result );
	}

	/**
	 * Update cart item quantity in checkout page by ajax.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function change_cart_item_qty() {
		$qty       = filter_input( INPUT_POST, 'qty', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$id        = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$action    = filter_input( INPUT_POST, 'mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$funnel_id = filter_input( INPUT_POST, 'related_checkout', FILTER_SANITIZE_NUMBER_INT );

		$this->make_changes_after_cart_item_edit( $funnel_id );

		if ( 'add' === $action ) {
			WC()->cart->add_to_cart( $id, $qty );
			$this->make_changes_after_cart_item_edit( $funnel_id );
			wp_send_json_success();
		}

		if ( 'remove' === $action ) {
			$post_type = get_post_type( $id );

			if ( 'product_variation' === $post_type ) {
				foreach ( WC()->cart->get_cart() as $item_key => $item ) {
					if ( $item['variation_id'] === (int) $id ) {
						WC()->cart->remove_cart_item( $item_key ); // we remove it.
						break; // stop the loop.
					}
				}

				$this->make_changes_after_cart_item_edit( $funnel_id );

				wp_send_json_success();
			}

			$product_cart_id = WC()->cart->generate_cart_id( $id );
			$cart_item_key   = WC()->cart->find_product_in_cart( $product_cart_id );

			if ( $cart_item_key ) {
				WC()->cart->remove_cart_item( $cart_item_key );
			}

			$this->make_changes_after_cart_item_edit( $funnel_id );

			wp_send_json_success();
		}

		( $qty > 0 ) ? WC()->cart->set_quantity( $id, $qty ) : WC()->cart->remove_cart_item( $id );

		$this->make_changes_after_cart_item_edit( $funnel_id );

		wp_send_json_success();
	}

	/**
	 * Apply changes after editing cart items.
	 *
	 * @since 2.3.0
	 * @param int $funnel_id funnel step id.
	 */
	public function make_changes_after_cart_item_edit( $funnel_id ) {
		if ( empty( $funnel_id ) ) {
			return;
		}

		$funnel_data       = get_post_meta( $funnel_id, 'step_data', true );
		$optimization_data = ! empty( $funnel_data['data']['optimization'] ) ? $funnel_data['data']['optimization'] : '';

		if ( empty( $optimization_data ) ) {
			return;
		}

		if ( function_exists( 'sellkit_pro' ) && ! sellkit_pro()->is_active_sellkit_pro ) {
			$optimization_data = null;
		}

		// Help to apply funnel discount prices and coupons when changing product quantity.
		$this->apply_discounted_prices( wc()->cart, $funnel_id );
		$this->apply_discounted_prices( wc()->cart, $funnel_id, 'bumps' );

		if ( Checkout::apply_coupon_validation( $optimization_data ) ) {
			WC()->cart->remove_coupons();

			foreach ( $optimization_data['auto_apply_coupons'] as $auto_apply_coupon ) {
				wc()->cart->add_discount( get_the_title( $auto_apply_coupon['value'] ) );
			}

			wc_clear_notices();
		}
	}

	/**
	 * Place shipping price based on design to checkout order-review.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function add_shipping_total_to_review_order() {
		?>
			<tr class="sellkit-shipping-total">
				<th><?php echo esc_html__( 'Shipping', 'sellkit' ); ?></th>
				<td>
					<?php
						$price = WC()->cart->get_shipping_total();

						if ( WC()->cart->display_prices_including_tax() ) {
							$price = WC()->cart->get_shipping_total() + WC()->cart->shipping_tax_total;
						}

						echo wc_price( $price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</td>
			</tr>
		<?php
	}

	/**
	 * Modify checkout review-order based on design.
	 *
	 * @return void
	 * @param string $row html string of cart row.
	 * @param object $product product object.
	 * @param array  $cart_item cart item information.
	 * @param string $cart_item_key cart item unique key.
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function modify_checkout_order_item( $row, $product, $cart_item, $cart_item_key ) {
		$post_id                 = get_the_ID();
		$review_order_attributes = $this->get_inner_block_attributes( 'checkout', 'checkout-review-order', $post_id );

		if ( isset( $review_order_attributes['disableCartEditing'] ) && $review_order_attributes['disableCartEditing'] ) {
			add_filter( 'sellkit-checkout-block-disable-quantity', [ $this, 'checkout_order_hidden_quantity' ], 10, 2 );
		}

		$cart_items = WC()->cart->get_cart();

		//phpcs:disable
		ob_start();
			?>
				<div style="display: inline-block" class="sellkit-checkout-widget-item-image">
					<?php echo $product->get_image(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php
		$img_html = ob_get_clean();

		ob_start();

		$extra_class = $product->get_type();

		if ( $cart_items[ $cart_item_key ] === end( $cart_items ) ) {
			$extra_class .= ' last-cart-item';
		}

		if ( 'variation' === $product->get_type() ) {
			add_filter( 'woocommerce_cart_item_name', [ $this, 'modify_variation_title' ], 50, 2 );
			add_filter( 'woocommerce_get_item_data', [ $this, 'modify_variation_items' ], 10, 2 );
		}
		?>
			<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ) . ' ' . esc_attr( $extra_class ); ?>">

				<td class="product-name sellkit-one-page-checkout-product-name">
					<?php
						echo $img_html;

						$fix_style = '';

						if ( '' === $img_html ) {
							$fix_style = 'margin-left: 0px;';
						}
					?>

					<div class="name-price" style="<?php echo esc_attr( $fix_style ); ?>">
						<span style="display:inline-block">
							<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key ) ) . '&nbsp;'; ?>
						</span>
						<span class="sellkit-checkout-variations" id="sellkit-checkout-variations">
							<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
						<?php echo apply_filters( 'sellkit-checkout-block-disable-quantity', '<input type="number" data-id="' . esc_attr( $cart_item_key ) . '" value="' . esc_attr( $cart_item['quantity'] ) . '" class="sellkit-one-page-checkout-product-qty" >', $cart_item['quantity'] ); ?>
					</div>
				</td>
				<td class="product-total sellkit-one-page-checkout-product-price">
					<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
		<?php
		$cart_item = ob_get_clean();
		echo $cart_item;

		//phpcs:enable

		if ( 'variation' === $product->get_type() ) {
			remove_filter( 'woocommerce_cart_item_name', [ $this, 'modify_variation_title' ], 50 );
			remove_filter( 'woocommerce_get_item_data', [ $this, 'modify_variation_items' ], 10 );
		}
	}

	/**
	 * Remove variation attributes from variation product title.
	 *
	 * @param string $default default value.
	 * @param array  $cart_item cart item data.
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function modify_variation_title( $default, $cart_item ) {
		$product = $cart_item['product_id'];

		return get_the_title( $product );
	}

	/**
	 * Display variation attributes separated from title even if those are less than 3.
	 *
	 * @param array $item_data attributes array.
	 * @param array $cart_item cart item data.
	 * @since 2.3.0
	 */
	public function modify_variation_items( $item_data, $cart_item ) {
		$product    = new \WC_Product_Variation( $cart_item['variation_id'] );
		$attributes = $product->get_attributes();

		if ( count( $attributes ) > 2 ) {
			return $item_data;
		}

		foreach ( $attributes as $key => $value ) {
			$key = str_replace( 'pa_', '', $key );
			$key = str_replace( '-', '', $key );
			$key = str_replace( '_', '', $key );

			$item_data[] = [
				'key'   => $key,
				'value' => $value,
			];
		}

		return $item_data;
	}

	/**
	 * Login user by ajax.
	 * sign user in by using form included in block.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function auth_user() {
		$email  = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );
		$pass   = filter_input( INPUT_POST, 'pass', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$verify = filter_var( $email, FILTER_VALIDATE_EMAIL );

		if ( ! $verify || empty( $email ) || empty( $pass ) ) {
			wp_send_json_error( esc_html__( 'Email or password field is not valid.', 'sellkit' ) );
		}

		$result = wp_authenticate( $email, $pass );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $result->ID );
		wp_set_auth_cookie( $result->ID );

		wp_send_json_success( $result );
	}

	/**
	 * Validate user defined custom value.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function validate_user_defined_fields() {
		$post_id = filter_input( INPUT_POST, 'sellkit_current_page_id', FILTER_DEFAULT, FILTER_SANITIZE_NUMBER_INT );

		$shipping_attributes = $this->get_inner_block_attributes( 'checkout', 'checkout-form-shipping', $post_id );
		$billing_attributes  = $this->get_inner_block_attributes( 'checkout', 'checkout-billing-details', $post_id );

		if ( isset( $shipping_attributes['locations'] ) ) {
			$this->checkout_fields_validation( $shipping_attributes['locations'], $billing_attributes['locations'] );
		}

		add_filter( 'woocommerce_checkout_fields', function( $default_fields ) {
			unset( $default_fields['billing']['billing_email'] );

			// Set all fields to optional by default.
			foreach ( $default_fields['billing'] as $field_key => $field ) {
				$default_fields['billing'][ $field_key ]['required'] = false;
			}

			foreach ( $default_fields['shipping'] as $field_key => $field ) {
				$default_fields['billing'][ $field_key ]['required'] = false;
			}

			return $default_fields;
		} );
	}

	/**
	 * Save user defined custom fields value to database.
	 *
	 * @param int $order_id id of woocommerce order.
	 * @return void
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function save_user_defined_fields( $order_id ) {
		$order   = wc_get_order( $order_id );
		$user_id = $order->get_user_id();

		$fields = [
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_postcode',
			'shipping_country',
			'shipping_state',
			'shipping_phone',
		];

		foreach ( $fields as $field ) {
			$this->update_shipping_field( $order, $user_id, $field );
		}

		$order->save();
	}

	/**
	 * Update shipping field for both order and user meta.
	 *
	 * @param WC_Order $order    The WooCommerce order object.
	 * @param int      $user_id  The user ID, if available.
	 * @param string   $field    The field name to update.
	 * @since 2.3.0
	 */
	private function update_shipping_field( $order, $user_id, $field ) {
		if ( ! empty( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$sanitized_value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification

			$setter_method = 'set_' . $field;
			if ( method_exists( $order, $setter_method ) ) {
				$order->$setter_method( $sanitized_value );
			}

			if ( $user_id ) {
				update_user_meta( $user_id, $field, $sanitized_value );
			}
		}
	}

	/**
	 * Place custom coupon form in checkout order-review based design.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function coupon_form() {
		$post_id = get_the_ID();

		if ( ! empty( $_POST ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			$checkout_form_data = filter_input( INPUT_POST, 'post_data', FILTER_DEFAULT );
			$checkout_form_data = explode( '&', $checkout_form_data );
			$posted_data        = [];

			foreach ( $checkout_form_data as $input ) {
				$item = explode( '=', urldecode( $input ) );

				$posted_data[ trim( $item[0] ) ] = $item[1];
			}

			$post_id = $posted_data['sellkit_current_page_id'];
		}

		$review_order_attributes = $this->get_inner_block_attributes( 'checkout', 'checkout-review-order', $post_id );

		if ( isset( $review_order_attributes['disableCoupon'] ) && $review_order_attributes['disableCoupon'] ) {
			return;
		}

		echo '<tr class="coupon-form border-none"><td colspan="2">';
			$this->form( $review_order_attributes );
		echo '</td></tr>';
	}

	/**
	 * New custom coupon form HTML.
	 *
	 * @param array $review_order_attributes Review order innerblock attributes.
	 * @return void
	 * @since 2.3.0
	 */
	public function form( $review_order_attributes ) {
		?>
			<div class="sellkit-custom-coupon-form sellkit-normal-coupon-form">
				<p class="sellkit-form-row-first">
					<input class="sellkit-coupon" type="text" placeholder="<?php echo esc_attr( $review_order_attributes['couponPlaceholderText'] ); ?>" />
				</p>

				<p class="sellkit-form-row-last">
					<span class="sellkit-checkout-widget-secondary-button sellkit-apply-coupon sellkit-apply-coupon" >
						<?php echo esc_html( $review_order_attributes['couponButtonText'] ); ?>
					</span>
				</p>
			</div>
		<?php
	}

	/**
	 * Look for an email if exists or not.
	 * Is used for login process.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function search_for_email() {
		$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );
		$valid = filter_var( $email, FILTER_VALIDATE_EMAIL );

		if ( ! $valid || empty( $email ) ) {
			wp_send_json_error();
		}

		$check = email_exists( $email );

		if ( false === $check ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Look for an username if exists or not.
	 * Is used for register process.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function search_for_username() {
		$username = filter_input( INPUT_POST, 'user', FILTER_SANITIZE_EMAIL );

		if ( empty( $username ) ) {
			wp_send_json_error();
		}

		$username = sanitize_user( $username );
		$check    = username_exists( $username );

		if ( false !== $check ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Simulate checkout page where our block is present.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	public function simulate_our_block_page_as_checkout() {
		$page_id = get_the_ID();
		$content = get_post_field( 'post_content', $page_id );

		if ( false === strpos( $content, 'sellkit-blocks/checkout' ) ) {
			return;
		}

		add_filter( 'woocommerce_is_checkout', function() {
			return true;
		} );

		add_filter( 'theme_mod_jupiterx_jupiterx_checkout_cart_elements', function() {
			return [];
		}, 10 );
	}

	/**
	 * Integrate user country with our block.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	public function set_customer_details_ajax() {
		$country          = filter_input( INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$state            = filter_input( INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$shipping_country = filter_input( INPUT_POST, 'shipping_country', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$shipping_state   = filter_input( INPUT_POST, 'shipping_state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! empty( $country ) ) {
			setcookie( 'sellkit-checkout-billing-cc', $country, time() + ( 86400 * 3 ), '/' );
			setcookie( 'sellkit-checkout-billing-state', $state, time() + ( 86400 * 3 ), '/' );
		}

		if ( ! empty( $shipping_country ) ) {
			setcookie( 'sellkit-checkout-shipping-cc', $shipping_country, time() + ( 86400 * 3 ), '/' );
			setcookie( 'sellkit-checkout-shipping-state', $shipping_state, time() + ( 86400 * 3 ), '/' );
		}

		wp_send_json_success();
	}

	/**
	 * Modify cart contents using bundled products.
	 *
	 * @since 2.3.0
	 * @return void
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function sellkit_checkout_modify_cart_by_bundle_products() {
		$qty         = filter_input( INPUT_POST, 'qty', FILTER_SANITIZE_NUMBER_INT );
		$id          = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		$type        = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$checkout_id = filter_input( INPUT_POST, 'checkout_id', FILTER_SANITIZE_NUMBER_INT );
		$modify      = filter_input( INPUT_POST, 'modify', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$key         = filter_input( INPUT_POST, 'key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! empty( $key ) ) {
			foreach ( WC()->cart->get_cart() as $item_key => $item ) {
				if ( $item_key === $key ) {
					WC()->cart->set_quantity( $item_key, $qty, true );
					break; // stop the loop.
				}
			}

			wp_send_json_success();
		}

		if ( 'radio' === $type ) {
			$step_data  = get_post_meta( $checkout_id, 'step_data', true );
			$products   = $step_data['data']['products']['list'];
			$reset_cart = isset( $step_data['data']['products']['reset_cart'] ) ? $step_data['data']['products']['reset_cart'] : 'true';

			if ( 'true' === $reset_cart ) {
				foreach ( WC()->cart->get_cart() as $item ) {
					if ( empty( $item['product_id'] ) ) {
						continue;
					}

					if ( array_key_exists( $item['product_id'], $products ) ) {
						continue;
					}

					$products[ $item['product_id'] ] = [
						'quantity' => $item['quantity'],
						'discount' => '',
						'discountType' => 'fixed',
					];
				}
			}

			$selected      = ! empty( $products[ $id ] ) ? $products[ $id ] : [];
			$real_quantity = ( empty( $selected['quantity'] ) ) ? 1 : $selected['quantity'];

			if ( (int) $real_quantity !== (int) $qty ) {
				wp_send_json_error( esc_html__( 'Oh do not be tricky.', 'sellkit' ) );
			}
		}

		if ( 'radio' === $type ) {
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( $id, $qty );
		}

		if ( 'checkbox' === $type && 'add' === $modify ) {
			WC()->cart->add_to_cart( $id, $qty );
		}

		if ( 'checkbox' === $type && 'remove' === $modify ) {
			$post_type = get_post_type( $id );

			// Maybe product is a variation.
			if ( 'product_variation' === $post_type ) {
				foreach ( WC()->cart->get_cart() as $item_key => $item ) {
					if ( $item['variation_id'] === (int) $id ) {
						WC()->cart->remove_cart_item( $item_key ); // we remove it.
						break; // stop the loop.
					}
				}

				$this->make_changes_after_cart_item_edit( $checkout_id );

				wp_send_json_success();
			}

			// Default product.
			$product_cart_id = WC()->cart->generate_cart_id( $id );
			$cart_item_key   = WC()->cart->find_product_in_cart( $product_cart_id );

			if ( $cart_item_key ) {
				WC()->cart->remove_cart_item( $cart_item_key );
			}
		}

		$this->make_changes_after_cart_item_edit( $checkout_id );

		wp_send_json_success();
	}

	/**
	 * Apply funnel discount.
	 * Will be called twice, first when page is loading , second during checkout ajax.
	 *
	 * @param object $cart cart object.
	 * @param int    $checkout_id_ajax checkout id.
	 * @param string $source source of products, bump or default products to be checked.
	 * @param array  $upsell_data upsell product data coming from popup.
	 * @since 2.3.0
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function apply_discounted_prices( $cart = [], $checkout_id_ajax = null, $source = 'default', $upsell_data = [] ) {
		$checkout_id = get_queried_object_id();

		if ( wp_doing_ajax() ) {
			$checkout_id = $checkout_id_ajax;
		}

		if ( empty( $checkout_id ) ) {
			return;
		}

		$step_data = get_post_meta( $checkout_id, 'step_data', true );

		if ( empty( $step_data ) ) {
			return;
		}

		$products = [];

		if (
			'default' === $source &&
			isset( $step_data['data']['products'] ) &&
			array_key_exists( 'data', $step_data ) &&
			array_key_exists( 'list', $step_data['data']['products'] )
		) {
			$products = $step_data['data']['products']['list'];
		}

		if ( 'bumps' === $source && ! empty( $step_data['bump'] ) ) {
			$bumps = $step_data['bump'];

			foreach ( $bumps as $bump ) {
				if ( ! array_key_exists( 'products', $bump['data'] ) ) {
					continue;
				}

				$key = ! empty( $bump['data']['products']['list'] ) ? array_key_first( $bump['data']['products']['list'] ) : null;

				if ( empty( $key ) ) {
					continue;
				}

				$products[ $key ] = $bump['data']['products']['list'][ $key ];
			}
		}

		if ( 'upsell' === $source ) {
			$products = $upsell_data['data']['products']['list'];
		}

		if ( empty( $products ) ) {
			return;
		}

		$final_price = 0;

		foreach ( $cart->get_cart() as $key => $details ) {
			$item_id = $details['product_id'];
			$price   = false;

			if ( ! empty( $details['variation_id'] ) ) {
				$item_id = $details['variation_id'];
			}

			foreach ( $products as $product_id => $product_details ) {
				if ( $item_id === $product_id ) {
					$discount_type  = isset( $product_details['discountType'] ) ? $product_details['discountType'] : '';
					$discount_value = isset( $product_details['discount'] ) ? $product_details['discount'] : '';

					$price = $this->calculate_discount( $item_id, $discount_type, $discount_value );

					if ( 'upsell' === $source ) {
						self::$sellkit_upsell_products[ $product_id ] = $price;
					}
				}
			}

			if ( false !== $price ) {
				$details['data']->set_price( $price );
			}

			$final_price += $details['data']->get_price() * $details['quantity'];
		}

		$this->modify_products_price_before_woo_apply_discounts( $final_price );
	}

	/**
	 * Modify cart subtotal before validating discounts.
	 *
	 * @since 2.3.0
	 * @param int $final_price cart new subtotal.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function modify_products_price_before_woo_apply_discounts( $final_price ) {
		add_filter( 'woocommerce_coupon_validate_minimum_amount', function( $value, $coupon ) use ( $final_price ) {
			return $coupon->get_minimum_amount() > $final_price;
		}, 99, 2 );

		add_filter( 'woocommerce_coupon_validate_maximum_amount', function( $value, $coupon ) use ( $final_price ) {
			return $coupon->get_maximum_amount() < $final_price;
		}, 99, 2 );
	}

	/**
	 * Fix shipping method price on checkout ajax state change.
	 *
	 * @param string $data checkout form data.
	 * @since 2.3.0
	 * @return void
	 */
	public function sellkit_checkout_during_ajax( $data ) {
		$data  = explode( '&', $data );
		$clear = [];

		foreach ( $data as $key => $input ) {
			$input              = explode( '=', $input );
			$clear[ $input[0] ] = $input[1];
		}

		if ( ! array_key_exists( 'sellkit_current_page_id', $clear ) ) {
			return;
		}

		add_action( 'sellkit-block-checkout-custom-coupon-form', [ $this, 'coupon_form' ] );

		$this->apply_template_replacement_during_ajax( $clear );

		$_POST = $this->assigning_default_ajax_shipping_fields( $_POST, $clear ); // phpcs:ignore

		$this->order_bump( $clear );

		if ( array_key_exists( 'sellkit-bundle-products', $clear ) ) {
			$option = $clear['sellkit-bundle-products'];

			$this->bundle_product_action( $option );
		}

		// Apply discounts.
		if ( ! array_key_exists( 'sellkit_current_page_id', $clear ) ) {
			return;
		}

		$this->apply_discounted_prices( wc()->cart, $clear['sellkit_current_page_id'] );
		$this->apply_discounted_prices( wc()->cart, $clear['sellkit_current_page_id'], 'bumps' );
	}

	/**
	 * Assign checkout ajax default shipping fields after ajax is sent.
	 *
	 * @param array $post post request.
	 * @param array $clear checkout form data.
	 * @return array
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function assigning_default_ajax_shipping_fields( $post, $clear ) {
		$field_mappings = [
			'billing_state'     => 'state',
			'shipping_country'  => 's_country',
			'shipping_state'    => 's_state',
			'shipping_postcode' => 's_postcode',
			'shipping_city'     => 's_city',
			'shipping_phone'    => 's_phone',
			'shipping_company'  => 's_company',
			'shipping_address_1' => 's_address',
			'shipping_address_2' => 's_address_2',
		];

		foreach ( $field_mappings as $clear_key => $post_key ) {
			$post = $this->update_post_field( $post, $clear, $clear_key, $post_key );
		}

		return $post;
	}

	/**
	 * Helper function to update post array with the corresponding value from clear array.
	 *
	 * @param array  $post      The post request array.
	 * @param array  $clear     The checkout form data array.
	 * @param string $clear_key The key in the $clear array.
	 * @param string $post_key  The key in the $post array.
	 * @return array
	 */
	private function update_post_field( $post, $clear, $clear_key, $post_key ) {
		if ( array_key_exists( $clear_key, $clear ) ) {
			$post[ $post_key ] = $clear[ $clear_key ];
		}

		return $post;
	}

	/**
	 * Template replacement during ajax call.
	 *
	 * @param array $data checkout form data.
	 * @return void
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	private function apply_template_replacement_during_ajax( $data ) {
		add_filter( 'wc_get_template', function( $located, $template_name, $args, $template_path, $default_path ) {
			$our_path = sellkit()->plugin_dir() . 'includes/block-editor/blocks/checkout/templates/';
			$files    = [
				'review-order.php',
				'payment-method.php',
				'payment.php',
				'form-checkout.php',
				'cart-item-data.php',
				'cart-shipping.php',
				'terms.php',
			];
			$template = str_replace( 'checkout/', '', $template_name );
			$template = str_replace( 'cart/', '', $template );

			if ( in_array( $template, $files, true ) ) {
				$located = $our_path . $template;
			}

			return $located;
		}, 10, 5 );

		$this->review_order_template_attributes();
	}

	/**
	 * Pass attribute args to review order template.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function review_order_template_attributes() {
		$post_data = filter_input( INPUT_POST, 'post_data', FILTER_DEFAULT );
		$post_id   = 0;

		if ( ! empty( $post_data ) ) {
			parse_str( $post_data, $parsed_data );

			if ( isset( $parsed_data['sellkit_current_page_id'] ) ) {
				$post_id = intval( $parsed_data['sellkit_current_page_id'] );
			}
		}

		$review_order_attributes = $this->get_inner_block_attributes( 'checkout', 'checkout-review-order', $post_id );

		$args = [
			'review_order_attributes' => $review_order_attributes,
		];

		wc_get_template_html( 'checkout/review-order.php', $args );
	}

	/**
	 * Modify default selected shipping method.
	 *
	 * @since 2.3.0
	 * @param string $default default selected method.
	 * @return string
	 */
	public function sellkit_default_shipping_method( $default ) {
		$applied_coupons = isset( WC()->cart ) ? WC()->cart->get_applied_coupons() : [];

		if ( count( $applied_coupons ) < 1 ) {
			return $default;
		}

		foreach ( $applied_coupons as $coupon_code ) {

			$coupon = new \WC_Coupon( $coupon_code );

			if ( $coupon->get_free_shipping() ) {
				if ( count( WC()->session->get( 'shipping_for_package_0' )['rates'] ) > 0 ) {
					// Loop through.
					foreach ( WC()->session->get( 'shipping_for_package_0' )['rates'] as $rate_id => $rate ) {
						// For free shipping.
						if ( 'free_shipping' === $rate->method_id ) {
							return $rate_id;
						}
					}
				}
			}
		}

		return $default;
	}

	/**
	 * Recalculate checkout and cart prices.
	 *
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function before_cart_calculate() {
		// More than twice isn't necessary. this is called multiple times by WooCommerce.
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 && wp_doing_ajax() ) {
			return;
		}

		// Gather checkout id.
		$checkout_id = get_queried_object_id();

		if ( wp_doing_ajax() ) {
			// First try to catch id using our ajax call.
			$checkout_id = filter_input( INPUT_POST, 'related_checkout', FILTER_SANITIZE_NUMBER_INT );

			// Second try to catch id using woocommerce ajax.
			if ( empty( $checkout_id ) ) {
				$checkout_id = filter_input( INPUT_POST, 'sellkit_current_page_id', FILTER_SANITIZE_NUMBER_INT );
			}

			// Catch id after pressing place order button. none of above methods worked.
			if ( empty( $checkout_id ) ) {
				$data = filter_input( INPUT_POST, 'post_data', FILTER_DEFAULT );

				if ( ! empty( $data ) ) {
					parse_str( $data, $data );
				}

				if ( is_array( $data ) && array_key_exists( 'sellkit_current_page_id', $data ) ) {
					$checkout_id = $data['sellkit_current_page_id'];
				}
			}
		}

		// Empty id ? no action.
		if ( empty( $checkout_id ) ) {
			return;
		}

		$checkout_id = (int) $checkout_id;

		// Apply funnel prices.
		$this->apply_discounted_prices( wc()->cart, $checkout_id );
		$this->apply_discounted_prices( wc()->cart, $checkout_id, 'bumps' );

		// Apply upsell discount.
		$sellkit_upsell_prices = null;
		if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
			$sellkit_upsell_prices = isset( $_POST['sellkit_product_prices'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sellkit_product_prices'] ) ) : null;
		}
		$upsell_product_ids;

		if ( isset( $sellkit_upsell_prices ) && ! empty( $sellkit_upsell_prices ) ) {
			$sellkit_upsell_prices = json_decode( $sellkit_upsell_prices, true );
			$upsell_product_ids    = array_keys( $sellkit_upsell_prices );
		}

		if ( isset( $upsell_product_ids ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product      = $cart_item['data'];
				$product_name = $product->get_name();
				$product_id   = $product->get_id();

				if ( in_array( $product_id, $upsell_product_ids, true ) ) {
					$upsell_price = $sellkit_upsell_prices[ $product_id ];
					$product->set_price( $upsell_price );
				}

				$price = $product->get_price();
			}
		}

		// We should re apply coupons after each price changes. to make sure everything is correct.
		$optimization_data = ! empty( $funnel_data['data']['optimization'] ) ? $funnel_data['data']['optimization'] : '';

		if ( Checkout::apply_coupon_validation( $optimization_data ) ) {
			WC()->cart->remove_coupons();

			foreach ( $optimization_data['auto_apply_coupons'] as $auto_apply_coupon ) {
				wc()->cart->add_discount( get_the_title( $auto_apply_coupon['value'] ) );
			}

			wc_clear_notices();
		}
	}

	/**
	 * Check sellkit step through ajax.
	 * And in order decide to show upsell or downsell steps through a popup.
	 *
	 * @since 2.3.0
	 */
	public function call_funnel_popups() {
		$step      = filter_input( INPUT_POST, 'step', FILTER_SANITIZE_NUMBER_INT );
		$funnel    = new Sellkit_Funnel( $step );
		$next_step = $funnel->next_step_data;
		$popups    = [ 'upsell', 'downsell' ];

		$funnel->next_step_data['type'] = (array) $funnel->next_step_data['type'];

		if ( 'decision' === $funnel->next_step_data['type']['key'] ) {
			$page_id = isset( $funnel->next_step_data['page_id'] ) ? $funnel->next_step_data['page_id'] : 0;

			$this->helper_id = $step;
			$this->take_care_of_decision_step( $page_id, $funnel->funnel_id );
			return;
		}

		if ( in_array( $funnel->next_step_data['type']['key'], $popups, true ) ) {
			wp_send_json_success( [
				'next_id'   => $next_step['page_id'],
				'next_type' => $next_step['type']['key'],
			] );
		}
	}

	/**
	 * Gets step id before the decision step and return result.
	 *
	 * @param int $step_id id of the step before decision step.
	 * @param int $funnel_id id of the funnel.
	 * @since 2.3.0
	 */
	private function take_care_of_decision_step( $step_id, $funnel_id = null ) {
		// Failed decision step due to no page id should be checked a little different.
		if ( empty( $step_id ) && ! empty( $funnel_id ) ) {
			$this->check_decision_step_with_no_page_id();
		}

		$funnel     = new Sellkit_Funnel( $step_id );
		$conditions = ! empty( $funnel->current_step_data['data']['conditions'] ) ? $funnel->current_step_data['data']['conditions'] : [];
		$is_valid   = sellkit_conditions_validation( $conditions );
		$next_step  = $funnel->next_no_step_data;

		if ( $is_valid ) {
			$next_step = $funnel->next_step_data;
		}

		$next_step['type'] = (array) $next_step['type'];

		if ( 'decision' === $next_step['type']['key'] ) {
			$this->take_care_of_decision_step( $next_step['page_id'] );

			return;
		}

		wp_send_json_success( [
			'next_id'   => $next_step['page_id'],
			'next_type' => $next_step['type']['key'],
		] );
	}

	/**
	 * Decision next step directly using funnel data.
	 *
	 * @since 2.3.0
	 */
	private function check_decision_step_with_no_page_id() {
		$funnel        = new Sellkit_Funnel( $this->helper_id );
		$decicion_data = $funnel->next_step_data;
		$conditions    = $decicion_data['data']['conditions'];
		$funnel_data   = get_post_meta( $funnel->funnel_id, 'nodes', true );
		$next_no       = 'none' !== $decicion_data['targets'][1]['nodeId'] ? $funnel_data[ $decicion_data['targets'][1]['nodeId'] ] : $funnel->end_node_step_data;
		$next_yes      = 'none' !== $decicion_data['targets'][0]['nodeId'] ? $funnel_data[ $decicion_data['targets'][0]['nodeId'] ] : $funnel->end_node_step_data;
		$is_valid      = sellkit_conditions_validation( $conditions );
		$next_step     = $next_no;

		if ( $is_valid ) {
			$next_step = $next_yes;
		}

		wp_send_json_success( [
			'next_id'   => $next_step['page_id'],
			'next_type' => $next_step['type']['key'],
		] );
	}

	/**
	 * Perform upsell popup accept button.
	 *
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function perform_upsell_accept_button() {
		// Gather and validate information.
		$upsell_id   = filter_input( INPUT_POST, 'upsell_id', FILTER_SANITIZE_NUMBER_INT );
		$checkout_id = filter_input( INPUT_POST, 'checkout_id', FILTER_SANITIZE_NUMBER_INT );
		$response    = [];

		if ( empty( $upsell_id ) ) {
			wp_send_json_error( esc_html__( 'Empty Upsell ID.', 'sellkit' ) );
		}

		$upsell_data = get_post_meta( $upsell_id, 'step_data', true );

		if ( empty( $upsell_data ) ) {
			wp_send_json_error( esc_html__( 'Empty Step Data.', 'sellkit' ) );
		}

		// Add product to cart.
		$product_id = '';
		$qty        = '';

		if (
			is_array( $upsell_data['data'] ) &&
			array_key_exists( 'products', $upsell_data['data'] ) &&
			! empty( $upsell_data['data']['products']['list'] )
		) {
			$product_id = array_key_first( $upsell_data['data']['products']['list'] );
			$qty        = ! empty( $upsell_data['data']['products']['list'][ $product_id ] ) ? $upsell_data['data']['products']['list'][ $product_id ]['quantity'] : 0;
		}

		if ( empty( $qty ) ) {
			$qty = 1;
		}

		$added_product_hash = '';

		if ( ! in_array( $product_id, array_column( WC()->cart->get_cart(), 'product_id' ), true ) ) {
			$added_product_hash = WC()->cart->add_to_cart( $product_id, $qty );

			// Take care of cart and applied discounts.
			$this->apply_discounted_prices( WC()->cart, $checkout_id, 'upsell', $upsell_data );
			$this->make_changes_after_cart_item_edit( $checkout_id );
		}

		// Response.
		$funnel            = new Sellkit_Funnel( $upsell_id );
		$next_step         = $funnel->next_step_data;
		$next_step['type'] = ! empty( $next_step['type'] ) ? (array) $next_step['type'] : null;

		if ( 'upsell' === $funnel->current_step_data['type']['key'] && isset( WC()->cart->cart_contents[ $added_product_hash ] ) ) {
			$analytics_updater = new Data_Updater();
			$total             = empty( WC()->cart->cart_contents[ $added_product_hash ]['line_total'] ) ? 0 : WC()->cart->cart_contents[ $added_product_hash ]['line_total'];

			$analytics_updater->set_funnel_id( $funnel->funnel_id );
			$analytics_updater->add_new_upsell_revenue_log( $total );
		}

		$contact_data = [
			'key' => $funnel->current_step_data['type']['key'],
			'page_id' => $funnel->current_step_data['page_id'],
		];

		Base_Contacts::step_is_passed( $contact_data );

		// Step with empty data, will be redirected to thankyou page.
		if ( empty( $next_step ) || empty( $next_step['type'] ) ) {
			wp_send_json_success(
				[
					'next_id'   => $funnel->end_node_step_data['page_id'],
					'next_type' => $funnel->end_node_step_data['type']['key'],
					'upsell_prices' => wp_json_encode( self::$sellkit_upsell_products ),
				]
			);
		}

		if ( 'decision' === $next_step['type']['key'] ) {
			$this->take_care_of_decision_step( $next_step['page_id'] );
			return;
		}

		$response = [
			'next_id'   => $next_step['page_id'],
			'next_type' => $next_step['type']['key'],
			'upsell_prices' => wp_json_encode( self::$sellkit_upsell_products ),
		];

		wp_send_json_success( $response );
	}

	/**
	 * Perform upsell popup reject button.
	 *
	 * @since 2.3.0
	 */
	public function perform_upsell_reject_button() {
		$upsell_id = filter_input( INPUT_POST, 'upsell_id', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $upsell_id ) ) {
			wp_send_json_error( esc_html__( 'Empty Upsell ID.', 'sellkit' ) );
		}

		$funnel    = new Sellkit_Funnel( $upsell_id );
		$next_step = $funnel->next_no_step_data;

		if ( isset( $next_step['type'] ) && false !== $next_step['type'] ) {
			$next_step['type'] = (array) $next_step['type'];
		}

		// Step with empty data, will be redirected to thankyou page.
		if ( empty( $next_step ) || empty( $next_step['type'] ) ) {
			wp_send_json_success(
				[
					'next_id'   => $funnel->end_node_step_data['page_id'],
					'next_type' => $funnel->end_node_step_data['type']['key'],
				]
			);
		}

		if ( 'decision' === $next_step['type']['key'] ) {
			$this->take_care_of_decision_step( $next_step['page_id'] );
			return;
		}

		$response = [
			'next_id'   => $next_step['page_id'],
			'next_type' => $next_step['type']['key'],
		];

		wp_send_json_success( $response );
	}

	/**
	 * Checkout fields validation.
	 *
	 * @param int $shipping_fields shipping fields.
	 * @param int $billing_fields billing fields.
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function checkout_fields_validation( $shipping_fields, $billing_fields ) {
		$shipping_prefix = esc_html__( 'Shipping', 'sellkit' );

		foreach ( $shipping_fields as $data ) {
			if ( ! WC()->cart->needs_shipping() ) {
				continue;
			}

			$final_name = isset( $data['role'] ) ? $data['role'] : '';
			$value      = filter_input( INPUT_POST, $final_name, FILTER_DEFAULT );

			if ( $data['required'] && ( empty( $value ) ) ) {
				/* translators: %s field_name. */
				wc_add_notice( sprintf( esc_html__( '%s is a required field.', 'sellkit' ), '<strong>' . esc_html( $shipping_prefix ) . ' ' . esc_html( $data['label'] ) . '</strong>' ), 'error' );
			}
		}

		$billing_method = filter_input( INPUT_POST, 'billing-method', FILTER_DEFAULT );
		$billing_prefix = esc_html__( 'Billing', 'sellkit' );

		foreach ( $billing_fields as $data ) {
			if ( 'same' === $billing_method ) {
				continue;
			}

			$final_name = isset( $data['role'] ) ? $data['role'] : '';
			$value      = filter_input( INPUT_POST, $final_name, FILTER_DEFAULT );

			if ( $data['required'] && ( ! array_key_exists( $final_name, $_POST ) || empty( $value ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				wc_add_notice( sprintf(
					/* translators: %s field_name. */
					esc_html__( '%s is a required field.', 'sellkit' ),
					'<strong>' . esc_html( $billing_prefix ) . ' ' . esc_html( $data['label'] ) . '</strong>'
				), 'error' );
			}
		}

		// Validate main checkout email.
		$email = filter_input( INPUT_POST, 'billing_email', FILTER_SANITIZE_EMAIL );
		$valid = filter_var( $email, FILTER_VALIDATE_EMAIL );

		if ( ! $email ) {
			/* translators: %s field_name. */
			wc_add_notice( sprintf( esc_html__( '%s is a required field.', 'sellkit' ), '<strong>' . esc_html__( 'Email address', 'sellkit' ) . '</strong>' ), 'error' );
			return;
		}

		if ( ! $valid ) {
			/* translators: %s field_name. */
			wc_add_notice( sprintf( esc_html__( '%s is not a valid email.', 'sellkit' ), '<strong>' . esc_html__( 'Email address', 'sellkit' ) . '</strong>' ), 'error' );
		}
	}

	/**
	 * Display checkout order notes field.
	 *
	 * @since 2.3.0
	 */
	public function order_notes() {
		// Get woocommerce order notes field.
		$notes = WC()->checkout()->get_checkout_fields( 'order' );

		// print notes field.
		foreach ( $notes as $key => $field ) {
			?>
				<div class="sellkit-checkout-order-fields sellkit-widget-checkout-fields">
					<?php
						if ( 'order_comments' === $key ) {
							?>
								<div class="sellkit-checkout-order-notes">
									<div class="sellkit-order-note-field-wrapper">
										<input id="sellkit-add-notes-to-order-box" type="checkbox">
										<label for="sellkit-add-notes-to-order-box">
											<?php echo esc_html__( 'Add a note to your order', 'sellkit' ); ?>
										</label>
									</div>
									<?php
										woocommerce_form_field( $key, $field, WC()->checkout()->get_value( $key ) );
									?>
								</div>
							<?php
						}
					?>
				</div>
			<?php
		}
	}

	/**
	 * Trigger to activate order bumb or not for this checkout.
	 *
	 * @param array $data checkout form data.
	 * @since 2.3.0
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function order_bump( $data = [] ) {
		if ( 'woocommerce_checkout_init' === current_action() ) {
			$this->order_bump_frontend_manager();
		}
	}

	/**
	 * Order bump to manage frontend.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	private function order_bump_frontend_manager() {
		if ( $this->is_bumps_applied ) {
			return;
		}

		global $wp_query;
		$query = $wp_query->query_vars;

		if ( ! array_key_exists( 'bump_data', $query ) ) {
			return;
		}

		$bumps = $query['bump_data'];

		foreach ( $bumps as $bump ) {
			if ( ! array_key_exists( 'products', $bump['data'] ) ) {
				continue;
			}

			$design   = $bump['data']['design'];
			$products = $bump['data']['products'];

			$design['sellkit_funnels_bump_position'] = strpos( $design['sellkit_funnels_bump_position'], 'sellkit' ) === 0
			? 'sellkit-block' . substr( $design['sellkit_funnels_bump_position'], 7 )
			: $design['sellkit_funnels_bump_position'];

			add_action( $design['sellkit_funnels_bump_position'], function() use ( $design, $products ) {
				$this->bump_html( $design, $products );
			}, 5 );
		}

		$this->is_bumps_applied = true;
	}

	/**
	 * Order bump html.
	 *
	 * @param array $design selected option in checkout step.
	 * @param array $products products in checkout step.
	 * @since 2.3.0
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function bump_html( $design, $products ) {
		$list = empty( $products['list'] ) ? [] : $products['list'];
		$ids  = [];

		foreach ( $list as $id => $details ) {
			if ( ! is_array( $details ) ) {
				continue;
			}

			$ids[]    = $id;
			$qty      = $details['quantity'];
			$discount = $details['discount'];
			$type     = $details['discountType'];
		}

		$ids_string = implode( '|', $ids );
		$product    = wc_get_product( $ids_string );
		$qty        = ( empty( $qty ) ) ? 1 : $qty;

		if ( empty( $product ) || ! $product->get_id() || ! $product->is_in_stock() ) {
			return;
		}

		$unique_id = 'bump-title-' . $ids_string;

		$class = '';
		if (
			'sellkit-block-checkout-before-order-summary' === current_action() ||
			'sellkit-block-checkout-after-order-summary' === current_action()
		) {
			$class = 'sellkit-bump-review-order';
		}

		$checked = empty( WC()->cart->find_product_in_cart( WC()->cart->generate_cart_id( $product->get_id() ) ) ) ? false : true;
		?>
			<div class="sellkit-checkout-step-bump-wrapper <?php echo esc_attr( $class ); ?>" >
				<div class="sellkit-checkout-bump-order-header">
					<div class="sellkit-bump-order-left-header">
						<img src="<?php echo esc_attr( sellkit()->plugin_assets_url() . 'img/right-arrow.svg' ); ?>" >
						<input
							type="checkbox"
							value="<?php echo esc_attr( $ids_string ); ?>"
							class="sellkit-checkout-bump-order-products"
							data-qty="<?php echo esc_attr( $qty ); ?>"
							name="sellkit_bump_data_<?php echo esc_attr( $ids_string ); ?>"
							id="<?php echo esc_html( $unique_id ); ?>"
							<?php echo ( true === $checked ) ? 'checked="true"' : ''; ?>
						>
						<label
							for="<?php echo esc_attr( $unique_id ); ?>"
							class="sellkit-checkout-order-bump-title"
						>
							<?php echo esc_html( $design['sellkit_funnels_bump_checkbox_label'] ); ?>
						</label>
					</div>
					<div class="sellkit-bump-order-right-header">
						<span class="sellkit-checkout-order-bump-price">
							<?php
								$discounted_price = $this->calculate_discount( (int) $ids_string, $type, $discount );
								$sale_price       = $product->get_sale_price();
								$regular_price    = $product->get_regular_price();
								$main_price       = ( strpos( $type, 'sale' ) !== false ) ? $sale_price : $regular_price;

								if ( floatval( $main_price ) > floatval( $discounted_price ) ) {
									?>
										<del aria-hidden="true">
											<span class="woocommerce-Price-amount amount">
												<bdi>
													<span class="woocommerce-Price-currencySymbol">
														<?php echo wc_price( $main_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													</span>
												</bdi>
											</span>
										</del>
										<bdi class="bump-price-bolded"><?php echo wc_price( $discounted_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></bdi>
									<?php
								} else {
									?>
										<bdi><?php echo wc_price( $main_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></bdi>
									<?php
								}
							?>
						</span>
					</div>
				</div>
				<div class="sellkit-checkout-bump-order-body" >
					<div class="sellkit-bump-order-left-body">
						<?php $image = wp_get_attachment_image_src( $product->get_image_id() ); ?>
						<?php if ( 'true' === $design['sellkit_funnels_bump_product_image'] && ! empty( $image[0] ) ) : ?>
						<img src="<?php echo esc_attr( $image[0] ); ?>" >
						<?php endif; ?>
					</div>
					<div class="sellkit-bump-order-right-body">
						<div class="sellkit-bump-order-description">
							<?php echo wp_kses_post( $design['sellkit_content'] ); ?>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Hide checkout order product quantity input and show raw quantity.
	 *
	 * @param string $input_html default quantity input.
	 * @param int    $quantity product quantity in cart.
	 * @since 2.3.0
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function checkout_order_hidden_quantity( $input_html, $quantity ) {
		echo sprintf(
			/** Translators: %s: product quantity */
			'<strong class="product-quantity">%s</strong>',
			esc_html( $quantity )
		);
	}

	/**
	 * Calculate item discount.
	 *
	 * @param int    $id product id.
	 * @param string $type discount type.
	 * @param int    $value discount value.
	 * @return int|boolean
	 * @since 2.3.0
	 */
	public function calculate_discount( $id, $type, $value ) {
		if ( false === $type && false === $value ) {
			return false;
		}

		$product = wc_get_product( $id );
		$regular = $product->get_regular_price();
		$sale    = $product->get_sale_price();
		$price   = false;

		if ( strpos( $type, 'sale' ) !== false ) {
			$discount = ( 'fixed-sale' === $type ) ? floatval( $value ) : ( floatval( $sale ) * floatval( $value ) ) / 100;
			$discount = floatval( $sale ) - $discount;

			return ( $discount > 0 ) ? $discount : 0;
		}

		if ( strpos( $type, 'sale' ) === false ) {
			$discount = ( 'fixed' === $type ) ? floatval( $value ) : ( floatval( $regular ) * floatval( $value ) ) / 100;
			$discount = floatval( $regular ) - $discount;

			return ( $discount > 0 ) ? $discount : 0;
		}

		return $price;
	}

	/**
	 * Add an extra hidden field to checkout form if funnel includes upsell step and also add sellkit current page id.
	 *
	 * @since 2.3.0
	 */
	public function add_trigger_field_for_upsell_steps() {
		echo sprintf( '<input type="hidden" id="sellkit_current_page_id" name="sellkit_current_page_id" value="%1$s">',
			esc_attr( get_the_ID() )
		);

		$include_upsell = $this->is_funnel_includes_upsell();

		if ( false === $include_upsell ) {
			return;
		}

		echo '<input type="hidden" id="sellkit_funnel_has_upsell" value="upsell" >';
		echo '<input type="hidden" id="sellkit_funnel_popup_step_id" value="0" >';
		echo '<input type="hidden" id="sellkit_product_prices" autocomplete="off" name="sellkit_product_prices" value="0" >';
	}

	/**
	 * Checks if funnel includes upsell step.
	 *
	 * @param string $goal customize return value.
	 * @param int    $ajax_id upsell id through ajax.
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function is_funnel_includes_upsell( $goal = 'is_upsell', $ajax_id = null ) {
		$id = get_queried_object_id();

		if ( wp_doing_ajax() ) {
			$id = $ajax_id;
		}

		$step_data          = get_post_meta( $id, 'step_data', true );
		$include_upsell     = false;
		$upsell_steps       = [];
		$upsell_ids         = [];
		$global_checkout_id = get_option( Global_Checkout::SELLKIT_GLOBAL_CHECKOUT_OPTION, 0 );

		// We are in default WooCommerce checkout page.
		if ( empty( $step_data ) && $global_checkout_id > 0 && 'publish' === get_post_status( $global_checkout_id ) ) {
			$steps       = get_post_meta( $global_checkout_id, 'nodes', true );
			$checkout_id = 0;

			foreach ( $steps as $step ) {
				$step['type'] = (array) $step['type'];

				if ( 'checkout' === $step['type']['key'] ) {
					$checkout_id = $step['page_id'];
				}
			}

			$step_data = get_post_meta( $checkout_id, 'step_data', true );
		}

		if ( empty( $step_data ) ) {
			return $include_upsell;
		}

		$funnel_id   = intval( $step_data['funnel_id'] );
		$funnel_data = get_post_meta( $funnel_id, 'nodes', true );

		if ( empty( $funnel_data ) ) {
			return $include_upsell;
		}

		$popups = [ 'downsell', 'upsell' ];

		foreach ( $funnel_data as $step ) {
			if ( isset( $step['data'] ) && isset( $step['data']['products'] ) && isset( $step['data']['products']['list'] ) ) {
				$product_id = array_keys( $step['data']['products']['list'] )[0] ?? 0;

				if ( $product_id ) {
					$product = wc_get_product( $product_id );

					if ( $product && ! $product->is_in_stock() ) {
						return $include_upsell;
					}
				}
			}

			$step['type'] = (array) $step['type'];

			if ( in_array( $step['type']['key'], $popups, true ) ) {
				$include_upsell = true;
				$upsell_steps[] = $step;
				$upsell_ids[]   = $step['page_id'];
			}
		}

		if ( 'get_ids' === $goal ) {
			return $upsell_ids;
		}

		// Send data to the filter that keeps steps data to prepare popup.
		add_filter( 'sellkit_upsell_steps_popup_information', function( $default ) use ( $upsell_steps ) {
			if ( ! empty( $upsell_steps ) ) {
				return $upsell_steps;
			}

			return $default;
		} );

		return $include_upsell;
	}

	/**
	 * Display sellkit upsell steps as popup.
	 *
	 * @since 2.3.0
	 */
	public function sellkit_funnel_display_upsell_as_popup() {
		$upsell_data = apply_filters( 'sellkit_upsell_steps_popup_information', [] );
		$i           = 1;

		if ( empty( $upsell_data ) ) {
			return;
		}

		foreach ( $upsell_data as $data ) {
			$id    = 'sellkit_funnel_upsell_popup_' . $i;
			$class = 'sellkit_funnel_upsell_popup sellkit_funnel_upsell_popup_' . $data['page_id'];

			// Check if the page was built with Elementor and get the content.
			if ( 'elementor' === sellkit()->page_builder() && class_exists( 'Elementor\Plugin' ) && \Elementor\Plugin::$instance->db->is_built_with_elementor( $data['page_id'] ) ) {
				$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $data['page_id'] );
			}

			// Render block editor content.
			if ( 'gutenberg' === sellkit()->page_builder() ) {
				$content = '';

				if ( ! $this->is_accept_reject_button_registered ) {
					$this->load_accept_reject_button_frontend();
				}

				$page_content = get_the_content( null, true, $data['page_id'] );
				$blocks       = parse_blocks( $page_content );

				foreach ( $blocks as $block ) {
					$content .= $this->render_block_html_or_styles( render_block( $block ), $block );
				}
			}

			if ( ! empty( $content ) ) {
				?>
				<div class="<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $id ); ?>">
					<?php echo $this->render_block_html_or_styles( render_block( $block ), $block, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo wp_kses_post( $content ); ?>
					<input type="hidden" class="identify" value="<?php echo esc_attr( $data['page_id'] ); ?>" >
				</div>
				<?php
			}

			$i++;
		}
	}

	/**
	 * Renders the HTML output for a given block with added styles based on the block's attributes and support.
	 *
	 * This function applies specific layout styles to a block based on its attributes such as 'layout' and 'blockGap'.
	 * It adjusts the HTML output by appending a unique class for the block and wraps the modified content with necessary styles.
	 * The function includes safeguards to skip serialization of the block's gap properties if unsupported and ensures that
	 * CSS values are safe to use.
	 *
	 * @param string $block_content The HTML content of the block.
	 * @param array  $block The block's attributes and information.
	 * @param array  $style_only if true function returns only style.
	 * @return string The HTML content of the block wrapped with a unique class and necessary styles.
	 *
	 * @since 2.3.0
	 */
	public function render_block_html_or_styles( $block_content, $block, $style_only = false ) {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
		if ( empty( $block_type ) ) {
			return;
		}

		$block_gap             = wp_get_global_settings( [ 'spacing', 'blockGap' ] );
		$has_block_gap_support = isset( $block_gap ) ? null !== $block_gap : false;
		$default_block_layout  = _wp_array_get( $block_type->supports, [ '__experimentalLayout', 'default' ], [] );
		$used_layout           = isset( $block['attrs']['layout'] ) ? $block['attrs']['layout'] : $default_block_layout;

		$class_name = wp_unique_id( 'wp-container-' );
		$gap_value  = _wp_array_get( $block, [ 'attrs', 'style', 'spacing', 'blockGap' ] );

		// Skip if gap value contains unsupported characters. Regex for CSS value borrowed from `safecss_filter_attr`, and used here because we only want to match against the value, not the CSS attribute.
		if ( is_array( $gap_value ) ) {
			foreach ( $gap_value as $key => $value ) {
				$gap_value[ $key ] = $value && preg_match( '%[\\\(&=}]|/\*%', $value ) ? null : $value;
			}
		} else {
			$gap_value = $gap_value && preg_match( '%[\\\(&=}]|/\*%', $gap_value ) ? null : $gap_value;
		}

		$fallback_gap_value = _wp_array_get( $block_type->supports, [ 'spacing', 'blockGap', '__experimentalDefault' ], '0.5em' );

		// If a block's block.json skips serialization for spacing or spacing.blockGap, don't apply the user-defined value to the styles.
		$should_skip_gap_serialization = wp_should_skip_block_supports_serialization( $block_type, 'spacing', 'blockGap' );
		$style                         = wp_get_layout_style( ".$class_name", $used_layout, $has_block_gap_support, $gap_value, $should_skip_gap_serialization, $fallback_gap_value );

		$content = preg_replace(
			'/' . preg_quote( 'class="', '/' ) . '/',
			'class="' . esc_attr( $class_name ) . ' ',
			$block_content,
			1
		);

		if ( $style_only ) {
			return '<style>' . $style . '</style>';
		}

		return $content;
	}

	/**
	 * Insert bundled product.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	public function bundled_products() {
		global $wp_query;
		$query  = $wp_query->query_vars;
		$option = 'default';

		if ( ! array_key_exists( 'funnel_product_settings', $query ) ) {
			return;
		}

		if ( ! empty( $query['funnel_product_settings'] ) ) {
			$option = $query['funnel_product_settings'];
		}

		if ( 'default' === $option ) {
			return;
		}

		add_action( 'sellkit_block_checkout_required_hidden_fields', function() use ( $option ) {
			?>
				<input type="hidden" name="sellkit-bundle-products" value="<?php echo esc_attr( $option ); ?>" >
			<?php
		}, 10 );

		$this->bundle_product_action( $option );
	}

	/**
	 * Display bundle products.
	 *
	 * @param string $option bundle products type.
	 * @since 2.3.0
	 * return void
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function bundle_product_action( $option ) {
		$fields_type = 'radio';
		$products    = WC()->cart->get_cart();
		$default     = null;
		$default_q   = null;
		$readonly    = 'readonly';

		if ( 'allow-buyers' === $option ) {
			$fields_type = 'checkbox';
			$readonly    = '';
		}

		ob_start();
		?>
			<section class="sellkit-checkout-bundled-products">
				<div class="sellkit-checkout-bundled-inner-wrap">
					<div class="sellkit-checkout-bundled-header heading"><?php echo esc_html__( 'Your Products', 'sellkit' ); ?></div>
					<table class="sellkit-checkout-bundled-products-table">
						<thead>
							<tr class="sellkit-checkout-bundled-products-head-row">
								<th class="sellkit-head-row-title"><?php echo esc_html__( 'Product', 'sellkit' ); ?></th>
								<th class="sellkit-head-row-quantity"><?php echo esc_html__( 'Quantity', 'sellkit' ); ?></th>
								<th class="sellkit-head-row-price"><?php echo esc_html__( 'Price', 'sellkit' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $products as $cart_item_key => $cart_item ) : ?>
								<?php
									if ( in_array( $cart_item_key, $this->in_cart, true ) ) {
										continue;
									}

									$_product  = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
									$default   = $_product->get_id();
									$default_q = $cart_item['quantity'];
									$unique_id = 'bundle-unique-id-' . $default;
									$checked   = '';

									if ( 'checkbox' === $fields_type ) {
										$checked = 'checked';
									}

									if ( 'radio' === $fields_type && array_key_last( $products ) === $cart_item_key ) {
										$checked = 'checked';
									}
								?>
								<tr class="sellkit-checkout-bundled-products-item">
									<td class="sellkit-checkout-bundled-title">
										<input
											type="<?php echo esc_attr( $fields_type ); ?>"
											value="<?php echo esc_attr( $_product->get_id() ); ?>"
											name="sellkit-checkout-bundle-item"
											class="sellkit-checkout-bundle-item"
											id="<?php echo esc_attr( $unique_id ); ?>"
											<?php echo esc_html( $checked ); ?>
										>
										<label for="<?php echo esc_attr( $unique_id ); ?>">
											<?php echo esc_html( $_product->get_name() ); ?>
										</label>
									</td>
									<td class="sellkit-checkout-bundled-quantity">
										<input type="number" <?php echo esc_attr( $readonly ); ?> min="1" value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" data-id="<?php echo esc_attr( $cart_item_key ); ?>" class="sellkit-checkout-single-bundle-item-quantity" >
									</td>
									<td class="sellkit-checkout-bundled-price">
										<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</td>
								<tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
		<?php
		$bundle_html = ob_get_clean();

		add_action( 'sellkit-bundled-products-position', function() use ( $bundle_html ) {
			echo $bundle_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} );

		if ( wp_doing_ajax() ) {
			return;
		}

		if ( 'radio' === $fields_type && null !== $default ) {
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( $default, $default_q );
		}

		$this->make_changes_after_cart_item_edit( get_the_id() );
	}

	/**
	 * Rename Shipping text
	 *
	 * @param string $name title of shipping methods.
	 * @return string
	 * @since 2.3.0
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function rename_shipping_text( $name ) {
		$post_id    = get_the_ID();
		$attributes = $this->get_inner_block_attributes( 'checkout', 'checkout-cart-shipping', $post_id );

		if ( ! isset( $attributes['shippingMethodsHeadingText'] ) ) {
			return $name;
		}

		return '<div id="shipping_header_title" class="header heading">' . esc_html( $attributes['shippingMethodsHeadingText'] ) . '</div>';
	}

	/**
	 * Get inner block attributes.
	 *
	 * @param string $block_name Block name.
	 * @param string $inner_block_name InnerBlock name.
	 * @param string $post_id Post ID.
	 * @since 2.3.0
	 * return void
	 */
	public function get_inner_block_attributes( $block_name, $inner_block_name, $post_id ) {
		if ( empty( $post_id ) ) {
			return;
		}

		$post_content = get_post_field( 'post_content', $post_id );
		$blocks       = parse_blocks( $post_content );

		foreach ( $blocks as $block ) {
			if ( 'sellkit-blocks/' . $block_name === $block['blockName'] ) {
				foreach ( $block['innerBlocks'] as $inner_block ) {
					if ( 'sellkit-inner-blocks/' . $inner_block_name === $inner_block['blockName'] ) {
						return $inner_block['attrs'];
					}
				}
			}
		}
	}

	/**
	 * Assign user defined values to each field.
	 *
	 * @param array  $default_fields : default woocommerce fields.
	 * @param array  $block_fields : fields get added by user from block option.
	 * @param string $type : billing / shipping.
	 * @return array
	 * @since 2.3.0
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public static function assign_settings_per_field( $default_fields, $block_fields, $type ) {
		if ( empty( $block_fields ) ) {
			return $default_fields;
		}

		// Unset unnecessary fields.
		$valid_roles = array_map( function( $field ) {
			return $field['role'];
		}, $block_fields );

		foreach ( $default_fields as $key => $value ) {
			if ( ! in_array( $key, $valid_roles, true ) ) {
				unset( $default_fields[ $key ] );
			}
		}

		// Assign user properties.
		foreach ( $block_fields as $key => $field ) {
			$default_fields[ $field['role'] ]['label']    = $field['label'] . ( ! $field['required'] ? ' ' . esc_html__( '(optional)', 'sellkit' ) : '' );
			$default_fields[ $field['role'] ]['class']    = explode( ' ', $field['customClass'] );
			$default_fields[ $field['role'] ]['class'][]  = $field['width'];
			$default_fields[ $field['role'] ]['class'][]  = 'sellkit-widget-checkout-fields';
			$default_fields[ $field['role'] ]['required'] = ( 'yes' === $field['required'] ) ? true : false;
			$default_fields[ $field['role'] ]['priority'] = ( $key + 1 ) * 10;
			$default_fields[ $field['role'] ]['local']    = true;

			if ( $field['role'] === $type . '_phone' ) {
				$default_fields[ $field['role'] ]['type'] = 'tel';
			}
		}

		foreach ( $default_fields as $key => $details ) {
			if ( ! array_key_exists( 'local', $details ) || false === $details['local'] ) {

				$default_fields[ $key ]['priority'] = 500;
			}
		}

		return $default_fields;
	}

	/**
	 * Register fields.
	 *
	 * @since 2.3.0
	 */
	public static function register_fields() {
		sellkit()->load_files( [
			'block-editor/blocks/checkout/form-fields/base',
			'block-editor/blocks/checkout/form-fields/select',
			'block-editor/blocks/checkout/form-fields/text',
			'block-editor/blocks/checkout/form-fields/tel',
			'block-editor/blocks/checkout/form-fields/hidden',
		] );
	}

	/**
	 * Load accept-reject-button block on frontend.
	 *
	 * @since 2.3.0
	 */
	public function load_accept_reject_button_frontend() {
		global $post;

		if ( empty( $post->post_content ) ) {
			return;
		}

		$this->post_id = $post->ID;

		$block = 'blocks/accept-reject-button';

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
		$new_class->register_block_meta();

		$this->is_accept_reject_button_registered = true;
	}
}
