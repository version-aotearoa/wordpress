<?php
defined( 'ABSPATH' ) || exit;

class FEUR_User_Admin_Page {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'Members', 'feur' ),
			__( 'Members', 'feur' ),
			'manage_options',
			'feur-members',
			array( $this, 'render' ),
			'dashicons-groups',
			71
		);
		add_submenu_page(
			'feur-members',
			__( 'All Members', 'feur' ),
			__( 'All Members', 'feur' ),
			'manage_options',
			'feur-members',
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$users = get_users(
			array(
				'role'    => 'member',
				'orderby' => 'user_login',
				'order'   => 'ASC',
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

		$fields = FEUR_Field_Repository::get_admin_edit_fields();

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'User', 'feur' ) . '</th>';
		echo '<th>' . esc_html__( 'Email', 'feur' ) . '</th>';
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
			echo '<tr>';
			echo '<td>' . esc_html( $user->display_name ? $user->display_name : $user->user_login ) . '</td>';
			echo '<td>' . esc_html( $user->user_email ) . '</td>';
			echo '<td><span class="feur-status feur-status-' . esc_attr( $status ) . '">' . esc_html( $status_labels[ $status ] ) . '</span></td>';
			foreach ( $fields as $field ) {
				$value = FEUR_Field_Repository::value( $user->ID, $field );
				$label = FEUR_Field_Types::label( $field, $value );
				echo '<td>' . ( '' !== $label && null !== $label ? esc_html( $label ) : '<span aria-hidden="true">&mdash;</span>' ) . '</td>';
			}
			echo '<td>' . self::actions( $user->ID, $status ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '</div>';
	}

	private static function action_url( $action, $user_id ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&user=' . $user_id ), $action . '_' . $user_id );
	}

	private static function actions( $user_id, $status ) {
		$html = '';
		$approve = '<a class="button button-primary button-small" href="' . esc_url( self::action_url( 'feur_approve', $user_id ) ) . '">' . esc_html__( 'Approve', 'feur' ) . '</a>';
		$deny = '<a class="button button-small" href="' . esc_url( self::action_url( 'feur_deny', $user_id ) ) . '">' . esc_html__( 'Deny', 'feur' ) . '</a>';
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
		return $html;
	}
}
