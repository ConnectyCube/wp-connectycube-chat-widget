<?php
/**
 * Uninstall: remove plugin options.
 *
 * @package ConnectyCubeChatWidget
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'connectycube_chat_widget_options' );
