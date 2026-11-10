<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Magic_Link {

	const TOKEN_META = 'fe_magic_token';
	const EXPIRY_META = 'fe_magic_expiry';

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_login' ) );
		add_action( 'login_init', array( __CLASS__, 'maybe_login' ) );
		add_action( 'login_init', array( __CLASS__, 'maybe_route_login_screen' ) );
		add_filter( 'authenticate', array( __CLASS__, 'block_pending_login' ), 30, 3 );
		add_filter( 'logout_redirect', array( __CLASS__, 'maybe_logout_redirect' ), 99, 3 );
	}

	public static function issue( $user_id ) {
		$token = wp_generate_password( 32, false );
		$expiry = (int) FEUR_Plugin::get_setting( 'magic_link_expiry', 48 );
		update_user_meta( $user_id, self::TOKEN_META, wp_hash( $token ) );
		update_user_meta( $user_id, self::EXPIRY_META, time() + ( max( 1, $expiry ) * HOUR_IN_SECONDS ) );
		return $token;
	}

	public static function login_url( $user_id, $token, $redirect = '' ) {
		$args = array(
			'fe_auth'  => $user_id,
			'fe_token' => $token,
		);
		if ( $redirect ) {
			$args['redirect_to'] = $redirect;
		}
		return add_query_arg( $args, wp_login_url() );
	}

	public static function validate( $user_id, $token ) {
		$stored = get_user_meta( $user_id, self::TOKEN_META, true );
		$expiry = (int) get_user_meta( $user_id, self::EXPIRY_META, true );
		if ( ! $stored || ! $expiry || time() > $expiry ) {
			return false;
		}
		return hash_equals( (string) $stored, wp_hash( (string) $token ) );
	}

	public static function revoke( $user_id ) {
		delete_user_meta( $user_id, self::TOKEN_META );
		delete_user_meta( $user_id, self::EXPIRY_META );
	}

	public static function maybe_login() {
		if ( ! isset( $_GET['fe_auth'] ) || ! isset( $_GET['fe_token'] ) ) {
			return;
		}
		$user_id = absint( $_GET['fe_auth'] );
		$token = sanitize_text_field( wp_unslash( $_GET['fe_token'] ) );
		if ( ! $user_id || '' === $token ) {
			return;
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! $user || get_user_meta( $user_id, 'fe_pending', true ) || get_user_meta( $user_id, 'fe_denied', true ) ) {
			$status = get_user_meta( $user_id, 'fe_denied', true ) ? 'denied' : 'pending_login';
			wp_safe_redirect( add_query_arg( array( 'fe_status' => $status, 'fe_tab' => 'login' ), FEUR_Page_Installer::get_url() ) );
			exit;
		}
		if ( ! self::validate( $user_id, $token ) ) {
			wp_safe_redirect( add_query_arg( array( 'fe_status' => 'invalid_link', 'fe_tab' => 'login' ), FEUR_Page_Installer::get_url() ) );
			exit;
		}
		delete_user_meta( $user_id, self::TOKEN_META );
		delete_user_meta( $user_id, self::EXPIRY_META );

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		$redirect = isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : '';
		wp_safe_redirect( FEUR_Plugin::login_redirect( $redirect ) );
		exit;
	}

	/**
	 * Send logged-out visitors who were bounced out of wp-admin to the account
	 * page instead of showing the stock WordPress login form.
	 *
	 * Deliberately narrow: only requests carrying a redirect_to that points at
	 * wp-admin are rerouted. A bare /wp-login.php hit (lost password, an admin
	 * deliberately using the core form) is left alone.
	 */
	public static function maybe_route_login_screen() {
		// Magic links carry their own params; maybe_login() handles those.
		if ( isset( $_GET['fe_auth'], $_GET['fe_token'] ) ) {
			return;
		}
		// Leave lostpassword/resetpass/logout/postpass and reauth modals alone.
		if ( ! empty( $_GET['action'] ) || ! empty( $_GET['interim_login'] ) ) {
			return;
		}
		if ( is_user_logged_in() ) {
			return;
		}

		$redirect = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), '' ) : '';
		if ( '' === $redirect || ! self::is_admin_url( $redirect ) ) {
			return;
		}

		wp_safe_redirect( add_query_arg( 'fe_tab', 'login', FEUR_Page_Installer::get_url() ), 302 );
		exit;
	}

	/**
	 * Send logouts to the account page instead of the WordPress login form.
	 *
	 * Covers the admin bar and wp-admin logout, which call wp_logout_url()
	 * without a target. An explicitly requested destination is left alone.
	 */
	public static function maybe_logout_redirect( $redirect_to, $requested_redirect_to, $user ) {
		if ( '' !== (string) $requested_redirect_to ) {
			return $redirect_to;
		}
		return add_query_arg( 'fe_status', 'logged_out', FEUR_Page_Installer::get_url() );
	}

	/**
	 * Compare paths rather than full URLs so scheme/proxy mismatches between
	 * admin_url() and the redirect_to value do not defeat the check.
	 */
	private static function is_admin_url( $url ) {
		$target = wp_parse_url( $url, PHP_URL_PATH );
		$admin  = wp_parse_url( admin_url(), PHP_URL_PATH );
		if ( ! is_string( $target ) || ! is_string( $admin ) || '' === $admin ) {
			return false;
		}
		return 0 === strpos( trailingslashit( $target ), trailingslashit( $admin ) );
	}

	public static function block_pending_login( $user, $username, $password ) {
		if ( ! $user instanceof WP_User ) {
			return $user;
		}
		if ( get_user_meta( $user->ID, 'fe_denied', true ) ) {
			return new WP_Error( 'feur_denied', __( 'Your membership application was declined. Please contact an administrator.', 'feur' ) );
		}
		if ( get_user_meta( $user->ID, 'fe_pending', true ) ) {
			return new WP_Error( 'feur_pending', __( 'Your account is pending approval. Please wait for an administrator to approve it.', 'feur' ) );
		}
		return $user;
	}
}
