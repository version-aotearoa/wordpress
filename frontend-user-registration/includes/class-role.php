<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Role {

	const ROLE    = 'member';
	const MANAGER = 'members_manager';
	const MANAGE  = 'feur_manage_members';

	public static function activate() {
		self::ensure();
	}

	public static function ensure() {
		if ( ! get_role( self::ROLE ) ) {
			add_role( self::ROLE, __( 'Member', 'feur' ), array( 'read' => true ) );
		}
		if ( ! get_role( self::MANAGER ) ) {
			add_role(
				self::MANAGER,
				__( 'Members Manager', 'feur' ),
				array(
					'read'       => true,
					self::MANAGE => true,
				)
			);
		}
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( self::MANAGE ) ) {
			$admin->add_cap( self::MANAGE );
		}
	}

	public static function is_member( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		return $user instanceof WP_User && in_array( self::ROLE, (array) $user->roles, true );
	}

	public static function is_manager( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		return $user instanceof WP_User && in_array( self::MANAGER, (array) $user->roles, true );
	}

	public static function has_member_access( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		if ( ! $user instanceof WP_User ) {
			return false;
		}
		if ( in_array( self::ROLE, (array) $user->roles, true ) ) {
			return true;
		}
		return $user->has_cap( self::MANAGE ) || $user->has_cap( 'manage_options' );
	}
}
