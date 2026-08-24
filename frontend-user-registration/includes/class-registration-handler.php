<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Registration_Handler {

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_register' ) );
	}

	public function maybe_register() {
		if ( empty( $_POST['feur_register'] ) || empty( $_POST['feur_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['feur_nonce'] ) ), 'feur_register' ) ) {
			$this->fail( array( __( 'Invalid security token. Please try again.', 'feur' ) ), array() );
		}
		if ( is_user_logged_in() ) {
			$this->success();
		}

		$errors = array();
		$values = array();

		if ( ! empty( $_POST['feur_honeypot'] ) ) {
			$this->fail( array(), array() );
		}

		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last_name = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$values['email'] = $email;
		$values['first_name'] = $first_name;
		$values['last_name'] = $last_name;

		if ( ! is_email( $email ) ) {
			$errors[] = __( 'Please enter a valid email address.', 'feur' );
		} elseif ( email_exists( $email ) ) {
			$errors[] = __( 'An account with that email already exists.', 'feur' );
		}

		if ( '' === $first_name ) {
			$errors[] = __( 'First name is required.', 'feur' );
		} elseif ( mb_strlen( $first_name ) > 100 ) {
			$errors[] = __( 'First name is too long.', 'feur' );
		}

		if ( '' === $last_name ) {
			$errors[] = __( 'Last name is required.', 'feur' );
		} elseif ( mb_strlen( $last_name ) > 100 ) {
			$errors[] = __( 'Last name is too long.', 'feur' );
		}

		if ( FEUR_Plugin::get_setting( 'terms_text' ) && empty( $_POST['feur_terms'] ) ) {
			$errors[] = __( 'You must accept the terms to register.', 'feur' );
		}

		foreach ( FEUR_Field_Repository::get_public_fields() as $field ) {
			$name = 'fe_field_' . $field['id'];
			$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
			$value = FEUR_Field_Types::sanitize( $field, $raw );
			$values[ $name ] = $value;
			$errors = array_merge( $errors, FEUR_Field_Types::validate( $field, $value ) );
		}

		if ( ! empty( $errors ) ) {
			$this->fail( $errors, $values );
		}

		$role = FEUR_Plugin::get_setting( 'default_role', 'member' );
		if ( ! get_role( $role ) ) {
			$role = 'member';
		}

		$username = self::generate_username( $email );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 32, true ),
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => trim( $first_name . ' ' . $last_name ),
				'role'         => $role,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$this->fail( array( $user_id->get_error_message() ), $values );
		}

		foreach ( FEUR_Field_Repository::get_public_fields() as $field ) {
			$name = 'fe_field_' . $field['id'];
			if ( array_key_exists( $name, $values ) ) {
				FEUR_Field_Repository::save_value( $user_id, $field, $values[ $name ] );
			}
		}

		$pending = (bool) FEUR_Plugin::get_setting( 'require_approval', true );
		if ( $pending ) {
			update_user_meta( $user_id, 'fe_pending', 1 );
		}

		FEUR_Approval::notify_registered( $user_id, $pending );

		$this->success( $pending ? 'pending' : 'success' );
	}

	private static function generate_username( $email ) {
		$base = strtolower( sanitize_user( $email, true ) );
		if ( '' === $base || mb_strlen( $base ) > 60 ) {
			$base = mb_substr( $base, 0, 60 );
		}
		if ( '' === $base ) {
			$base = 'member';
		}
		$username = $base;
		$i = 1;
		while ( username_exists( $username ) ) {
			$suffix = '-' . $i++;
			$username = mb_substr( $base, 0, 60 - mb_strlen( $suffix ) ) . $suffix;
		}
		return $username;
	}

	private function fail( $errors, $values ) {
		$key = wp_generate_password( 12, false );
		set_transient(
			'feur_state_' . $key,
			array(
				'errors' => $errors,
				'values' => $values,
			),
			10 * MINUTE_IN_SECONDS
		);
		wp_safe_redirect(
			add_query_arg(
				array(
					'fe_status' => 'error',
					'fe_key'    => $key,
					'fe_tab'    => 'register',
				),
				FEUR_Page_Installer::get_url()
			)
		);
		exit;
	}

	private function success( $status = 'success' ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'fe_status' => $status,
					'fe_tab'    => 'login',
				),
				FEUR_Page_Installer::get_url()
			)
		);
		exit;
	}
}
