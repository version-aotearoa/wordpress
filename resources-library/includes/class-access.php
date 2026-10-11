<?php
defined( 'ABSPATH' ) || exit;

class RL_Access {

	public function __construct() {
		add_filter( 'feur_skip_access_page', array( __CLASS__, 'skip_access_page' ) );
		add_filter( 'feur_skip_post_type_restriction', array( __CLASS__, 'skip_post_type_restriction' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_login_assets' ), 20 );
	}

	public static function public_featured_enabled() {
		return (bool) RL_Plugin::get_setting( 'public_featured', 0 );
	}

	public static function is_member() {
		if ( ! class_exists( 'FEUR_Role' ) ) {
			return true;
		}
		return FEUR_Role::has_member_access();
	}

	public static function featured_only() {
		return self::public_featured_enabled() && ! self::is_member();
	}

	public static function is_library_page() {
		if ( is_page() && get_page_template_slug() === RL_Page_Template::TEMPLATE ) {
			return true;
		}
		if ( is_singular() ) {
			global $post;
			if ( $post && has_shortcode( $post->post_content, 'resources_library' ) ) {
				return true;
			}
		}
		return false;
	}

	public static function resource_is_featured( $post_id ) {
		return (bool) get_post_meta( $post_id, RL_Plugin::FEATURED_META, true );
	}

	public static function is_gated_view() {
		if ( ! self::featured_only() ) {
			return false;
		}
		$tag = isset( $_GET['tag'] ) ? sanitize_title( wp_unslash( $_GET['tag'] ) ) : '';
		$favourites = ( '1' === ( isset( $_GET['favourites'] ) ? sanitize_key( wp_unslash( $_GET['favourites'] ) ) : '' ) );
		return ( '' !== $tag || $favourites );
	}

	public static function is_gated_single( $post_id ) {
		return self::featured_only() && ! self::resource_is_featured( $post_id );
	}

	public static function skip_access_page( $skip ) {
		if ( self::featured_only() && self::is_library_page() ) {
			return true;
		}
		return $skip;
	}

	public static function skip_post_type_restriction( $skip, $target = '' ) {
		if ( self::featured_only() && is_singular( RL_Plugin::PT ) ) {
			return true;
		}
		return $skip;
	}

	public static function login_panel( $redirect = '' ) {
		$html = '<div class="rl-gate">';
		$html .= '<h2 class="rl-gate-title">' . esc_html__( 'Members only', 'rl' ) . '</h2>';
		$text = is_user_logged_in()
			? __( 'Your account does not have access to this section.', 'rl' )
			: __( 'Log in to view this section.', 'rl' );
		$html .= '<p class="rl-gate-text">' . esc_html( $text ) . '</p>';

		$form = '';
		if ( shortcode_exists( 'fe_login_form' ) ) {
			$previous = isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : null;
			if ( $redirect ) {
				$_GET['redirect_to'] = $redirect;
			}
			$form = do_shortcode( '[fe_login_form]' );
			if ( null === $previous ) {
				unset( $_GET['redirect_to'] );
			} else {
				$_GET['redirect_to'] = $previous;
			}
		}

		if ( '' === trim( (string) $form ) ) {
			$login_url = $redirect ? wp_login_url( $redirect ) : wp_login_url();
			$form = '<p><a class="rl-gate-link" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Log in', 'rl' ) . '</a></p>';
		}

		$html .= $form;
		$html .= '</div>';
		return $html;
	}

	public static function enqueue_login_assets() {
		if ( ! self::featured_only() ) {
			return;
		}
		if ( ! self::is_library_page() && ! is_singular( RL_Plugin::PT ) ) {
			return;
		}
		if ( wp_style_is( 'feur', 'registered' ) ) {
			wp_enqueue_style( 'feur' );
		}
	}
}
