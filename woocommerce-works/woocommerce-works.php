<?php
/**
 * Plugin Name: WooCommerce Works
 * Description: A browse-first portfolio layer for WooCommerce: a Works page template with a masonry grid, quick view, and work details read from product attributes.
 * Version:     1.1.0
 * Author:      WooCommerce Works
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-works
 * WC requires at least: 7.0
 * WC tested up to: 10.9
 */

defined( 'ABSPATH' ) || exit;

define( 'WCW_VERSION', '1.1.0' );
define( 'WCW_FILE', __FILE__ );
define( 'WCW_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCW_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'woocommerce-works', false, dirname( plugin_basename( WCW_FILE ) ) . '/languages' );
	}
);

require_once WCW_PATH . 'includes/class-plugin.php';
require_once WCW_PATH . 'includes/class-work-data.php';
require_once WCW_PATH . 'includes/class-query.php';
require_once WCW_PATH . 'includes/class-render.php';
require_once WCW_PATH . 'includes/class-template.php';
require_once WCW_PATH . 'includes/class-rest.php';
require_once WCW_PATH . 'includes/class-browse.php';
require_once WCW_PATH . 'includes/class-settings.php';

register_activation_hook( __FILE__, array( 'WCW_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WCW_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WCW_Plugin', 'instance' ), 20 );

/**
 * Declare compatibility with WooCommerce features we do not touch.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCW_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WCW_FILE, true );
		}
	}
);
