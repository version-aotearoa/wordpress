<?php
defined( 'ABSPATH' ) || exit;

/**
 * Minimal front-end profile editor.
 *
 * Members edit their own name and the fields flagged "editable in profile".
 * Login email and password are deliberately not editable here: changing the
 * email would silently redirect future magic links, and there is no password to
 * manage while login is magic-link only.
 */
class FEUR_Profile_Editor {

	const NONCE  = 'feur_profile_update';
	const FIELD  = 'feur_profile';
	const VIEW   = 'profile';

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_update' ) );
	}

	/** True when the profile form was requested on the account page. */
	public static function is_open() {
		$view = isset( $_GET['fe_view'] ) ? sanitize_key( wp_unslash( $_GET['fe_view'] ) ) : '';
		return self::VIEW === $view;
	}

	/** URL that opens the editor. */
	public static function url( $base = '' ) {
		$base = $base ? $base : FEUR_Page_Installer::account_url();
		return add_query_arg( 'fe_view', self::VIEW, $base );
	}

	public function maybe_update() {
		if ( empty( $_POST[ self::FIELD ] ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! isset( $_POST['feur_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['feur_profile_nonce'] ) ), self::NONCE ) ) {
			$this->redirect( 'invalid_token' );
		}

		$user    = wp_get_current_user();
		$first   = isset( $_POST['feur_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['feur_first_name'] ) ) : '';
		$last    = isset( $_POST['feur_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['feur_last_name'] ) ) : '';
		$display = trim( $first . ' ' . $last );

		$args = array(
			'ID'         => $user->ID,
			'first_name' => $first,
			'last_name'  => $last,
		);

		// Keep the display name in step only while it is still the derived one,
		// so a deliberately customised display name is never overwritten.
		$derived_old = trim( $user->first_name . ' ' . $user->last_name );
		if ( '' !== $display && ( $user->display_name === $derived_old || $user->display_name === $user->user_login ) ) {
			$args['display_name'] = $display;
		}

		$result = wp_update_user( $args );
		if ( is_wp_error( $result ) ) {
			$this->redirect( 'profile_error' );
		}

		foreach ( FEUR_Field_Repository::get_profile_fields() as $field ) {
			$name = 'fe_field_' . $field['id'];
			$raw  = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
			FEUR_Field_Repository::save_value( $user->ID, $field, FEUR_Field_Types::sanitize( $field, $raw ) );
		}

		$this->redirect( 'profile_saved' );
	}

	private function redirect( $status ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'fe_view'   => self::VIEW,
					'fe_status' => $status,
				),
				FEUR_Page_Installer::get_url()
			)
		);
		exit;
	}

	public static function form( $user ) {
		if ( ! $user instanceof WP_User ) {
			return '';
		}

		$html = '<form method="post" class="feur-form feur-profile-form" action="' . esc_url( FEUR_Page_Installer::get_url() ) . '">';
		$html .= '<input type="hidden" name="' . esc_attr( self::FIELD ) . '" value="1">';
		$html .= wp_nonce_field( self::NONCE, 'feur_profile_nonce', true, false );

		$html .= '<p><label for="feur_first_name">' . esc_html__( 'First name', 'feur' ) . '</label><br>';
		$html .= '<input type="text" id="feur_first_name" name="feur_first_name" class="feur-input" value="' . esc_attr( $user->first_name ) . '" autocomplete="given-name"></p>';

		$html .= '<p><label for="feur_last_name">' . esc_html__( 'Last name', 'feur' ) . '</label><br>';
		$html .= '<input type="text" id="feur_last_name" name="feur_last_name" class="feur-input" value="' . esc_attr( $user->last_name ) . '" autocomplete="family-name"></p>';

		foreach ( FEUR_Field_Repository::get_profile_fields() as $field ) {
			$value = FEUR_Field_Repository::value( $user->ID, $field );
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

		$html .= '<p class="feur-profile-email"><strong>' . esc_html__( 'Email', 'feur' ) . ':</strong> ' . esc_html( $user->user_email );
		$html .= '<br><small>' . esc_html__( 'Contact an administrator to change the email address on your account.', 'feur' ) . '</small></p>';

		$html .= '<p><button type="submit" class="feur-button">' . esc_html__( 'Save profile', 'feur' ) . '</button></p>';
		$html .= '</form>';

		return $html;
	}
}
