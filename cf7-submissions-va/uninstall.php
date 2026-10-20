<?php
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$table = $wpdb->prefix . 'cf7s_submissions';

$wpdb->query( "DROP TABLE IF EXISTS $table" );

delete_option( 'cf7s_db_version' );
