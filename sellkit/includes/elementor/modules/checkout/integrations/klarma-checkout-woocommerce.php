<?php

namespace Sellkit\Elementor\Modules\Checkout\Integrations;

defined( 'ABSPATH' ) || die();

use Sellkit\Elementor\Modules\Checkout\Integrations\Integration;

/**
 * Integration class to integrate klarma checkout for woocommerce with sellkit checkout widget.
 *
 * @since 1.1.0
 */
class Klarma_Checkout_Woocommerce extends Integration {
	/**
	 * Klarma payment gateway id.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	private $payment_id = 'kco';

	/**
	 * Check requirement to enable gateway in sellkit checkout widget.
	 *
	 * @return bool
	 * @since 1.1.0
	 */
	protected function requirements() {
		// Plugin not installed.
		if ( ! defined( 'KCO_WC_PLUGIN_PATH' ) ) {
			return false;
		}

		// Gateway inactive.
		if ( ! array_key_exists( $this->payment_id, $this->gateways ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Content of express checkout methods.
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function content() {
		?>
			<div id="sellkit-klarna-pay-button" class="sellkit-klarna-pay-button">
				<span>
					<svg version="1.1" viewBox="0 0 45 25" height="35" width="60" xmlns="http://www.w3.org/2000/svg">
						<g fill="none" fill-rule="evenodd">
						<g transform="translate(-310 -37)" fill-rule="nonzero">
						<g transform="translate(310 37)">
						<rect x="5.6843e-14" width="45" height="25" rx="4.321" fill="#FFB3C7"/>
						<g transform="translate(4.4136 8.4)" fill="#0A0B09">
						<path d="m36.38 6.2463c-0.58875 0-1.066 0.48158-1.066 1.0757 0 0.594 0.47725 1.0757 1.066 1.0757 0.58874 0 1.0661-0.48167 1.0661-1.0757 0-0.59416-0.47734-1.0757-1.0661-1.0757zm-3.5073-0.83166c0-0.81338-0.68897-1.4726-1.5389-1.4726s-1.539 0.65925-1.539 1.4726c0 0.81339 0.68898 1.4728 1.539 1.4728s1.5389-0.65941 1.5389-1.4728zm0.0057148-2.8622h1.6984v5.7242h-1.6984v-0.36584c-0.47982 0.3302-1.059 0.52431-1.6837 0.52431-1.6531 0-2.9933-1.3523-2.9933-3.0205s1.3402-3.0204 2.9933-3.0204c0.6247 0 1.2039 0.1941 1.6837 0.5244v-0.36619zm-13.592 0.74562v-0.74554h-1.7389v5.7241h1.7428v-2.6725c0-0.90167 0.96849-1.3863 1.6405-1.3863 0.0068818 0 0.013306 6.6771e-4 0.020188 7.527e-4v-1.6656c-0.68973 0-1.3241 0.298-1.6646 0.7452zm-4.3316 2.1166c0-0.81338-0.68905-1.4726-1.539-1.4726-0.84991 0-1.539 0.65925-1.539 1.4726 0 0.81339 0.68905 1.4728 1.539 1.4728 0.84998 0 1.539-0.65941 1.539-1.4728zm0.0056186-2.8622h1.6985v5.7242h-1.6985v-0.36584c-0.47982 0.3302-1.059 0.52431-1.6836 0.52431-1.6532 0-2.9934-1.3523-2.9934-3.0205s1.3402-3.0204 2.9934-3.0204c0.62464 0 1.2038 0.1941 1.6836 0.5244v-0.36619zm10.223-0.15396c-0.67846 0-1.3206 0.21255-1.7499 0.79895v-0.64465h-1.6911v5.7239h1.7119v-3.0081c0-0.87046 0.57847-1.2967 1.275-1.2967 0.74646 0 1.1756 0.44996 1.1756 1.2849v3.0199h1.6964v-3.6401c0-1.3321-1.0496-2.238-2.4179-2.238zm-17.374 5.8782h1.7777v-8.2751h-1.7777v8.2751zm-7.8091 0.0022581h1.8824v-8.2789h-1.8824v8.2789zm6.584-8.2789c0 1.7923-0.69219 3.4596-1.9256 4.6989l2.602 3.5803h-2.325l-2.8278-3.891 0.72981-0.55152c1.2103-0.91484 1.9045-2.3132 1.9045-3.8367h1.8421z"/>
						</g>
						</g>
						</g>
						</g>
					</svg>
				</span>
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

	}
}
