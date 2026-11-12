<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Shortcode {

	public function __construct() {
		add_shortcode( 'fe_account_form', array( $this, 'account_form' ) );
		add_shortcode( 'fe_login_form', array( $this, 'login_form_shortcode' ) );
		add_shortcode( 'fe_registration_form', array( $this, 'registration_form_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'template_redirect', array( $this, 'nocache' ), 1 );
	}

	private function has_fe_form() {
		if ( is_page( FEUR_Page_Installer::get_page_id() ) ) {
			return true;
		}
		if ( is_singular() ) {
			global $post;
			if ( $post && ( has_shortcode( $post->post_content, 'fe_account_form' ) || has_shortcode( $post->post_content, 'fe_login_form' ) || has_shortcode( $post->post_content, 'fe_registration_form' ) ) ) {
				return true;
			}
		}
		return false;
	}

	public function nocache() {
		if ( ! $this->has_fe_form() ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		// Magic link tokens travel in the query string; never leak them onward.
		header( 'Referrer-Policy: no-referrer' );
	}

	public function assets() {
		wp_register_style( 'feur', FEUR_URL . 'assets/css/registration.css', array(), FEUR_VERSION );
		wp_register_script( 'feur-tabs', FEUR_URL . 'assets/js/account-tabs.js', array(), FEUR_VERSION, true );

		if ( $this->has_fe_form() ) {
			wp_enqueue_style( 'feur' );
			wp_enqueue_script( 'feur-tabs' );
		}
	}

	public function account_form( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return $this->logged_in_panel();
		}

		$confirm = $this->confirm_panel();
		if ( '' !== $confirm ) {
			return $confirm;
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
		$html .= $this->messages( array( 'errors' => array() ) );
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

		$html .= $this->review_panel();
		$html .= FEUR_Profile_Editor::is_open() ? FEUR_Profile_Editor::form( $user ) . $this->account_links( true ) : $this->account_links( false );
		$html .= '</div>';
		return $html;
	}

	private function account_links( $editing ) {
		$logout = '<a href="' . esc_url( wp_logout_url( FEUR_Page_Installer::get_url() ) ) . '">' . esc_html__( 'Log out', 'feur' ) . '</a>';

		if ( $editing ) {
			return '<p class="feur-profile-links"><a href="' . esc_url( FEUR_Page_Installer::get_url() ) . '">' . esc_html__( 'Cancel', 'feur' ) . '</a> &middot; ' . $logout . '</p>';
		}

		$edit = '<a href="' . esc_url( FEUR_Profile_Editor::url() ) . '">' . esc_html__( 'Edit your profile', 'feur' ) . '</a>';
		return '<p class="feur-profile-links">' . $edit . ' &middot; ' . $logout . '</p>';
	}

	/**
	 * Shortcuts for administrators and Members Managers. Registration
	 * notification emails point at this page, so the destination for "someone
	 * needs approving" has to be reachable from here. Resource shortcuts are
	 * only rendered while the Resources Library post type is present.
	 */
	private function review_panel() {
		$items = array();

		if ( current_user_can( FEUR_Role::MANAGE ) ) {
			$url     = admin_url( 'admin.php?page=feur-members' );
			$pending = FEUR_Approval::pending_count();

			if ( $pending > 0 ) {
				$label = sprintf(
					/* translators: %s: number of members awaiting approval. */
					_n( '%s member awaiting approval', '%s members awaiting approval', $pending, 'feur' ),
					number_format_i18n( $pending )
				);
				$items[] = '<p><a href="' . esc_url( $url ) . '"><strong>' . esc_html( $label ) . ' &rarr; ' . esc_html__( 'Review', 'feur' ) . '</strong></a></p>';
			} else {
				$items[] = '<p><a href="' . esc_url( $url ) . '">' . esc_html__( 'Manage members', 'feur' ) . '</a></p>';
			}
		}

		$resources = $this->resource_links();
		if ( '' !== $resources ) {
			$items[] = $resources;
		}

		if ( empty( $items ) ) {
			return '';
		}

		return '<div class="feur-review">' . implode( '', $items ) . '</div>';
	}

	/**
	 * Shortcut to the Resources screens. The Resources Library post type uses
	 * the default post capabilities, so edit_posts is the gate.
	 */
	private function resource_links() {
		if ( ! post_type_exists( 'resource' ) || ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		return '<p><a href="' . esc_url( admin_url( 'post-new.php?post_type=resource' ) ) . '">' . esc_html__( 'Add resource', 'feur' ) . '</a> &middot; <a href="' . esc_url( admin_url( 'edit.php?post_type=resource' ) ) . '">' . esc_html__( 'Manage resources', 'feur' ) . '</a></p>';
	}

	/**
	 * Two-step sign in: the emailed link opens this panel and the token is only
	 * consumed by the form below. Keeping the GET inert is what stops email
	 * scanners from burning one-time links. See docs/magic-link-confirm.md.
	 */
	private function confirm_panel() {
		if ( ! isset( $_GET['fe_auth'], $_GET['fe_token'] ) ) {
			return '';
		}

		$user_id = absint( wp_unslash( $_GET['fe_auth'] ) );
		$token   = sanitize_text_field( wp_unslash( $_GET['fe_token'] ) );
		if ( ! $user_id || '' === $token ) {
			return '';
		}

		$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
		$result   = FEUR_Magic_Link::check( $user_id, $token );

		$html  = '<div class="feur-account feur-confirm">';
		$html .= '<h2>' . esc_html__( 'Confirm sign in', 'feur' ) . '</h2>';

		if ( ! $result instanceof WP_User ) {
			$messages = $this->status_messages();
			$message  = isset( $messages[ $result ] ) ? $messages[ $result ] : $messages['invalid_link'];
			$html    .= '<div class="feur-notice feur-error">' . esc_html( $message ) . '</div>';
			$html    .= '<p><a href="' . esc_url( FEUR_Page_Installer::account_url() ) . '">' . esc_html__( 'Get a new sign-in link', 'feur' ) . '</a></p>';
			return $html . '</div>';
		}

		$html .= '<p>' . esc_html__( 'Press the button to finish signing in. This link works once.', 'feur' ) . '</p>';
		$html .= '<form method="post" class="feur-form feur-confirm-form" action="' . esc_url( FEUR_Page_Installer::account_url() ) . '">';
		$html .= '<input type="hidden" name="fe_confirm" value="1">';
		$html .= '<input type="hidden" name="fe_auth" value="' . esc_attr( $user_id ) . '">';
		$html .= '<input type="hidden" name="fe_token" value="' . esc_attr( $token ) . '">';
		if ( $redirect ) {
			$html .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect ) . '">';
		}
		$html .= '<p><button type="submit" class="feur-button">' . esc_html__( 'Sign in', 'feur' ) . '</button></p>';
		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	private function login_form() {
		$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
		$html = '<form method="post" class="feur-form feur-login-form" action="' . esc_url( $this->form_action() ) . '">';
		$html .= '<input type="hidden" name="feur_login" value="1">';
		$html .= '<p><label for="feur_login_email">' . esc_html__( 'Email', 'feur' ) . '</label><br><input type="email" id="feur_login_email" name="email" class="feur-input" required></p>';
		if ( $redirect ) {
			$html .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect ) . '">';
		}
		$html .= '<p><button type="submit" class="feur-button">' . esc_html__( 'Send login link', 'feur' ) . '</button></p>';
		$html .= '</form>';
		return $html;
	}

	private function registration_form( $state ) {
		$values = isset( $state['values'] ) && is_array( $state['values'] ) ? $state['values'] : array();
		$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
		$terms_text = FEUR_Plugin::get_setting( 'terms_text' );

		$html = '<form method="post" class="feur-form feur-register-form" action="' . esc_url( $this->form_action() ) . '">';
		$html .= '<input type="hidden" name="feur_register" value="1">';
		$html .= '<div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true"><input type="text" name="feur_honeypot" tabindex="-1" autocomplete="new-password" data-lpignore="true" value=""></div>';

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
		$messages = $this->status_messages();
		$error_statuses = array( 'invalid_link', 'invalid_email', 'invalid_token', 'profile_error' );
		if ( isset( $messages[ $status ] ) ) {
			$class = in_array( $status, $error_statuses, true ) ? 'feur-error' : 'feur-success';
			$html .= '<div class="feur-notice ' . esc_attr( $class ) . '">' . esc_html( $messages[ $status ] ) . '</div>';
		}
		return $html;
	}

	private function status_messages() {
		return array(
			'pending'        => __( 'Your account has been created and is awaiting approval. You will receive an email with a login link once an administrator approves your membership.', 'feur' ) . ' ' . $this->spam_hint(),
			'success'        => __( 'Your account has been created. You can now log in.', 'feur' ),
			'link_sent'      => __( 'If an account exists for that email address, a login link has been sent.', 'feur' ) . ' ' . $this->spam_hint(),
			'pending_login'  => __( 'That account is still pending approval. Please wait for an administrator to approve it.', 'feur' ),
			'denied'         => __( 'Your membership application was declined. Please contact an administrator for assistance.', 'feur' ),
			'invalid_link'   => __( 'That login link is invalid or has expired. Request a new one below.', 'feur' ),
			'invalid_email'  => __( 'Please enter a valid email address.', 'feur' ),
			'invalid_token'  => __( 'Invalid security token. Please try again.', 'feur' ),
			'members_only'   => __( 'This content is for members only. Please log in to continue.', 'feur' ),
			'logged_out'     => __( 'You have been logged out.', 'feur' ),
			'profile_saved'  => __( 'Your profile has been updated.', 'feur' ),
			'profile_error'  => __( 'Your profile could not be updated. Please try again.', 'feur' ),
		);
	}

	/**
	 * Reminder for the messages that promise an email: senders are not always in
	 * the recipient's inbox yet, particularly before the From domain passes
	 * DKIM/DMARC checks.
	 */
	private function spam_hint() {
		return __( 'If it has not arrived after a few minutes, check your spam or junk folder.', 'feur' );
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
