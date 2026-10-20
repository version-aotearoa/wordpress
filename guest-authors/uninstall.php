<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$ga_post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'guest_author' ) );
foreach ( $ga_post_ids as $ga_post_id ) {
	wp_delete_post( (int) $ga_post_id, true );
}

$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_ga_guest_author' ) );

delete_option( 'ga_version' );
