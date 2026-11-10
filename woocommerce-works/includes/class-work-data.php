<?php
defined( 'ABSPATH' ) || exit;

/**
 * Reads work details off a WC_Product. No custom product meta is stored: every
 * line comes from a mapped WooCommerce attribute.
 */
class WCW_Work_Data {

	/**
	 * Value of a mapped attribute slot.
	 *
	 * @param WC_Product $product Product.
	 * @param string     $slot    year|medium|dimensions|status.
	 * @return string
	 */
	public static function attribute_value( $product, $slot ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$map = WCW_Plugin::attribute_map();
		$slug = isset( $map[ $slot ] ) ? (string) $map[ $slot ] : '';
		if ( '' === $slug ) {
			return '';
		}

		$attributes = $product->get_attributes();
		if ( empty( $attributes ) ) {
			return '';
		}

		$keys = array( $slug );
		if ( 0 === strpos( $slug, 'pa_' ) ) {
			$keys[] = substr( $slug, 3 );
		} else {
			$keys[] = 'pa_' . $slug;
		}

		$attribute = null;
		foreach ( $keys as $key ) {
			if ( isset( $attributes[ $key ] ) ) {
				$attribute = $attributes[ $key ];
				break;
			}
		}
		if ( ! $attribute ) {
			return '';
		}

		if ( $attribute->is_taxonomy() ) {
			$names = wc_get_product_terms(
				$product->get_id(),
				$attribute->get_name(),
				array( 'fields' => 'names' )
			);
			$value = is_array( $names ) ? implode( ', ', $names ) : '';
		} else {
			$options = $attribute->get_options();
			$value   = is_array( $options ) ? implode( ', ', $options ) : (string) $options;
		}

		return trim( wp_strip_all_tags( $value ) );
	}

	/**
	 * Display lines for the mapped detail attributes, in a fixed order.
	 *
	 * @param WC_Product $product Product.
	 * @return array[] Each item: array( 'key' => slug, 'value' => string ).
	 */
	public static function lines( $product ) {
		$lines = array();
		foreach ( array( 'year', 'medium', 'dimensions' ) as $slot ) {
			$value = self::attribute_value( $product, $slot );
			if ( '' === $value ) {
				continue;
			}
			$lines[] = array(
				'key'   => $slot,
				'value' => $value,
			);
		}
		return $lines;
	}

	/**
	 * Normalised availability state.
	 *
	 * @param WC_Product $product Product.
	 * @return string sold|reserved|enquire|available|other|''
	 */
	public static function status( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$raw   = self::attribute_value( $product, 'status' );
		$value = strtolower( trim( $raw ) );

		if ( '' !== $value ) {
			$sold     = strtolower( trim( (string) WCW_Plugin::get_setting( 'status_sold', 'sold' ) ) );
			$reserved = strtolower( trim( (string) WCW_Plugin::get_setting( 'status_reserved', 'reserved' ) ) );

			if ( $sold && $value === $sold ) {
				return 'sold';
			}
			if ( $reserved && $value === $reserved ) {
				return 'reserved';
			}
			if ( false !== strpos( $value, 'sold' ) ) {
				return 'sold';
			}
			if ( false !== strpos( $value, 'reserv' ) ) {
				return 'reserved';
			}
			if ( false !== strpos( $value, 'enquir' ) || false !== strpos( $value, 'inquir' ) ) {
				return 'enquire';
			}
			if ( false !== strpos( $value, 'available' ) ) {
				return 'available';
			}
			return 'other';
		}

		if ( WCW_Plugin::get_setting( 'stock_as_sold', 1 ) && $product->is_purchasable() && ! $product->is_in_stock() ) {
			return 'sold';
		}

		return '';
	}

	public static function status_label( $status ) {
		switch ( $status ) {
			case 'sold':
				return __( 'Sold', 'woocommerce-works' );
			case 'reserved':
				return __( 'Reserved', 'woocommerce-works' );
			case 'available':
				return __( 'Available', 'woocommerce-works' );
		}
		return '';
	}

	/**
	 * Badge text for the card / quick view. Only sold and reserved are treated
	 * as badges; other status values are left alone.
	 */
	public static function badge( $product ) {
		if ( ! WCW_Plugin::get_setting( 'show_badge', 1 ) ) {
			return '';
		}
		$status = self::status( $product );
		if ( 'sold' !== $status && 'reserved' !== $status ) {
			return '';
		}
		return (string) apply_filters( 'wcw_status_label', self::status_label( $status ), $status, $product );
	}

	/**
	 * A product is "for sale" only when it has a price and is not sold or
	 * reserved. Everything else gets the enquiry button instead.
	 */
	public static function has_price( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}
		$status = self::status( $product );
		if ( 'sold' === $status || 'reserved' === $status ) {
			return false;
		}
		if ( ! $product->is_purchasable() ) {
			return false;
		}
		$price = $product->get_price();
		return '' !== $price && null !== $price;
	}

	public static function price_html( $product ) {
		if ( ! self::has_price( $product ) ) {
			return '';
		}
		$html = $product->get_price_html();
		return $html ? $html : '';
	}

	/**
	 * Gallery attachment IDs, featured image first.
	 *
	 * @return int[]
	 */
	public static function images( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return array();
		}
		$ids = array();

		$featured = (int) $product->get_image_id();
		if ( $featured ) {
			$ids[] = $featured;
		}
		foreach ( (array) $product->get_gallery_image_ids() as $id ) {
			$id = (int) $id;
			if ( $id && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * Enquiry target for a work: a contact page URL, a mailto: link, or a bare
	 * email address. Empty when nothing is configured.
	 */
	public static function enquiry_url( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$target = trim( (string) WCW_Plugin::get_setting( 'enquiry_url', '' ) );
		if ( '' === $target ) {
			return '';
		}

		$title   = $product->get_name();
		$subject = str_replace( '{title}', $title, (string) WCW_Plugin::get_setting( 'enquiry_subject', 'Enquiry: {title}' ) );

		if ( preg_match( '/^mailto:/i', $target ) || is_email( $target ) ) {
			$base = is_email( $target ) ? 'mailto:' . $target : $target;
			$sep  = ( false === strpos( $base, '?' ) ) ? '?' : '&';
			return $base . $sep . 'subject=' . rawurlencode( $subject )
				. '&body=' . rawurlencode( $title . ' — ' . get_permalink( $product->get_id() ) );
		}

		return str_replace( '{title}', rawurlencode( $title ), $target );
	}

	public static function enquiry_label() {
		$label = trim( (string) WCW_Plugin::get_setting( 'enquiry_label', '' ) );
		return '' !== $label ? $label : __( 'Enquire', 'woocommerce-works' );
	}

	/**
	 * Image sizes offered in settings.
	 *
	 * @return array size => label
	 */
	public static function image_size_choices() {
		$sizes = array(
			'woocommerce_single'    => __( 'WooCommerce single (uncropped)', 'woocommerce-works' ),
			'woocommerce_thumbnail' => __( 'WooCommerce thumbnail (cropped square)', 'woocommerce-works' ),
			'large'                 => __( 'Large', 'woocommerce-works' ),
			'full'                  => __( 'Full', 'woocommerce-works' ),
		);
		foreach ( get_intermediate_image_sizes() as $size ) {
			if ( ! isset( $sizes[ $size ] ) ) {
				$sizes[ $size ] = $size;
			}
		}
		return $sizes;
	}
}
