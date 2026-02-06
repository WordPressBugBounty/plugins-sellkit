<?php
/**
 * Handles global admin promotion banner.
 *
 * @package Sellkit\Admin\Promotion_Banner
 *
 * @since 2.4.0
 */

defined( 'ABSPATH' ) || die();

/**
 * Global admin promotion banner.
 *
 * @since 2.4.0
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Sellkit_Admin_Promotion_Banner {

	/**
	 * Cache of active banners for current request.
	 *
	 * @since 2.4.0
	 *
	 * @var array|null
	 */
	private $active_banners = null;

	/**
	 * Constructor.
	 *
	 * @since 2.4.0
	 */
	public function __construct() {
		add_action( 'all_admin_notices', [ $this, 'render' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_sellkit_dismiss_admin_promotion', [ $this, 'ajax_dismiss' ] );
	}

	/**
	 * Get promotion banners config.
	 *
	 * Each banner item is an associative array with the following keys:
	 * - id            (string) Unique identifier for the banner. Required.
	 * - heading       (string) Main heading text.
	 * - description   (string) Secondary description text.
	 * - mainImageURL  (string) Absolute URL to the main image shown on the left.
	 * - backgroundImage (string) Optional absolute URL used as background for the banner container.
	 * - couponCode    (string) Coupon code text.
	 * - ctaText       (string) Call-to-action button label.
	 * - ctaUrl        (string) Call-to-action button URL.
	 * - ctaSubText    (string) Small meta text shown under the main copy (e.g. expiry info).
	 * - isDismissible (bool)   Whether the banner can be dismissed. Defaults to true.
	 * - startsAt      (string) Empty string or ISO-8601 datetime string (UTC) when the banner becomes visible.
	 * - expiresAt     (string) Empty string or ISO-8601 datetime string (UTC) when the banner expires.
	 * - sellkitAdminOnly (bool) Whether to show the banner only on SellKit admin pages. Defaults to false.
	 *
	 * @since 2.4.0
	 *
	 * @return array
	 */
	private function get_banners() {
		return [
			[
				'id'                   => 'sellkit-bf2025-promotion',
				'heading'              => '50% OFF Lifetime Plan',
				'description'          => 'UNLIMITED WEBSITES',
				'mainImageURL'         => sellkit()->plugin_url() . 'assets/img/promotions/black-friday-2025.png',
				'backgroundImage'      => '',
				'couponCode'           => '2025BF50',
				'ctaText'              => 'SHOP SALE',
				'ctaSubText'           => 'ENDS 2 DEC 23:59 (GMT +3)',
				'ctaUrl'               => 'https://getsellkit.com/pricing/?utm_campaign=BF2025Deal&utm_source=WPDashboard',
				'isDismissible'        => true,
				'startsAt'             => '2025-11-27T00:00:00+03:00',
				'expiresAt'            => '2025-12-02T23:59:00+03:00',
				'sellkitAdminOnly'     => true,
			],
		];
	}

	/**
	 * Validate that a promotion ID exists in the banners configuration.
	 *
	 * @since 2.4.0
	 *
	 * @param string $promotion_id Promotion identifier.
	 *
	 * @return bool
	 */
	private function is_valid_promotion_id( $promotion_id ) {
		if ( empty( $promotion_id ) ) {
			return false;
		}

		foreach ( $this->get_banners() as $banner ) {
			if ( ! empty( $banner['id'] ) && $promotion_id === $banner['id'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the current admin page is a SellKit admin page.
	 *
	 * @since 2.4.0
	 *
	 * @return bool
	 */
	private function is_sellkit_admin_page() {
		// phpcs:ignore WordPress.Security.NonceVerification -- Reading query param for page routing, not form processing.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		return ! empty( $page ) && strpos( $page, 'sellkit' ) !== false;
	}

	/**
	 * Get active banners for the current user and time.
	 *
	 * @since 2.4.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 *
	 * @return array
	 */
	private function get_active_banners() {
		if ( null !== $this->active_banners ) {
			return $this->active_banners;
		}

		$banners = $this->get_banners();

		if ( empty( $banners ) || ! is_array( $banners ) ) {
			$this->active_banners = [];

			return $this->active_banners;
		}

		$now                   = current_time( 'timestamp' );
		$is_sellkit_admin_page = $this->is_sellkit_admin_page();
		$filtered              = [];

		foreach ( $banners as $banner ) {
			if ( empty( $banner ) || empty( $banner['id'] ) ) {
				continue;
			}

			$dismissed = get_user_meta(
				get_current_user_id(),
				'sellkit_admin_promotion_dismissed_' . $banner['id'],
				true
			);

			if ( $dismissed ) {
				continue;
			}

			if ( ! empty( $banner['startsAt'] ) ) {
				$starts_at = strtotime( $banner['startsAt'] );

				if ( $starts_at && $now < $starts_at ) {
					continue;
				}
			}

			if ( ! empty( $banner['expiresAt'] ) ) {
				$expires_at = strtotime( $banner['expiresAt'] );

				if ( $expires_at && $now > $expires_at ) {
					continue;
				}
			}

			if ( ! empty( $banner['sellkitAdminOnly'] ) && ! $is_sellkit_admin_page ) {
				continue;
			}

			$filtered[] = $banner;
		}

		$this->active_banners = $filtered;

		return $this->active_banners;
	}

	/**
	 * Render global admin promotion banner.
	 *
	 * @since 2.4.0
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 *
	 * @return void
	 */
	public function render() {
		if ( ! is_admin() || is_network_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$banners = $this->get_active_banners();

		if ( empty( $banners ) ) {
			return;
		}

		foreach ( $banners as $banner ) {
			$promotion_id = $banner['id'];
			$nonce        = wp_create_nonce( 'sellkit_dismiss_admin_promotion' );
			$image_url    = isset( $banner['mainImageURL'] ) ? $banner['mainImageURL'] : '';
			$bg_image     = isset( $banner['backgroundImage'] ) ? $banner['backgroundImage'] : '';
			$heading      = isset( $banner['heading'] ) ? $banner['heading'] : '';
			$description  = isset( $banner['description'] ) ? $banner['description'] : '';
			$coupon_code  = isset( $banner['couponCode'] ) ? $banner['couponCode'] : '';
			$cta_text     = isset( $banner['ctaText'] ) ? $banner['ctaText'] : '';
			$cta_url      = isset( $banner['ctaUrl'] ) ? $banner['ctaUrl'] : '';
			$cta_subtext  = isset( $banner['ctaSubText'] ) ? $banner['ctaSubText'] : '';
			$has_code     = ! empty( $coupon_code );
			$has_cta      = ! empty( $cta_text ) && ! empty( $cta_url );
			$bg_style     = '';

			if ( ! empty( $bg_image ) ) {
				$bg_style = 'background-image:url(' . esc_url( $bg_image ) . ');';
			}

			$this->render_banner_template(
				[
					'promotion_id'   => $promotion_id,
					'nonce'          => $nonce,
					'image_url'      => $image_url,
					'heading'        => $heading,
					'description'    => $description,
					'coupon_code'    => $coupon_code,
					'cta_text'       => $cta_text,
					'cta_url'        => $cta_url,
					'cta_subtext'    => $cta_subtext,
					'has_code'       => $has_code,
					'has_cta'        => $has_cta,
					'bg_style'       => $bg_style,
					'is_dismissible' => ! empty( $banner['isDismissible'] ),
				]
			);
		}
	}

	/**
	 * Enqueue styles and scripts for promotion banners.
	 *
	 * @since 2.4.0
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! is_admin() || is_network_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $this->get_active_banners() ) ) {
			return;
		}

		wp_enqueue_style(
			'sellkit-promotion-banner-font',
			'https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;800&display=swap',
			[],
			'1.0.0'
		);

		wp_enqueue_style(
			'sellkit-admin-promotion-banner',
			sellkit()->plugin_url() . 'assets/dist/css/promotion-banner.css',
			[ 'sellkit-promotion-banner-font' ],
			sellkit()->version()
		);

		wp_enqueue_script(
			'sellkit-admin-promotion-banner',
			sellkit()->plugin_url() . 'assets/dist/js/promotion-banner.js',
			[ 'jquery' ],
			sellkit()->version(),
			true
		);

		wp_localize_script(
			'sellkit-admin-promotion-banner',
			'sellkitPromotionBanner',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * Render a single promotion banner using a template.
	 *
	 * @since 2.4.0
	 *
	 * @param array $context Banner context data.
	 *
	 * @return void
	 */
	private function render_banner_template( array $context ) {
		$template = __DIR__ . '/views/promotion-banner.php';

		if ( ! file_exists( $template ) ) {
			return;
		}

		$promotion_id   = $context['promotion_id'];
		$nonce          = $context['nonce'];
		$image_url      = $context['image_url'];
		$heading        = $context['heading'];
		$description    = $context['description'];
		$coupon_code    = $context['coupon_code'];
		$cta_text       = $context['cta_text'];
		$cta_url        = $context['cta_url'];
		$cta_subtext    = $context['cta_subtext'];
		$has_code       = $context['has_code'];
		$has_cta        = $context['has_cta'];
		$bg_style       = $context['bg_style'];
		$is_dismissible = $context['is_dismissible'];

		include $template;
	}

	/**
	 * Handle AJAX dismissal of the admin promotion banner.
	 *
	 * @since 2.4.0
	 *
	 * @return void
	 */
	public function ajax_dismiss() {
		check_ajax_referer( 'sellkit_dismiss_admin_promotion', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$promotion_id = isset( $_POST['promotionId'] ) ? sanitize_text_field( wp_unslash( $_POST['promotionId'] ) ) : '';

		if ( empty( $promotion_id ) || ! $this->is_valid_promotion_id( $promotion_id ) ) {
			wp_send_json_error();
		}

		update_user_meta(
			get_current_user_id(),
			'sellkit_admin_promotion_dismissed_' . $promotion_id,
			1
		);

		wp_send_json_success();
	}
}

new Sellkit_Admin_Promotion_Banner();

