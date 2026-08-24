<?php
defined( 'ABSPATH' ) || exit;

class RL_Admin_Columns {

	public function __construct() {
		add_filter( 'manage_' . RL_Plugin::PT . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . RL_Plugin::PT . '_posts_custom_column', array( $this, 'render' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'tag_filter' ) );
		add_filter( 'pre_get_posts', array( $this, 'apply_tag_filter' ) );
	}

	public function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['rl_thumb']    = __( 'Image', 'rl' );
				$new['rl_tags']     = __( 'Tags', 'rl' );
				$new['rl_format']   = __( 'Format', 'rl' );
				$new['rl_featured'] = __( 'Featured', 'rl' );
			}
		}
		return $new;
	}

	public function render( $column, $post_id ) {
		if ( 'rl_thumb' === $column ) {
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, 'thumbnail', array( 'style' => 'max-width:64px;height:auto' ) );
			} else {
				echo '<span aria-hidden="true">&mdash;</span>';
			}
		}
		if ( 'rl_format' === $column ) {
			$terms = get_the_terms( $post_id, RL_Plugin::FORMAT_TAX );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$names = array();
				foreach ( $terms as $term ) {
					$names[] = esc_html( $term->name );
				}
				echo implode( ', ', $names );
			} else {
				echo '<span aria-hidden="true">&mdash;</span>';
			}
		}
		if ( 'rl_featured' === $column ) {
			$featured = (bool) get_post_meta( $post_id, RL_Plugin::FEATURED_META, true );
			$nonce = wp_create_nonce( 'rl_toggle_featured_' . $post_id );
			echo '<input type="checkbox" class="rl-featured-toggle" data-id="' . esc_attr( $post_id ) . '" data-nonce="' . esc_attr( $nonce ) . '"' . checked( $featured, true, false ) . '>';
		}
		if ( 'rl_tags' === $column ) {
			$terms = get_the_terms( $post_id, RL_Plugin::TAG_TAX );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$links = array();
				foreach ( $terms as $term ) {
					$links[] = '<a href="' . esc_url( get_edit_term_link( $term->term_id, RL_Plugin::TAG_TAX ) ) . '">' . esc_html( $term->name ) . '</a>';
				}
				echo implode( ', ', $links );
			} else {
				echo '<span aria-hidden="true">&mdash;</span>';
			}
		}
	}

	public function tag_filter( $post_type ) {
		if ( RL_Plugin::PT !== $post_type ) {
			return;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => RL_Plugin::TAG_TAX,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}
		$current = isset( $_GET['rl_tag'] ) ? sanitize_key( wp_unslash( $_GET['rl_tag'] ) ) : '';
		echo '<select name="rl_tag">';
		echo '<option value="">' . esc_html__( 'All tags', 'rl' ) . '</option>';
		foreach ( $terms as $term ) {
			echo '<option value="' . esc_attr( $term->slug ) . '"' . selected( $current, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
		}
		echo '</select>';
	}

	public function apply_tag_filter( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || RL_Plugin::PT !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( empty( $_GET['rl_tag'] ) ) {
			return;
		}
		$slug = sanitize_key( wp_unslash( $_GET['rl_tag'] ) );
		if ( $slug ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => RL_Plugin::TAG_TAX,
						'field'    => 'slug',
						'terms'    => $slug,
					),
				)
			);
		}
	}
}
