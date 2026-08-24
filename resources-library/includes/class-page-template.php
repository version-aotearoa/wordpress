<?php
defined( 'ABSPATH' ) || exit;

class RL_Page_Template {

	const TEMPLATE = 'templates/resource-library.php';
	const SINGLE_TEMPLATE = 'templates/single-resource.php';

	const URL_TRANSIENT = 'rl_library_url';

	private static $library_url = null;

	public function __construct() {
		add_filter( 'theme_page_templates', array( $this, 'register' ) );
		add_filter( 'template_include', array( $this, 'route' ) );
		add_shortcode( 'resources_library', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'save_post_page', array( __CLASS__, 'clear_library_url' ) );
	}

	public function shortcode( $atts = array() ) {
		return RL_Render::render_library();
	}

	public function register( $templates ) {
		$templates[ self::TEMPLATE ] = __( 'Resource Library', 'rl' );
		return $templates;
	}

	public function route( $template ) {
		if ( is_singular( RL_Plugin::PT ) ) {
			$file = RL_PATH . self::SINGLE_TEMPLATE;
			if ( file_exists( $file ) ) {
				return $file;
			}
		}
		if ( is_page() && get_page_template_slug() === self::TEMPLATE ) {
			$file = RL_PATH . self::TEMPLATE;
			if ( file_exists( $file ) ) {
				return $file;
			}
		}
		return $template;
	}

	public static function library_url() {
		if ( null !== self::$library_url ) {
			return self::$library_url;
		}
		$url = get_transient( self::URL_TRANSIENT );
		if ( ! $url ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'no_found_rows'  => true,
					'meta_query'     => array(
						array(
							'key'   => '_wp_page_template',
							'value' => self::TEMPLATE,
						),
					),
				)
			);
			if ( $query->have_posts() ) {
				$url = get_permalink( $query->posts[0] );
			} else {
				foreach ( get_pages() as $page ) {
					if ( has_shortcode( $page->post_content, 'resources_library' ) ) {
						$url = get_permalink( $page );
						break;
					}
				}
			}
			if ( ! $url ) {
				$url = home_url( '/' );
			}
			set_transient( self::URL_TRANSIENT, $url, HOUR_IN_SECONDS );
		}
		self::$library_url = $url;
		return $url;
	}

	public static function clear_library_url() {
		delete_transient( self::URL_TRANSIENT );
		self::$library_url = null;
	}

	public function assets() {
		$enqueue = false;
		if ( is_singular( RL_Plugin::PT ) ) {
			$enqueue = true;
		}
		if ( is_page() && get_page_template_slug() === self::TEMPLATE ) {
			$enqueue = true;
		}
		if ( is_singular() ) {
			global $post;
			if ( $post && has_shortcode( $post->post_content, 'resources_library' ) ) {
				$enqueue = true;
			}
		}
		if ( ! $enqueue ) {
			return;
		}
		wp_register_style( 'rl-library', RL_URL . 'assets/css/library.css', array(), RL_VERSION );
		wp_register_script( 'rl-library', RL_URL . 'assets/js/library.js', array(), RL_VERSION, true );
		wp_enqueue_style( 'rl-library' );
		$accent = RL_Plugin::get_setting( 'accent_color', '#2271b1' );
		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $accent ) ) {
			wp_add_inline_style( 'rl-library', ':root{--rl-accent:' . $accent . ';}' );
		}
		wp_enqueue_script( 'rl-library' );
		wp_localize_script(
			'rl-library',
			'rl_library',
			array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'rl_load_posts' ),
			'loading'    => __( 'Loading…', 'rl' ),
			'load_more'  => __( 'Load more', 'rl' ),
			'no_results' => __( 'No resources found.', 'rl' ),
			'featured'   => __( 'Featured Resources', 'rl' ),
			'favourites' => __( 'Favourites', 'rl' ),
			'link_copied' => __( 'Link copied', 'rl' ),
			)
		);
	}
}
