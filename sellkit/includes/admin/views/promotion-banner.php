<?php
/**
 * Template for the global admin promotion banner.
 *
 * @package Sellkit\Admin\Promotion_Banner
 *
 * @since 2.4.0
 */

?>
<div
	class="sellkit-promotion-banner<?php echo empty( $description ) ? ' sellkit-promotion-banner--compact' : ''; ?><?php echo ! empty( $image_url ) ? ' sellkit-promotion-banner--has-image' : ''; ?>"
	data-sellkit-promotion-id="<?php echo esc_attr( $promotion_id ); ?>"
	data-sellkit-promotion-nonce="<?php echo esc_attr( $nonce ); ?>"
	<?php echo ! empty( $bg_style ) ? 'style="' . esc_attr( $bg_style ) . '"' : ''; ?>
>
	<?php if ( ! empty( $image_url ) ) : ?>
		<div class="sellkit-promotion-banner__image">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="" />
		</div>
	<?php endif; ?>

	<div class="sellkit-promotion-banner__inner">
		<div class="sellkit-promotion-banner__content">
			<div class="sellkit-promotion-banner__top-row">
				<?php if ( ! empty( $heading ) ) : ?>
					<div class="sellkit-promotion-banner__title">
						<?php echo esc_html( $heading ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $description ) || $has_code ) : ?>
					<div class="sellkit-promotion-banner__description-cell">
						<?php if ( ! empty( $description ) ) : ?>
							<div class="sellkit-promotion-banner__description">
								<?php echo esc_html( $description ); ?>
							</div>
						<?php endif; ?>

						<?php if ( $has_code ) : ?>
							<div class="sellkit-promotion-banner__code">
								<?php echo esc_html( "CODE {$coupon_code}" ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $has_cta ) : ?>
					<div class="sellkit-promotion-banner__cta">
						<a
							href="<?php echo esc_url( $cta_url ); ?>"
							target="_blank"
							rel="noopener noreferrer"
							class="button"
						>
							<span class="cta-text"><?php echo esc_html( $cta_text ); ?></span>
							<span class="cta-subtext"><?php echo esc_html( $cta_subtext ); ?></span>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php if ( $is_dismissible ) : ?>
		<button
			type="button"
			class="sellkit-promotion-banner__dismiss"
			aria-label="<?php echo esc_attr_x( 'Dismiss promotion', 'admin promotion banner', 'sellkit' ); ?>"
		></button>
	<?php endif; ?>
</div>

