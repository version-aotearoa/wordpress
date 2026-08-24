<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'feur_settings' );
delete_option( 'feur_fields' );

$page_id = (int) get_option( 'feur_account_page_id' );
if ( $page_id ) {
	$post = get_post( $page_id );
	if ( $post && has_shortcode( $post->post_content, 'fe_account_form' ) ) {
		wp_delete_post( $page_id, true );
	}
}
delete_option( 'feur_account_page_id' );

$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", 'fe\_%' ) );

$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_feur_state_%' ) );

if ( get_role( 'member' ) ) {
	remove_role( 'member' );
}
