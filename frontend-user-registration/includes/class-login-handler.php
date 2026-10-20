<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Login_Handler {

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_request_link' ) );
	}

	public function maybe_request_link() {
		if ( empty( $_POST['feur_login'] ) ) {
			return;
		}
		if ( is_user_logged_in() ) {
			$redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
			wp_safe_redirect( FEUR_Plugin::login_redirect( $redirect ) );
			exit;
		}

		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			$this->redirect( 'invalid_email' );
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			$this->redirect( 'link_sent' );
		}
		if ( get_user_meta( $user->ID, 'fe_pending', true ) || get_user_meta( $user->ID, 'fe_denied', true ) ) {
			$this->redirect( get_user_meta( $user->ID, 'fe_denied', true ) ? 'denied' : 'pending_login' );
		}

		$rate_key = 'feur_login_' . md5( strtolower( $email ) );
		if ( get_transient( $rate_key ) ) {
			$this->redirect( 'link_sent' );
		}
		set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

		$redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
		$token = FEUR_Magic_Link::issue( $user->ID );
		FEUR_Approval::send_magic_link_email( $user->ID, $token, $redirect );

		$this->redirect( 'link_sent' );
	}

	private function redirect( $status ) {
		$args = array(
			'fe_status' => $status,
			'fe_tab'    => 'login',
		);
		if ( isset( $_POST['redirect_to'] ) ) {
			$args['redirect_to'] = wp_unslash( $_POST['redirect_to'] );
		}
		wp_safe_redirect( add_query_arg( $args, FEUR_Page_Installer::get_url() ) );
		exit;
	}
}
