<?php
defined( 'ABSPATH' ) || exit;

final class CF7S_Plugin {

	const DB_VERSION_OPTION = 'cf7s_db_version';
	const DB_TABLE = 'cf7s_submissions';

	private static $instance = null;

	private function __construct() {
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
		CF7S_DB::maybe_upgrade();
	}

	public static function deactivate() {
	}

	public function missing_cf7_notice() {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Contact Form 7 Submissions requires the Contact Form 7 plugin to be active.', 'cf7s' ) . '</p></div>';
	}
}
