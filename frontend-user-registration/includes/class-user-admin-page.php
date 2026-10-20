<?php
defined( 'ABSPATH' ) || exit;

class FEUR_User_Admin_Page {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_feur_promote', array( $this, 'promote' ) );
		add_action( 'admin_post_feur_demote', array( $this, 'demote' ) );
		add_action( 'admin_post_feur_edit_member', array( $this, 'save_member' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'Members', 'feur' ),
			__( 'Members', 'feur' ),
			FEUR_Role::MANAGE,
			'feur-members',
			array( $this, 'render' ),
			'dashicons-groups',
			71
		);
		add_submenu_page(
			'feur-members',
			__( 'All Members', 'feur' ),
			__( 'All Members', 'feur' ),
			FEUR_Role::MANAGE,
			'feur-members',
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! current_user_can( FEUR_Role::MANAGE ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		if ( 'edit' === $action ) {
			$this->render_edit( isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0 );
			return;
		}

		$users = get_users(
			array(
				'role__in' => array( FEUR_Role::ROLE, FEUR_Role::MANAGER ),
				'orderby'  => 'user_login',
				'order'    => 'ASC',
			)
		);
		$pending_count = count(
			array_filter( $users, function ( $user ) {
				return (bool) get_user_meta( $user->ID, 'fe_pending', true );
			} )
		);

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Members', 'feur' ) . '</h1>';
		if ( $pending_count ) {
			echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( _n( '%s member is awaiting approval.', '%s members are awaiting approval.', $pending_count, 'feur' ), $pending_count ) ) . '</p></div>';
		}
		if ( isset( $_GET['feur_approved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Member approved. A login link email was sent.', 'feur' ) . '</p></div>';
		}
		if ( isset( $_GET['feur_denied'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Member denied.', 'feur' ) . '</p></div>';
		}
		if ( isset( $_GET['feur_pending'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Member moved back to pending.', 'feur' ) . '</p></div>';
		}
		if ( isset( $_GET['feur_promoted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Member promoted to Members Manager.', 'feur' ) . '</p></div>';
		}
		if ( isset( $_GET['feur_demoted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Members Manager demoted to Member.', 'feur' ) . '</p></div>';
		}

		$fields = FEUR_Field_Repository::get_admin_edit_fields();

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'User', 'feur' ) . '</th>';
		echo '<th>' . esc_html__( 'Email', 'feur' ) . '</th>';
		echo '<th>' . esc_html__( 'Role', 'feur' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'feur' ) . '</th>';
		foreach ( $fields as $field ) {
			echo '<th>' . esc_html( $field['label'] ) . '</th>';
		}
		echo '<th>' . esc_html__( 'Actions', 'feur' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';
		foreach ( $users as $user ) {
			$status = FEUR_Approval::status( $user->ID );
			$status_labels = array(
				'pending'  => __( 'Pending', 'feur' ),
				'approved' => __( 'Approved', 'feur' ),
				'denied'   => __( 'Denied', 'feur' ),
			);
			$is_manager = in_array( FEUR_Role::MANAGER, (array) $user->roles, true );
			echo '<tr>';
			echo '<td>' . esc_html( $user->display_name ? $user->display_name : $user->user_login ) . '</td>';
			echo '<td>' . esc_html( $user->user_email ) . '</td>';
			echo '<td>' . esc_html( $is_manager ? __( 'Members Manager', 'feur' ) : __( 'Member', 'feur' ) ) . '</td>';
			echo '<td><span class="feur-status feur-status-' . esc_attr( $status ) . '">' . esc_html( $status_labels[ $status ] ) . '</span></td>';
			foreach ( $fields as $field ) {
				$value = FEUR_Field_Repository::value( $user->ID, $field );
				$label = FEUR_Field_Types::label( $field, $value );
				echo '<td>' . ( '' !== $label && null !== $label ? esc_html( $label ) : '<span aria-hidden="true">&mdash;</span>' ) . '</td>';
			}
			echo '<td>' . self::actions( $user, $status ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '</div>';
	}

	private function render_edit( $user_id ) {
		if ( ! current_user_can( FEUR_Role::MANAGE ) ) {
			return;
		}
		$user = get_user_by( 'id', $user_id );
		echo '<div class="wrap">';
		if ( ! $user || ! in_array( FEUR_Role::ROLE, (array) $user->roles, true ) ) {
			echo '<h1>' . esc_html__( 'Edit Member', 'feur' ) . '</h1>';
			echo '<p>' . esc_html__( 'That account is not an editable member.', 'feur' ) . '</p>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=feur-members' ) ) . '">&larr; ' . esc_html__( 'Back to members', 'feur' ) . '</a></p>';
			echo '</div>';
			return;
		}

		$fields = FEUR_Field_Repository::get_admin_edit_fields();

		echo '<h1>' . esc_html__( 'Edit Member', 'feur' ) . '</h1>';
		if ( isset( $_GET['feur_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Member updated.', 'feur' ) . '</p></div>';
		}
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=feur-members' ) ) . '">&larr; ' . esc_html__( 'Back to members', 'feur' ) . '</a></p>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="feur_edit_member">';
		echo '<input type="hidden" name="user" value="' . esc_attr( $user_id ) . '">';
		wp_nonce_field( 'feur_edit_member_' . $user_id );
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th>' . esc_html__( 'Email', 'feur' ) . '</th><td>' . esc_html( $user->user_email ) . ' <span class="description">' . esc_html__( 'The login email cannot be changed here.', 'feur' ) . '</span></td></tr>';
		echo '<tr><th><label for="feur_first_name">' . esc_html__( 'First name', 'feur' ) . '</label></th><td><input type="text" id="feur_first_name" name="first_name" value="' . esc_attr( $user->first_name ) . '" class="regular-text"></td></tr>';
		echo '<tr><th><label for="feur_last_name">' . esc_html__( 'Last name', 'feur' ) . '</label></th><td><input type="text" id="feur_last_name" name="last_name" value="' . esc_attr( $user->last_name ) . '" class="regular-text"></td></tr>';
		foreach ( $fields as $field ) {
			$value = FEUR_Field_Repository::value( $user_id, $field );
			echo '<tr><th><label for="feur-field-' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label></th>';
			echo '<td>' . FEUR_Field_Types::render( $field, $value, array( 'name' => 'fe_field_' . $field['id'], 'class' => 'regular-text' ) ) . '</td></tr>';
		}
		echo '</table>';
		submit_button( __( 'Save Member', 'feur' ) );
		echo '</form>';
		echo '</div>';
	}

	public function promote() {
		$user_id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
		check_admin_referer( 'feur_promote_' . $user_id );
		if ( ! current_user_can( FEUR_Role::MANAGE ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'feur' ) );
		}
		$user = get_user_by( 'id', $user_id );
		if ( $user && in_array( FEUR_Role::ROLE, (array) $user->roles, true ) ) {
			$user->set_role( FEUR_Role::MANAGER );
			FEUR_Approval::set_status( $user_id, 'approved' );
		}
		$this->back( 'feur_promoted' );
	}

	public function demote() {
		$user_id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
		check_admin_referer( 'feur_demote_' . $user_id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Only administrators can demote a Members Manager.', 'feur' ) );
		}
		if ( $user_id === get_current_user_id() ) {
			wp_die( esc_html__( 'You cannot demote yourself.', 'feur' ) );
		}
		$user = get_user_by( 'id', $user_id );
		if ( $user && in_array( FEUR_Role::MANAGER, (array) $user->roles, true ) ) {
			$user->set_role( FEUR_Role::ROLE );
		}
		$this->back( 'feur_demoted' );
	}

	public function save_member() {
		$user_id = isset( $_POST['user'] ) ? absint( $_POST['user'] ) : 0;
		check_admin_referer( 'feur_edit_member_' . $user_id );
		if ( ! current_user_can( FEUR_Role::MANAGE ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'feur' ) );
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! $user || ! in_array( FEUR_Role::ROLE, (array) $user->roles, true ) ) {
			wp_die( esc_html__( 'You are not allowed to edit that member.', 'feur' ) );
		}

		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';

		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => trim( $first_name . ' ' . $last_name ),
			)
		);

		foreach ( FEUR_Field_Repository::get_admin_edit_fields() as $field ) {
			$name  = 'fe_field_' . $field['id'];
			$raw   = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
			$value = FEUR_Field_Types::sanitize( $field, $raw );
			FEUR_Field_Repository::save_value( $user_id, $field, $value );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'feur-members', 'action' => 'edit', 'user' => $user_id, 'feur_updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function back( $notice ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'feur-members', $notice => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function action_url( $action, $user_id ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&user=' . $user_id ), $action . '_' . $user_id );
	}

	private static function actions( $user, $status ) {
		$user_id = $user->ID;

		if ( in_array( FEUR_Role::MANAGER, (array) $user->roles, true ) ) {
			if ( current_user_can( 'manage_options' ) && get_current_user_id() !== $user_id ) {
				return '<a class="button button-small" href="' . esc_url( self::action_url( 'feur_demote', $user_id ) ) . '">' . esc_html__( 'Demote to Member', 'feur' ) . '</a>';
			}
			return '<span aria-hidden="true">&mdash;</span>';
		}

		$edit = add_query_arg( array( 'page' => 'feur-members', 'action' => 'edit', 'user' => $user_id ), admin_url( 'admin.php' ) );
		$html = '<a class="button button-small" href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'feur' ) . '</a> ';

		$approve = '<a class="button button-primary button-small" href="' . esc_url( self::action_url( 'feur_approve', $user_id ) ) . '">' . esc_html__( 'Approve', 'feur' ) . '</a>';
		$deny    = '<a class="button button-small" href="' . esc_url( self::action_url( 'feur_deny', $user_id ) ) . '">' . esc_html__( 'Deny', 'feur' ) . '</a>';
		$pending = '<a class="button button-small" href="' . esc_url( self::action_url( 'feur_pending', $user_id ) ) . '">' . esc_html__( 'Set pending', 'feur' ) . '</a>';

		switch ( $status ) {
			case 'approved':
				$html .= $deny . ' ' . $pending;
				break;
			case 'denied':
				$html .= $approve . ' ' . $pending;
				break;
			default:
				$html .= $approve . ' ' . $deny;
		}

		$html .= ' <a class="button button-small" href="' . esc_url( self::action_url( 'feur_promote', $user_id ) ) . '">' . esc_html__( 'Promote to Manager', 'feur' ) . '</a>';

		return $html;
	}
}
