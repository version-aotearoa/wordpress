<?php
defined( 'ABSPATH' ) || exit;

$product = isset( $args['product'] ) && $args['product'] instanceof WC_Product ? $args['product'] : null;
if ( ! $product ) {
	return;
}

$product_id = $product->get_id();
$title      = $product->get_name();
$permalink  = get_permalink( $product_id );
$images     = WCW_Work_Data::images( $product );
$main_image = $images ? $images[0] : 0;
$hover      = ( count( $images ) > 1 && WCW_Plugin::get_setting( 'hover_image', 1 ) ) ? $images[1] : 0;
$first      = ! empty( $args['first'] );
$size       = WCW_Plugin::get_setting( 'card_image_size', 'woocommerce_single' );
$columns    = max( 1, (int) WCW_Plugin::get_setting( 'columns', 3 ) );
$tablet     = max( 1, (int) WCW_Plugin::get_setting( 'columns_tablet', 2 ) );

$sizes = sprintf(
	'(max-width: 640px) 100vw, (max-width: 960px) %dvw, %dvw',
	(int) floor( 100 / $tablet ),
	(int) floor( 100 / $columns )
);

$badge        = WCW_Render::badge_html( $product );
$quick_view   = (bool) WCW_Plugin::get_setting( 'quick_view', 1 ) && ( ! array_key_exists( 'quick_view', $args ) || $args['quick_view'] );
$show_price   = (bool) WCW_Plugin::get_setting( 'show_price_on_cards', 0 );
$price        = $show_price ? WCW_Work_Data::price_html( $product ) : '';
$image_attrs  = array(
	'class'    => 'wcw-img',
	'alt'      => $title,
	'sizes'    => $sizes,
	'decoding' => 'async',
	'loading'  => $first ? 'eager' : 'lazy',
);
if ( $first ) {
	$image_attrs['fetchpriority'] = 'high';
}
?>
<article class="wcw-item" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-title="<?php echo esc_attr( $title ); ?>" data-url="<?php echo esc_url( $permalink ); ?>">
	<div class="wcw-media">
		<a class="wcw-media-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<?php
			if ( $main_image ) {
				echo wp_get_attachment_image( $main_image, $size, false, $image_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<span class="wcw-media-placeholder" aria-hidden="true"></span>';
			}

			if ( $hover ) {
				echo wp_get_attachment_image(
					$hover,
					$size,
					false,
					array(
						'class'     => 'wcw-img wcw-img--hover',
						'alt'       => '',
						'aria-hidden' => 'true',
						'sizes'     => $sizes,
						'decoding'  => 'async',
						'loading'   => 'lazy',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			<span class="wcw-shade" aria-hidden="true"></span>
		</a>

		<?php echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php if ( $quick_view ) : ?>
			<a class="wcw-quick-view" href="<?php echo esc_url( $permalink ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: work title. */ __( 'Quick view: %s', 'woocommerce-works' ), $title ) ); ?>">
				<?php esc_html_e( 'Quick View', 'woocommerce-works' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<div class="wcw-caption">
		<a class="wcw-title-link" href="<?php echo esc_url( $permalink ); ?>">
			<h2 class="wcw-title"><?php echo esc_html( $title ); ?></h2>
		</a>
		<?php if ( $price ) : ?>
			<p class="wcw-card-price"><?php echo wp_kses_post( $price ); ?></p>
		<?php endif; ?>
	</div>
</article>
