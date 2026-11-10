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
$size       = WCW_Plugin::get_setting( 'large_image_size', 'woocommerce_single' );
$thumbs     = ( count( $images ) > 1 && WCW_Plugin::get_setting( 'quick_view_thumbs', 1 ) );
$description = '';

if ( WCW_Plugin::get_setting( 'quick_view_description', 1 ) ) {
	$raw = $product->get_short_description();
	if ( '' === trim( wp_strip_all_tags( (string) $raw ) ) ) {
		$raw = $product->get_description();
	}
	$raw = trim( wp_strip_all_tags( (string) $raw ) );
	if ( '' !== $raw ) {
		$words       = max( 5, (int) WCW_Plugin::get_setting( 'description_words', 40 ) );
		$description = wp_trim_words( $raw, $words, '…' );
	}
}
?>
<div class="wcw-qv-inner" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-wcw-qv-inner>
	<div class="wcw-qv-media">
		<div class="wcw-qv-stage">
			<?php
			if ( $images ) {
				echo wp_get_attachment_image(
					$images[0],
					$size,
					false,
					array(
						'class'    => 'wcw-qv-img',
						'alt'      => $title,
						'sizes'    => '(max-width: 900px) 100vw, 55vw',
						'decoding' => 'async',
						'data-wcw-stage-img' => '',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<span class="wcw-media-placeholder" aria-hidden="true"></span>';
			}
			?>
		</div>

		<?php if ( $thumbs ) : ?>
			<div class="wcw-qv-thumbs" role="group" aria-label="<?php esc_attr_e( 'Images', 'woocommerce-works' ); ?>">
				<?php foreach ( $images as $index => $image_id ) : ?>
					<?php
					$full   = wp_get_attachment_image_url( $image_id, $size );
					$srcset = wp_get_attachment_image_srcset( $image_id, $size );
					?>
					<button type="button"
						class="wcw-qv-thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
						data-src="<?php echo esc_url( $full ); ?>"
						data-srcset="<?php echo esc_attr( $srcset ? $srcset : '' ); ?>"
						data-alt="<?php echo esc_attr( $title ); ?>"
						aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>">
						<?php echo wp_get_attachment_image( $image_id, 'woocommerce_gallery_thumbnail', false, array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="screen-reader-text">
							<?php
							/* translators: %d: image number. */
							echo esc_html( sprintf( __( 'View image %d', 'woocommerce-works' ), $index + 1 ) );
							?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="wcw-qv-info">
		<h2 class="wcw-qv-title"><?php echo esc_html( $title ); ?></h2>

		<?php
		echo WCW_Render::badge_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo WCW_Render::detail_lines_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo WCW_Render::buy_html( $product, 'quick' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>

		<?php if ( $description ) : ?>
			<p class="wcw-qv-desc"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>

		<a class="wcw-qv-permalink" href="<?php echo esc_url( $permalink ); ?>">
			<?php esc_html_e( 'View full work', 'woocommerce-works' ); ?>
			<span aria-hidden="true">&rarr;</span>
		</a>
	</div>
</div>
