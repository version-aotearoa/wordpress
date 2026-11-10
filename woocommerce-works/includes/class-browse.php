<?php
defined( 'ABSPATH' ) || exit;

/**
 * Single product page: detail lines, status badge, enquiry fallback, uncropped
 * gallery sizing and the related works grid.
 */
class WCW_Browse {

	public function __construct() {
		add_filter( 'woocommerce_gallery_image_size', array( $this, 'gallery_image_size' ) );
		add_action( 'wp', array( $this, 'related_hooks' ), 20 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'summary_details' ), 6 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'summary_enquiry' ), 31 );
	}

	public function gallery_image_size( $size ) {
		$configured = WCW_Plugin::get_setting( 'large_image_size', '' );
		return $configured ? $configured : $size;
	}

	public function related_hooks() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
		if ( ! WCW_Plugin::get_setting( 'restyle_related', 1 ) ) {
			return;
		}
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'related' ), 20 );
	}

	public function related() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		echo WCW_Render::related( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function summary_details() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$badge = WCW_Render::badge_html( $product );
		$lines = WCW_Render::detail_lines_html( $product );

		if ( '' === $badge && '' === $lines ) {
			return;
		}

		echo '<div class="wcw-details">' . $badge . $lines . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function summary_enquiry() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		if ( WCW_Work_Data::has_price( $product ) ) {
			return;
		}

		$url = WCW_Work_Data::enquiry_url( $product );
		if ( ! $url ) {
			return;
		}

		echo '<a class="wcw-button wcw-enquire wcw-enquire--single" href="' . esc_url( $url ) . '">'
			. esc_html( WCW_Work_Data::enquiry_label() )
			. '</a>';
	}
}
