<?php
/**
 * Plugin Name:       ConnectyCube Chat Widget
 * Plugin URI:        https://connectycube.com
 * Description:       Embed ConnectyCube chat widget on your WordPress site. Configure chat, presence, calls, and AI features via Settings.
 * Version:           0.10.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            ConnectyCube
 * Author URI:        https://connectycube.com
 * License:           Apache 2.0
 * Text Domain:       connectycube-chat-widget
 *
 * @package ConnectyCubeChatWidget
 */

declare(strict_types=1);

namespace ConnectyCubeChatWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CONNECTYCUBE_CHAT_WIDGET_VERSION', '1.0.0' );
define( 'CONNECTYCUBE_CHAT_WIDGET_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONNECTYCUBE_CHAT_WIDGET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CONNECTYCUBE_CHAT_WIDGET_OPTION_GROUP', 'connectycube_chat_widget_settings' );
define( 'CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME', 'connectycube_chat_widget_options' );

require_once CONNECTYCUBE_CHAT_WIDGET_PLUGIN_DIR . 'includes/class-admin.php';
require_once CONNECTYCUBE_CHAT_WIDGET_PLUGIN_DIR . 'includes/class-frontend.php';

/**
 * Bootstrap the plugin.
 */
function connectycube_chat_widget_init(): void {
	Admin::instance()->init();
	Frontend::instance()->init();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\connectycube_chat_widget_init' );

/**
 * Activation: set defaults.
 */
register_activation_hook( __FILE__, function (): void {
	if ( get_option( CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME ) === false ) {
		update_option( CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME, Admin::get_default_options() );
	}
} );
