<?php
defined( 'ABSPATH' ) || exit;

final class FEUR_Plugin {

	private static $instance = null;

	private static $default_settings = array(
		'require_approval'         => 1,
		'default_role'             => 'member',
		'magic_link_expiry'        => 48,
		'login_redirect'           => '',
		'terms_text'               => '',
		'pending_email_enabled'    => 1,
		'admin_notify_enabled'     => 1,
		'magic_link_email_enabled' => 1,
		'deny_email_enabled'       => 1,
		'restricted_post_types'    => array(),
		'access_page_id'           => 0,
	);

	private function __construct() {
		add_action( 'init', array( 'FEUR_Role', 'ensure' ) );
		add_action( 'init', array( 'FEUR_Page_Installer', 'init' ) );

		FEUR_Magic_Link::init();
		new FEUR_Registration_Handler();
		new FEUR_Login_Handler();
		new FEUR_Approval();
		new FEUR_Shortcode();
		new FEUR_User_Profile();
		new FEUR_Users_List();
		new FEUR_User_Admin_Page();
		new FEUR_Member_Access();
		new FEUR_Admin_Menu( new FEUR_Field_Builder() );
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate() {
		FEUR_Role::ensure();
		FEUR_Page_Installer::ensure_page();
		if ( false === get_option( 'feur_settings', false ) ) {
			add_option( 'feur_settings', self::$default_settings );
		}
		if ( false === get_option( 'feur_fields', false ) ) {
			add_option( 'feur_fields', array() );
		}
	}

	public static function deactivate() {
	}

	public static function get_settings() {
		$saved = get_option( 'feur_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::$default_settings );
	}

	public static function get_setting( $key, $default = '' ) {
		$settings = self::get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	public static function login_redirect( $redirect = '' ) {
		if ( $redirect ) {
			$url = wp_validate_redirect( $redirect, '' );
			if ( $url ) {
				return $url;
			}
		}
		$setting = self::get_setting( 'login_redirect', '' );
		if ( $setting ) {
			return esc_url_raw( $setting );
		}
		return FEUR_Page_Installer::get_url();
	}
}
