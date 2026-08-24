<?php
/**
 * Plugin Name: Resources Library
 * Description: A Resources custom post type with section tags and a filterable, AJAX-driven library page template (Video | Link | Article).
 * Version:     1.1.17
 * Author:      Resources Library
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rl
 */

defined( 'ABSPATH' ) || exit;

define( 'RL_VERSION', '1.1.17' );
define( 'RL_FILE', __FILE__ );
define( 'RL_PATH', plugin_dir_path( __FILE__ ) );
define( 'RL_URL', plugin_dir_url( __FILE__ ) );

require_once RL_PATH . 'includes/class-post-type.php';
require_once RL_PATH . 'includes/class-meta.php';
require_once RL_PATH . 'includes/class-render.php';
require_once RL_PATH . 'includes/class-ajax.php';
require_once RL_PATH . 'includes/class-page-template.php';
require_once RL_PATH . 'includes/class-admin-columns.php';
require_once RL_PATH . 'includes/class-settings.php';
require_once RL_PATH . 'includes/class-reorder.php';
require_once RL_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'RL_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RL_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'RL_Plugin', 'instance' ) );
