<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Admin_Menu {

	private $field_builder;

	public function __construct( $field_builder ) {
		$this->field_builder = $field_builder;
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function menu() {
		add_users_page(
			__( 'Registration', 'feur' ),
			__( 'Registration', 'feur' ),
			'manage_options',
			'feur-registration',
			array( $this, 'render' )
		);
	}

	public function render() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'fields';
		if ( ! in_array( $tab, array( 'fields', 'settings' ), true ) ) {
			$tab = 'fields';
		}
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'User Registration', 'feur' ) . '</h1>';
		echo '<nav class="nav-tab-wrapper">';
		echo '<a class="nav-tab' . ( 'fields' === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'users.php?page=feur-registration&tab=fields' ) ) . '">' . esc_html__( 'Fields', 'feur' ) . '</a>';
		echo '<a class="nav-tab' . ( 'settings' === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'users.php?page=feur-registration&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'feur' ) . '</a>';
		echo '</nav>';
		echo '<div style="margin-top:12px">';
		if ( 'fields' === $tab ) {
			$this->field_builder->render();
		} else {
			echo '<form method="post" action="options.php">';
			settings_fields( 'feur_settings_group' );
			do_settings_sections( 'feur-settings' );
			submit_button();
			echo '</form>';
		}
		echo '</div>';
		echo '</div>';
	}

	public function register_settings() {
		register_setting(
			'feur_settings_group',
			'feur_settings',
			array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) )
		);

		add_settings_section( 'feur_general', __( 'General', 'feur' ), '__return_false', 'feur-settings' );
		add_settings_field( 'require_approval', __( 'Approval', 'feur' ), array( $this, 'field_require_approval' ), 'feur-settings', 'feur_general' );
		add_settings_field( 'default_role', __( 'Default role', 'feur' ), array( $this, 'field_default_role' ), 'feur-settings', 'feur_general' );
		add_settings_field( 'magic_link_expiry', __( 'Magic link expiry', 'feur' ), array( $this, 'field_magic_link_expiry' ), 'feur-settings', 'feur_general' );
		add_settings_field( 'login_redirect', __( 'Login redirect', 'feur' ), array( $this, 'field_login_redirect' ), 'feur-settings', 'feur_general' );
		add_settings_field( 'terms_text', __( 'Terms text', 'feur' ), array( $this, 'field_terms_text' ), 'feur-settings', 'feur_general' );

		add_settings_section( 'feur_emails', __( 'Emails', 'feur' ), '__return_false', 'feur-settings' );
		add_settings_field( 'pending_email_enabled', __( 'Pending notice', 'feur' ), array( $this, 'field_pending_email_enabled' ), 'feur-settings', 'feur_emails' );
		add_settings_field( 'admin_notify_enabled', __( 'Admin notification', 'feur' ), array( $this, 'field_admin_notify_enabled' ), 'feur-settings', 'feur_emails' );
		add_settings_field( 'magic_link_email_enabled', __( 'Magic link email', 'feur' ), array( $this, 'field_magic_link_email_enabled' ), 'feur-settings', 'feur_emails' );
		add_settings_field( 'deny_email_enabled', __( 'Decline email', 'feur' ), array( $this, 'field_deny_email_enabled' ), 'feur-settings', 'feur_emails' );

		add_settings_section( 'feur_access', __( 'Member Access', 'feur' ), array( $this, 'access_section_text' ), 'feur-settings' );
		add_settings_field( 'restricted_post_types', __( 'Members-only content', 'feur' ), array( $this, 'field_restricted_post_types' ), 'feur-settings', 'feur_access' );
		add_settings_field( 'access_page', __( 'Member content page', 'feur' ), array( $this, 'field_access_page' ), 'feur-settings', 'feur_access' );
	}

	public function access_section_text() {
		echo '<p>' . esc_html__( 'Restrict entire post types to logged-in Members.', 'feur' ) . '</p>';
	}

	public function field_require_approval() {
		$this->field_checkbox( 'require_approval', __( 'Require admin approval before a member can log in.', 'feur' ) );
	}

	public function field_default_role() {
		$current = FEUR_Plugin::get_setting( 'default_role', 'member' );
		$roles = get_editable_roles();
		echo '<select name="feur_settings[default_role]">';
		foreach ( $roles as $key => $role ) {
			if ( empty( $role['capabilities']['read'] ) ) {
				continue;
			}
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $current, $key, false ) . '>' . esc_html( translate_user_role( $role['name'] ) ) . '</option>';
		}
		echo '</select>';
	}

	public function field_magic_link_expiry() {
		$value = (int) FEUR_Plugin::get_setting( 'magic_link_expiry', 48 );
		echo '<input type="number" min="1" step="1" name="feur_settings[magic_link_expiry]" value="' . esc_attr( $value ) . '" class="small-text"> ';
		echo '<span class="description">' . esc_html__( 'Hours before a magic login link expires.', 'feur' ) . '</span>';
	}

	public function field_login_redirect() {
		$value = FEUR_Plugin::get_setting( 'login_redirect', '' );
		echo '<input type="url" name="feur_settings[login_redirect]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr( FEUR_Page_Installer::get_url() ) . '"> ';
		echo '<span class="description">' . esc_html__( 'Where members land after logging in. Leave empty for the account page.', 'feur' ) . '</span>';
	}

	public function field_terms_text() {
		$value = FEUR_Plugin::get_setting( 'terms_text', '' );
		echo '<textarea name="feur_settings[terms_text]" rows="2" class="large-text">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Shown next to a required terms checkbox. Leave empty to disable.', 'feur' ) . '</p>';
	}

	public function field_pending_email_enabled() {
		$this->field_checkbox( 'pending_email_enabled', __( 'Email members a confirmation when their registration is awaiting approval.', 'feur' ) );
	}

	public function field_admin_notify_enabled() {
		$this->field_checkbox( 'admin_notify_enabled', __( 'Notify the admin by email when someone registers.', 'feur' ) );
	}

	public function field_magic_link_email_enabled() {
		$this->field_checkbox( 'magic_link_email_enabled', __( 'Email members their magic login link upon approval.', 'feur' ) );
	}

	public function field_deny_email_enabled() {
		$this->field_checkbox( 'deny_email_enabled', __( 'Email members when their application is declined.', 'feur' ) );
	}

	public function field_restricted_post_types() {
		$selected = (array) FEUR_Plugin::get_setting( 'restricted_post_types', array() );
		$types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $types as $type ) {
			echo '<label style="display:block;margin:.25em 0">';
			echo '<input type="checkbox" name="feur_settings[restricted_post_types][]" value="' . esc_attr( $type->name ) . '"' . checked( in_array( $type->name, $selected, true ), true, false ) . '> ';
			echo esc_html( $type->labels->name ) . ' <code>' . esc_html( $type->name ) . '</code>';
			echo '</label>';
		}
		echo '<p class="description">' . esc_html__( 'Content of these types is visible only to logged-in Members. Everyone else is redirected to the account page.', 'feur' ) . '</p>';
	}

	public function field_access_page() {
		$current = (int) FEUR_Plugin::get_setting( 'access_page_id', 0 );
		echo '<select name="feur_settings[access_page_id]">';
		echo '<option value="0"' . selected( $current, 0, false ) . '>' . esc_html__( 'No page (content-type restriction only)', 'feur' ) . '</option>';
		$pages = get_pages( array( 'sort_column' => 'menu_order, post_title' ) );
		foreach ( $pages as $page ) {
			$id = (int) $page->ID;
			$permalink = get_permalink( $id );
			$label = $page->post_title . ( $permalink ? ' (' . $permalink . ')' : '' );
			echo '<option value="' . esc_attr( $id ) . '"' . selected( $current, $id, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<p><label for="feur_access_page_new">' . esc_html__( 'Or create a new page:', 'feur' ) . '</label> ';
		echo '<input type="text" id="feur_access_page_new" name="feur_settings[access_page_new]" value="" class="regular-text" placeholder="' . esc_attr__( 'Page title', 'feur' ) . '" /></p>';
		echo '<p class="description">' . esc_html__( 'Non-members are redirected to the login page when they visit this page. Choose the page where member content is accessed, or create one.', 'feur' ) . '</p>';
	}

	private function field_checkbox( $key, $label ) {
		$value = (bool) FEUR_Plugin::get_setting( $key );
		echo '<label>';
		echo '<input type="checkbox" name="feur_settings[' . esc_attr( $key ) . ']" value="1"' . checked( $value, true, false ) . '> ';
		echo esc_html( $label );
		echo '</label>';
	}

	public function sanitize_settings( $input ) {
		$settings = FEUR_Plugin::get_settings();
		$input = is_array( $input ) ? $input : array();

		$settings['require_approval'] = ! empty( $input['require_approval'] ) ? 1 : 0;

		$role = isset( $input['default_role'] ) ? sanitize_key( $input['default_role'] ) : 'member';
		$settings['default_role'] = get_role( $role ) ? $role : 'member';

		$expiry = isset( $input['magic_link_expiry'] ) ? absint( $input['magic_link_expiry'] ) : 48;
		$settings['magic_link_expiry'] = max( 1, $expiry );

		$settings['login_redirect'] = ! empty( $input['login_redirect'] ) ? esc_url_raw( trim( $input['login_redirect'] ) ) : '';
		$settings['terms_text'] = isset( $input['terms_text'] ) ? sanitize_textarea_field( $input['terms_text'] ) : '';

		$settings['pending_email_enabled'] = ! empty( $input['pending_email_enabled'] ) ? 1 : 0;
		$settings['admin_notify_enabled'] = ! empty( $input['admin_notify_enabled'] ) ? 1 : 0;
		$settings['magic_link_email_enabled'] = ! empty( $input['magic_link_email_enabled'] ) ? 1 : 0;
		$settings['deny_email_enabled'] = ! empty( $input['deny_email_enabled'] ) ? 1 : 0;

		$restricted = array();
		if ( ! empty( $input['restricted_post_types'] ) && is_array( $input['restricted_post_types'] ) ) {
			$public = get_post_types( array( 'public' => true ), 'names' );
			foreach ( $input['restricted_post_types'] as $pt ) {
				$pt = sanitize_key( $pt );
				if ( in_array( $pt, $public, true ) ) {
					$restricted[] = $pt;
				}
			}
		}
		$settings['restricted_post_types'] = $restricted;

		$access_page_id = isset( $input['access_page_id'] ) ? absint( $input['access_page_id'] ) : 0;
		$access_page_new = isset( $input['access_page_new'] ) ? trim( sanitize_text_field( $input['access_page_new'] ) ) : '';
		if ( '' !== $access_page_new ) {
			$created = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => $access_page_new,
				)
			);
			if ( ! is_wp_error( $created ) && $created ) {
				$access_page_id = (int) $created;
			}
		} elseif ( $access_page_id && 'page' !== get_post_type( $access_page_id ) ) {
			$access_page_id = 0;
		}
		$settings['access_page_id'] = $access_page_id;

		return $settings;
	}
}
