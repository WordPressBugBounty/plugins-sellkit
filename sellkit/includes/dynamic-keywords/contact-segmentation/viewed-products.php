<?php

namespace Sellkit\Dynamic_Keywords\Contact_Segmentation;

/**
 * Class Viewed Products.
 *
 * @package Sellkit\Dynamic_Keywords\Contact_Segmentation
 * @since 1.1.0
 */
class Viewed_Products extends Contact_Segmentation_Base {

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get class id.
	 *
	 * @return string
	 */
	public function get_id() {
		return '_viewed_products';
	}

	/**
	 * Get class title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Viewed Products', 'sellkit' );
	}

	/**
	 * Render content.
	 *
	 * @param array $atts array of shortcode arguments.
	 * @return string
	 */
	public function render_content( $atts ) {
		if ( empty( self::$contact_segmentation ) ) {
			$this->get_data();
		}

		if ( ! isset( self::$contact_segmentation['viewed_product'] ) ) {
			return $this->shortcode_content( $atts );
		}

		$products = self::$contact_segmentation['viewed_product'];

		if ( empty( $products ) || ! is_array( $products ) ) {
			return $this->shortcode_content( $atts );
		}

		$result = [];

		foreach ( $products as $product ) {
			$product = absint( $product );

			// Values may come from a visitor cookie: only show published, viewable products.
			if ( empty( $product ) || 'product' !== get_post_type( $product ) || ! sellkit_is_post_publicly_accessible( $product ) ) {
				continue;
			}

			$result[] = get_the_title( $product );
		}

		if ( empty( $result ) ) {
			return $this->shortcode_content( $atts );
		}

		$result = $this->get_result( $result );

		return implode( ', ', $result );
	}
}
