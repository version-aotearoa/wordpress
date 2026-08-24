<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Shortcode {

	public function __construct() {
		add_shortcode( 'fe_account_form', array( $this, 'account_form' ) );
		add_shortcode( 'fe_login_form', array( $this, 'login_form_shortcode' ) );
		add_shortcode( 'fe_registration_form', array( $this, 'registration_form_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function assets() {
		wp_register_style( 'feur', FEUR_URL . 'assets/css/registration.css', array(), FEUR_VERSION );
		wp_register_script( 'feur-tabs', FEUR_URL . 'assets/js/account-tabs.js', array(), FEUR_VERSION, true );

		$enqueue = false;
		if ( is_page( FEUR_Page_Installer::get_page_id() ) ) {
			$enqueue = true;
		} elseif ( is_singular() ) {
			global $post;
			if ( $post && ( has_shortcode( $post->post_content, 'fe_account_form' ) || has_shortcode( $post->post_content, 'fe_login_form' ) || has_shortcode( $post->post_content, 'fe_registration_form' ) ) ) {
				$enqueue = true;
			}
		}
		if ( $enqueue ) {
			wp_enqueue_style( 'feur' );
			wp_enqueue_script( 'feur-tabs' );
		}
	}

	public function account_form( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return $this->logged_in_panel();
		}
		$state = $this->get_state();
		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'reg' === $requested ) {
			$requested = 'register';
		} elseif ( 'login' !== $requested ) {
			$requested = isset( $_GET['fe_tab'] ) ? sanitize_key( wp_unslash( $_GET['fe_tab'] ) ) : '';
		}
		$tab = in_array( $requested, array( 'login', 'register' ), true ) ? $requested : 'login';
		if ( ! empty( $state['errors'] ) ) {
			$tab = 'register';
		}

		$html = '<div class="feur-account">';
		$html .= $this->messages( $state );
		$html .= '<div class="feur-tabs">';
		$html .= '<button type="button" class="feur-tab' . ( 'login' === $tab ? ' feur-tab-active' : '' ) . '" data-tab="login">' . esc_html__( 'Login', 'feur' ) . '</button>';
		$html .= '<button type="button" class="feur-tab' . ( 'register' === $tab ? ' feur-tab-active' : '' ) . '" data-tab="register">' . esc_html__( 'Register', 'feur' ) . '</button>';
		$html .= '</div>';
		$html .= '<div class="feur-panel' . ( 'login' === $tab ? ' feur-panel-active' : '' ) . '" data-panel="login">' . $this->login_form() . '</div>';
		$html .= '<div class="feur-panel' . ( 'register' === $tab ? ' feur-panel-active' : '' ) . '" data-panel="register">' . $this->registration_form( $state ) . '</div>';
		$html .= '</div>';
		return $html;
	}

	public function login_form_shortcode( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return $this->logged_in_panel();
		}
		$state = $this->get_state();
		return $this->messages( $state ) . $this->login_form();
	}

	public function registration_form_shortcode( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return $this->logged_in_panel();
		}
		$state = $this->get_state();
		return $this->messages( $state ) . $this->registration_form( $state );
	}

	private function logged_in_panel() {
		$user = wp_get_current_user();
		$html = '<div class="feur-account feur-logged-in">';
		$html .= '<h2>' . esc_html__( 'You are logged in', 'feur' ) . '</h2>';
		$html .= '<p>' . sprintf( esc_html__( 'Welcome, %s.', 'feur' ), esc_html( $user->display_name ) ) . '</p>';

		if ( FEUR_Role::is_member( $user ) ) {
			$rows = array();
			$name = $user->display_name ? $user->display_name : $user->user_login;
			$rows[] = array( __( 'Name', 'feur' ), $name );
			$rows[] = array( __( 'Email', 'feur' ), $user->user_email );

			foreach ( FEUR_Field_Repository::get_public_fields() as $field ) {
				$value = FEUR_Field_Repository::value( $user->ID, $field );
				$label = FEUR_Field_Types::label( $field, $value );
				if ( '' !== $label && null !== $label ) {
					$rows[] = array( $field['label'], $label );
				}
			}

			if ( ! empty( $rows ) ) {
				$html .= '<div class="feur-details">';
				$html .= '<h3>' . esc_html__( 'Member Details', 'feur' ) . '</h3>';
				$html .= '<dl>';
				foreach ( $rows as $row ) {
					$html .= '<dt>' . esc_html( $row[0] ) . '</dt>';
					$html .= '<dd>' . esc_html( $row[1] ) . '</dd>';
				}
				$html .= '</dl>';
				$html .= '</div>';
			}
		}

		$html .= '<p><a href="' . esc_url( admin_url( 'profile.php' ) ) . '">' . esc_html__( 'Edit your profile', 'feur' ) . '</a> &middot; <a href="' . esc_url( wp_logout_url( get_permalink() ) ) . '">' . esc_html__( 'Log out', 'feur' ) . '</a></p>';
		$html .= '</div>';
		return $html;
	}

	private function login_form() {
		$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
		$html = '<form method="post" class="feur-form feur-login-form" action="' . esc_url( $this->form_action() ) . '">';
		$html .= '<input type="hidden" name="feur_login" value="1">';
		$html .= wp_nonce_field( 'feur_login', 'feur_nonce', true, false );
		$html .= '<p><label for="feur_login_email">' . esc_html__( 'Email', 'feur' ) . '</label><br><input type="email" id="feur_login_email" name="email" class="feur-input" required></p>';
		if ( $redirect ) {
			$html .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect ) . '">';
		}
		$html .= '<p><button type="submit" class="feur-button">' . esc_html__( 'Email me a login link', 'feur' ) . '</button></p>';
		$html .= '</form>';
		return $html;
	}

	private function registration_form( $state ) {
		$values = isset( $state['values'] ) && is_array( $state['values'] ) ? $state['values'] : array();
		$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
		$terms_text = FEUR_Plugin::get_setting( 'terms_text' );

		$html = '<form method="post" class="feur-form feur-register-form" action="' . esc_url( $this->form_action() ) . '">';
		$html .= '<input type="hidden" name="feur_register" value="1">';
		$html .= wp_nonce_field( 'feur_register', 'feur_nonce', true, false );
		$html .= '<div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true"><input type="text" name="feur_honeypot" tabindex="-1" autocomplete="off" value=""></div>';

		$html .= '<p><label for="feur_reg_email">' . esc_html__( 'Email', 'feur' ) . ' <span class="feur-required">*</span></label><br>';
		$html .= '<input type="email" id="feur_reg_email" name="email" class="feur-input" value="' . esc_attr( isset( $values['email'] ) ? $values['email'] : '' ) . '" required></p>';

		$html .= '<p><label for="feur_reg_first_name">' . esc_html__( 'First name', 'feur' ) . ' <span class="feur-required">*</span></label><br>';
		$html .= '<input type="text" id="feur_reg_first_name" name="first_name" class="feur-input" value="' . esc_attr( isset( $values['first_name'] ) ? $values['first_name'] : '' ) . '" autocomplete="given-name" required></p>';

		$html .= '<p><label for="feur_reg_last_name">' . esc_html__( 'Last name', 'feur' ) . ' <span class="feur-required">*</span></label><br>';
		$html .= '<input type="text" id="feur_reg_last_name" name="last_name" class="feur-input" value="' . esc_attr( isset( $values['last_name'] ) ? $values['last_name'] : '' ) . '" autocomplete="family-name" required></p>';

		foreach ( FEUR_Field_Repository::get_public_fields() as $field ) {
			$name = 'fe_field_' . $field['id'];
			$value = isset( $values[ $name ] ) ? $values[ $name ] : '';
			$label = esc_html( $field['label'] ) . ( ! empty( $field['required'] ) ? ' <span class="feur-required">*</span>' : '' );
			$html .= '<p class="feur-field feur-field-' . esc_attr( $field['type'] ) . '">';
			if ( FEUR_Field_Types::is_choice( $field['type'] ) ) {
				$html .= '<span class="feur-field-label">' . $label . '</span><br>';
			} else {
				$html .= '<label for="feur-field-' . esc_attr( $field['id'] ) . '">' . $label . '</label><br>';
			}
			$html .= FEUR_Field_Types::render( $field, $value );
			$html .= '</p>';
		}

		if ( $terms_text ) {
			$html .= '<p class="feur-field feur-terms"><label class="feur-checkbox"><input type="checkbox" name="feur_terms" value="1" required> ' . esc_html( $terms_text ) . '</label></p>';
		}

		if ( $redirect ) {
			$html .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect ) . '">';
		}
		$html .= '<p><button type="submit" class="feur-button">' . esc_html__( 'Register', 'feur' ) . '</button></p>';
		$html .= '</form>';
		return $html;
	}

	private function messages( $state ) {
		$html = '';
		if ( ! empty( $state['errors'] ) ) {
			$html .= '<div class="feur-notice feur-error"><ul>';
			foreach ( $state['errors'] as $error ) {
				$html .= '<li>' . esc_html( $error ) . '</li>';
			}
			$html .= '</ul></div>';
		}
		if ( ! isset( $_GET['fe_status'] ) ) {
			return $html;
		}
		$status = sanitize_key( wp_unslash( $_GET['fe_status'] ) );
		$messages = array(
			'pending'        => __( 'Your account has been created and is awaiting approval. You will receive an email with a login link once an administrator approves your membership.', 'feur' ),
			'success'        => __( 'Your account has been created. You can now log in.', 'feur' ),
			'link_sent'      => __( 'If an account exists for that email address, a login link has been sent.', 'feur' ),
			'pending_login'  => __( 'That account is still pending approval. Please wait for an administrator to approve it.', 'feur' ),
			'denied'         => __( 'Your membership application was declined. Please contact an administrator for assistance.', 'feur' ),
			'invalid_link'   => __( 'That login link is invalid or has expired. Request a new one below.', 'feur' ),
			'invalid_email'  => __( 'Please enter a valid email address.', 'feur' ),
			'invalid_token'  => __( 'Invalid security token. Please try again.', 'feur' ),
			'members_only'   => __( 'This content is for members only. Please log in to continue.', 'feur' ),
		);
		$error_statuses = array( 'invalid_link', 'invalid_email', 'invalid_token' );
		if ( isset( $messages[ $status ] ) ) {
			$class = in_array( $status, $error_statuses, true ) ? 'feur-error' : 'feur-success';
			$html .= '<div class="feur-notice ' . esc_attr( $class ) . '">' . esc_html( $messages[ $status ] ) . '</div>';
		}
		return $html;
	}

	private function get_state() {
		$state = array(
			'errors' => array(),
			'values' => array(),
		);
		if ( isset( $_GET['fe_status'] ) && 'error' === sanitize_key( wp_unslash( $_GET['fe_status'] ) ) && isset( $_GET['fe_key'] ) ) {
			$key = sanitize_text_field( wp_unslash( $_GET['fe_key'] ) );
			$data = get_transient( 'feur_state_' . $key );
			if ( is_array( $data ) ) {
				$state['errors'] = isset( $data['errors'] ) && is_array( $data['errors'] ) ? array_map( 'sanitize_text_field', $data['errors'] ) : array();
				$state['values'] = isset( $data['values'] ) && is_array( $data['values'] ) ? $data['values'] : array();
				delete_transient( 'feur_state_' . $key );
			}
		}
		return $state;
	}

	private function form_action() {
		return remove_query_arg( array( 'fe_status', 'fe_key', 'fe_tab', 'redirect_to' ), FEUR_Page_Installer::get_url() );
	}
}
