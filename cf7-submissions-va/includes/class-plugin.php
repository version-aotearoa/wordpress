<?php
defined( 'ABSPATH' ) || exit;

final class CF7S_Plugin {

	const DB_VERSION_OPTION = 'cf7s_db_version';
	const DB_TABLE = 'cf7s_submissions';

	/**
	 * Capability required to view submissions. Deliberately not manage_options:
	 * other roles (Members Managers) can be granted it without gaining plugin,
	 * theme or settings access. Frontend User Registration grants this same
	 * capability to its Members Manager role - change both together.
	 */
	const CAP = 'cf7s_view_submissions';

	private static $instance = null;

	private function __construct() {
		// Keep the capability on administrators, including existing installs
		// that never re-ran the activation hook.
		add_action( 'admin_init', array( __CLASS__, 'ensure_caps' ) );

		if ( ! defined( 'WPCF7_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_cf7_notice' ) );
			return;
		}

		new CF7S_Capture();
		if ( is_admin() ) {
			new CF7S_Admin();
		}
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate() {
		self::ensure_caps();
		CF7S_DB::maybe_upgrade();
	}

	public static function ensure_caps() {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( self::CAP ) ) {
			$role->add_cap( self::CAP );
		}
	}

	public static function deactivate() {
	}

	public function missing_cf7_notice() {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Contact Form 7 Submissions requires the Contact Form 7 plugin to be active.', 'cf7s' ) . '</p></div>';
	}
}
