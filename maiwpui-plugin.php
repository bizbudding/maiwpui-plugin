<?php
/**
 * Plugin Name:       MaiWPUI
 * Plugin URI:        https://bizbudding.com
 * Description:       WordPress UI library for building mobile apps with Expo.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Text Domain:       maiwpui
 * Author:            BizBudding
 * Author URI:        https://bizbudding.com
 *
 * @package MaiWPUI
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'MAIWPUI_VERSION', '0.1.0' );
define( 'MAIWPUI_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAIWPUI_URL', plugin_dir_url( __FILE__ ) );

// Include core classes.
require_once MAIWPUI_DIR . 'classes/class-logger.php';
require_once MAIWPUI_DIR . 'classes/class-plugin.php';
require_once MAIWPUI_DIR . 'classes/class-auth.php';
require_once MAIWPUI_DIR . 'classes/class-rest-api.php';
require_once MAIWPUI_DIR . 'classes/class-membership-provider.php';
require_once MAIWPUI_DIR . 'classes/class-membership-manager.php';

// Include membership providers.
require_once MAIWPUI_DIR . 'classes/membership-providers/class-woocommerce-memberships.php';
require_once MAIWPUI_DIR . 'classes/membership-providers/class-restrict-content-pro.php';

// Activation/deactivation hooks.
register_activation_hook( __FILE__, [ 'MaiWPUI\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'MaiWPUI\\Plugin', 'deactivate' ] );

add_action( 'plugins_loaded', 'maiwpui_init' );
/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 *
 * @return void
 */
function maiwpui_init() {
	// Initialize core plugin (handles meta registration, upgrades).
	MaiWPUI\Plugin::get_instance();

	// Initialize REST API.
	MaiWPUI\REST_API::get_instance();

	// Initialize Membership Manager (auto-detects available providers).
	MaiWPUI\Membership_Manager::get_instance();
}
