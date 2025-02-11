<?php

namespace Sellkit\Contact_Segmentation\Operators;

use Sellkit\Contact_Segmentation\Operator_Base;

defined( 'ABSPATH' ) || die();

/**
 * Class Contains
 *
 * @package Sellkit\Contact_Segmentation\Conditions
 * @since 1.1.0
 */
class Contains extends Operator_Base {

	/**
	 * Condition name.
	 *
	 * @since 1.1.0
	 */
	public function get_name() {
		return 'contains';
	}

	/**
	 * Condition title.
	 *
	 * @since 1.1.0
	 */
	public function get_title() {
		return __( 'contains', 'sellkit' );
	}

	/**
	 * Conditions.
	 *
	 * @since 1.1.0
	 */
	public function get_conditions() {
		return [
			'utm-source',
			'utm-medium',
			'utm-campaign',
			'utm-content',
			'utm-term',
			'referral-source-url',
			'billing-city',
			'billing-city-checkout',
			'shipping-city',
			'shipping-city-checkout',
			'visitor-city',
			'url-query-string',
		];
	}

	/**
	 * Condition title.
	 *
	 * @since 1.1.0
	 * @param mixed $value            mixed The value of current value.
	 * @param mixed $condition_value  The value of condition input.
	 */
	public function is_valid( $value, $condition_value ) {
		if ( false !== strpos( $value, $condition_value ) ) {
			return true;
		}

		return false;
	}
}
