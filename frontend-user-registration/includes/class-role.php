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
		} else {
			self::rename_role( self::ROLE, __( 'Member', 'feur' ) );
		}
		if ( ! get_role( self::MANAGER ) ) {
			add_role( self::MANAGER, __( 'Members Manager', 'feur' ), self::manager_caps() );
		} else {
			// Existing installs: top up caps added after the role was created.
			self::grant_caps( self::MANAGER, self::manager_caps() );
		}
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( self::MANAGE ) ) {
			$admin->add_cap( self::MANAGE );
		}
	}

	/**
	 * Capabilities for the Members Manager role.
	 *
	 * Members Managers are content managers: alongside member approval they get
	 * the default post capabilities plus the separate page capabilities, which
	 * is what the Resources post type and Pages use. Idempotent on purpose -
	 * ensure() runs on every request.
	 */
	private static function manager_caps() {
		return array(
			'read'                   => true,
			self::MANAGE             => true,
			'edit_posts'             => true,
			'edit_others_posts'      => true,
			'edit_published_posts'   => true,
			'publish_posts'          => true,
			'delete_posts'           => true,
			'delete_others_posts'    => true,
			'delete_published_posts' => true,
			'edit_pages'             => true,
			'edit_others_pages'      => true,
			'edit_published_pages'   => true,
			'publish_pages'          => true,
			'delete_pages'           => true,
			'delete_others_pages'    => true,
			'delete_published_pages' => true,
			'upload_files'           => true,
			'manage_categories'      => true,
			// Shared with the CF7 Submissions VA plugin (CF7S_Plugin::CAP);
			// change both together.
			'cf7s_view_submissions'  => true,
		);
	}

	private static function grant_caps( $role_key, $caps ) {
		$role = get_role( $role_key );
		if ( ! $role ) {
			return;
		}
		foreach ( $caps as $cap => $grant ) {
			if ( $grant && ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Correct a stored role label that was created with the legacy plural name.
	 *
	 * Only the known plural label is rewritten, so deliberately customised role
	 * names are left alone. The role option and the in-memory role object are
	 * both updated so the Users screen reflects the change immediately.
	 */
	private static function rename_role( $key, $name ) {
		$roles = wp_roles();
		if ( ! isset( $roles->roles[ $key ] ) ) {
			return;
		}
		$current = $roles->roles[ $key ]['name'];
		if ( 'Members' !== $current && __( 'Members', 'feur' ) !== $current ) {
			return;
		}
		$roles->roles[ $key ]['name'] = $name;
		if ( isset( $roles->role_objects[ $key ] ) ) {
			$roles->role_objects[ $key ]->name = $name;
		}
		update_option( $roles->role_key, $roles->roles );
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
