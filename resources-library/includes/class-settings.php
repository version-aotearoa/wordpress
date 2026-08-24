<?php
defined( 'ABSPATH' ) || exit;

class RL_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . RL_Plugin::PT,
			__( 'Settings', 'rl' ),
			__( 'Settings', 'rl' ),
			'manage_options',
			'rl-settings',
			array( $this, 'render' )
		);
	}

	public function register() {
		register_setting(
			'rl_settings_group',
			RL_Plugin::SETTINGS_OPTION,
			array( 'sanitize_callback' => array( $this, 'sanitize' ) )
		);
		add_settings_section( 'rl_settings_main', __( 'Library Settings', 'rl' ), '__return_false', 'rl-settings' );
		add_settings_field( 'posts_per_page', __( 'Cards per page', 'rl' ), array( $this, 'field_posts_per_page' ), 'rl-settings', 'rl_settings_main' );
		add_settings_field( 'recent_tags_count', __( 'Recent tags count', 'rl' ), array( $this, 'field_recent_tags_count' ), 'rl-settings', 'rl_settings_main' );
		add_settings_field( 'accent_color', __( 'Accent colour', 'rl' ), array( $this, 'field_accent_color' ), 'rl-settings', 'rl_settings_main' );
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Resources Library Settings', 'rl' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'rl_settings_group' );
		do_settings_sections( 'rl-settings' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	public function field_posts_per_page() {
		$value = (int) RL_Plugin::get_setting( 'posts_per_page', 6 );
		echo '<input type="number" min="1" step="1" name="' . esc_attr( RL_Plugin::SETTINGS_OPTION ) . '[posts_per_page]" value="' . esc_attr( $value ) . '" class="small-text"> ';
		echo '<p class="description">' . esc_html__( 'How many resource cards load before "Load more" appears.', 'rl' ) . '</p>';
	}

	public function field_recent_tags_count() {
		$value = (int) RL_Plugin::get_setting( 'recent_tags_count', 10 );
		echo '<input type="number" min="1" step="1" name="' . esc_attr( RL_Plugin::SETTINGS_OPTION ) . '[recent_tags_count]" value="' . esc_attr( $value ) . '" class="small-text"> ';
		echo '<p class="description">' . esc_html__( 'How many recently used tags appear in the resource editor.', 'rl' ) . '</p>';
	}

	public function field_accent_color() {
		$value = RL_Plugin::get_setting( 'accent_color', '#2271b1' );
		echo '<input type="text" class="rl-color-field" name="' . esc_attr( RL_Plugin::SETTINGS_OPTION ) . '[accent_color]" value="' . esc_attr( $value ) . '" data-default-color="#2271b1"> ';
		echo '<p class="description">' . esc_html__( 'Colour used for active tags, format filters and buttons in the library.', 'rl' ) . '</p>';
	}

	public function admin_assets( $hook ) {
		if ( ! isset( $_GET['page'] ) || 'rl-settings' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){$(".rl-color-field").wpColorPicker();});'
		);
	}

	public function sanitize( $input ) {
		$settings = RL_Plugin::get_settings();
		$input = is_array( $input ) ? $input : array();

		$ppp = isset( $input['posts_per_page'] ) ? absint( $input['posts_per_page'] ) : 6;
		$settings['posts_per_page'] = max( 1, min( 100, $ppp ) );

		$rtc = isset( $input['recent_tags_count'] ) ? absint( $input['recent_tags_count'] ) : 10;
		$settings['recent_tags_count'] = max( 1, min( 50, $rtc ) );

		$accent = isset( $input['accent_color'] ) ? sanitize_text_field( $input['accent_color'] ) : '';
		$settings['accent_color'] = self::sanitize_hex( $accent );

		return $settings;
	}

	private static function sanitize_hex( $color ) {
		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color, $matches ) ) {
			return '#' . strtolower( $matches[1] );
		}
		return '#2271b1';
	}
}
