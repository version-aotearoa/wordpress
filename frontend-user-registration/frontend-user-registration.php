<?php
/**
 * Plugin Name: Frontend User Registration
 * Description: Front-end registration and magic-link login for Members, with custom fields, admin approval, and Members-only access control.
 * Version:     1.1.4
 * Author:      Frontend User Registration
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: feur
 */

defined( 'ABSPATH' ) || exit;

define( 'FEUR_VERSION', '1.1.4' );
define( 'FEUR_FILE', __FILE__ );
define( 'FEUR_PATH', plugin_dir_path( __FILE__ ) );
define( 'FEUR_URL', plugin_dir_url( __FILE__ ) );

require_once FEUR_PATH . 'includes/class-field-repository.php';
require_once FEUR_PATH . 'includes/class-field-types.php';
require_once FEUR_PATH . 'includes/class-role.php';
require_once FEUR_PATH . 'includes/class-page-installer.php';
require_once FEUR_PATH . 'includes/class-magic-link.php';
require_once FEUR_PATH . 'includes/class-registration-handler.php';
require_once FEUR_PATH . 'includes/class-login-handler.php';
require_once FEUR_PATH . 'includes/class-approval.php';
require_once FEUR_PATH . 'includes/class-shortcode.php';
require_once FEUR_PATH . 'includes/class-user-profile.php';
require_once FEUR_PATH . 'includes/class-users-list.php';
require_once FEUR_PATH . 'includes/class-user-admin-page.php';
require_once FEUR_PATH . 'includes/class-member-access.php';
require_once FEUR_PATH . 'includes/class-plugin.php';
require_once FEUR_PATH . 'admin/class-admin-menu.php';
require_once FEUR_PATH . 'admin/class-field-builder.php';

register_activation_hook( __FILE__, array( 'FEUR_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'FEUR_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'FEUR_Plugin', 'instance' ) );
