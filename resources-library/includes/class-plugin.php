<?php
defined( 'ABSPATH' ) || exit;

final class RL_Plugin {

	const PT = 'resource';
	const TAG_TAX = 'resource_tag';
	const FORMAT_TAX = 'resource_format';

	const FEATURED_META = 'rl_featured';
	const URL_META = 'rl_url';

	const FORMATS = array( 'Video', 'Link', 'Article' );

	const VERSION_OPTION = 'rl_version';
	const SETTINGS_OPTION = 'rl_settings';

	private static $default_settings = array(
		'posts_per_page'    => 6,
		'recent_tags_count' => 10,
		'accent_color'      => '#2271b1',
	);

	private static $instance = null;

	private function __construct() {
		add_action( 'init', array( 'RL_Post_Type', 'register' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
		new RL_Meta();
		new RL_Ajax();
		new RL_Page_Template();
		new RL_Admin_Columns();
		new RL_Settings();
		new RL_Reorder();
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate() {
		RL_Post_Type::register();
		flush_rewrite_rules();
		update_option( self::VERSION_OPTION, RL_VERSION, false );
		if ( false === get_option( self::SETTINGS_OPTION, false ) ) {
			add_option( self::SETTINGS_OPTION, self::$default_settings );
		}
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( get_option( self::VERSION_OPTION, '' ) !== RL_VERSION ) {
			flush_rewrite_rules();
			update_option( self::VERSION_OPTION, RL_VERSION, false );
		}
		$settings = self::get_settings();
		$accent = isset( $settings['accent_color'] ) ? $settings['accent_color'] : '';
		if ( $accent && ! preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $accent ) ) {
			$settings['accent_color'] = '#2271b1';
			update_option( self::SETTINGS_OPTION, $settings, false );
		}
	}

	public static function get_settings() {
		$saved = get_option( self::SETTINGS_OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::$default_settings );
	}

	public static function get_setting( $key, $default = '' ) {
		$settings = self::get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}
}
