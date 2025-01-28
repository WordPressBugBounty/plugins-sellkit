<?php
/**
 * Template Name: Sellkit Default Canvas
 *
 * @package Sellkit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_global_checkout = apply_filters( 'sellkit_global_checkout_activated', false );

if ( $is_global_checkout ) {
	remove_action( 'jupiterx_main_content_before_markup', 'jupiterx_wc_add_steps' );
}
?>

<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class( 'sellkit' ); ?>>

	<?php wp_body_open(); ?>
	<?php get_header(); ?>
	<div class="sellkit-container" >

	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
