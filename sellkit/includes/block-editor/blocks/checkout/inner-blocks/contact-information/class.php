<?php
namespace Sellkit\Blocks\Inner_Block;

/**
 * Contact Information class.
 *
 * @since 2.3.0
 * @package Sellkit\Blocks\Inner_Block
 */
class Checkout_Contact_Information {
	/**
	 * Register block meta.
	 *
	 * @since 2.3.0
	 */
	public function register_block_meta() {
		register_block_type_from_metadata(
			__DIR__
		);
	}

	/**
	 * Render contact information.
	 *
	 * @param string $content Block content.
	 * @param array  $attributes Block attributes.
	 * @since 2.3.0
	 * @return string
	 */
	public function checkout_contact_information( $content, $attributes ) {
		if ( is_user_logged_in() ) {
			$this_user = wp_get_current_user();
			$email     = get_user_meta( $this_user->ID, 'billing_email', true );

			if ( empty( $email ) ) {
				$email = $this_user->user_email;
			}

			add_filter( 'sellkit_checkout_block_create_website_account', function() {
				return esc_html( $attributes['contactDescriptionText'] );
			} );

			ob_start();
			?>
			<div class="sellkit-checkout-widget-login-section sellkit-checkout-widget-logged-user sellkit-checkout-local-fields <?php echo esc_attr( $attributes['customClassName'] ); ?>">
				<div class="header heading"><?php echo esc_html( $attributes['contactHeadingText'] ); ?></div>
				<div class="sellkit-checkout-widget-email-holder">
					<div class="sellkit-checkout-fields-wrapper sellkit-widget-checkout-fields sellkit-checkout-excluded-wrapper-fields sellkit-login-section">
						<div style="position:relative">
							<span class="free-label">
								<?php echo esc_html__( 'Email address', 'sellkit' ); ?>
							</span>
						</div>
						<p class="log-email">
							<span class="woocommerce-input-wrapper">
								<input
									type="email"
									class="input-text validate-email"
									name="billing_email"
									id="billing_email"
									readonly
									placeholder="<?php echo esc_html__( 'Email Address', 'sellkit' ); ?>"
									value="<?php echo esc_attr( $email ); ?>"
									autocomplete="email"
								/>
							</span>
						</p>
					</div>
				</div>
			</div>
			<?php
			$final_content = ob_get_clean();

			return str_replace( '</div>', $final_content . '</div>', $content );
		}

		ob_start();
		?>
		<div class="sellkit-checkout-widget-login-section sellkit-checkout-local-fields <?php echo esc_attr( $attributes['customClassName'] ); ?>">
			<div class="header heading"><?php echo esc_html( $attributes['contactHeadingText'] ); ?></div>
			<div class="sellkit-checkout-widget-email-holder">
				<div class="sellkit-checkout-fields-wrapper sellkit-widget-checkout-fields sellkit-checkout-excluded-wrapper-fields sellkit-login-section">
					<div style="position:relative">
						<span class="free-label">
							<?php echo esc_html__( 'Email address', 'sellkit' ); ?>
						</span>
					</div>
					<p>
						<span class="woocommerce-input-wrapper">
							<input
								type="text"
								name="billing_email"
								id="billing_email"
								class="login-mail validate-email"
								placeholder="<?php echo esc_html__( 'Email Address', 'sellkit' ); ?>"
								autocomplete=""
							>
						</span>
						<span class="sellkit-checkout-widget-email-error login-section-error">
							<?php echo esc_html__( 'Email address is not valid.', 'sellkit' ); ?>
						</span>
						<span class="sellkit-checkout-widget-email-empty login-section-error">
							<?php echo esc_html__( 'Email address is empty.', 'sellkit' ); ?>
						</span>
						<span class="jupiter-checkout-widget-email-search">
							<i class="fas fa-sync fa-spin"></i>
							<?php echo esc_html__( 'Checking...', 'sellkit' ); ?>
						</span>
					</p>
				</div>
			</div>
			<div class="login_hidden_section sellkit-checkout-widget-username-field">
				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<div class="sellkit-checkout-fields-wrapper sellkit-widget-checkout-fields sellkit-checkout-excluded-wrapper-fields sellkit-login-section">
						<div style="position:relative">
							<span class="free-label">
								<?php echo esc_html__( 'Username', 'sellkit' ); ?>
							</span>
						</div>
						<p>
							<input
								type="text"
								class="login-username"
								name="account_username"
								id="register_user"
								placeholder="<?php echo esc_html__( 'Username', 'sellkit' ); ?>"
							>
							<span class="sellkit-checkout-widget-username-error login-section-error">
								<?php echo esc_html__( 'An account is already registered with that username. Please choose another..', 'sellkit' ); ?>
							</span>
						</p>
					</div>
				<?php endif; ?>
			</div>
			<div class="login_hidden_section sellkit-checkout-widget-password-field">
				<div class="sellkit-checkout-fields-wrapper sellkit-widget-checkout-fields sellkit-checkout-excluded-wrapper-fields sellkit-login-section">
					<div style="position:relative">
						<span class="free-label">
							<?php echo esc_html__( 'Password', 'sellkit' ); ?>
						</span>
					</div>
					<p>
						<input
							type="password"
							class="login-pass"
							<?php
								if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
									echo 'name="account_password"';
									echo 'id="register_pass"';
								} else {
									echo 'name="account_password"';
									echo 'id="login_pass"';
								}
							?>
							placeholder="<?php echo esc_html__( 'Password', 'sellkit' ); ?>"
						>
					</p>
				</div>
			</div>
			<?php if ( 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) ) : ?>
			<div class="create-desc">
				<div class="sellkit-checkout-fields-wrapper sellkit-widget-checkout-fields sellkit-checkout-excluded-wrapper-fields sellkit-login-section">
					<p>
						<input
							type="checkbox"
							class="sellkit-create-account-checkbox woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
							id="createaccount"
							name="createaccount"
							value="1"
						>
						<label for="createaccount" class="sellkit-create-account-checkbox-label">
							<?php echo esc_html( $attributes['contactDescriptionText'] ); ?>
						</label>
					</p>
				</div>
			</div>
			<?php endif; ?>
			<div class="login-wrapper login_hidden_section">
				<div class="sellkit-checkout-fields-wrapper sellkit-widget-checkout-fields sellkit-login-section">
					<span class="login-submit sellkit-checkout-widget-secondary-button">
						<?php echo esc_html__( 'Login', 'sellkit' ); ?>
					</span>
					<label class="login-result"></label>
				</div>
			</div>
		</div>
		<?php

		$final_content = ob_get_clean();

		return str_replace( '</div>', $final_content . '</div>', $content );
	}
}
