<?php

namespace Sellkit\Elementor\Modules\Checkout\Integrations;

defined( 'ABSPATH' ) || die();

use Sellkit\Elementor\Modules\Checkout\Integrations\Integration;

/**
 * Integration class to integrate stripe woocommerce official with sellkit checkout widget.
 *
 * @since 1.1.0
 */
class Stripe_Woocommerce_Official extends Integration {
	/**
	 * Original plugin class holder.
	 *
	 * @since 1.1.0
	 * @var object
	 */
	private $parent;

	/**
	 * Stripe express integration type.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	private $integration_type = '';

	/**
	 * Check requirement to enable gateway in sellkit checkout widget.
	 *
	 * @return bool
	 * @since 1.1.0
	 */
	protected function requirements() {
		// Plugin not installed.
		if ( ! defined( 'WC_STRIPE_PLUGIN_PATH' ) ) {
			return false;
		}

		if ( class_exists( 'WC_Stripe_Express_Checkout_Element' ) ) {
			$this->parent           = \WC_Stripe_Express_Checkout_Element::instance();
			$this->integration_type = 'express_checkout_element';

			return true;
		}

		if ( class_exists( 'WC_Stripe_Payment_Request' ) ) {
			$this->parent           = \WC_Stripe_Payment_Request::instance();
			$this->integration_type = 'payment_request';

			return true;
		}

		return false;
	}

	/**
	 * Content of express checkout methods.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function content() {
		?>
			<div class="sellkit-stripe-woocommerce-official-integration" >
				<?php
				if ( method_exists( $this->parent, 'display_express_checkout_button_html' ) ) {
					ob_start();
					$this->parent->display_express_checkout_button_html();
					$button_html = ob_get_clean();
					$button_html = preg_replace( '/<p id="wc-stripe-express-checkout-button-separator"[^>]*>.*?<\/p>/is', '', $button_html );
					echo wp_kses_post( $button_html );
				} elseif ( method_exists( $this->parent, 'display_payment_request_button_html' ) ) {
					$this->parent->display_payment_request_button_html();
				}
				?>
			</div>
		<?php
	}

	/**
	 * Hooks to integrate current gateway with sellkit checkout widget.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function hooks() {
		$callbacks = [];

		if ( 'express_checkout_element' === $this->integration_type ) {
			$callbacks = [
				[ $this->parent, 'display_express_checkout_button_html' ],
				[ $this->parent, 'display_express_checkout_button_separator_html' ],
				[ 'WC_Stripe_Express_Checkout_Element', 'display_express_checkout_button_html' ],
				[ 'WC_Stripe_Express_Checkout_Element', 'display_express_checkout_button_separator_html' ],
			];
		}

		if ( 'payment_request' === $this->integration_type ) {
			$callbacks = [
				[ $this->parent, 'display_payment_request_button_html' ],
				[ $this->parent, 'display_payment_request_button_separator_html' ],
				[ 'WC_Stripe_Payment_Request', 'display_payment_request_button_html' ],
				[ 'WC_Stripe_Payment_Request', 'display_payment_request_button_separator_html' ],
			];
		}

		foreach ( $callbacks as $callback ) {
			remove_action( 'woocommerce_checkout_before_customer_details', $callback, 1 );
			remove_action( 'woocommerce_checkout_before_customer_details', $callback, 2 );
		}
	}
}
