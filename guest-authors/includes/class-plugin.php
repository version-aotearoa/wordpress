<?php
defined( 'ABSPATH' ) || exit;

final class GA_Plugin {

	const PT         = 'guest_author';
	const ARCHIVE_SLUG = 'guest-authors';
	const SINGLE_SLUG  = 'guest-author';

	const META_GUEST_ID = '_ga_guest_author';

	const JOB_TITLE_META        = '_ga_job_title';
	const COMPANY_META          = '_ga_company';
	const WEBSITE_META          = '_ga_website';
	const SOCIAL_X_META         = '_ga_social_x';
	const SOCIAL_LINKEDIN_META  = '_ga_social_linkedin';
	const EMAIL_META            = '_ga_email';
	const LINKED_USER_META      = '_ga_linked_user';

	const VERSION_OPTION = 'ga_version';

	private static $instance = null;

	private function __construct() {
		add_action( 'init', array( 'GA_Post_Type', 'register' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );

		new GA_Profile();
		new GA_Meta();
		new GA_Author_Override();
		new GA_Admin_Columns();
		new GA_Render();
		new GA_Rest();
		new GA_Shortcode();
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate() {
		GA_Post_Type::register();
		flush_rewrite_rules();
		update_option( self::VERSION_OPTION, GA_VERSION, false );
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( get_option( self::VERSION_OPTION, '' ) !== GA_VERSION ) {
			GA_Post_Type::register();
			flush_rewrite_rules();
			update_option( self::VERSION_OPTION, GA_VERSION, false );
		}
	}

	/**
	 * Post types a guest author can be attributed to.
	 *
	 * @return string[]
	 */
	public static function supported_post_types() {
		$types = apply_filters( 'ga_supported_post_types', array( 'post' ) );
		$types = array_map( 'sanitize_key', (array) $types );
		return array_values( array_filter( $types ) );
	}

	public static function is_supported( $post_type ) {
		return in_array( $post_type, self::supported_post_types(), true );
	}
}
