<?php
defined( 'ABSPATH' ) || exit;

/**
 * Works page template, shortcode and front-end assets.
 */
class WCW_Template {

	const TEMPLATE = 'woocommerce-works/works-page.php';

	public function __construct() {
		add_filter( 'theme_page_templates', array( $this, 'register' ) );
		add_filter( 'template_include', array( $this, 'route' ) );
		add_shortcode( 'works_grid', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	public function register( $templates ) {
		$templates[ self::TEMPLATE ] = __( 'Works', 'woocommerce-works' );
		return $templates;
	}

	public function route( $template ) {
		if ( is_page() && get_page_template_slug() === self::TEMPLATE ) {
			$file = locate_template( array( 'woocommerce-works/works-page.php' ) );
			if ( ! $file ) {
				$file = WCW_PATH . 'templates/works-page.php';
			}
			if ( file_exists( $file ) ) {
				return $file;
			}
		}
		return $template;
	}

	/**
	 * [works_grid cat="" per_page="" chips=""]
	 *
	 * A category-scoped grid hides the filter chips by default; pass chips="1"
	 * to show them.
	 */
	public function shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'cat'      => '',
				'per_page' => '',
				'chips'    => '',
			),
			$atts,
			'works_grid'
		);

		$cat = sanitize_title( $atts['cat'] );

		// A category-scoped grid hides the chips unless chips="1" is explicit.
		$chips = '' === (string) $atts['chips'] ? ( '' === $cat ) : ! in_array( strtolower( (string) $atts['chips'] ), array( '0', 'false', 'no' ), true );

		$args = array(
			'show_chips' => $chips,
			'base'       => get_permalink(),
		);

		if ( $cat ) {
			$args['cat'] = $cat;
		}
		if ( '' !== $atts['per_page'] ) {
			$args['per_page'] = max( 1, min( 100, absint( $atts['per_page'] ) ) );
		}

		return WCW_Render::works_section( $args );
	}

	public function is_works_page() {
		return is_page() && get_page_template_slug() === self::TEMPLATE;
	}

	private function page_has_shortcode() {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		return $post && has_shortcode( (string) $post->post_content, 'works_grid' );
	}

	public function body_class( $classes ) {
		if ( $this->is_works_page() || $this->page_has_shortcode() ) {
			$classes[] = 'wcw-works-page';
		}
		if ( is_singular( 'product' ) ) {
			$classes[] = 'wcw-product';
		}
		return $classes;
	}

	public function assets() {
		$works = $this->is_works_page() || $this->page_has_shortcode();
		$single = is_singular( 'product' );

		if ( ! $works && ! $single ) {
			return;
		}

		wp_enqueue_style( 'wcw-works', WCW_URL . 'assets/css/works.css', array(), WCW_VERSION );

		if ( ! $works ) {
			return;
		}

		wp_enqueue_script( 'wcw-works', WCW_URL . 'assets/js/works.js', array(), WCW_VERSION, true );
		wp_localize_script(
			'wcw-works',
			'wcwData',
			array(
				'rest'   => esc_url_raw( rest_url( WCW_Rest::NS . '/' ) ),
				'wcAjax' => esc_url_raw( add_query_arg( 'wc-ajax', 'add_to_cart', home_url( '/' ) ) ),
				'i18n'   => array(
					'loadMore'  => __( 'Load more', 'woocommerce-works' ),
					'loading'   => __( 'Loading…', 'woocommerce-works' ),
					'error'     => __( 'Something went wrong. Please try again.', 'woocommerce-works' ),
					'empty'     => __( 'No works found.', 'woocommerce-works' ),
					'adding'    => __( 'Adding…', 'woocommerce-works' ),
					'added'     => __( 'Added to cart', 'woocommerce-works' ),
					'addFailed' => __( 'Could not add to cart.', 'woocommerce-works' ),
				),
			)
		);
	}
}
