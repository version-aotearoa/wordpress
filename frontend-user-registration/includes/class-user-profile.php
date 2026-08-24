<?php
defined( 'ABSPATH' ) || exit;

class FEUR_User_Profile {

	const NONCE = 'feur_profile_fields';

	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'render' ) );
		add_action( 'edit_user_profile', array( $this, 'render' ) );
		add_action( 'personal_options_update', array( $this, 'save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );
	}

	public function render( $user ) {
		$fields = FEUR_Field_Repository::get_profile_fields();
		if ( empty( $fields ) ) {
			return;
		}
		wp_nonce_field( self::NONCE, self::NONCE );
		echo '<h2>' . esc_html__( 'Member Fields', 'feur' ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		foreach ( $fields as $field ) {
			$value = FEUR_Field_Repository::value( $user->ID, $field );
			echo '<tr>';
			echo '<th><label for="feur-field-' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label></th>';
			echo '<td>' . FEUR_Field_Types::render(
				$field,
				$value,
				array(
					'name'  => 'fe_field_' . $field['id'],
					'class' => 'regular-text',
				)
			) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}

	public function save( $user_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		foreach ( FEUR_Field_Repository::get_profile_fields() as $field ) {
			$name = 'fe_field_' . $field['id'];
			$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
			$value = FEUR_Field_Types::sanitize( $field, $raw );
			FEUR_Field_Repository::save_value( $user_id, $field, $value );
		}
	}
}
