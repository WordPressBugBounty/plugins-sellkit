<?php
namespace Sellkit\Blocks\Render;
defined( 'ABSPATH' ) || die();

use Sellkit\Blocks\Sellkit_Blocks;
use Sellkit_Funnel;
use Sellkit\Global_Checkout\Checkout as Global_Checkout;

/**
 * Accept/Reject Button block.
 *
 * @since 2.3.0
 */
class Accept_Reject_Button {
	private $post_id;

	/**
	 * Helper id.
	 *
	 * @var int
	 */
	private $helper_id = 0;

	/**
	 * Accept_Reject_Button constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Check block activation.
	 *
	 * @since 2.3.0
	 * @return boolean
	 */
	public function is_active() {
		if ( empty( $this->post_id ) ) {
			return false;
		}

		$step_data = get_post_meta( $this->post_id, 'step_data', true );

		if ( ! empty( $step_data['type']['key'] ) && in_array( $step_data['type']['key'], [ 'downsell', 'upsell' ], true ) ) {
			return true;
		}

		if ( $this->is_active_steps() ) {
			return true;
		}

		$global_checkout_id = get_option( Global_Checkout::SELLKIT_GLOBAL_CHECKOUT_OPTION, 0 );

		if (
			0 !== $global_checkout_id ||
			'publish' === get_post_status( (int) $global_checkout_id ) ||
			( function_exists( 'is_checkout' ) && is_checkout() )
		) {
			$steps = get_post_meta( $global_checkout_id, 'nodes', true );

			if ( ! is_array( $steps ) ) {
				return false;
			}

			foreach ( $steps as $step ) {
				$step['type'] = (array) $step['type'];

				if ( 'checkout' === $step['type']['key'] ) {
					$this->post_id = $step['page_id'];
				}
			}

			if ( $this->is_active_steps() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if block is available based on steps.
	 *
	 * @since 2.3.0
	 * @return boolean
	 */
	public function is_active_steps() {
		$funnel    = new Sellkit_Funnel( $this->post_id );
		$next_step = $funnel->next_step_data;
		$popups    = [ 'upsell', 'downsell' ];

		if ( ! isset( $funnel->next_step_data['type'] ) ) {
			return false;
		}

		$funnel->next_step_data['type'] = (array) $funnel->next_step_data['type'];

		if ( 'decision' === $funnel->next_step_data['type']['key'] ) {
			$page_id = isset( $funnel->next_step_data['page_id'] ) ? $funnel->next_step_data['page_id'] : 0;

			$this->helper_id = $this->post_id;

			if ( ! $page_id ) {
				return true;
			}

			return $this->take_care_of_decision_step( $page_id, $funnel->funnel_id );
		}

		if ( in_array( $funnel->next_step_data['type']['key'], $popups, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets step id before the decision step and return result.
	 *
	 * @param int $step_id id of the step before decision step.
	 * @param int $funnel_id id of the funnel.
	 * @since 2.3.0
	 */
	private function take_care_of_decision_step( $step_id, $funnel_id = null ) {
		$funnel     = new Sellkit_Funnel( $step_id );
		$conditions = ! empty( $funnel->current_step_data['data']['conditions'] ) ? $funnel->current_step_data['data']['conditions'] : [];
		$is_valid   = sellkit_conditions_validation( $conditions );
		$next_step  = $funnel->next_no_step_data;
		$popups     = [ 'upsell', 'downsell' ];

		if ( $is_valid ) {
			$next_step = $funnel->next_step_data;
		}

		$next_step['type'] = (array) $next_step['type'];

		if ( 'decision' === $next_step['type']['key'] ) {
			$this->take_care_of_decision_step( $next_step['page_id'] );
		}

		if ( in_array( $funnel->next_step_data['type']['key'], $popups, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Register block from meta.
	 *
	 * @since 2.3.0
	 */
	public function register_block_meta() {
		if ( ! $this->is_active() ) {
			return;
		}

		register_block_type_from_metadata(
			__DIR__,
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Render block in front-end.
	 *
	 * @param array    $attributes Block attributes.
	 * @since 2.3.0
	 * @return string
	 */
	public function render( $attributes, $content, $instance ) {
		if ( ! is_admin() ) {
			Sellkit_Blocks::load_scripts( 'accept-reject-button' );
		}

		$order_key         = sellkit_htmlspecialchars( INPUT_GET, 'order-key' );
		$button_type_class = 'accept' === $attributes['offerType'] ? 'sellkit-upsell-accept-button' : 'sellkit-upsell-reject-button';

		$button = sprintf(
			'<a class="%1$s %2$s" style="%4$s" data-order-key="%3$s">%5$s</a>',
			esc_attr( $attributes['dynamicClassNames'] ),
			esc_attr( $button_type_class ),
			esc_attr( $order_key ),
			esc_attr( $attributes['combinedStyle'] ),
			$attributes['title']
		);

		ob_start();
		?>
			<div class="sellkit-accept-reject-button-block-frontend">
				<?php echo wp_kses_post( $button ); ?>
				<div class="sellkit-upsell-popup">
					<div class="sellkit-upsell-popup-body">
						<div class="sellkit-upsell-popup-header">
							<img src="<?php echo esc_url( sellkit()->plugin_url() . 'assets/img/icons/close-cross.svg' ); ?>" >
						</div>
						<div class="sellkit-upsell-popup-content">
							<div class="sellkit-upsell-popup-icon">
								<img class="rotate sellkit-upsell-updating active" src="<?php echo esc_url( sellkit()->plugin_url() . 'assets/img/icons/sync-alt.svg' ); ?>" >
								<img class="sellkit-upsell-accepted" src="<?php echo esc_url( sellkit()->plugin_url() . 'assets/img/icons/check-circle.svg' ); ?>" >
								<img class="sellkit-upsell-rejected" src="<?php echo esc_url( sellkit()->plugin_url() . 'assets/img/icons/times-circle.svg' ); ?>" >
							</div>
							<div class="sellkit-upsell-popup-text">
								<div class="sellkit-upsell-updating active">
									<?php esc_html_e( 'Updating your order…', 'sellkit' ); ?>
								</div>
								<div class="sellkit-upsell-accepted">
									<?php esc_html_e( 'Congratulations! Your item has been successfully added to the order.', 'sellkit' ); ?>
								</div>
								<div class="sellkit-upsell-rejected">
									<?php esc_html_e( 'Sorry! We were unable to add this item to your order.', 'sellkit' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}
}

