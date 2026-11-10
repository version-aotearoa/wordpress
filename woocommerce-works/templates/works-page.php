<?php
/**
 * Template Name: Works
 *
 * Browse-first portfolio grid. Assign this template to a page and the page
 * renders the works grid instead of its normal content (any content typed
 * above the grid is kept as an intro).
 *
 * @package WooCommerce Works
 */

defined( 'ABSPATH' ) || exit;

$wcw_is_block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();

ob_start();
while ( have_posts() ) {
	the_post();

	if ( WCW_Plugin::get_setting( 'show_page_title', 0 ) ) {
		echo '<h1 class="wcw-page-title">' . esc_html( get_the_title() ) . '</h1>';
	}

	$wcw_content = get_the_content();
	if ( '' !== trim( (string) $wcw_content ) && false === strpos( (string) $wcw_content, '[works_grid]' ) ) {
		echo '<div class="wcw-page-intro">' . apply_filters( 'the_content', $wcw_content ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo WCW_Render::works_section( array( 'base' => get_permalink() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
$wcw_inner = (string) ob_get_clean();

if ( $wcw_is_block_theme ) {
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php
	if ( function_exists( 'block_header_area' ) ) {
		block_header_area();
	}
	?>
	<main id="main" class="wcw-main">
		<div class="wcw-main-inner">
			<?php echo $wcw_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</main>
	<?php
	if ( function_exists( 'block_footer_area' ) ) {
		block_footer_area();
	}
	wp_footer();
	?>
</body>
</html>
	<?php
} else {
	get_header();
	?>
	<main id="main" class="wcw-main">
		<div class="wcw-main-inner">
			<?php echo $wcw_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</main>
	<?php
	get_footer();
}
