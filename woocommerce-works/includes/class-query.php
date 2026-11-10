<?php
defined( 'ABSPATH' ) || exit;

/**
 * Product queries and the filter facets (category, medium, year).
 */
class WCW_Query {

	/** Attribute slots that can become filter facets (in display order). */
	const FACET_SLOTS = array( 'medium', 'year' );

	/** Sentinel for "no value assigned". */
	const NONE = '_none';

	/** URL parameter per facet. */
	const PARAMS = array(
		'cat'    => 'wcw_cat',
		'medium' => 'wcw_medium',
		'year'   => 'wcw_year',
	);

	/**
	 * Normalise ordering settings to a WP_Query orderby/order pair.
	 */
	public static function order( $orderby = null, $order = null ) {
		$orderby = $orderby ? $orderby : WCW_Plugin::get_setting( 'orderby', 'menu_order' );
		$order   = $order ? $order : WCW_Plugin::get_setting( 'order', 'asc' );

		$allowed = array( 'menu_order', 'date', 'title', 'rand', 'modified' );
		if ( ! in_array( $orderby, $allowed, true ) ) {
			$orderby = 'menu_order';
		}

		$order = ( 'desc' === strtolower( (string) $order ) ) ? 'DESC' : 'ASC';

		if ( 'menu_order' === $orderby ) {
			return array(
				'orderby' => array(
					'menu_order' => $order,
					'title'      => 'ASC',
				),
			);
		}

		return array(
			'orderby' => $orderby,
			'order'   => $order,
		);
	}

	/**
	 * Facets that are both mapped to an attribute and switched on in settings.
	 *
	 * @return array slot => array( slot, taxonomy, label )
	 */
	public static function enabled_facets() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$map      = WCW_Plugin::attribute_map();
		$settings = WCW_Plugin::get_settings();
		$enabled  = isset( $settings['filter_attributes'] ) ? (array) $settings['filter_attributes'] : array();
		$facets   = array();

		foreach ( self::FACET_SLOTS as $slot ) {
			if ( empty( $enabled[ $slot ] ) || empty( $map[ $slot ] ) ) {
				continue;
			}
			$taxonomy = $map[ $slot ];
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$facets[ $slot ] = array(
				'slot'     => $slot,
				'taxonomy' => $taxonomy,
				'label'    => self::facet_label( $slot, $taxonomy ),
			);
		}

		$cache = $facets;
		return $facets;
	}

	public static function facet_label( $slot, $taxonomy ) {
		if ( function_exists( 'wc_attribute_label' ) ) {
			$label = wc_attribute_label( $taxonomy );
			if ( $label ) {
				return $label;
			}
		}
		return ucfirst( $slot );
	}

	/**
	 * Validate one facet value against a taxonomy. Returns a term slug, NONE, or ''.
	 */
	public static function sanitize_facet_value( $slot, $value ) {
		$value = is_string( $value ) ? wp_unslash( $value ) : '';

		if ( self::NONE === $value ) {
			return self::NONE;
		}

		$value = sanitize_title( $value );
		if ( '' === $value ) {
			return '';
		}

		$facets = self::enabled_facets();
		if ( ! isset( $facets[ $slot ] ) ) {
			return '';
		}

		return term_exists( $value, $facets[ $slot ]['taxonomy'] ) ? $value : '';
	}

	/**
	 * Filters from the current request.
	 *
	 * @return array cat|medium|year => slug or ''
	 */
	public static function requested_filters() {
		$filters = array( 'cat' => self::requested_cat() );

		foreach ( self::FACET_SLOTS as $slot ) {
			$param = self::PARAMS[ $slot ];
			$raw   = isset( $_GET[ $param ] ) ? wp_unslash( $_GET[ $param ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filters[ $slot ] = self::sanitize_facet_value( $slot, is_string( $raw ) ? $raw : '' );
		}

		return $filters;
	}

	/**
	 * Requested category from the URL, validated against real categories.
	 */
	public static function requested_cat() {
		if ( ! isset( $_GET['wcw_cat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}
		$slug = sanitize_title( wp_unslash( $_GET['wcw_cat'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return self::has_category( $slug ) ? $slug : '';
	}

	public static function requested_page() {
		if ( ! isset( $_GET['wcw_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 1;
		}
		return max( 1, absint( wp_unslash( $_GET['wcw_page'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Merge explicit render args with the request into a complete filter set.
	 */
	public static function normalize_filters( $args = array() ) {
		$filters = isset( $args['filters'] ) && is_array( $args['filters'] ) ? $args['filters'] : array();

		if ( array_key_exists( 'cat', $filters ) ) {
			$filters['cat'] = self::has_category( $filters['cat'] ) ? sanitize_title( $filters['cat'] ) : '';
		} elseif ( array_key_exists( 'cat', $args ) ) {
			$filters['cat'] = self::has_category( $args['cat'] ) ? sanitize_title( $args['cat'] ) : '';
		} else {
			$filters['cat'] = self::requested_cat();
		}

		$requested = null;
		foreach ( self::FACET_SLOTS as $slot ) {
			if ( array_key_exists( $slot, $filters ) ) {
				$filters[ $slot ] = self::sanitize_facet_value( $slot, (string) $filters[ $slot ] );
				continue;
			}
			if ( null === $requested ) {
				$requested = self::requested_filters();
			}
			$filters[ $slot ] = $requested[ $slot ];
		}

		return $filters;
	}

	/**
	 * Build WP_Query arguments for a works grid.
	 *
	 * @param array $args {
	 *     @type string $cat       product_cat slug.
	 *     @type array  $filters   cat|medium|year => slug.
	 *     @type int    $page      1-based page.
	 *     @type int    $per_page  Items per page.
	 *     @type bool   $unlimited No paging (used for facet counts).
	 *     @type string $orderby   menu_order|date|title|rand|modified.
	 *     @type string $order     asc|desc.
	 * }
	 */
	public static function args( $args = array() ) {
		$filters = self::normalize_filters( $args );

		$per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : (int) WCW_Plugin::get_setting( 'per_page', 24 );
		$per_page = max( 1, min( 100, $per_page ) );
		$page     = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;

		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
			'fields'              => 'ids',
		);

		if ( ! empty( $args['unlimited'] ) ) {
			$query_args['posts_per_page'] = -1;
			unset( $query_args['paged'] );
			$query_args['no_found_rows'] = true;
		}

		$query_args = array_merge(
			$query_args,
			self::order(
				isset( $args['orderby'] ) ? $args['orderby'] : null,
				isset( $args['order'] ) ? $args['order'] : null
			)
		);

		$tax_query = array();

		if ( WCW_Plugin::get_setting( 'respect_visibility', 1 ) && function_exists( 'wc_get_product_visibility_term_ids' ) ) {
			$visibility = wc_get_product_visibility_term_ids();
			$exclude    = array();
			if ( ! empty( $visibility['exclude-from-catalog'] ) ) {
				$exclude[] = $visibility['exclude-from-catalog'];
			}
			if ( WCW_Plugin::get_setting( 'hide_out_of_stock', 0 ) && ! empty( $visibility['outofstock'] ) ) {
				$exclude[] = $visibility['outofstock'];
			}
			if ( $exclude ) {
				$tax_query[] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $exclude,
					'operator' => 'NOT IN',
				);
			}
		}

		if ( '' !== $filters['cat'] ) {
			$tax_query[] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'slug',
				'terms'            => array( $filters['cat'] ),
				'include_children' => true,
				'operator'         => 'IN',
			);
		}

		$facets = self::enabled_facets();
		foreach ( $facets as $slot => $facet ) {
			$value = isset( $filters[ $slot ] ) ? $filters[ $slot ] : '';
			if ( '' === $value ) {
				continue;
			}

			if ( self::NONE === $value ) {
				$term_ids = get_terms(
					array(
						'taxonomy'   => $facet['taxonomy'],
						'hide_empty' => false,
						'fields'     => 'ids',
					)
				);
				if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
					continue;
				}
				$tax_query[] = array(
					'taxonomy' => $facet['taxonomy'],
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', $term_ids ),
					'operator' => 'NOT IN',
				);
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $facet['taxonomy'],
				'field'    => 'slug',
				'terms'    => array( $value ),
				'operator' => 'IN',
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		if ( $tax_query ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		/**
		 * Filter the works grid query arguments.
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param array $args       Raw render arguments.
		 */
		return apply_filters( 'wcw_query_args', $query_args, $args );
	}

	public static function run( $args = array() ) {
		return new WP_Query( self::args( $args ) );
	}

	/**
	 * IDs matching a filter set (no paging).
	 *
	 * @return int[]
	 */
	private static function ids( $filters, $base_args = array() ) {
		$args                   = $base_args;
		$args['filters']        = $filters;
		$args['unlimited']      = true;
		$args['page']           = 1;
		$query                  = new WP_Query( self::args( $args ) );
		return array_map( 'intval', $query->posts );
	}

	/**
	 * Tally term assignments for a set of product IDs.
	 *
	 * @return array{counts: array<string,int>, with: int}
	 */
	private static function term_counts( $ids, $taxonomy ) {
		$counts = array();
		$seen   = array();

		if ( empty( $ids ) ) {
			return array( 'counts' => $counts, 'with' => 0 );
		}

		$terms = wp_get_object_terms( $ids, $taxonomy, array( 'fields' => 'all_with_object_id' ) );
		if ( is_wp_error( $terms ) ) {
			return array( 'counts' => $counts, 'with' => 0 );
		}

		foreach ( $terms as $term ) {
			$counts[ $term->slug ] = isset( $counts[ $term->slug ] ) ? $counts[ $term->slug ] + 1 : 1;
			$seen[ $term->object_id ] = true;
		}

		return array(
			'counts' => $counts,
			'with'   => count( $seen ),
		);
	}

	/**
	 * Facet rows with counts that respect the other active filters.
	 *
	 * @param array $filters   Complete filter set.
	 * @param array $base_args Extra args (e.g. a locked category) for the count queries.
	 * @return array slot => row
	 */
	public static function facets( $filters = null, $base_args = array() ) {
		$filters = null === $filters ? self::requested_filters() : $filters;
		$rows    = array();

		/* Categories. */
		$other = $filters;
		unset( $other['cat'] );
		$ids    = self::ids( $other, $base_args );
		$counts = self::term_counts( $ids, 'product_cat' );
		$terms  = array();

		foreach ( self::categories() as $term ) {
			$count = isset( $counts['counts'][ $term->slug ] ) ? (int) $counts['counts'][ $term->slug ] : 0;
			$is_active = isset( $filters['cat'] ) && $filters['cat'] === $term->slug;
			if ( ! $count && ! $is_active ) {
				continue;
			}
			$terms[] = array(
				'slug'   => $term->slug,
				'name'   => $term->name,
				'count'  => $count,
				'active' => $is_active,
			);
		}
		usort(
			$terms,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		$rows['cat'] = array(
			'slot'  => 'cat',
			'label' => __( 'Category', 'woocommerce-works' ),
			'terms' => $terms,
			'none'  => null,
			'total' => count( $ids ),
		);

		/* Attribute facets. */
		foreach ( self::enabled_facets() as $slot => $facet ) {
			$other = $filters;
			unset( $other[ $slot ] );

			$ids    = self::ids( $other, $base_args );
			$counts = self::term_counts( $ids, $facet['taxonomy'] );

			$all_terms = get_terms(
				array(
					'taxonomy'   => $facet['taxonomy'],
					'hide_empty' => false,
				)
			);
			if ( is_wp_error( $all_terms ) ) {
				$all_terms = array();
			}

			$terms = array();
			foreach ( $all_terms as $term ) {
				$count     = isset( $counts['counts'][ $term->slug ] ) ? (int) $counts['counts'][ $term->slug ] : 0;
				$is_active = isset( $filters[ $slot ] ) && $filters[ $slot ] === $term->slug;
				if ( ! $count && ! $is_active ) {
					continue;
				}
				$terms[] = array(
					'slug'   => $term->slug,
					'name'   => $term->name,
					'count'  => $count,
					'active' => $is_active,
				);
			}
			usort(
				$terms,
				static function ( $a, $b ) {
					return strcasecmp( $a['name'], $b['name'] );
				}
			);

			$none_count = max( 0, count( $ids ) - (int) $counts['with'] );
			$none_active = isset( $filters[ $slot ] ) && self::NONE === $filters[ $slot ];

			$rows[ $slot ] = array(
				'slot'  => $slot,
				'label' => $facet['label'],
				'terms' => $terms,
				'none'  => ( $none_count > 0 || $none_active )
					? array(
						'count'  => $none_count,
						'active' => $none_active,
					)
					: null,
				'total' => count( $ids ),
			);
		}

		return $rows;
	}

	/**
	 * Product categories that actually contain products, minus the store's
	 * default "Uncategorised" term.
	 *
	 * @return WP_Term[]
	 */
	public static function categories() {
		$cached = get_transient( 'wcw_categories' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$default = (int) get_option( 'default_product_cat', 0 );
		$out     = array();
		foreach ( $terms as $term ) {
			if ( $default && (int) $term->term_id === $default ) {
				continue;
			}
			if ( 0 === (int) $term->count ) {
				continue;
			}
			$out[] = $term;
		}

		set_transient( 'wcw_categories', $out, 10 * MINUTE_IN_SECONDS );
		return $out;
	}

	public static function has_category( $slug ) {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return false;
		}
		foreach ( self::categories() as $term ) {
			if ( $term->slug === $slug ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * URL for a facet chip: changes one facet, keeps the others.
	 */
	public static function filter_url( $facet, $value, $filters, $base ) {
		$params = array();

		foreach ( self::PARAMS as $slot => $param ) {
			$current = $slot === $facet ? $value : ( isset( $filters[ $slot ] ) ? $filters[ $slot ] : '' );
			if ( '' !== $current ) {
				$params[ $param ] = $current;
			}
		}

		$base = $base ? $base : home_url( '/' );
		return $params ? add_query_arg( $params, $base ) : $base;
	}
}
