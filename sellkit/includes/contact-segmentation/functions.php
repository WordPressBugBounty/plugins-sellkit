<?php
defined( 'ABSPATH' ) || exit;

use Sellkit\Contact_Segmentation\Conditions;

/**
 * Condition match wrapper.
 *
 * @since 1.1.0
 * @param string $condition_name  Condition name.
 * @param string $operator_name   Condition operator.
 * @param mixed  $condition_value Condition value.
 */
function sellkit_condition_match( $condition_name, $operator_name, $condition_value ) {
	$condition_value = apply_filters( "sellkit_cs_conditions_value_{$condition_name}", $condition_value );

	if ( ! class_exists( 'Conditions' ) ) {
		require_once __DIR__ . '/conditions.php';
		new Conditions();
	}
	return Conditions::match( $condition_name, $operator_name, $condition_value );
}

/**
 * Get all of the products, it's searchable.
 *
 * @since 1.1.0
 * @param string $input_value The input value for searching.
 */
function sellkit_get_products( $input_value ) {
	if ( ! sellkit()->has_valid_dependencies() ) {
		return [];
	}

	$filtered_products = [];
	$args              = [
		'post_type' => 'product',
		'post_status' => 'any',
		's' => $input_value,
	];

	$query = new \WP_Query( $args );

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$filtered_products[ get_the_ID() ] = html_entity_decode( get_the_title() );
		}
	}

	return $filtered_products;
}

/**
 * Gets the products categories.
 *
 * @since 1.1.0
 * @param string $taxonomy The taxonomy.
 * @param string $input_value The input value.
 */
function sellkit_get_terms( $taxonomy, $input_value ) {
	if ( ! sellkit()->has_valid_dependencies() ) {
		return [];
	}

	$filtered_terms = [];

	$args = array(
		'taxonomy'   => $taxonomy,
		'number'     => 20,
		'orderby'    => 'ID',
		'order'      => 'DESC',
		'hide_empty' => false,
		'name__like' => $input_value,
	);

	$terms = get_terms( $args );

	foreach ( $terms as $term ) {
		$filtered_terms[ $term->term_id ] = $term->name;
	}

	return $filtered_terms;
}

/**
 * Sellkit filter array based on a specific string.
 *
 * @since 1.1.0
 * @param array  $array Array of data.
 * @param string $string Required string.
 */
function sellkit_filter_array( $array, $string ) {
	return array_filter( $array, function ( $country ) use ( $string ) {
		return strpos( strtolower( $country ), strtolower( $string ) ) !== false;
	} );
}

/**
 * Gets Ip.
 *
 * Uses REMOTE_ADDR only; client-supplied headers (Client-IP, X-Forwarded-For) can be spoofed.
 * Sites behind a trusted proxy/CDN can provide the real client IP via the `sellkit_client_ip` filter.
 *
 * @since 1.1.0
 * @return string
 */
function sellkit_get_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	/**
	 * Filters the visitor IP used by SellKit (e.g. to read a trusted proxy header).
	 *
	 * @since 2.8.1
	 * @param string $ip Visitor IP from REMOTE_ADDR.
	 */
	$ip = (string) apply_filters( 'sellkit_client_ip', $ip );

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Gets contact's column value.
 *
 * @since 1.5.0
 * @param string $column Column name.
 * @return mixed|string|void
 */
function sellkit_get_funnel_contact_value_by_column( $column ) {
	$results  = [];
	$contacts = sellkit()->db->get( 'funnel_contact', [
		'user_id' => get_current_user_id()
	] );

	foreach ( $contacts as $contact ) {
		if ( ! empty( $contact[ $column ] ) && is_serialized( $contact[ $column ] ) ) {
			$value = unserialize( $contact[ $column ], [ 'allowed_classes' => false ] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- No objects allowed.

			if ( is_array( $value ) && isset( $value[0] ) ) {
				$results[] = $value[0];
			}
		}
	}

	return $results;
}
