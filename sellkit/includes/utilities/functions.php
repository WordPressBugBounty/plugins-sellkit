<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get value from $_POST.
 *
 * @since 1.1.0
 *
 * @param string $needle  Name of the searched key.
 * @param mixed  $default Optional. Value to return if the needle isn't found.
 *
 * @return string Returns the value if found; else $default is returned.
 */
function sellkit_post( $needle, $default = null ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.CSRF.NonceVerification.NoNonceVerification -- Generic accessor; callers verify nonces when required.
	return sellkit_get( $needle, $_POST, $default );
}


/**
 * Get value from $_GET or defined $haystack.
 *
 * @since 1.1.0
 *
 * @param string $needle   Name of the searched key.
 * @param mixed  $haystack Optional. The target to search. If false, $_GET is set to be the $haystack.
 * @param mixed  $default  Optional. Value to return if the needle isn't found.
 *
 * @return string Returns the value if found; else $default is returned.
 */
function sellkit_get( $needle, $haystack = false, $default = null ) {

	if ( false === $haystack ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.CSRF.NonceVerification.NoNonceVerification -- Generic read helper; callers verify nonces when required.
		$haystack = $_GET;
	}

	$haystack = (array) $haystack;

	if ( isset( $haystack[ $needle ] ) ) {
		return $haystack[ $needle ];
	}

	return $default;
}
