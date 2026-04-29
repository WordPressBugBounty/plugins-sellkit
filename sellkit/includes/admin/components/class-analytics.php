<?php

namespace Sellkit\Admin\Components;

use Sellkit\Admin\Components\Analytics\Discount\summary;

defined( 'ABSPATH' ) || die();

/**
 * Components class.
 *
 * @since 1.1.0
 */
class Analytics {

	/**
	 * Class instance.
	 *
	 * @since 1.1.0
	 * @var Analytics
	 */
	private static $instance = null;

	/**
	 * Class instance.
	 *
	 * @since 1.1.0
	 * @var $date_range
	 */
	public static $date_range;

	/**
	 * Get a class instance.
	 *
	 * @since 1.1.0
	 *
	 * @return Analytics Class instance.
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
		self::$date_range = 7;

		add_action( 'wp_ajax_sellkit_get_chart_data', [ $this, 'get_chart_data' ] );
		add_action( 'wp_ajax_sellkit_get_summary_chart_data', [ $this, 'get_summary' ] );
		add_action( 'wp_ajax_sellkit_get_dashboard_stats', [ $this, 'get_dashboard_stats' ] );
	}

	/**
	 * Gets analytics data.
	 *
	 * @since 1.1.0
	 */
	public function get_chart_data() {
		check_ajax_referer( 'sellkit', 'nonce' );

		$feature    = sellkit_htmlspecialchars( INPUT_GET, 'feature' );
		$class      = sellkit_htmlspecialchars( INPUT_GET, 'type' );
		$date_range = sellkit_htmlspecialchars( INPUT_GET, 'date_range' );
		$target_id  = sellkit_htmlspecialchars( INPUT_GET, 'target_id' );

		self::$date_range = $this->resolve_date_range( $date_range );

		$file_name = str_replace( '_', '-', $class );

		sellkit()->load_files( [
			'admin/components/analytics/class',
			"admin/components/analytics/{$feature}/base",
			"admin/components/analytics/{$feature}/{$file_name}",
		] );

		$feature = ucfirst( $feature );
		$class   = __NAMESPACE__ . "\Analytics\\$feature\\" . $class;

		$analytics = new $class( $target_id );

		wp_send_json_success( $analytics->get_data() );
	}

	/**
	 * Gets summary data.
	 *
	 * @since 1.1.0
	 */
	public function get_summary() {
		check_ajax_referer( 'sellkit', 'nonce' );

		$feature    = sellkit_htmlspecialchars( INPUT_GET, 'feature' );
		$date_range = sellkit_htmlspecialchars( INPUT_GET, 'date_range' );

		self::$date_range = $this->resolve_date_range( $date_range );

		sellkit()->load_files( [ "admin/components/analytics/{$feature}/summary" ] );

		$class = __NAMESPACE__ . "\Analytics\\$feature\\summary";

		wp_send_json_success( $class::get_instance()->data );
	}

	/**
	 * Post counts for the dashboard overview (funnels, steps, Pro post types).
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function get_dashboard_stats() {
		check_ajax_referer( 'sellkit', 'nonce' );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Forbidden.', 'sellkit' ) ], 403 );
		}

		$data = [
			'funnels' => $this->get_post_type_counts( 'sellkit-funnels' ),
			'steps'   => $this->get_post_type_counts( 'sellkit_step' ),
			'coupons' => $this->get_post_type_counts( 'sellkit-coupon' ),
			'discounts' => $this->get_post_type_counts( 'sellkit-discount' ),
			'notices' => $this->get_post_type_counts( 'sellkit-alert' ),
		];

		wp_send_json_success( $data );
	}

	/**
	 * Total and published counts for a post type.
	 *
	 * @since 2.5.0
	 * @param string $post_type Post type name.
	 * @return array{total:int,active:int,draft:int}
	 */
	private function get_post_type_counts( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return [
				'total'  => 0,
				'active' => 0,
				'draft'  => 0,
			];
		}

		$counts = wp_count_posts( $post_type );

		if ( ! $counts ) {
			return [
				'total'  => 0,
				'active' => 0,
				'draft'  => 0,
			];
		}

		$total = 0;

		foreach ( (array) $counts as $num ) {
			$total += (int) $num;
		}

		return [
			'total'  => $total,
			'active' => isset( $counts->publish ) ? (int) $counts->publish : 0,
			'draft'  => isset( $counts->draft ) ? (int) $counts->draft : 0,
		];
	}

	/**
	 * Resolve a dashboard date range key to a day count.
	 *
	 * @since 2.5.0
	 * @param string $date_range Preset key.
	 * @return int
	 */
	private function resolve_date_range( $date_range ) {
		$today = new \DateTimeImmutable( 'now', wp_timezone() );

		switch ( $date_range ) {
			case 'today':
				return 0;

			case 'this-week':
				return (int) $today->format( 'w' );

			case 'this-month':
				return (int) $today->format( 'j' ) - 1;

			case 'this-quarter':
				$month               = (int) $today->format( 'n' );
				$quarter_start_month = (int) ( floor( ( $month - 1 ) / 3 ) * 3 ) + 1;
				$quarter_start       = $today->setDate( (int) $today->format( 'Y' ), $quarter_start_month, 1 );

				return (int) $quarter_start->diff( $today )->days;

			case 'ninety-days':
				return 90;

			case 'this-year':
				return (int) $today->format( 'z' );

			case 'thirty-days':
				return 30;

			case 'seven-days':
			default:
				return 7;
		}
	}

	/**
	 * The condition which is related to the target id.
	 *
	 * @since 1.1.0
	 * @return string
	 * @param int $target_id Target id.
	 */
	public static function target_id_condition( $target_id ) {
		$target_id_condition = '=';

		if ( empty( $target_id ) ) {
			$target_id_condition = '!=';
		}

		return $target_id_condition;
	}
}

Analytics::get_instance();
