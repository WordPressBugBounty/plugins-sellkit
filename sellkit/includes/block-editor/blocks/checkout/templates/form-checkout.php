<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', esc_html__( 'You must be logged in to checkout.', 'sellkit' ) ) );
	return;
}

$extra_class = '';

if ( ! WC()->cart->needs_shipping() ) {
	$extra_class = 'sellkit-checkout-virtual-session';
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout <?php echo esc_attr( $extra_class ); ?>" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
	<section class="sellkit-one-page-checkout-login">
		<?php wc_get_template( 'checkout/form-login.php' ); ?>

		<?php if ( $checkout->get_checkout_fields() ) : ?>

			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div id="customer_details">
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>
		<?php endif; ?>
	</section>

	<section class="sellkit-one-page-shipping-methods">
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

				<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

		<?php endif; ?>
	</section>

	<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php do_action( 'woocommerce_checkout_billing' ); ?>
	<?php do_action( 'sellkit_block_checkout_after_term_and_condition' ); ?>
	<?php
		$order_button_text = apply_filters( 'sellkit_block_checkout_place_order_btn_text', esc_html__( 'Place Order', 'sellkit' ) );
		require __DIR__ . '/payment-continue.php';
	?>

	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'sellkit-block-checkout-before-order-summary' ); ?>

		<?php do_action( 'sellkit-bundled-products-position' ); ?>

		<div id="sellkit-checkout-widget-order-review-wrap" >
			<div class="sellkit-checkout-order-review-heading header heading">
				<?php esc_html_e( 'Your order', 'sellkit' ); ?>
		</div>
			<?php do_action( 'woocommerce_checkout_order_review' ); ?>
		</div>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

	<?php do_action( 'sellkit_block_checkout_required_hidden_fields' ); ?>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
