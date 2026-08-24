<?php
defined( 'ABSPATH' ) || exit;

class RL_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_rl_load_posts', array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_rl_load_posts', array( $this, 'handle' ) );
		add_action( 'wp_ajax_rl_load_single', array( $this, 'single' ) );
		add_action( 'wp_ajax_nopriv_rl_load_single', array( $this, 'single' ) );
		add_action( 'wp_ajax_rl_toggle_featured', array( $this, 'toggle_featured' ) );
		add_action( 'wp_ajax_rl_toggle_favourite', array( $this, 'toggle_favourite' ) );
	}

	public function handle() {
		check_ajax_referer( 'rl_load_posts', 'nonce' );

		$tag = isset( $_GET['tag'] ) ? sanitize_title( wp_unslash( $_GET['tag'] ) ) : '';
		$format = isset( $_GET['format'] ) ? sanitize_title( wp_unslash( $_GET['format'] ) ) : '';
		$paged = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
		$base = isset( $_GET['base'] ) ? esc_url_raw( wp_unslash( $_GET['base'] ) ) : '';
		$favourites = ( '1' === ( isset( $_GET['favourites'] ) ? sanitize_key( wp_unslash( $_GET['favourites'] ) ) : '' ) );

		$query = RL_Render::query( $tag, $format, $paged, $favourites );

		$formats = RL_Render::applicable_formats( $tag, $favourites );
		$format_slugs = array();
		foreach ( $formats as $format_term ) {
			$format_slugs[] = $format_term->slug;
		}

		$cards = RL_Render::cards( $query );

		wp_send_json_success(
			array(
				'html'         => $cards ? $cards : RL_Render::empty_html( $favourites ),
				'more'         => $query->max_num_pages > $paged,
				'formats'      => $format_slugs,
				'formats_html' => RL_Render::formats_html( $formats, $format, $tag, $base, $favourites ),
			)
		);
	}

	public function single() {
		check_ajax_referer( 'rl_load_posts', 'nonce' );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$base = isset( $_GET['base'] ) ? esc_url_raw( wp_unslash( $_GET['base'] ) ) : '';
		$tag = isset( $_GET['tag'] ) ? sanitize_title( wp_unslash( $_GET['tag'] ) ) : '';

		$post = get_post( $post_id );
		if ( ! $post || RL_Plugin::PT !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_send_json_error();
		}

		wp_send_json_success(
			array(
				'html' => RL_Render::single( $post, $base, $tag ),
				'url'  => get_permalink( $post ),
			)
		);
	}

	public function toggle_featured() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		check_ajax_referer( 'rl_toggle_featured_' . $post_id, 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		$post = get_post( $post_id );
		if ( ! $post || RL_Plugin::PT !== $post->post_type ) {
			wp_send_json_error();
		}
		$featured = ! (bool) get_post_meta( $post_id, RL_Plugin::FEATURED_META, true );
		update_post_meta( $post_id, RL_Plugin::FEATURED_META, $featured ? '1' : '' );
		wp_send_json_success( array( 'featured' => $featured ) );
	}

	public function toggle_favourite() {
		check_ajax_referer( 'rl_load_posts', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$post = get_post( $post_id );
		if ( ! $post || RL_Plugin::PT !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_send_json_error();
		}

		$user_id = get_current_user_id();
		$favs = get_user_meta( $user_id, 'rl_favourites', true );
		if ( ! is_array( $favs ) ) {
			$favs = array();
		}
		$favs = array_map( 'absint', $favs );
		$key = array_search( $post_id, $favs, true );
		if ( false !== $key ) {
			unset( $favs[ $key ] );
			$added = false;
		} else {
			$favs[] = $post_id;
			$added = true;
		}
		update_user_meta( $user_id, 'rl_favourites', array_values( $favs ) );
		wp_send_json_success( array( 'faved' => $added ) );
	}
}
