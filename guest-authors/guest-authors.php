<?php
/**
 * Plugin Name: Guest Authors
 * Description: Manage guest author identities as a custom post type. Staff publish posts as usual, but the public byline is attributed to one or more guest authors, each with a profile, archive and single page.
 * Version:     1.0.0
 * Author:      Guest Authors
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ga
 */

defined( 'ABSPATH' ) || exit;

define( 'GA_VERSION', '1.0.0' );
define( 'GA_FILE', __FILE__ );
define( 'GA_PATH', plugin_dir_path( __FILE__ ) );
define( 'GA_URL', plugin_dir_url( __FILE__ ) );

require_once GA_PATH . 'includes/class-plugin.php';
require_once GA_PATH . 'includes/class-post-type.php';
require_once GA_PATH . 'includes/class-profile.php';
require_once GA_PATH . 'includes/class-meta.php';
require_once GA_PATH . 'includes/class-author-override.php';
require_once GA_PATH . 'includes/class-admin-columns.php';
require_once GA_PATH . 'includes/class-render.php';
require_once GA_PATH . 'includes/class-rest.php';
require_once GA_PATH . 'includes/class-shortcode.php';

register_activation_hook( __FILE__, array( 'GA_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'GA_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'GA_Plugin', 'instance' ) );
