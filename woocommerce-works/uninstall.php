<?php
/**
 * WooCommerce Works uninstall.
 *
 * The plugin stores no product data of its own, so only its options go.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( (array) $sites as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'wcw_settings' );
		delete_option( 'wcw_version' );
		delete_transient( 'wcw_categories' );
		restore_current_blog();
	}
} else {
	delete_option( 'wcw_settings' );
	delete_option( 'wcw_version' );
	delete_transient( 'wcw_categories' );
}
