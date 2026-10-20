<?php
defined( 'ABSPATH' ) || exit;

class GA_Admin_Columns {

	public function __construct() {
		add_filter( 'manage_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_post_column' ), 10, 2 );

		add_filter( 'manage_' . GA_Plugin::PT . '_posts_columns', array( $this, 'guest_columns' ) );
		add_action( 'manage_' . GA_Plugin::PT . '_posts_custom_column', array( $this, 'render_guest_column' ), 10, 2 );
	}

	private function current_post_type() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return '';
		}
		$screen = get_current_screen();
		return ( $screen && isset( $screen->post_type ) ) ? $screen->post_type : '';
	}

	public function columns( $columns ) {
		if ( ! GA_Plugin::is_supported( $this->current_post_type() ) ) {
			return $columns;
		}
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'author' === $key ) {
				$new['ga_author'] = __( 'Guest author', 'ga' );
			} else {
				$new[ $key ] = $label;
			}
		}
		if ( ! isset( $new['ga_author'] ) ) {
			$new['ga_author'] = __( 'Guest author', 'ga' );
		}
		return $new;
	}

	public function render_post_column( $column, $post_id ) {
		if ( 'ga_author' !== $column ) {
			return;
		}
		$items = GA_Author_Override::get_byline_items( $post_id );
		if ( ! empty( $items ) ) {
			$names = array();
			foreach ( $items as $item ) {
				$names[] = esc_html( $item['name'] );
			}
			echo implode( ', ', $names ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
			return;
		}

		$author_id = (int) get_post_field( 'post_author', $post_id );
		$user      = $author_id ? get_userdata( $author_id ) : false;
		if ( $user ) {
			echo '<span class="description">' . esc_html( $user->display_name ) . '</span>';
		} else {
			echo '<span aria-hidden="true">&mdash;</span>';
		}
	}

	public function guest_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['ga_avatar'] = __( 'Avatar', 'ga' );
				$new['ga_role']   = __( 'Role', 'ga' );
				$new['ga_posts']  = __( 'Posts', 'ga' );
			}
		}
		return $new;
	}

	public function render_guest_column( $column, $post_id ) {
		if ( 'ga_avatar' === $column ) {
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, 'thumbnail', array( 'style' => 'max-width:48px;height:auto;border-radius:50%' ) );
			} else {
				echo '<span aria-hidden="true">&mdash;</span>';
			}
		}

		if ( 'ga_role' === $column ) {
			$job     = (string) get_post_meta( $post_id, GA_Plugin::JOB_TITLE_META, true );
			$company = (string) get_post_meta( $post_id, GA_Plugin::COMPANY_META, true );
			$role    = trim( $job . ( $job && $company ? ', ' : '' ) . $company );
			echo $role ? esc_html( $role ) : '<span aria-hidden="true">&mdash;</span>';
		}

		if ( 'ga_posts' === $column ) {
			$query = new WP_Query(
				array(
					'post_type'      => GA_Plugin::supported_post_types(),
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => GA_Plugin::META_GUEST_ID,
					'meta_value'     => $post_id,
					'no_found_rows'  => false,
				)
			);
			echo (int) $query->found_posts;
		}
	}
}
