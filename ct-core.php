<?php
/**
 * Plugin Name: CT Core
 * Plugin URI: https://mnor.top/ct-core
 * Description: Core blocks functionality for WordPress block themes
 * Version: 1.0.0
 * Author: mnor
 * Author URI: https://mnor.top
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ct-core
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 7.3
 *
 * @package Ct_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'CT_CORE_VERSION', '1.0.0' );
define( 'CT_CORE_PLUGIN_FILE', __FILE__ );
define( 'CT_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CT_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CT_CORE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load the main loader.
require_once CT_CORE_PLUGIN_DIR . 'class-ct-core-loader.php';

/**
 * Initialize the plugin
 */
function ct_core_init() {
	// Initialize the loader.
	Ct_Core_Loader::instance();
}
add_action( 'plugins_loaded', 'ct_core_init' );

/**
 * Plugin activation hook
 */
function ct_core_activate() {
	// Set default options.
	add_option( 'ct-core_version', CT_CORE_VERSION );
	add_option( 'ct-core_activated', time() );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ct_core_activate' );

/**
 * Plugin deactivation hook
 */
function ct_core_deactivate() {
	// Cleanup tasks.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ct_core_deactivate' );
