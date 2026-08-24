<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Magic_Link {

	const TOKEN_META = 'fe_magic_token';
	const EXPIRY_META = 'fe_magic_expiry';

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_login' ) );
		add_action( 'login_init', array( __CLASS__, 'maybe_login' ) );
		add_filter( 'authenticate', array( __CLASS__, 'block_pending_login' ), 30, 3 );
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
