<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'resource' ) );
foreach ( $post_ids as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}

foreach ( array( 'resource_tag', 'resource_format' ) as $taxonomy ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( (int) $term_id, $taxonomy );
		}
	}
}

$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s)", 'rl_featured', 'rl_url' ) );

delete_option( 'rl_version' );
delete_option( 'rl_settings' );
