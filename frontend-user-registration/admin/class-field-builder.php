<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Field_Builder {

	public function __construct() {
		add_action( 'admin_post_feur_save_field', array( $this, 'save_field' ) );
		add_action( 'admin_post_feur_delete_field', array( $this, 'delete_field' ) );
		add_action( 'admin_post_feur_move_field', array( $this, 'move_field' ) );
	}

	public function render() {
		$editing = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : '';
		$field = $editing ? FEUR_Field_Repository::get_field( $editing ) : null;

		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Field saved.', 'feur' ) . '</p></div>';
		}

		echo $this->list();

		echo '<hr>';
		if ( $field ) {
			echo '<h2>' . esc_html__( 'Edit Field', 'feur' ) . '</h2>';
		} else {
			echo '<h2>' . esc_html__( 'Add New Field', 'feur' ) . '</h2>';
		}
		echo $this->form( $field );
	}

	private function list() {
		$fields = FEUR_Field_Repository::get_fields();
		$html = '<h2>' . esc_html__( 'Registration Fields', 'feur' ) . '</h2>';
		$html .= '<p class="description">' . esc_html__( 'Fields added here appear on the registration form and are stored per member.', 'feur' ) . '</p>';
		$html .= '<table class="widefat striped"><thead><tr>';
		$html .= '<th>' . esc_html__( 'Label', 'feur' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Type', 'feur' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Required', 'feur' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Options', 'feur' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Visibility', 'feur' ) . '</th>';
		$html .= '<th></th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $fields as $f ) {
			$tags = array();
			if ( ! empty( $f['show_in_admin_list'] ) ) {
				$tags[] = __( 'List', 'feur' );
			}
			if ( ! empty( $f['editable_in_profile'] ) ) {
				$tags[] = __( 'Profile', 'feur' );
			}
			if ( ! empty( $f['admin_only'] ) ) {
				$tags[] = __( 'Admin only', 'feur' );
			}
			$html .= '<tr>';
			$html .= '<td><strong>' . esc_html( $f['label'] ) . '</strong><br><code>fe_' . esc_html( $f['id'] ) . '</code></td>';
			$types = FEUR_Field_Types::types();
			$html .= '<td>' . esc_html( isset( $types[ $f['type'] ] ) ? $types[ $f['type'] ] : $f['type'] ) . '</td>';
			$html .= '<td>' . ( ! empty( $f['required'] ) ? '&check;' : '' ) . '</td>';
			$html .= '<td>' . esc_html( implode( ', ', FEUR_Field_Types::options( $f ) ) ) . '</td>';
			$html .= '<td>' . esc_html( implode( ', ', $tags ) ) . '</td>';
			$html .= '<td>';
			$edit = add_query_arg( array( 'page' => 'feur-registration', 'tab' => 'fields', 'edit' => $f['id'] ), admin_url( 'users.php' ) );
			$del = wp_nonce_url( admin_url( 'admin-post.php?action=feur_delete_field&field=' . urlencode( $f['id'] ) ), 'feur_delete_field_' . $f['id'] );
			$up = wp_nonce_url( admin_url( 'admin-post.php?action=feur_move_field&field=' . urlencode( $f['id'] ) . '&dir=-1' ), 'feur_move_field_' . $f['id'] );
			$down = wp_nonce_url( admin_url( 'admin-post.php?action=feur_move_field&field=' . urlencode( $f['id'] ) . '&dir=1' ), 'feur_move_field_' . $f['id'] );
			$html .= '<a class="button button-small" href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'feur' ) . '</a> ';
			$html .= '<a class="button button-small" href="' . esc_url( $up ) . '" aria-label="' . esc_attr__( 'Move up', 'feur' ) . '">&uarr;</a> ';
			$html .= '<a class="button button-small" href="' . esc_url( $down ) . '" aria-label="' . esc_attr__( 'Move down', 'feur' ) . '">&darr;</a> ';
			$html .= '<a class="button button-small" href="' . esc_url( $del ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this field?', 'feur' ) ) . '\');">' . esc_html__( 'Delete', 'feur' ) . '</a>';
			$html .= '</td></tr>';
		}

		if ( empty( $fields ) ) {
			$html .= '<tr><td colspan="6">' . esc_html__( 'No custom fields yet. Add one below.', 'feur' ) . '</td></tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}

	private function form( $field ) {
		$editing = $field ? $field['id'] : '';
		$id = $field ? $field['id'] : '';
		$label = $field ? $field['label'] : '';
		$type = $field ? $field['type'] : 'text';
		$required = $field ? ! empty( $field['required'] ) : false;
		$options = $field ? implode( "\n", FEUR_Field_Types::options( $field ) ) : '';
		$placeholder = $field && isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$validation = $field && isset( $field['validation'] ) ? $field['validation'] : 'none';
		$validation_regex = $field && isset( $field['validation_regex'] ) ? $field['validation_regex'] : '';
		$show_list = $field ? ! empty( $field['show_in_admin_list'] ) : true;
		$editable_profile = $field ? ! empty( $field['editable_in_profile'] ) : true;
		$admin_only = $field ? ! empty( $field['admin_only'] ) : false;

		$html = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="feur-field-form">';
		$html .= '<input type="hidden" name="action" value="feur_save_field">';
		$html .= wp_nonce_field( 'feur_save_field', '_wpnonce', true, false );
		$html .= '<input type="hidden" name="original_id" value="' . esc_attr( $editing ) . '">';
		$html .= '<table class="form-table" role="presentation">';

		$html .= '<tr><th><label for="feur_f_id">' . esc_html__( 'ID', 'feur' ) . '</label></th><td>';
		$html .= '<input type="text" id="feur_f_id" name="field[id]" value="' . esc_attr( $id ) . '" class="regular-text"' . ( $editing ? ' readonly' : '' ) . '>';
		$html .= ' <span class="description">' . esc_html__( 'Lowercase letters, numbers, dashes. Stored as user meta key fe_&lt;id&gt;. Cannot be changed after creation.', 'feur' ) . '</span>';
		$html .= '</td></tr>';

		$html .= '<tr><th><label for="feur_f_label">' . esc_html__( 'Label', 'feur' ) . '</label></th><td>';
		$html .= '<input type="text" id="feur_f_label" name="field[label]" value="' . esc_attr( $label ) . '" class="regular-text" required>';
		$html .= '</td></tr>';

		$html .= '<tr><th><label for="feur_f_type">' . esc_html__( 'Type', 'feur' ) . '</label></th><td>';
		$html .= '<select id="feur_f_type" name="field[type]"' . ( $editing ? ' disabled' : '' ) . '>';
		foreach ( FEUR_Field_Types::types() as $key => $tlabel ) {
			$html .= '<option value="' . esc_attr( $key ) . '"' . selected( $type, $key, false ) . '>' . esc_html( $tlabel ) . '</option>';
		}
		$html .= '</select>';
		if ( $editing ) {
			$html .= '<input type="hidden" name="field[type]" value="' . esc_attr( $type ) . '">';
		}
		$html .= ' <span class="description">' . esc_html__( 'Cannot be changed after creation.', 'feur' ) . '</span>';
		$html .= '</td></tr>';

		$html .= '<tr><th><label for="feur_f_options">' . esc_html__( 'Options', 'feur' ) . '</label></th><td>';
		$html .= '<textarea id="feur_f_options" name="field[options]" rows="4" class="large-text">' . esc_textarea( $options ) . '</textarea>';
		$html .= '<p class="description">' . esc_html__( 'One option per line. Used for Select, Radio, and Checkbox types.', 'feur' ) . '</p>';
		$html .= '</td></tr>';

		$html .= '<tr><th><label for="feur_f_placeholder">' . esc_html__( 'Placeholder', 'feur' ) . '</label></th><td>';
		$html .= '<input type="text" id="feur_f_placeholder" name="field[placeholder]" value="' . esc_attr( $placeholder ) . '" class="regular-text">';
		$html .= '</td></tr>';

		$html .= '<tr><th><label for="feur_f_validation">' . esc_html__( 'Validation', 'feur' ) . '</label></th><td>';
		$html .= '<select id="feur_f_validation" name="field[validation]">';
		$vals = array(
			'none'   => __( 'None', 'feur' ),
			'email'  => __( 'Email', 'feur' ),
			'custom' => __( 'Custom regex', 'feur' ),
		);
		foreach ( $vals as $k => $v ) {
			$html .= '<option value="' . esc_attr( $k ) . '"' . selected( $validation, $k, false ) . '>' . esc_html( $v ) . '</option>';
		}
		$html .= '</select><br>';
		$html .= '<input type="text" name="field[validation_regex]" value="' . esc_attr( $validation_regex ) . '" class="regular-text" placeholder="' . esc_attr__( '/^pattern$/', 'feur' ) . '">';
		$html .= '</td></tr>';

		$html .= '<tr><th>' . esc_html__( 'Flags', 'feur' ) . '</th><td>';
		$html .= '<label style="display:block;margin:.25em 0"><input type="checkbox" name="field[required]" value="1"' . checked( $required, true, false ) . '> ' . esc_html__( 'Required', 'feur' ) . '</label>';
		$html .= '<label style="display:block;margin:.25em 0"><input type="checkbox" name="field[show_in_admin_list]" value="1"' . checked( $show_list, true, false ) . '> ' . esc_html__( 'Show in Users list', 'feur' ) . '</label>';
		$html .= '<label style="display:block;margin:.25em 0"><input type="checkbox" name="field[editable_in_profile]" value="1"' . checked( $editable_profile, true, false ) . '> ' . esc_html__( 'Editable in profile', 'feur' ) . '</label>';
		$html .= '<label style="display:block;margin:.25em 0"><input type="checkbox" name="field[admin_only]" value="1"' . checked( $admin_only, true, false ) . '> ' . esc_html__( 'Admin only (not shown on the registration form)', 'feur' ) . '</label>';
		$html .= '</td></tr>';

		$html .= '</table>';
		$html .= '<p class="submit">';
		$html .= '<button type="submit" class="button button-primary">' . esc_html__( 'Save Field', 'feur' ) . '</button> ';
		if ( $editing ) {
			$html .= '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'feur-registration', 'tab' => 'fields' ), admin_url( 'users.php' ) ) ) . '">' . esc_html__( 'Cancel', 'feur' ) . '</a>';
		}
		$html .= '</p>';
		$html .= '</form>';
		return $html;
	}

	public function save_field() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'feur' ) );
		}
		check_admin_referer( 'feur_save_field' );

		$data = isset( $_POST['field'] ) && is_array( $_POST['field'] ) ? wp_unslash( $_POST['field'] ) : array();
		$original = isset( $_POST['original_id'] ) ? sanitize_key( wp_unslash( $_POST['original_id'] ) ) : '';

		$id = isset( $data['id'] ) ? sanitize_key( wp_unslash( $data['id'] ) ) : '';
		$label = isset( $data['label'] ) ? sanitize_text_field( wp_unslash( $data['label'] ) ) : '';
		$type = isset( $data['type'] ) ? sanitize_key( wp_unslash( $data['type'] ) ) : 'text';
		$options_text = isset( $data['options'] ) ? sanitize_textarea_field( wp_unslash( $data['options'] ) ) : '';
		$placeholder = isset( $data['placeholder'] ) ? sanitize_text_field( wp_unslash( $data['placeholder'] ) ) : '';
		$validation = isset( $data['validation'] ) ? sanitize_key( wp_unslash( $data['validation'] ) ) : 'none';
		$validation_regex = isset( $data['validation_regex'] ) ? sanitize_text_field( wp_unslash( $data['validation_regex'] ) ) : '';

		if ( ! array_key_exists( $type, FEUR_Field_Types::types() ) ) {
			$type = 'text';
		}
		if ( ! in_array( $validation, array( 'none', 'email', 'custom' ), true ) ) {
			$validation = 'none';
		}

		if ( $original ) {
			$id = $original;
		} else {
			if ( '' === $id || ! preg_match( '/^[a-z0-9_-]+$/', $id ) ) {
				wp_die( esc_html__( 'Field ID must contain only lowercase letters, numbers, dashes, or underscores.', 'feur' ) );
			}
			if ( FEUR_Field_Repository::get_field( $id ) ) {
				wp_die( esc_html__( 'A field with that ID already exists.', 'feur' ) );
			}
		}
		if ( '' === $label ) {
			wp_die( esc_html__( 'A label is required.', 'feur' ) );
		}

		$options = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $options_text ) as $opt ) {
			$opt = trim( $opt );
			if ( '' !== $opt ) {
				$options[] = $opt;
			}
		}

		$field = array(
			'id'                  => $id,
			'label'               => $label,
			'type'                => $type,
			'required'            => ! empty( $data['required'] ),
			'options'             => $options,
			'placeholder'         => $placeholder,
			'validation'          => $validation,
			'validation_regex'    => 'custom' === $validation ? $validation_regex : '',
			'show_in_admin_list'  => ! empty( $data['show_in_admin_list'] ),
			'editable_in_profile' => ! empty( $data['editable_in_profile'] ),
			'admin_only'          => ! empty( $data['admin_only'] ),
		);

		$fields = FEUR_Field_Repository::get_fields();
		if ( $original ) {
			foreach ( $fields as $i => $f ) {
				if ( $f['id'] === $original ) {
					$fields[ $i ] = $field;
					break;
				}
			}
		} else {
			$fields[] = $field;
		}
		FEUR_Field_Repository::save_fields( $fields );

		$url = add_query_arg( array( 'page' => 'feur-registration', 'tab' => 'fields', 'saved' => '1' ), admin_url( 'users.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	public function delete_field() {
		$id = isset( $_GET['field'] ) ? sanitize_key( wp_unslash( $_GET['field'] ) ) : '';
		check_admin_referer( 'feur_delete_field_' . $id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'feur' ) );
		}
		FEUR_Field_Repository::delete_field( $id );
		wp_safe_redirect( add_query_arg( array( 'page' => 'feur-registration', 'tab' => 'fields' ), admin_url( 'users.php' ) ) );
		exit;
	}

	public function move_field() {
		$id = isset( $_GET['field'] ) ? sanitize_key( wp_unslash( $_GET['field'] ) ) : '';
		$dir = isset( $_GET['dir'] ) ? (int) $_GET['dir'] : 0;
		check_admin_referer( 'feur_move_field_' . $id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'feur' ) );
		}
		FEUR_Field_Repository::move( $id, $dir );
		wp_safe_redirect( add_query_arg( array( 'page' => 'feur-registration', 'tab' => 'fields' ), admin_url( 'users.php' ) ) );
		exit;
	}
}
