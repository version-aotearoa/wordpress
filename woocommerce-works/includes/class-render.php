<?php
defined( 'ABSPATH' ) || exit;

/**
 * Markup for the works grid, cards, quick view and related works.
 */
class WCW_Render {

	/**
	 * Load a plugin template, letting a theme override it at
	 * yourtheme/woocommerce-works/{name}.
	 *
	 * @param string $name Relative template path, e.g. "parts/card.php".
	 * @param array  $args Variables made available to the template.
	 */
	public static function template( $name, $args = array() ) {
		$file = locate_template( array( 'woocommerce-works/' . $name ) );
		if ( ! $file ) {
			$file = WCW_PATH . 'templates/' . $name;
		}
		if ( ! file_exists( $file ) ) {
			return;
		}
		load_template( $file, false, $args );
	}

	public static function template_html( $name, $args = array() ) {
		ob_start();
		self::template( $name, $args );
		return (string) ob_get_clean();
	}

	/**
	 * Render a single card.
	 */
	public static function card( $product, $args = array() ) {
		$args['product'] = $product;
		return self::template_html( 'parts/card.php', $args );
	}

	/**
	 * Render cards for a query.
	 *
	 * @param WP_Query $query Query of product IDs.
	 * @param array    $args  Extra card args.
	 */
	public static function cards( $query, $args = array() ) {
		$html = '';
		$i    = 0;

		foreach ( (array) $query->posts as $post ) {
			$product = wc_get_product( $post instanceof WP_Post ? $post->ID : (int) $post );
			if ( ! $product ) {
				continue;
			}
			$card_args          = $args;
			$card_args['first'] = ( 0 === $i );
			$html              .= self::card( $product, $card_args );
			$i++;
		}

		return $html;
	}

	public static function count_text( $shown, $total ) {
		return sprintf(
			/* translators: 1: number of works shown, 2: total number of works. */
			_n( '%1$s of %2$s work', '%1$s of %2$s works', (int) $total, 'woocommerce-works' ),
			number_format_i18n( (int) $shown ),
			number_format_i18n( (int) $total )
		);
	}

	/**
	 * CSS custom properties for the grid.
	 */
	public static function grid_style( $columns, $tablet, $mobile, $gap = null ) {
		$style = sprintf(
			'--wcw-cols:%1$d;--wcw-cols-md:%2$d;--wcw-cols-sm:%3$d;',
			max( 1, (int) $columns ),
			max( 1, (int) $tablet ),
			max( 1, (int) $mobile )
		);
		if ( null !== $gap && (int) $gap >= 0 ) {
			$style .= sprintf( '--wcw-gap:%dpx;', (int) $gap );
		}
		return $style;
	}

	public static function base_url( $base = '' ) {
		$base = $base ? $base : get_permalink();
		if ( ! $base ) {
			$base = home_url( '/' );
		}
		return remove_query_arg( array( 'wcw_cat', 'wcw_medium', 'wcw_year', 'wcw_page' ), $base );
	}

	/**
	 * Facet chips: category, then each enabled attribute facet.
	 *
	 * @param array  $filters         Complete filter set.
	 * @param array  $facets          Rows from WCW_Query::facets().
	 * @param string $base            Base URL for chip links.
	 * @param bool   $show_categories Whether to render the category row.
	 */
	public static function filters_html( $filters, $facets, $base = '', $show_categories = true ) {
		$base = self::base_url( $base );
		$rows = array();

		if ( $show_categories && ! empty( $facets['cat'] ) && count( $facets['cat']['terms'] ) >= 1 ) {
			$rows['cat'] = $facets['cat'];
		}

		foreach ( WCW_Query::FACET_SLOTS as $slot ) {
			if ( empty( $facets[ $slot ] ) ) {
				continue;
			}
			$row     = $facets[ $slot ];
			$options = count( $row['terms'] ) + ( $row['none'] ? 1 : 0 );
			if ( $options < 1 ) {
				continue;
			}
			$rows[ $slot ] = $row;
		}

		if ( ! $rows ) {
			return '';
		}

		$html = '<div class="wcw-facets">';

		foreach ( $rows as $slot => $row ) {
			$active = isset( $filters[ $slot ] ) ? $filters[ $slot ] : '';
			$html  .= '<div class="wcw-facet" data-facet="' . esc_attr( $slot ) . '">';
			$html  .= '<span class="wcw-facet-label" id="wcw-facet-' . esc_attr( $slot ) . '">' . esc_html( $row['label'] ) . '</span>';
			$html  .= '<div class="wcw-chips" role="group" aria-labelledby="wcw-facet-' . esc_attr( $slot ) . '">';
			$html  .= self::chip( $slot, '', __( 'All', 'woocommerce-works' ), (int) $row['total'], '' === $active, $filters, $base );

			foreach ( $row['terms'] as $term ) {
				$html .= self::chip( $slot, $term['slug'], $term['name'], (int) $term['count'], ! empty( $term['active'] ), $filters, $base );
			}

			if ( ! empty( $row['none'] ) ) {
				$html .= self::chip( $slot, WCW_Query::NONE, __( 'Not specified', 'woocommerce-works' ), (int) $row['none']['count'], ! empty( $row['none']['active'] ), $filters, $base );
			}

			$html .= '</div></div>';
		}

		$html .= '</div>';
		return $html;
	}

	private static function chip( $facet, $value, $label, $count, $active, $filters, $base ) {
		$url  = WCW_Query::filter_url( $facet, $value, $filters, $base );
		$html = '<a class="wcw-chip' . ( $active ? ' is-active' : '' ) . '"'
			. ' href="' . esc_url( $url ) . '"'
			. ' data-facet="' . esc_attr( $facet ) . '"'
			. ' data-value="' . esc_attr( $value ) . '"'
			. ( $active ? ' aria-current="true"' : '' ) . '>';
		$html .= esc_html( $label );
		if ( $count > 0 ) {
			$html .= ' <span class="wcw-chip-count">' . esc_html( number_format_i18n( $count ) ) . '</span>';
		}
		return $html . '</a>';
	}

	/**
	 * The whole works section: chips, grid, count, load more and pagination.
	 *
	 * @param array $args {
	 *     @type string $cat        Category slug override.
	 *     @type int    $page       Page override.
	 *     @type int    $per_page   Per page override.
	 *     @type string $base       Base URL for chips/pagination.
	 *     @type string $class      Extra wrapper classes.
	 *     @type bool   $show_chips Whether to render category chips.
	 * }
	 */
	public static function works_section( $args = array() ) {
		$filters    = WCW_Query::normalize_filters( $args );
		$page       = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : WCW_Query::requested_page();
		$per_page   = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : (int) WCW_Plugin::get_setting( 'per_page', 24 );
		$per_page   = max( 1, min( 100, $per_page ) );
		$base       = self::base_url( isset( $args['base'] ) ? $args['base'] : '' );
		$show_chips = ! isset( $args['show_chips'] ) || $args['show_chips'];

		$columns = (int) WCW_Plugin::get_setting( 'columns', 3 );
		$tablet  = (int) WCW_Plugin::get_setting( 'columns_tablet', 2 );
		$mobile  = (int) WCW_Plugin::get_setting( 'columns_mobile', 1 );
		$gap     = (int) WCW_Plugin::get_setting( 'gap', 24 );

		$query_args             = $args;
		$query_args['filters']  = $filters;
		$query_args['page']     = $page;
		$query_args['per_page'] = $per_page;
		unset( $query_args['unlimited'] );

		$query  = WCW_Query::run( $query_args );
		$facets = WCW_Query::facets( $filters, $args );

		$total = (int) $query->found_posts;
		$shown = min( $total, ( $page - 1 ) * $per_page + $query->post_count );

		$pagination_args = array();
		foreach ( WCW_Query::PARAMS as $slot => $param ) {
			if ( ! empty( $filters[ $slot ] ) ) {
				$pagination_args[ $param ] = $filters[ $slot ];
			}
		}

		$classes = 'wcw-works';
		if ( ! empty( $args['class'] ) ) {
			$classes .= ' ' . $args['class'];
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>"
			data-wcw
			data-base="<?php echo esc_url( $base ); ?>"
			data-filters="<?php echo esc_attr( (string) wp_json_encode( $filters ) ); ?>"
			data-chips="<?php echo $show_chips ? '1' : '0'; ?>"
			data-page="<?php echo esc_attr( $page ); ?>"
			data-more="<?php echo $query->max_num_pages > $page ? '1' : '0'; ?>"
			data-per-page="<?php echo esc_attr( max( 1, min( 100, $per_page ) ) ); ?>"
			data-cols="<?php echo esc_attr( $columns ); ?>"
			data-cols-md="<?php echo esc_attr( $tablet ); ?>"
			data-cols-sm="<?php echo esc_attr( $mobile ); ?>"
			data-gap="<?php echo esc_attr( $gap ); ?>"
			style="<?php echo esc_attr( self::grid_style( $columns, $tablet, $mobile, $gap ) ); ?>">

			<div class="wcw-works-head">
				<?php echo self::filters_html( $filters, $facets, $base, $show_chips ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="wcw-count" data-wcw-count aria-live="polite"><?php echo esc_html( self::count_text( $shown, $total ) ); ?></p>
			</div>

			<div class="wcw-grid" data-wcw-grid>
				<?php echo self::cards( $query ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="wcw-empty" data-wcw-empty<?php echo $query->have_posts() ? ' hidden' : ''; ?>>
				<p><?php esc_html_e( 'No works found.', 'woocommerce-works' ); ?></p>
			</div>

			<div class="wcw-actions">
				<button type="button" class="wcw-load-more" data-wcw-more hidden>
					<?php esc_html_e( 'Load more', 'woocommerce-works' ); ?>
				</button>
				<?php
				if ( $query->max_num_pages > 1 ) {
					$links = paginate_links(
						array(
							'base'      => trailingslashit( $base ) . '%_%',
							'format'    => '?wcw_page=%#%',
							'current'   => $page,
							'total'     => (int) $query->max_num_pages,
							'type'      => 'list',
							'prev_text' => '&lsaquo;',
							'next_text' => '&rsaquo;',
							'add_args'  => $pagination_args ? $pagination_args : false,
						)
					);
					if ( $links ) {
						echo '<nav class="wcw-pagination" aria-label="' . esc_attr__( 'Works pages', 'woocommerce-works' ) . '">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				}
				?>
			</div>

			<?php if ( WCW_Plugin::get_setting( 'quick_view', 1 ) ) : ?>
				<?php self::template( 'parts/quick-view-dialog.php' ); ?>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Body of the quick view dialog for one product.
	 */
	public static function quick_view_content( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		return self::template_html( 'parts/quick-view-content.php', array( 'product' => $product ) );
	}

	/**
	 * Related works grid for a product page.
	 */
	public static function related( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$count = max( 1, min( 12, (int) WCW_Plugin::get_setting( 'related_count', 4 ) ) );
		$ids   = wc_get_related_products( $product->get_id(), $count );
		if ( empty( $ids ) ) {
			return '';
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'post__in'       => array_map( 'absint', $ids ),
				'orderby'        => 'post__in',
				'posts_per_page' => $count,
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return '';
		}

		$columns = max( 1, min( 4, (int) $query->post_count ) );

		ob_start();
		?>
		<section class="wcw-related" aria-labelledby="wcw-related-title">
			<h2 class="wcw-related-title" id="wcw-related-title"><?php esc_html_e( 'Related works', 'woocommerce-works' ); ?></h2>
			<div class="wcw-grid wcw-grid--related" style="<?php echo esc_attr( self::grid_style( $columns, min( 2, $columns ), 1 ) ); ?>">
				<?php echo self::cards( $query, array( 'quick_view' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Detail lines shared by the quick view and the single product page.
	 */
	public static function detail_lines_html( $product ) {
		if ( ! WCW_Plugin::get_setting( 'show_attribute_lines', 1 ) ) {
			return '';
		}
		$lines = WCW_Work_Data::lines( $product );
		if ( empty( $lines ) ) {
			return '';
		}

		$html = '<ul class="wcw-lines">';
		foreach ( $lines as $line ) {
			$html .= '<li class="wcw-line wcw-line--' . esc_attr( $line['key'] ) . '">' . esc_html( $line['value'] ) . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	public static function badge_html( $product ) {
		$label = WCW_Work_Data::badge( $product );
		if ( '' === $label ) {
			return '';
		}
		$status = WCW_Work_Data::status( $product );
		return '<span class="wcw-badge wcw-badge--' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Price + add to cart, or the enquiry button.
	 *
	 * @param WC_Product $product  Product.
	 * @param string     $context  card|quick|single.
	 */
	public static function buy_html( $product, $context = 'quick' ) {
		$has_price = WCW_Work_Data::has_price( $product );
		$enquiry   = $has_price ? '' : WCW_Work_Data::enquiry_url( $product );
		$html      = '<div class="wcw-buy wcw-buy--' . esc_attr( $context ) . '">';

		if ( $has_price ) {
			$html .= '<div class="wcw-price">' . WCW_Work_Data::price_html( $product ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( 'quick' === $context && WCW_Plugin::get_setting( 'quick_view_add_to_cart', 1 ) && $product->is_type( 'simple' ) ) {
				$html .= '<form class="wcw-atc" method="get" action="' . esc_url( get_permalink( $product->get_id() ) ) . '" data-wcw-atc data-product-id="' . esc_attr( $product->get_id() ) . '">';
				$html .= '<input type="hidden" name="add-to-cart" value="' . esc_attr( $product->get_id() ) . '">';
				$html .= '<button type="submit" class="wcw-button wcw-atc-button">' . esc_html__( 'Add to cart', 'woocommerce-works' ) . '</button>';
				$html .= '<p class="wcw-atc-note" aria-live="polite"></p>';
				$html .= '</form>';
			} elseif ( 'quick' === $context ) {
				$html .= '<a class="wcw-button" href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html__( 'Choose options', 'woocommerce-works' ) . '</a>';
			}
		} elseif ( $enquiry ) {
			$html .= '<a class="wcw-button wcw-enquire" href="' . esc_url( $enquiry ) . '">' . esc_html( WCW_Work_Data::enquiry_label() ) . '</a>';
		}

		$html .= '</div>';
		return $html;
	}
}
