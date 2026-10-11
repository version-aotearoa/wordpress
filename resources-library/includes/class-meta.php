<?php
defined( 'ABSPATH' ) || exit;

class RL_Meta {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'admin_bar_menu', array( $this, 'reorder_new_menu' ), 999 );
		add_action( 'save_post_' . RL_Plugin::PT, array( $this, 'save' ), 10, 2 );
		add_filter( 'get_user_option_meta-box-order_' . RL_Plugin::PT, array( $this, 'keep_settings_box_on_top' ) );
	}

	public function add_boxes() {
		add_meta_box( 'rl_library_options', __( 'Resource Settings', 'rl' ), array( $this, 'render' ), RL_Plugin::PT, 'side', 'high' );
	}

	public function keep_settings_box_on_top( $order ) {
		if ( ! is_array( $order ) ) {
			return $order;
		}
		foreach ( $order as $context => $ids ) {
			if ( ! is_string( $ids ) || '' === $ids ) {
				continue;
			}
			$parts = array_filter(
				array_map( 'trim', explode( ',', $ids ) ),
				static function ( $id ) {
					return $id && 'rl_library_options' !== $id;
				}
			);
			$order[ $context ] = implode( ',', $parts );
		}
		return $order;
	}

	public function admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			return;
		}
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		if ( ! $post_type ) {
			$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
			if ( $post_id ) {
				$post_type = get_post_type( $post_id );
			}
		}
		if ( RL_Plugin::PT !== $post_type ) {
			return;
		}
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
		}
		wp_enqueue_style( 'rl-admin', RL_URL . 'assets/css/admin.css', array(), RL_VERSION );
		wp_enqueue_script( 'rl-admin', RL_URL . 'assets/js/admin.js', array( 'jquery' ), RL_VERSION, true );
		wp_localize_script(
			'rl-admin',
			'rl_admin',
			array(
				'file_mimes'  => self::file_mime_types(),
				'select_file' => __( 'Select downloadable file', 'rl' ),
				'use_file'    => __( 'Use file', 'rl' ),
			)
		);
	}

	private static function file_mime_types() {
		$map = array(
			'pdf'  => array( 'application/pdf' ),
			'doc'  => array( 'application/msword' ),
			'docx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ),
			'xls'  => array( 'application/vnd.ms-excel' ),
			'xlsx' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ),
			'ppt'  => array( 'application/vnd.ms-powerpoint' ),
			'pptx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.presentation' ),
			'zip'  => array( 'application/zip' ),
			'csv'  => array( 'text/csv' ),
			'txt'  => array( 'text/plain' ),
			'rtf'  => array( 'application/rtf', 'text/rtf' ),
		);
		$mimes = array();
		foreach ( RL_Plugin::FILE_TYPES as $ext ) {
			if ( isset( $map[ $ext ] ) ) {
				$mimes = array_merge( $mimes, $map[ $ext ] );
			}
		}
		return array_values( array_unique( $mimes ) );
	}

	public function reorder_new_menu( $wp_admin_bar ) {
		$parent = 'new-content';
		$children = array();
		foreach ( $wp_admin_bar->get_nodes() as $node ) {
			if ( isset( $node->parent ) && $parent === $node->parent ) {
				$children[ $node->id ] = $node;
			}
		}
		if ( empty( $children ) ) {
			return;
		}
		foreach ( array_keys( $children ) as $id ) {
			$wp_admin_bar->remove_node( $id );
		}
		if ( isset( $children['new-resource'] ) ) {
			$first = $children['new-resource'];
			unset( $children['new-resource'] );
			$wp_admin_bar->add_node( get_object_vars( $first ) );
		}
		foreach ( $children as $node ) {
			$wp_admin_bar->add_node( get_object_vars( $node ) );
		}
	}

	public function render( $post ) {
		wp_nonce_field( 'rl_save_library_options', 'rl_library_options_nonce' );
		$featured = (bool) get_post_meta( $post->ID, RL_Plugin::FEATURED_META, true );
		$url = get_post_meta( $post->ID, RL_Plugin::URL_META, true );
		$file_id = (int) get_post_meta( $post->ID, RL_Plugin::FILE_META, true );
		echo '<p><label style="display:block;margin-bottom:.25em"><input type="checkbox" name="rl_featured" value="1"' . checked( $featured, true, false ) . '> ' . esc_html__( 'Feature on library page', 'rl' ) . '</label></p>';
		echo '<p><label for="rl_url" style="display:block;margin-bottom:.25em">' . esc_html__( 'Resource URL', 'rl' ) . '</label>';
		echo '<input type="url" name="rl_url" id="rl_url" value="' . esc_attr( $url ) . '" class="widefat" placeholder="https://example.com">';
		echo '<span class="description">' . esc_html__( 'Optional external link; the card links here when set.', 'rl' ) . '</span></p>';

		echo '<div class="rl-media-field rl-file-field">';
		echo '<label style="display:block;margin-bottom:.25em">' . esc_html__( 'Downloadable file', 'rl' ) . '</label>';
		echo '<input type="hidden" class="rl-media-id" name="rl_file" value="' . esc_attr( $file_id ) . '">';
		echo '<div class="rl-media-preview rl-file-preview">';
		if ( $file_id ) {
			echo $this->file_preview_html( $file_id ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';
		echo '<button type="button" class="button rl-media-select"' . ( $file_id ? ' style="display:none"' : '' ) . '>' . esc_html__( 'Select file', 'rl' ) . '</button> ';
		echo '<button type="button" class="button rl-media-remove"' . ( $file_id ? '' : ' style="display:none"' ) . '>' . esc_html__( 'Remove', 'rl' ) . '</button>';
		echo '<span class="description" style="display:block;margin-top:.5em">' . esc_html__( 'Shown as a Download button on the card and single resource.', 'rl' ) . '</span>';
		echo '</div>';

		echo '<h4 class="rl-tax-heading">' . esc_html__( 'Resource Tags', 'rl' ) . '</h4>';
		RL_Post_Type::taxonomy_meta_box( $post, array( 'args' => array( 'taxonomy' => RL_Plugin::TAG_TAX ) ) );

		echo '<h4 class="rl-tax-heading">' . esc_html__( 'Resource Formats', 'rl' ) . '</h4>';
		RL_Post_Type::taxonomy_meta_box( $post, array( 'args' => array( 'taxonomy' => RL_Plugin::FORMAT_TAX ) ) );
	}

	private function file_preview_html( $file_id ) {
		$url = wp_get_attachment_url( $file_id );
		if ( ! $url ) {
			return '';
		}
		$filename = basename( $url );
		$ext = strtoupper( pathinfo( $url, PATHINFO_EXTENSION ) );
		$meta = array();
		if ( $ext ) {
			$meta[] = $ext;
		}
		$path = get_attached_file( $file_id );
		if ( $path && file_exists( $path ) ) {
			$meta[] = size_format( filesize( $path ) );
		}
		return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $filename ) . '</a>'
			. ( $meta ? ' <span class="rl-file-meta">' . esc_html( implode( ' · ', $meta ) ) . '</span>' : '' );
	}

	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['rl_library_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rl_library_options_nonce'] ) ), 'rl_save_library_options' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$featured = ! empty( $_POST['rl_featured'] ) ? '1' : '';
		update_post_meta( $post_id, RL_Plugin::FEATURED_META, $featured );

		$url = isset( $_POST['rl_url'] ) ? esc_url_raw( wp_unslash( $_POST['rl_url'] ) ) : '';
		if ( $url ) {
			update_post_meta( $post_id, RL_Plugin::URL_META, $url );
		} else {
			delete_post_meta( $post_id, RL_Plugin::URL_META );
		}

		$file_id = isset( $_POST['rl_file'] ) ? absint( wp_unslash( $_POST['rl_file'] ) ) : 0;
		if ( $file_id && $this->is_allowed_file( $file_id ) ) {
			update_post_meta( $post_id, RL_Plugin::FILE_META, $file_id );
		} else {
			delete_post_meta( $post_id, RL_Plugin::FILE_META );
		}

		$this->save_new_terms( $post_id );
	}

	private function is_allowed_file( $file_id ) {
		$url = wp_get_attachment_url( $file_id );
		if ( ! $url ) {
			return false;
		}
		$ext = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		$types = apply_filters( 'rl_file_types', RL_Plugin::FILE_TYPES );
		return in_array( $ext, $types, true );
	}

	private function save_new_terms( $post_id ) {
		$taxonomies = array(
			RL_Plugin::TAG_TAX    => array( 'rl_new_tag', 'rl_new_tag_slug' ),
			RL_Plugin::FORMAT_TAX => array( 'rl_new_format', 'rl_new_format_slug' ),
		);

		foreach ( $taxonomies as $taxonomy => $fields ) {
			$name = isset( $_POST[ $fields[0] ] ) ? sanitize_text_field( wp_unslash( $_POST[ $fields[0] ] ) ) : '';
			if ( '' === $name ) {
				continue;
			}
			$slug = isset( $_POST[ $fields[1] ] ) ? sanitize_title( wp_unslash( $_POST[ $fields[1] ] ) ) : '';

			$existing = get_term_by( 'name', $name, $taxonomy );
			if ( $existing ) {
				$term_id = (int) $existing->term_id;
			} else {
				$args = $slug ? array( 'slug' => $slug ) : array();
				$result = wp_insert_term( $name, $taxonomy, $args );
				if ( is_wp_error( $result ) ) {
					continue;
				}
				$term_id = (int) $result['term_id'];
			}

			wp_set_object_terms( $post_id, $term_id, $taxonomy, true );
		}
	}
}
