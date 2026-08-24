<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Users_List {

	public function __construct() {
		add_filter( 'manage_users_columns', array( $this, 'columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'value' ), 10, 3 );
	}

	public function columns( $columns ) {
		foreach ( FEUR_Field_Repository::get_list_fields() as $field ) {
			$columns[ 'fe_' . $field['id'] ] = $field['label'];
		}
		return $columns;
	}

	public function value( $output, $column, $user_id ) {
		foreach ( FEUR_Field_Repository::get_list_fields() as $field ) {
			if ( 'fe_' . $field['id'] === $column ) {
				$value = FEUR_Field_Repository::value( $user_id, $field );
				return esc_html( FEUR_Field_Types::label( $field, $value ) );
			}
		}
		return $output;
	}
}
