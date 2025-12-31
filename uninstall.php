<?php
/**
 * MaiWPUI Uninstall.
 *
 * Fired when the plugin is uninstalled.
 *
 * @since 0.1.0
 *
 * @package MaiWPUI
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Include the plugin file to get constants and classes.
require_once __DIR__ . '/maiwpui-plugin.php';

// Run uninstall.
MaiWPUI\Plugin::uninstall();
