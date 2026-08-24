<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Field_Repository {

	const OPTION = 'feur_fields';

	private static $cache = null;

	public static function get_fields() {
		if ( null === self::$cache ) {
			$raw = get_option( self::OPTION, array() );
			self::$cache = is_array( $raw ) ? array_values( $raw ) : array();
		}
		return self::$cache;
	}

	public static function get_field( $id ) {
		foreach ( self::get_fields() as $field ) {
			if ( isset( $field['id'] ) && $field['id'] === $id ) {
				return $field;
			}
		}
		return null;
	}

	public static function save_fields( $fields ) {
		self::$cache = null;
		$clean = array();
		foreach ( $fields as $field ) {
			if ( is_array( $field ) && ! empty( $field['id'] ) ) {
				$clean[] = $field;
			}
		}
		return update_option( self::OPTION, $clean, false );
	}

	public static function get_public_fields() {
		return array_values(
			array_filter( self::get_fields(), function ( $f ) {
				return empty( $f['admin_only'] );
			} )
		);
	}

	public static function get_list_fields() {
		return array_values(
			array_filter( self::get_fields(), function ( $f ) {
				return ! empty( $f['show_in_admin_list'] );
			} )
		);
	}

	public static function get_profile_fields() {
		return array_values(
			array_filter( self::get_fields(), function ( $f ) {
				return ! empty( $f['editable_in_profile'] );
			} )
		);
	}

	public static function get_admin_edit_fields() {
		return array_values(
			array_filter( self::get_fields(), function ( $f ) {
				return ! empty( $f['editable_in_profile'] ) || ! empty( $f['admin_only'] );
			} )
		);
	}

	public static function meta_key( $field ) {
		return 'fe_' . $field['id'];
	}

	public static function value( $user_id, $field ) {
		return get_user_meta( $user_id, self::meta_key( $field ), true );
	}

	public static function save_value( $user_id, $field, $value ) {
		update_user_meta( $user_id, self::meta_key( $field ), $value );
	}

	public static function delete_field( $id ) {
		$fields = self::get_fields();
		foreach ( $fields as $i => $field ) {
			if ( isset( $field['id'] ) && $field['id'] === $id ) {
				array_splice( $fields, $i, 1 );
				self::save_fields( $fields );
				return true;
			}
		}
		return false;
	}

	public static function move( $id, $dir ) {
		$dir = $dir < 0 ? -1 : 1;
		$fields = self::get_fields();
		foreach ( $fields as $i => $field ) {
			if ( isset( $field['id'] ) && $field['id'] === $id ) {
				$swap = $i + $dir;
				if ( isset( $fields[ $swap ] ) ) {
					$tmp = $fields[ $i ];
					$fields[ $i ] = $fields[ $swap ];
					$fields[ $swap ] = $tmp;
					self::save_fields( $fields );
				}
				return;
			}
		}
	}
}
