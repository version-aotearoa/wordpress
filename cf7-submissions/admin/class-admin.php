<?php
defined( 'ABSPATH' ) || exit;

class CF7S_Admin {

	const PAGE = 'cf7s-submissions';
	const PER_PAGE = 20;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_upgrade_db' ) );
		add_action( 'admin_post_cf7s_delete', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_cf7s_bulk_delete', array( $this, 'handle_bulk_delete' ) );
		add_action( 'admin_init', array( $this, 'handle_export' ) );
	}

	public function maybe_upgrade_db() {
		CF7S_DB::maybe_upgrade();
	}

	public function menu() {
		add_submenu_page(
			'wpcf7',
			__( 'Submissions', 'cf7s' ),
			__( 'Submissions', 'cf7s' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! empty( $_GET['view'] ) && 'detail' === $_GET['view'] ) {
			$this->render_detail();
			return;
		}

		$this->render_list();
	}

	private function render_list() {
		$form_id = ! empty( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		$search = ! empty( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$paged = ! empty( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		$filter = array(
			'form_id' => $form_id,
			'search'  => $search,
			'limit'   => self::PER_PAGE,
			'offset'  => ( $paged - 1 ) * self::PER_PAGE,
		);

		$total = CF7S_DB::count( $filter );
		$rows = CF7S_DB::query( $filter );
		$forms = CF7S_DB::distinct_forms();
		$total_pages = (int) ceil( $total / self::PER_PAGE );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Contact Form 7 Submissions', 'cf7s' ) . '</h1>';

		if ( ! empty( $_GET['cf7s_deleted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Submission deleted.', 'cf7s' ) . '</p></div>';
		}

		$form_action = add_query_arg( array( 'page' => self::PAGE ), admin_url( 'admin.php' ) );
		echo '<form method="get" action="' . esc_url( $form_action ) . '">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::PAGE ) . '">';
		echo '<select name="form_id">';
		echo '<option value="0"' . selected( $form_id, 0, false ) . '>' . esc_html__( 'All forms', 'cf7s' ) . '</option>';
		foreach ( $forms as $form ) {
			echo '<option value="' . esc_attr( $form->form_id ) . '"' . selected( $form_id, (int) $form->form_id, false ) . '>' . esc_html( $form->form_title ) . ' (' . (int) $form->total . ')</option>';
		}
		echo '</select>';
		echo ' <input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search submissions', 'cf7s' ) . '">';
		echo ' <button type="submit" class="button">' . esc_html__( 'Filter', 'cf7s' ) . '</button>';
		echo ' <a class="button" href="' . esc_url( $this->export_url( $form_id ) ) . '">' . esc_html__( 'Export CSV', 'cf7s' ) . '</a>';
		echo '</form>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="cf7s_bulk_delete">';
		wp_nonce_field( 'cf7s_bulk_delete' );

		echo '<table class="widefat striped" style="margin-top:12px">';
		echo '<thead><tr>';
		echo '<th class="check-column"><input type="checkbox" id="cf7s-check-all"></th>';
		echo '<th>' . esc_html__( 'ID', 'cf7s' ) . '</th>';
		echo '<th>' . esc_html__( 'Form', 'cf7s' ) . '</th>';
		echo '<th>' . esc_html__( 'Date', 'cf7s' ) . '</th>';
		echo '<th>' . esc_html__( 'Fields', 'cf7s' ) . '</th>';
		echo '<th>' . esc_html__( 'IP', 'cf7s' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'cf7s' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		if ( ! $rows ) {
			echo '<tr><td colspan="7">' . esc_html__( 'No submissions found.', 'cf7s' ) . '</td></tr>';
		}

		foreach ( $rows as $row ) {
			$detail_url = $this->detail_url( $row->id );
			$delete_url = $this->delete_url( $row->id );
			$summary = $this->field_summary( $row->data );

			echo '<tr>';
			echo '<th class="check-column"><input type="checkbox" name="ids[]" value="' . esc_attr( $row->id ) . '"></th>';
			echo '<td>' . (int) $row->id . '</td>';
			echo '<td>' . esc_html( $row->form_title ) . '</td>';
			echo '<td>' . esc_html( $row->created_at ) . '</td>';
			echo '<td>' . esc_html( $summary ) . '</td>';
			echo '<td>' . esc_html( $row->remote_ip ) . '</td>';
			echo '<td>';
			echo '<a href="' . esc_url( $detail_url ) . '">' . esc_html__( 'View', 'cf7s' ) . '</a> | ';
			echo '<a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this submission?', 'cf7s' ) ) . '\');">' . esc_html__( 'Delete', 'cf7s' ) . '</a>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		if ( $rows ) {
			echo '<p style="margin-top:12px"><button type="submit" class="button" onclick="return confirm(\'' . esc_js( __( 'Delete selected submissions?', 'cf7s' ) ) . '\');">' . esc_html__( 'Delete selected', 'cf7s' ) . '</button></p>';
		}

		echo '</form>';

		if ( $total_pages > 1 ) {
			$base = add_query_arg(
				array( 'page' => self::PAGE, 'form_id' => $form_id, 's' => $search, 'paged' => '%#%' ),
				admin_url( 'admin.php' )
			);
			echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links( array( 'base' => $base, 'format' => '', 'current' => $paged, 'total' => $total_pages, 'prev_text' => '&laquo;', 'next_text' => '&raquo;' ) ) . '</div></div>';
		}

		echo '</div>';
	}

	private function render_detail() {
		$id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$row = $id ? CF7S_DB::get( $id ) : null;

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Submission', 'cf7s' ) . ' #' . (int) $id . '</h1>';
		echo '<p><a class="button" href="' . esc_url( $this->list_url() ) . '">&larr; ' . esc_html__( 'Back to submissions', 'cf7s' ) . '</a></p>';

		if ( ! $row ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Submission not found.', 'cf7s' ) . '</p></div>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat striped">';
		echo '<tbody>';
		echo '<tr><th style="width:180px">' . esc_html__( 'Form', 'cf7s' ) . '</th><td>' . esc_html( $row->form_title ) . '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Date', 'cf7s' ) . '</th><td>' . esc_html( $row->created_at ) . '</td></tr>';
		echo '<tr><th>' . esc_html__( 'IP address', 'cf7s' ) . '</th><td>' . esc_html( $row->remote_ip ) . '</td></tr>';
		echo '<tr><th>' . esc_html__( 'User agent', 'cf7s' ) . '</th><td>' . esc_html( $row->user_agent ) . '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Page', 'cf7s' ) . '</th><td>' . ( $row->url ? '<a href="' . esc_url( $row->url ) . '" target="_blank" rel="noopener">' . esc_html( $row->url ) . '</a>' : '&mdash;' ) . '</td></tr>';
		$user_label = '&mdash;';
		if ( $row->user_id ) {
			$user = get_userdata( $row->user_id );
			$user_label = $user ? $user->display_name . ' (#' . $row->user_id . ')' : '#' . $row->user_id;
		}
		echo '<tr><th>' . esc_html__( 'User', 'cf7s' ) . '</th><td>' . esc_html( $user_label ) . '</td></tr>';
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Fields', 'cf7s' ) . '</h2>';
		echo '<table class="widefat striped">';
		echo '<thead><tr><th style="width:220px">' . esc_html__( 'Field', 'cf7s' ) . '</th><th>' . esc_html__( 'Value', 'cf7s' ) . '</th></tr></thead>';
		echo '<tbody>';

		$data = is_array( $row->data ) ? $row->data : array();
		if ( ! $data ) {
			echo '<tr><td colspan="2">' . esc_html__( 'No field data.', 'cf7s' ) . '</td></tr>';
		}
		foreach ( $data as $key => $value ) {
			echo '<tr><th>' . esc_html( (string) $key ) . '</th><td>' . esc_html( $this->format_value( $value ) ) . '</td></tr>';
		}

		echo '</tbody></table>';

		echo '<p style="margin-top:16px"><a class="button button-link-delete" href="' . esc_url( $this->delete_url( $row->id ) ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this submission?', 'cf7s' ) ) . '\');">' . esc_html__( 'Delete submission', 'cf7s' ) . '</a></p>';
		echo '</div>';
	}

	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'cf7s' ) );
		}
		$id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'cf7s_delete_' . $id );
		CF7S_DB::delete( array( $id ) );
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'cf7s_deleted' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_bulk_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'cf7s' ) );
		}
		check_admin_referer( 'cf7s_bulk_delete' );
		$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
		if ( $ids ) {
			CF7S_DB::delete( $ids );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'cf7s_deleted' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_export() {
		if ( empty( $_GET['cf7s_export'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'cf7s' ) );
		}
		check_admin_referer( 'cf7s_export' );

		$form_id = ! empty( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		$rows = CF7S_DB::query( array( 'form_id' => $form_id ) );

		if ( $form_id ) {
			$form = get_post( $form_id );
			$slug = $form ? sanitize_title( $form->post_title ) : (string) $form_id;
		} else {
			$slug = 'all';
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="cf7-submissions-' . $slug . '-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fprintf( $out, "\xEF\xBB\xBF" );

		$headers = array( 'ID', 'Date', 'Form', 'IP', 'Page' );
		$field_keys = array();
		foreach ( $rows as $row ) {
			foreach ( array_keys( (array) $row->data ) as $key ) {
				$field_keys[ $key ] = true;
			}
		}
		$field_keys = array_keys( $field_keys );
		foreach ( $field_keys as $key ) {
			$headers[] = $key;
		}
		fputcsv( $out, $headers );

		foreach ( $rows as $row ) {
			$line = array(
				$row->id,
				$row->created_at,
				$row->form_title,
				$row->remote_ip,
				$row->url,
			);
			foreach ( $field_keys as $key ) {
				$value = isset( $row->data[ $key ] ) ? $row->data[ $key ] : '';
				$line[] = $this->format_value( $value );
			}
			fputcsv( $out, $line );
		}

		fclose( $out );
		exit;
	}

	private function field_summary( $data ) {
		if ( ! is_array( $data ) ) {
			return '';
		}
		$parts = array();
		$count = 0;
		foreach ( $data as $key => $value ) {
			if ( $count >= 3 ) {
				break;
			}
			$parts[] = $key . ': ' . $this->format_value( $value );
			$count++;
		}
		return implode( ', ', $parts );
	}

	private function format_value( $value ) {
		if ( is_array( $value ) ) {
			return implode( '; ', array_map( array( $this, 'format_value' ), $value ) );
		}
		return (string) $value;
	}

	private function list_url() {
		return add_query_arg( array( 'page' => self::PAGE ), admin_url( 'admin.php' ) );
	}

	private function detail_url( $id ) {
		return add_query_arg( array( 'page' => self::PAGE, 'view' => 'detail', 'id' => $id ), admin_url( 'admin.php' ) );
	}

	private function delete_url( $id ) {
		return wp_nonce_url(
			add_query_arg( array( 'action' => 'cf7s_delete', 'id' => $id ), admin_url( 'admin-post.php' ) ),
			'cf7s_delete_' . $id
		);
	}

	private function export_url( $form_id = 0 ) {
		return wp_nonce_url(
			add_query_arg( array( 'page' => self::PAGE, 'cf7s_export' => 1, 'form_id' => $form_id ), admin_url( 'admin.php' ) ),
			'cf7s_export'
		);
	}
}
