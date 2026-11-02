<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'sellkit_update_option' ) ) {
	/**
	 * Update option from options storage.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Update value.
	 *
	 * @return boolean False if value was not updated and true if value was updated.
	 */
	function sellkit_update_option( $option, $value ) {
		$options = get_option( 'sellkit', [] );

		// No need to update the same value.
		if ( isset( $options[ $option ] ) && $value === $options[ $option ] ) {
			return false;
		}

		// Update the option.
		$options[ $option ] = $value;
		update_option( 'sellkit', $options );

		return true;
	}
}

if ( ! function_exists( 'sellkit_get_option' ) ) {
	/**
	 * Get option from options storage.
	 *
	 * @param string  $option Option name.
	 * @param boolean $default Default value.
	 *
	 * @return mixed Value set for the option.
	 */
	function sellkit_get_option( $option, $default = false ) {
		$options = get_option( 'sellkit', [] );

		if ( ! isset( $options[ $option ] ) ) {
			return $default;
		}

		return $options[ $option ];
	}
}

if ( ! function_exists( 'sellkit_htmlspecialchars' ) ) {
	/**
	 * Sellkit html special chars is created because FILTER_SANITIZE_STRING is deprecated.
	 *
	 * @since 1.2.1
	 * @param string $input_type Type of input.
	 * @param string $param Parameter key.
	 */
	function sellkit_htmlspecialchars( $input_type, $param ) {
		$value = '';

		if ( INPUT_GET === $input_type ) {
			$value = ! empty( $_GET[ $param ] ) ? htmlspecialchars( $_GET[ $param ] ) : ''; //phpcs:ignore
		}

		if ( INPUT_POST === $input_type ) {
			$value = ! empty( $_POST[ $param ] ) ? htmlspecialchars( $_POST[ $param ] ) : ''; //phpcs:ignore
		}

		if ( INPUT_COOKIE === $input_type ) {
			$value = ! empty( $_COOKIE[ $param ] ) ? htmlspecialchars( $_COOKIE[ $param ] ) : ''; //phpcs:ignore
		}

		return $value;
	}
}

if ( ! function_exists( 'sellkit_decode_entity_title' ) ) {
	/**
	 * Decode HTML entities in titles for JSON/React (fixes literal &#8211; etc. in chart headers).
	 *
	 * @param string $title Title string.
	 * @return string
	 */
	function sellkit_decode_entity_title( $title ) {
		if ( ! is_string( $title ) || '' === $title ) {
			return $title;
		}

		return html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}

/**
 * Delete option from options storage.
 *
 * @param string $option Option name.
 *
 * @return boolean False if value was not deleted and true if value was deleted.
 */
function sellkit_delete_option( $option ) {
	$options = get_option( 'sellkit', [] );

	// Option not exist.
	if ( ! isset( $options[ $option ] ) ) {
		return false;
	}

	// Remove the option.
	unset( $options[ $option ] );
	update_option( 'sellkit', $options );

	return true;
}

/**
 * Get multi-select values.
 *
 * @since 1.1.0
 * @param array $array Multi select data.
 * @return array
 */
function sellkit_get_multi_select_values( $array ) {
	$values = [];

	foreach ( $array as $item ) {
		$values[] = $item['value'];
	}

	return $values;
}

/**
 * Checks conditions validation.
 *
 * @since 1.1.0
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @param array $conditions Conditions data.
 */
function sellkit_conditions_validation( $conditions ) {
	$is_valid = true;

	if ( empty( $conditions ) ) {
		return true;
	}

	$first_condition = reset( $conditions );
	$condition_type  = ! empty( $first_condition['type'] ) ? $first_condition['type'] : 'and';

	if ( 'or' === $condition_type ) {
		$is_valid = false;
	}

	foreach ( $conditions as $condition ) {
		if ( is_array( $condition['condition_value'] ) && ! empty( $condition['condition_value'][0]['value'] ) ) {
			$condition['condition_value'] = sellkit_get_multi_select_values( $condition['condition_value'] );
		}

		$result = sellkit_condition_match( $condition['condition_subject'], $condition['condition_operator'], $condition['condition_value'] );

		if ( is_wp_error( $result ) ) {
			continue;
		}

		if ( ! $result ) {
			$is_valid = false;
		}

		if ( $result && 'or' === $condition_type ) {
			$is_valid = true;
			break;
		}
	}

	if ( true === $is_valid ) {
		return true;
	}

	return false;
}

if ( ! function_exists( 'array_key_last' ) ) {
	/**
	 * Adds support for array_key_last function for lower php than 7.3.0 & WP lower than 5.9.0.
	 *
	 * @param array $array array.
	 */
	function array_key_last( $array ) {
		if ( empty( $array ) ) {
			return null;
		}

		end( $array );
		return key( $array );
	}
}

add_action( 'woocommerce_removed_coupon', 'sellkit_set_cookie_after_auto_apply_coupon_removed', 10, 1 );

/**
 * Create cookie for deleted auto apply coupon.
 *
 * @param string $coupon_code coupon code.
 */
function sellkit_set_cookie_after_auto_apply_coupon_removed( $coupon_code ) {
	$cookie_value = null;
	$parsed_url   = wp_parse_url( site_url() );
	$domain       = $parsed_url['host'];

	if ( isset( $_COOKIE['sellkit_rejected_coupon'] ) ) {
		$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE['sellkit_rejected_coupon'] ) );
	}

	$cookie_value = $cookie_value . ',' . $coupon_code;
	setcookie( 'sellkit_rejected_coupon', $cookie_value, time() + 600, '/', $domain );
}

add_action( 'woocommerce_checkout_order_processed', 'sellkit_remove_rejected_auto_apply_coupon_cookie' );

/**
 * Remove cookie after make an order.
 */
function sellkit_remove_rejected_auto_apply_coupon_cookie() {
	if ( isset( $_COOKIE['sellkit_rejected_coupon'] ) ) {
		$parsed_url = wp_parse_url( site_url() );
		$domain     = $parsed_url['host'];
		setcookie( 'sellkit_rejected_coupon', '', time() - 600, '/', $domain );
	}
}

/**
 * Get the nonce action for an opt-in file download.
 *
 * The nonce is bound to the exact file path, so a download URL can only be used
 * for the file the server issued it for.
 *
 * @since 2.8.1
 * @param string $file_path Absolute file path.
 * @return string
 */
function sellkit_get_optin_download_nonce_action( $file_path ) {
	return 'sellkit_download_file_' . md5( $file_path );
}

/**
 * Build the download URL for an opt-in "download file" action.
 *
 * @since 2.8.1
 * @param string $file_path Absolute file path.
 * @return string
 */
function sellkit_get_optin_download_url( $file_path ) {
	$args = [
		'action'   => 'sellkit_download_file',
		'file'     => base64_encode( $file_path ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		'_wpnonce' => wp_create_nonce( sellkit_get_optin_download_nonce_action( $file_path ) ),
	];

	return add_query_arg( $args, admin_url( 'admin-post.php' ) );
}

/**
 * Resolve an opt-in download file to its real path.
 *
 * Only allowed file types that physically live inside the uploads directory are accepted.
 *
 * @since 2.8.1
 * @param string $file_path File path.
 * @return string|false Real file path, or false when the file is not downloadable.
 */
function sellkit_get_optin_download_real_path( $file_path ) {
	if ( empty( $file_path ) || ! is_string( $file_path ) || false !== strpos( $file_path, "\0" ) ) {
		return false;
	}

	$upload_dir    = wp_get_upload_dir();
	$real_base_dir = realpath( $upload_dir['basedir'] );
	$real_path     = realpath( $file_path );

	if ( false === $real_base_dir || false === $real_path || ! is_file( $real_path ) ) {
		return false;
	}

	$real_base_dir = trailingslashit( wp_normalize_path( $real_base_dir ) );

	$relative_path = substr( wp_normalize_path( $real_path ), strlen( $real_base_dir ) );

	if ( 0 !== strpos( wp_normalize_path( $real_path ), $real_base_dir ) ) {
		return false;
	}

	// Never serve files from protected upload folders (e.g. WooCommerce paid downloads and logs).
	$protected_dirs = apply_filters( 'sellkit_optin_download_protected_dirs', [ 'woocommerce_uploads', 'wc-logs' ] );

	foreach ( (array) $protected_dirs as $protected_dir ) {
		if ( 0 === stripos( $relative_path, trailingslashit( $protected_dir ) ) ) {
			return false;
		}
	}

	if ( ! sellkit_is_optin_download_source( $relative_path ) ) {
		return false;
	}

	$file_info = wp_check_filetype( wp_basename( $real_path ) );

	if ( empty( $file_info['ext'] ) || empty( $file_info['type'] ) ) {
		return false;
	}

	return $real_path;
}

/**
 * Whether an uploads file was added as an opt-in download.
 *
 * Accepts media library attachments (block editor) and files stored by the SellKit
 * file uploader control (Elementor), which are saved as "name__{uniqid}.ext".
 *
 * @since 2.8.1
 * @param string $relative_path File path relative to the uploads directory.
 * @return bool
 */
function sellkit_is_optin_download_source( $relative_path ) {
	global $wpdb;

	if ( preg_match( '/__[0-9a-f]{13}\.[a-z0-9]+$/i', wp_basename( $relative_path ) ) ) {
		return true;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$attachment_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
			$relative_path
		)
	);

	return ! empty( $attachment_id ) && 'attachment' === get_post_type( (int) $attachment_id );
}

/**
 * Stream an opt-in download file. Hooked to admin-post.php.
 *
 * @since 2.8.1
 * @return void
 */
function sellkit_handle_optin_file_download() {
	$file  = sanitize_text_field( (string) filter_input( INPUT_GET, 'file' ) );
	$nonce = sanitize_text_field( (string) filter_input( INPUT_GET, '_wpnonce' ) );

	if ( empty( $file ) || empty( $nonce ) ) {
		wp_die( '<script>window.close();</script>' );
	}

	$file = base64_decode( $file, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

	if ( empty( $file ) || ! wp_verify_nonce( $nonce, sellkit_get_optin_download_nonce_action( $file ) ) ) {
		wp_die( '<script>window.close();</script>' );
	}

	$real_path = sellkit_get_optin_download_real_path( $file );

	if ( false === $real_path ) {
		wp_die( '<script>window.close();</script>' );
	}

	$file_name = wp_basename( $real_path );
	$file_ext  = pathinfo( $real_path, PATHINFO_EXTENSION );

	// Strip hash.
	$file_name  = str_replace( '.' . $file_ext, '', $file_name );
	$file_parts = explode( '__', $file_name );
	$file_name  = sanitize_file_name( array_shift( $file_parts ) . '.' . $file_ext );

	// Drop any output printed earlier in the request so it can't corrupt the file.
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . filesize( $real_path ) );
	// phpcs:ignore WordPress.WP.AlternativeFunctions
	readfile( $real_path );
	exit;
}

/**
 * Whether a post is published and publicly viewable (not draft, private, or password protected without the password).
 *
 * Used before reading builder settings from a post ID that comes from a request.
 *
 * @since 2.8.1
 * @param int $post_id Post ID.
 * @return bool
 */
function sellkit_is_post_publicly_accessible( $post_id ) {
	$post = get_post( absint( $post_id ) );

	if ( ! $post || 'publish' !== $post->post_status || post_password_required( $post ) ) {
		return false;
	}

	return is_post_type_viewable( $post->post_type );
}

/**
 * Whether the given value is a valid http(s) URL.
 *
 * @since 2.8.1
 * @param string $url URL.
 * @return bool
 */
function sellkit_is_http_url( $url ) {
	if ( ! is_string( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	return in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), [ 'http', 'https' ], true );
}

/**
 * Verify the SellKit admin nonce and the user's capability for admin AJAX requests.
 *
 * A nonce only protects against CSRF, so every admin AJAX handler must also check a capability.
 *
 * @since 2.8.1
 * @param string $capability Required capability. Defaults to the capability of the SellKit menu.
 * @return void
 */
function sellkit_admin_ajax_guard( $capability = 'edit_theme_options' ) {
	check_ajax_referer( 'sellkit', 'nonce' );

	if ( ! current_user_can( $capability ) ) {
		wp_send_json_error( esc_html__( 'You are not allowed to do this action.', 'sellkit' ), 403 );
	}
}
