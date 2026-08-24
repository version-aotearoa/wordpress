<?php
/**
 * Plugin Name: Contact Form 7 Submissions
 * Description: Saves Contact Form 7 submissions to the database with an admin list, detail view and CSV export.
 * Version:     1.0.1
 * Author:      Contact Form 7 Submissions
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf7s
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'CF7S_VERSION', '1.0.1' );
define( 'CF7S_FILE', __FILE__ );
define( 'CF7S_PATH', plugin_dir_path( __FILE__ ) );
define( 'CF7S_URL', plugin_dir_url( __FILE__ ) );

require_once CF7S_PATH . 'includes/class-db.php';
require_once CF7S_PATH . 'includes/class-capture.php';
require_once CF7S_PATH . 'includes/class-plugin.php';
require_once CF7S_PATH . 'admin/class-admin.php';

register_activation_hook( __FILE__, array( 'CF7S_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CF7S_Plugin', 'deactivate' ) );
register_uninstall_hook( __FILE__, 'cf7s_uninstall' );

add_action( 'plugins_loaded', array( 'CF7S_Plugin', 'instance' ) );

function cf7s_uninstall() {
	require_once CF7S_PATH . 'uninstall.php';
}
