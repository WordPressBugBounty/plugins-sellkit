<?php

namespace Sellkit\Dynamic_Keywords\Contact_Segmentation;

/**
 * Class Visitor Country.
 *
 * @package Sellkit\Dynamic_Keywords\Contact_Segmentation
 * @since 1.1.0
 */
class Visitor_Country extends Contact_Segmentation_Base {

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
		return '_visitor_country';
	}

	/**
	 * Get class title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Visitor Country', 'sellkit' );
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

		if ( ! isset( self::$contact_segmentation['visitor_country'] ) ) {
			return $this->shortcode_content( $atts );
		}

		return ucwords( self::$contact_segmentation['visitor_country'] );
	}
}
