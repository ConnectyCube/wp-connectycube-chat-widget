<?php
/**
 * Admin settings for ConnectyCube Chat Widget.
 *
 * @package ConnectyCubeChatWidget
 */

declare(strict_types=1);

namespace ConnectyCubeChatWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings and options.
 */
final class Admin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( CONNECTYCUBE_CHAT_WIDGET_PLUGIN_DIR . 'connectycube-chat-widget.php' ), [ $this, 'plugin_action_links' ] );
	}

	public function add_menu_page(): void {
		add_options_page(
			__( 'ConnectyCube Chat Widget', 'connectycube-chat-widget' ),
			__( 'ConnectyCube Chat', 'connectycube-chat-widget' ),
			'manage_options',
			'connectycube-chat-widget',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings(): void {
		$option_name = CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME;
		register_setting(
			CONNECTYCUBE_CHAT_WIDGET_OPTION_GROUP,
			$option_name,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_options' ],
				'default'          => self::get_default_options(),
			]
		);

		$sections = [
			'credentials'   => [ __( 'Credentials', 'connectycube-chat-widget' ), __( 'ConnectyCube Application ID and Auth Key from your ConnectyCube Dashboard.', 'connectycube-chat-widget' ) ],
			'user'          => [ __( 'User', 'connectycube-chat-widget' ), __( 'How the current visitor is identified in chat. When "Use logged-in user" is enabled, WordPress user data is used.', 'connectycube-chat-widget' ) ],
			'display'       => [ __( 'Display', 'connectycube-chat-widget' ), __( 'Widget layout, position, and visibility.', 'connectycube-chat-widget' ) ],
			'features'      => [ __( 'Features', 'connectycube-chat-widget' ), __( 'Tabs, chat creation, moderation, calls, and presence.', 'connectycube-chat-widget' ) ],
			'notifications' => [ __( 'Notifications', 'connectycube-chat-widget' ), __( 'Browser and push notifications and sounds.', 'connectycube-chat-widget' ) ],
			'single_view'   => [ __( 'Single View (Support Chat)', 'connectycube-chat-widget' ), __( 'Customer support mode: one chat with your team. Quick actions and terms link.', 'connectycube-chat-widget' ) ],
			'ai'            => [ __( 'AI', 'connectycube-chat-widget' ), __( 'Message tone and chat summarization (Google AI API key required).', 'connectycube-chat-widget' ) ],
			'push'          => [ __( 'Push Notifications', 'connectycube-chat-widget' ), __( 'Web push when the tab is closed. Requires VAPID keys and a service worker.', 'connectycube-chat-widget' ) ],
			'styling'       => [ __( 'Styling', 'connectycube-chat-widget' ), __( 'Optional inline styles for button, portal, and badge (CSS JSON).', 'connectycube-chat-widget' ) ],
		];

		$page = 'connectycube-chat-widget';
		foreach ( $sections as $id => $label_desc ) {
			add_settings_section(
				$id,
				$label_desc[0],
				function () use ( $label_desc ) {
					echo '<p class="description">' . esc_html( $label_desc[1] ) . '</p>';
				},
				$page
			);
		}

		$this->register_fields( $page );
	}

	private function register_fields( string $page ): void {
		$opt = CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME;

		// Credentials
		$this->add_field( $page, 'credentials', 'app_id', __( 'App ID', 'connectycube-chat-widget' ), 'number', __( 'ConnectyCube Application ID (numeric).', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'credentials', 'auth_key', __( 'Auth Key', 'connectycube-chat-widget' ), 'text', __( 'ConnectyCube Authentication Key.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'credentials', 'config_debug', __( 'Debug mode', 'connectycube-chat-widget' ), 'checkbox', __( 'Enable ConnectyCube SDK debug (config.debug.mode: 1).', 'connectycube-chat-widget' ) );

		// User
		$this->add_field( $page, 'user', 'use_wp_user', __( 'Use logged-in user', 'connectycube-chat-widget' ), 'checkbox', __( 'Use current WordPress user ID, display name, email, and avatar. If unchecked, enable "User login" below for guests.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'user', 'enable_user_login', __( 'User login/register', 'connectycube-chat-widget' ), 'checkbox', __( 'Allow visitors to log in or register within the widget (e.g. for guests).', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'user', 'user_id_override', __( 'User ID override', 'connectycube-chat-widget' ), 'text', __( 'Optional. Override userId (your system ID). Leave empty when using logged-in user.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'user', 'user_name_override', __( 'User name override', 'connectycube-chat-widget' ), 'text', __( 'Optional. Override display name.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'user', 'user_profile_link', __( 'User profile link URL', 'connectycube-chat-widget' ), 'url', __( 'Optional. URL to user profile (e.g. author archive).', 'connectycube-chat-widget' ) );

		// Display
		$this->add_field( $page, 'display', 'enabled', __( 'Enable widget', 'connectycube-chat-widget' ), 'checkbox', __( 'Show the chat widget on the frontend.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'translation', __( 'Language', 'connectycube-chat-widget' ), 'select', __( 'Widget language.', 'connectycube-chat-widget' ), [ 'options' => [ 'en' => 'English', 'el' => 'Greek', 'ua' => 'Ukrainian', 'es' => 'Spanish' ] ] );
		$this->add_field( $page, 'display', 'split_view', __( 'Split view', 'connectycube-chat-widget' ), 'checkbox', __( 'Desktop: show chats list and messages side by side. Uncheck for mobile-style single panel.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'embed_view', __( 'Embedded view', 'connectycube-chat-widget' ), 'checkbox', __( 'Embed widget in page (e.g. full width). Hides floating button.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'hide_widget_button', __( 'Hide toggle button', 'connectycube-chat-widget' ), 'checkbox', __( 'Hide the floating chat button (open via custom button or embed).', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'button_title', __( 'Button title (accessibility)', 'connectycube-chat-widget' ), 'text', __( 'Title attribute for the chat toggle button.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'disable_click_outside', __( 'Disable close on click outside', 'connectycube-chat-widget' ), 'checkbox', __( 'Do not close widget when clicking outside.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'disable_esc_key_press', __( 'Disable close on Escape', 'connectycube-chat-widget' ), 'checkbox', __( 'Do not close widget on Escape key.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'show_chat_status', __( 'Show connection status', 'connectycube-chat-widget' ), 'checkbox', __( 'Display connection status indicator.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'show_chat_actions_as_menu', __( 'Chat actions as menu', 'connectycube-chat-widget' ), 'checkbox', __( 'Show chat header actions as menu (default: icon buttons).', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'display', 'img_logo_source', __( 'Logo URL', 'connectycube-chat-widget' ), 'url', __( 'Custom logo (relative path or URL).', 'connectycube-chat-widget' ) );

		// Features
		$this->add_field( $page, 'features', 'show_online_users_tab', __( 'Online users tab', 'connectycube-chat-widget' ), 'checkbox', __( 'Show tab with online users. Enable Online Users API in ConnectyCube Dashboard.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'hide_new_chat_button', __( 'Hide New Chat button', 'connectycube-chat-widget' ), 'checkbox', __( 'Hide the main New Chat button.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'hide_new_user_chat_option', __( 'Hide New 1-on-1 option', 'connectycube-chat-widget' ), 'checkbox', __( 'Hide option to start new 1-on-1 in Create Chat.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'hide_new_group_chat_option', __( 'Hide New Group option', 'connectycube-chat-widget' ), 'checkbox', __( 'Hide New Group in Create Chat.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'enable_calls', __( 'Voice & video calls', 'connectycube-chat-widget' ), 'checkbox', __( 'Enable audio/video call buttons.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'enable_user_statuses', __( 'User statuses', 'connectycube-chat-widget' ), 'checkbox', __( 'Enable Available, Busy, Away.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'enable_last_seen', __( 'Last seen', 'connectycube-chat-widget' ), 'checkbox', __( 'Show online dot and last seen in chat header.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'enable_content_reporting', __( 'Content reporting', 'connectycube-chat-widget' ), 'checkbox', __( 'Show Report button in user profile. Requires UserReports table in Dashboard.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'enable_block_list', __( 'Block list', 'connectycube-chat-widget' ), 'checkbox', __( 'Allow users to block/unblock others.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'enable_online_users_badge', __( 'Online users badge', 'connectycube-chat-widget' ), 'checkbox', __( 'Show online users count on widget button.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'get_online_users_interval', __( 'Online users refresh (seconds)', 'connectycube-chat-widget' ), 'number', __( 'Min 30. How often to update online count.', 'connectycube-chat-widget' ), [ 'min' => 30 ] );
		$this->add_field( $page, 'features', 'enable_url_preview', __( 'URL preview', 'connectycube-chat-widget' ), 'checkbox', __( 'Unfurl links in messages.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'features', 'limit_urls_previews', __( 'Max URL previews per message', 'connectycube-chat-widget' ), 'number', __( 'Max 5.', 'connectycube-chat-widget' ), [ 'min' => 1, 'max' => 5 ] );
		$this->add_field( $page, 'features', 'attachments_accept', __( 'Attachments accept', 'connectycube-chat-widget' ), 'text', __( 'HTML accept attribute for file input (e.g. image/*,.pdf). Empty to hide attachment button.', 'connectycube-chat-widget' ) );

		// Notifications
		$this->add_field( $page, 'notifications', 'muted', __( 'Mute all', 'connectycube-chat-widget' ), 'checkbox', __( 'Mute notifications and sounds.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'notifications', 'show_notifications', __( 'Browser notifications', 'connectycube-chat-widget' ), 'checkbox', __( 'Show browser notifications for new messages.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'notifications', 'play_sound', __( 'Play sound', 'connectycube-chat-widget' ), 'checkbox', __( 'Play sound on new messages.', 'connectycube-chat-widget' ) );

		// Single view
		$this->add_field( $page, 'single_view', 'single_view', __( 'Single view (support mode)', 'connectycube-chat-widget' ), 'checkbox', __( 'One chat with support team. Configure opponents and quick actions below.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'single_view', 'single_view_chat_name', __( 'Support chat name', 'connectycube-chat-widget' ), 'text', __( 'e.g. "Support".', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'single_view', 'single_view_chat_photo', __( 'Support chat photo URL', 'connectycube-chat-widget' ), 'url', __( 'Image URL for support chat.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'single_view', 'single_view_opponent_user_ids', __( 'Opponent user IDs', 'connectycube-chat-widget' ), 'text', __( 'Comma-separated ConnectyCube user IDs (support agents).', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'single_view', 'single_view_external_id', __( 'External dialog ID', 'connectycube-chat-widget' ), 'text', __( 'Optional. Stable chat identity across sessions.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'single_view', 'terms_and_conditions', __( 'Terms and Conditions URL', 'connectycube-chat-widget' ), 'url', __( 'Shown in confirm email form when single view is enabled.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'single_view', 'quick_actions_title', __( 'Quick actions title', 'connectycube-chat-widget' ), 'text', __( 'Title for quick actions section.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'single_view', 'quick_actions_description', __( 'Quick actions description', 'connectycube-chat-widget' ), 'textarea', __( 'Short description above quick action buttons.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'single_view', 'quick_actions_list', __( 'Quick actions (one per line)', 'connectycube-chat-widget' ), 'textarea', __( 'One action per line, e.g. "🛒 Order question".', 'connectycube-chat-widget' ) );

		// AI
		$this->add_field( $page, 'ai', 'ai_api_key', __( 'Google AI API key', 'connectycube-chat-widget' ), 'text', __( 'Google Generative AI key for Gemini (optional). Get at aistudio.google.com.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'ai', 'ai_change_message_tone', __( 'Change message tone', 'connectycube-chat-widget' ), 'checkbox', __( 'Allow rephrasing message tone before send.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'ai', 'ai_text_summarization', __( 'Chat summarization', 'connectycube-chat-widget' ), 'checkbox', __( 'Allow generating conversation summary.', 'connectycube-chat-widget' ) );

		// Push
		$this->add_field( $page, 'push', 'web_push_notifications', __( 'Web push notifications', 'connectycube-chat-widget' ), 'checkbox', __( 'Receive push when tab is closed. Set VAPID in Dashboard.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'push', 'web_push_vapid_public_key', __( 'VAPID public key', 'connectycube-chat-widget' ), 'text', __( 'Same as in ConnectyCube Dashboard > Push > Web Push.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'push', 'service_worker_path', __( 'Service worker path/URL', 'connectycube-chat-widget' ), 'text', __( 'Path or URL to your service worker file.', 'connectycube-chat-widget' ) );

		// Styling (optional JSON)
		$this->add_field( $page, 'styling', 'button_style_json', __( 'Button style (JSON)', 'connectycube-chat-widget' ), 'textarea', __( 'e.g. {"left":"0.5rem","right":"auto"}. Leave empty for default.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'styling', 'portal_style_json', __( 'Portal style (JSON)', 'connectycube-chat-widget' ), 'textarea', __( 'Inline styles for chat window. Leave empty for default.', 'connectycube-chat-widget' ) );
		$this->add_field( $page, 'styling', 'badge_style_json', __( 'Badge style (JSON)', 'connectycube-chat-widget' ), 'textarea', __( 'Unread badge styles. Leave empty for default.', 'connectycube-chat-widget' ) );
	}

	private function add_field( string $page, string $section, string $key, string $title, string $type, string $description = '', array $args = [] ): void {
		$field_id = CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME . '_' . $key;
		add_settings_field(
			$field_id,
			$title,
			function () use ( $key, $type, $description, $args ) {
				$this->render_field( $key, $type, $description, $args );
			},
			$page,
			$section,
			[ 'label_for' => $field_id ]
		);
	}

	private function render_field( string $key, string $type, string $description, array $args ): void {
		$opts = get_option( CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME, [] );
		$val  = $opts[ $key ] ?? null;
		$id   = CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME . '[' . $key . ']';
		$name = $id;

		switch ( $type ) {
			case 'number':
				$min = $args['min'] ?? null;
				$max = $args['max'] ?? null;
				$attr = '';
				if ( $min !== null ) {
					$attr .= ' min="' . esc_attr( (string) $min ) . '"';
				}
				if ( $max !== null ) {
					$attr .= ' max="' . esc_attr( (string) $max ) . '"';
				}
				echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $val ) . '" class="small-text"' . $attr . ' />';
				break;
			case 'checkbox':
				$checked = ! empty( $val );
				echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . ( $checked ? 'checked="checked"' : '' ) . ' /> ' . esc_html__( 'Yes', 'connectycube-chat-widget' ) . '</label>';
				break;
			case 'url':
			case 'text':
				$input_type = $type === 'url' ? 'url' : 'text';
				echo '<input type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( is_scalar( $val ) ? (string) $val : '' ) . '" class="regular-text" />';
				break;
			case 'textarea':
				echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="4" class="large-text">' . esc_textarea( is_scalar( $val ) ? (string) $val : '' ) . '</textarea>';
				break;
			case 'select':
				$options = $args['options'] ?? [];
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $options as $k => $label ) {
					echo '<option value="' . esc_attr( $k ) . '" ' . selected( $val, $k, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				break;
			default:
				echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( is_scalar( $val ) ? (string) $val : '' ) . '" class="regular-text" />';
		}
		if ( $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
	}

	public function sanitize_options( $input ): array {
		if ( ! is_array( $input ) ) {
			return self::get_default_options();
		}
		$defaults = self::get_default_options();
		$out      = [];

		foreach ( $defaults as $key => $default ) {
			$raw = $input[ $key ] ?? $default;
			switch ( $key ) {
				case 'app_id':
					$out[ $key ] = absint( $raw ) ?: $default;
					break;
				case 'config_debug':
				case 'use_wp_user':
				case 'enable_user_login':
				case 'enabled':
				case 'split_view':
				case 'embed_view':
				case 'hide_widget_button':
				case 'disable_click_outside':
				case 'disable_esc_key_press':
				case 'show_chat_status':
				case 'show_chat_actions_as_menu':
				case 'show_online_users_tab':
				case 'hide_new_chat_button':
				case 'hide_new_user_chat_option':
				case 'hide_new_group_chat_option':
				case 'enable_calls':
				case 'enable_user_statuses':
				case 'enable_last_seen':
				case 'enable_content_reporting':
				case 'enable_block_list':
				case 'enable_online_users_badge':
				case 'enable_url_preview':
				case 'muted':
				case 'show_notifications':
				case 'play_sound':
				case 'single_view':
				case 'web_push_notifications':
				case 'ai_change_message_tone':
				case 'ai_text_summarization':
					$out[ $key ] = ! empty( $raw );
					break;
				case 'get_online_users_interval':
					$v = absint( $raw );
					$out[ $key ] = ( $v >= 30 ) ? $v : 300;
					break;
				case 'limit_urls_previews':
					$v = absint( $raw );
					$out[ $key ] = $v >= 1 && $v <= 5 ? $v : 1;
					break;
				case 'translation':
					$out[ $key ] = in_array( $raw, [ 'en', 'el', 'ua', 'es' ], true ) ? $raw : 'en';
					break;
				case 'auth_key':
				case 'user_id_override':
				case 'user_name_override':
				case 'user_profile_link':
				case 'button_title':
				case 'img_logo_source':
				case 'single_view_chat_name':
				case 'single_view_chat_photo':
				case 'single_view_opponent_user_ids':
				case 'single_view_external_id':
				case 'terms_and_conditions':
				case 'quick_actions_title':
				case 'quick_actions_description':
				case 'quick_actions_list':
				case 'ai_api_key':
				case 'web_push_vapid_public_key':
				case 'service_worker_path':
				case 'attachments_accept':
					$out[ $key ] = sanitize_text_field( (string) $raw );
					break;
				case 'button_style_json':
				case 'portal_style_json':
				case 'badge_style_json':
					$decoded = json_decode( (string) $raw, true );
					$out[ $key ] = is_array( $decoded ) ? wp_json_encode( $decoded ) : '';
					break;
				default:
					$out[ $key ] = is_scalar( $raw ) ? sanitize_text_field( (string) $raw ) : $default;
			}
		}
		return $out;
	}

	public static function get_default_options(): array {
		return [
			'app_id'                      => 0,
			'auth_key'                    => '',
			'config_debug'                => false,
			'use_wp_user'                 => true,
			'enable_user_login'           => false,
			'user_id_override'            => '',
			'user_name_override'          => '',
			'user_profile_link'          => '',
			'enabled'                     => true,
			'translation'                 => 'en',
			'split_view'                  => true,
			'embed_view'                  => false,
			'hide_widget_button'          => false,
			'button_title'                => 'Chat',
			'disable_click_outside'       => false,
			'disable_esc_key_press'       => false,
			'show_chat_status'            => false,
			'show_chat_actions_as_menu'   => true,
			'img_logo_source'             => '/logo.png',
			'show_online_users_tab'       => false,
			'hide_new_chat_button'        => false,
			'hide_new_user_chat_option'   => false,
			'hide_new_group_chat_option'  => false,
			'enable_calls'                => false,
			'enable_user_statuses'        => false,
			'enable_last_seen'            => false,
			'enable_content_reporting'    => false,
			'enable_block_list'           => false,
			'enable_online_users_badge'   => false,
			'get_online_users_interval'   => 300,
			'enable_url_preview'          => false,
			'limit_urls_previews'         => 1,
			'attachments_accept'          => '*/*',
			'muted'                       => false,
			'show_notifications'          => false,
			'play_sound'                  => true,
			'single_view'                 => false,
			'single_view_chat_name'       => '',
			'single_view_chat_photo'      => '',
			'single_view_opponent_user_ids' => '',
			'single_view_external_id'     => '',
			'terms_and_conditions'        => '',
			'quick_actions_title'         => '',
			'quick_actions_description'  => '',
			'quick_actions_list'          => '',
			'ai_api_key'                  => '',
			'ai_change_message_tone'      => false,
			'ai_text_summarization'       => false,
			'web_push_notifications'      => false,
			'web_push_vapid_public_key'   => '',
			'service_worker_path'         => '',
			'button_style_json'           => '',
			'portal_style_json'           => '',
			'badge_style_json'            => '',
		];
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>
				<?php
				echo wp_kses(
					__( 'Configure the <a href="https://developers.connectycube.com/chat-widget/getting-started/" target="_blank" rel="noopener">ConnectyCube Chat Widget</a> (Vanilla JS). Get your App ID and Auth Key from the <a href="https://connectycube.com" target="_blank" rel="noopener">ConnectyCube Dashboard</a>.', 'connectycube-chat-widget' ),
					[ 'a' => [ 'href' => true, 'target' => true, 'rel' => true ] ]
				);
				?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( CONNECTYCUBE_CHAT_WIDGET_OPTION_GROUP );
				do_settings_sections( 'connectycube-chat-widget' );
				submit_button( __( 'Save settings', 'connectycube-chat-widget' ) );
				?>
			</form>
		</div>
		<?php
	}

	public function plugin_action_links( array $links ): array {
		$url = admin_url( 'options-general.php?page=connectycube-chat-widget' );
		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'connectycube-chat-widget' ) . '</a>';
		return $links;
	}
}
