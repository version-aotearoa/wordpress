<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Approval {

	public function __construct() {
		add_action( 'admin_post_feur_approve', array( $this, 'approve' ) );
		add_action( 'admin_post_feur_deny', array( $this, 'deny' ) );
		add_action( 'admin_post_feur_pending', array( $this, 'pending' ) );
	}

	public function approve() {
		$user_id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
		check_admin_referer( 'feur_approve_' . $user_id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'feur' ) );
		}
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			self::set_status( $user_id, 'approved' );
			$redirect = isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : '';
			$token = FEUR_Magic_Link::issue( $user_id );
			self::send_magic_link_email( $user_id, $token, $redirect );
		}
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=feur-members' );
		wp_safe_redirect( add_query_arg( 'feur_approved', '1', $back ) );
		exit;
	}

	public function deny() {
		$user_id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
		check_admin_referer( 'feur_deny_' . $user_id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'feur' ) );
		}
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			self::set_status( $user_id, 'denied' );
			self::send_deny_email( $user_id );
		}
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=feur-members' );
		wp_safe_redirect( add_query_arg( 'feur_denied', '1', $back ) );
		exit;
	}

	public function pending() {
		$user_id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
		check_admin_referer( 'feur_pending_' . $user_id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'feur' ) );
		}
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			self::set_status( $user_id, 'pending' );
		}
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=feur-members' );
		wp_safe_redirect( add_query_arg( 'feur_pending', '1', $back ) );
		exit;
	}

	public static function status( $user_id ) {
		if ( get_user_meta( $user_id, 'fe_pending', true ) ) {
			return 'pending';
		}
		if ( get_user_meta( $user_id, 'fe_denied', true ) ) {
			return 'denied';
		}
		return 'approved';
	}

	public static function set_status( $user_id, $status ) {
		switch ( $status ) {
			case 'pending':
				update_user_meta( $user_id, 'fe_pending', 1 );
				delete_user_meta( $user_id, 'fe_denied' );
				break;
			case 'denied':
				update_user_meta( $user_id, 'fe_denied', 1 );
				delete_user_meta( $user_id, 'fe_pending' );
				FEUR_Magic_Link::revoke( $user_id );
				if ( class_exists( 'WP_Session_Tokens' ) ) {
					WP_Session_Tokens::get_instance( $user_id )->destroy_all();
				}
				break;
			case 'approved':
				delete_user_meta( $user_id, 'fe_pending' );
				delete_user_meta( $user_id, 'fe_denied' );
				break;
		}
	}

	public static function notify_registered( $user_id, $pending ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		if ( $pending && FEUR_Plugin::get_setting( 'pending_email_enabled', true ) ) {
			$subject = sprintf( __( '[%s] Your registration is awaiting approval', 'feur' ), $site_name );
			$body = '<p>' . esc_html__( 'Hi,', 'feur' ) . '</p>';
			$body .= '<p>' . esc_html__( 'Your account has been created and is now awaiting approval. You will receive an email with a login link once an administrator approves your membership.', 'feur' ) . '</p>';
			self::email( $user->user_email, $subject, $body );
		}

		if ( FEUR_Plugin::get_setting( 'admin_notify_enabled', true ) ) {
			$subject = sprintf( __( '[%s] New %s registration: %s', 'feur' ), $site_name, $pending ? __( 'pending', 'feur' ) : __( 'member', 'feur' ), $user->user_login );
			$body = '<p>' . esc_html__( 'A new registration was submitted:', 'feur' ) . '</p>';
			$body .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">';
			$body .= '<tr><td><strong>' . esc_html__( 'First name', 'feur' ) . '</strong></td><td>' . esc_html( $user->first_name ) . '</td></tr>';
			$body .= '<tr><td><strong>' . esc_html__( 'Last name', 'feur' ) . '</strong></td><td>' . esc_html( $user->last_name ) . '</td></tr>';
			$body .= '<tr><td><strong>' . esc_html__( 'Email', 'feur' ) . '</strong></td><td>' . esc_html( $user->user_email ) . '</td></tr>';
			$body .= '<tr><td><strong>' . esc_html__( 'Status', 'feur' ) . '</strong></td><td>' . ( $pending ? esc_html__( 'Pending approval', 'feur' ) : esc_html__( 'Approved', 'feur' ) ) . '</td></tr>';
			foreach ( FEUR_Field_Repository::get_public_fields() as $field ) {
				$value = FEUR_Field_Repository::value( $user->ID, $field );
				$body .= '<tr><td><strong>' . esc_html( $field['label'] ) . '</strong></td><td>' . esc_html( FEUR_Field_Types::label( $field, $value ) ) . '</td></tr>';
			}
			$body .= '</table>';
			if ( $pending ) {
				$body .= '<p><a href="' . esc_url( admin_url( 'admin.php?page=feur-members' ) ) . '">' . esc_html__( 'Review pending members', 'feur' ) . '</a></p>';
			}
			self::email( get_option( 'admin_email' ), $subject, $body );
		}
	}

	public static function send_magic_link_email( $user_id, $token, $redirect = '' ) {
		if ( ! FEUR_Plugin::get_setting( 'magic_link_email_enabled', true ) ) {
			return;
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}
		$link = FEUR_Magic_Link::login_url( $user_id, $token, $redirect );
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = sprintf( __( '[%s] Your login link', 'feur' ), $site_name );
		$body = '<p>' . sprintf( esc_html__( 'Hi %s,', 'feur' ), esc_html( $user->display_name ) ) . '</p>';
		$body .= '<p>' . esc_html__( 'Your membership has been approved. Click the button below to log in:', 'feur' ) . '</p>';
		$body .= '<p><a style="display:inline-block;background:#2271b1;color:#fff;padding:.6em 1.2em;text-decoration:none;border-radius:4px" href="' . esc_url( $link ) . '">' . esc_html__( 'Log in to your account', 'feur' ) . '</a></p>';
		$body .= '<p><small>' . esc_html__( 'This link is single-use and will expire.', 'feur' ) . '</small></p>';
		self::email( $user->user_email, $subject, $body );
	}

	public static function send_deny_email( $user_id ) {
		if ( ! FEUR_Plugin::get_setting( 'deny_email_enabled', true ) ) {
			return;
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = sprintf( __( '[%s] Membership application update', 'feur' ), $site_name );
		$body = '<p>' . esc_html__( 'Hi,', 'feur' ) . '</p>';
		$body .= '<p>' . esc_html__( 'We are sorry, but your membership application has been declined.', 'feur' ) . '</p>';
		self::email( $user->user_email, $subject, $body );
	}

	private static function email( $to, $subject, $body ) {
		return wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
}
