<?php

namespace Sellkit\Dynamic_Keywords\Contact_Segmentation;

/**
 * Class UTM Term.
 *
 * @package Sellkit\Dynamic_Keywords\Contact_Segmentation
 * @since 1.1.0
 */
class Utm_Term extends Contact_Segmentation_Base {

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
		return '_utm_term';
	}

	/**
	 * Get class title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'UTM Term', 'sellkit' );
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

		if ( ! isset( self::$contact_segmentation['utm_term'] ) ) {
			return $this->shortcode_content( $atts );
		}

		return self::$contact_segmentation['utm_term'];
	}
}
