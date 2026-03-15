<?php
/**
 * Frontend: enqueue ConnectyCube Chat Widget (Vanilla JS) and pass options.
 *
 * @package ConnectyCubeChatWidget
 */

declare(strict_types=1);

namespace ConnectyCubeChatWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend widget loader.
 */
final class Frontend {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_widget' ], 20 );
		add_action( 'wp_footer', [ $this, 'render_widget_container' ], 5 );
	}

	public function enqueue_widget(): void {
		$opts = get_option( CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME, [] );
		if ( empty( $opts['enabled'] ) || empty( $opts['app_id'] ) || empty( $opts['auth_key'] ) ) {
			return;
		}

		$react_ver = '18';
		$connectycube_ver = '6.1.2';
		$widget_ver = '0.43.0';

		wp_enqueue_script(
			'react',
			"https://unpkg.com/react@{$react_ver}/umd/react.production.min.js",
			[],
			$react_ver,
			true
		);
		wp_script_add_data( 'react', 'crossorigin', 'anonymous' );

		wp_enqueue_script(
			'react-dom',
			"https://unpkg.com/react-dom@{$react_ver}/umd/react-dom.production.min.js",
			[ 'react' ],
			$react_ver,
			true
		);
		wp_script_add_data( 'react-dom', 'crossorigin', 'anonymous' );

		wp_enqueue_script(
			'connectycube',
			"https://unpkg.com/connectycube@{$connectycube_ver}/dist/connectycube.min.js",
			[],
			$connectycube_ver,
			true
		);

		wp_enqueue_script(
			'connectycube-chat-widget-umd',
			"https://unpkg.com/@connectycube/chat-widget@{$widget_ver}/dist/index.umd.js",
			[ 'react', 'react-dom', 'connectycube' ],
			$widget_ver,
			true
		);
		wp_script_add_data( 'connectycube-chat-widget-umd', 'crossorigin', 'anonymous' );

		$props = $this->build_widget_props( $opts );
		$inline = $this->get_inline_script( $props );
		wp_add_inline_script( 'connectycube-chat-widget-umd', $inline, 'after' );
	}

	public function render_widget_container(): void {
		$opts = get_option( CONNECTYCUBE_CHAT_WIDGET_OPTION_NAME, [] );
		if ( empty( $opts['enabled'] ) || empty( $opts['app_id'] ) || empty( $opts['auth_key'] ) ) {
			return;
		}
		echo '<div id="ConnectyCube_chat-widget" aria-hidden="false"></div>';
	}

	/**
	 * Build widget props from plugin options and current user.
	 *
	 * @param array<string, mixed> $opts Plugin options.
	 * @return array<string, mixed> Props for ConnectyCubeChatWidget.
	 */
	private function build_widget_props( array $opts ): array {
		$props = [
			'appId'                   => (int) $opts['app_id'],
			'authKey'                 => (string) $opts['auth_key'],
			'userId'                  => $this->get_user_id( $opts ),
			'userName'                => $this->get_user_name( $opts ),
			'translation'             => (string) ( $opts['translation'] ?? 'en' ),
			'splitView'               => ! empty( $opts['split_view'] ),
			'embedView'               => ! empty( $opts['embed_view'] ),
			'hideWidgetButton'        => ! empty( $opts['hide_widget_button'] ),
			'buttonTitle'             => (string) ( $opts['button_title'] ?? 'Chat' ),
			'disableClickOutside'     => ! empty( $opts['disable_click_outside'] ),
			'disableEscKeyPress'      => ! empty( $opts['disable_esc_key_press'] ),
			'showChatStatus'          => ! empty( $opts['show_chat_status'] ),
			'showChatActionsAsMenu'   => ! empty( $opts['show_chat_actions_as_menu'] ),
			'showOnlineUsersTab'      => ! empty( $opts['show_online_users_tab'] ),
			'hideNewChatButton'       => ! empty( $opts['hide_new_chat_button'] ),
			'hideNewUserChatOption'   => ! empty( $opts['hide_new_user_chat_option'] ),
			'hideNewGroupChatOption'  => ! empty( $opts['hide_new_group_chat_option'] ),
			'imgLogoSource'           => (string) ( $opts['img_logo_source'] ?? '/logo.png' ),
			'muted'                   => ! empty( $opts['muted'] ),
			'showNotifications'       => ! empty( $opts['show_notifications'] ),
			'playSound'               => ! empty( $opts['play_sound'] ),
			'enableCalls'             => ! empty( $opts['enable_calls'] ),
			'enableUserStatuses'      => ! empty( $opts['enable_user_statuses'] ),
			'enableLastSeen'          => ! empty( $opts['enable_last_seen'] ),
			'enableContentReporting'  => ! empty( $opts['enable_content_reporting'] ),
			'enableBlockList'         => ! empty( $opts['enable_block_list'] ),
			'enableOnlineUsersBadge'  => ! empty( $opts['enable_online_users_badge'] ),
			'getOnlineUsersInterval'  => (int) ( $opts['get_online_users_interval'] ?? 300 ),
			'enableUrlPreview'        => ! empty( $opts['enable_url_preview'] ),
			'limitUrlsPreviews'       => (int) ( $opts['limit_urls_previews'] ?? 1 ),
			'enableUserLogin'         => ! empty( $opts['enable_user_login'] ),
		];

		if ( ! empty( $opts['config_debug'] ) ) {
			$props['config'] = [ 'debug' => [ 'mode' => 1 ] ];
		}

		$user_email = $this->get_user_email();
		if ( $user_email !== '' ) {
			$props['userEmail'] = $user_email;
		}
		$user_avatar = $this->get_user_avatar();
		if ( $user_avatar !== '' ) {
			$props['userAvatar'] = $user_avatar;
		}
		$profile_link = (string) ( $opts['user_profile_link'] ?? '' );
		if ( $profile_link !== '' ) {
			$props['userProfileLink'] = $profile_link;
		}

		if ( (string) ( $opts['attachments_accept'] ?? '' ) !== '' ) {
			$props['attachmentsAccept'] = (string) $opts['attachments_accept'];
		}

		// Single view (support chat)
		if ( ! empty( $opts['single_view'] ) ) {
			$props['singleView'] = true;
			$props['termsAndConditions'] = (string) ( $opts['terms_and_conditions'] ?? '' );
			$opponent_ids = (string) ( $opts['single_view_opponent_user_ids'] ?? '' );
			$props['singleViewChat'] = [
				'name'             => (string) ( $opts['single_view_chat_name'] ?? '' ),
				'photo'            => (string) ( $opts['single_view_chat_photo'] ?? '' ),
				'opponentUserIds'  => array_filter( array_map( 'trim', explode( ',', $opponent_ids ) ) ),
			];
			$external_id = (string) ( $opts['single_view_external_id'] ?? '' );
			if ( $external_id !== '' ) {
				$props['singleViewChat']['externalId'] = $external_id;
			}
			$qa_title = (string) ( $opts['quick_actions_title'] ?? '' );
			$qa_desc  = (string) ( $opts['quick_actions_description'] ?? '' );
			$qa_list  = (string) ( $opts['quick_actions_list'] ?? '' );
			if ( $qa_title !== '' || $qa_desc !== '' || $qa_list !== '' ) {
				$actions = array_filter( array_map( 'trim', explode( "\n", $qa_list ) ) );
				$props['quickActions'] = [
					'title'       => $qa_title,
					'description' => $qa_desc,
					'actions'     => array_values( $actions ),
				];
			}
		}

		// AI
		$ai_key = (string) ( $opts['ai_api_key'] ?? '' );
		if ( $ai_key !== '' && ( ! empty( $opts['ai_change_message_tone'] ) || ! empty( $opts['ai_text_summarization'] ) ) ) {
			$props['ai'] = [
				'apiKey'             => $ai_key,
				'changeMessageTone'  => ! empty( $opts['ai_change_message_tone'] ),
				'textSummarization'  => ! empty( $opts['ai_text_summarization'] ),
			];
		}

		// Web push
		if ( ! empty( $opts['web_push_notifications'] ) ) {
			$props['webPushNotifications'] = true;
			$vapid = (string) ( $opts['web_push_vapid_public_key'] ?? '' );
			if ( $vapid !== '' ) {
				$props['webPushVapidPublicKey'] = $vapid;
			}
			$sw = (string) ( $opts['service_worker_path'] ?? '' );
			if ( $sw !== '' ) {
				$props['serviceWorkerPath'] = $sw;
			}
		}

		// Optional inline styles (JSON)
		foreach ( [ 'button_style_json' => 'buttonStyle', 'portal_style_json' => 'portalStyle', 'badge_style_json' => 'badgeStyle' ] as $opt_key => $prop_key ) {
			$json = (string) ( $opts[ $opt_key ] ?? '' );
			if ( $json !== '' ) {
				$decoded = json_decode( $json, true );
				if ( is_array( $decoded ) ) {
					$props[ $prop_key ] = $decoded;
				}
			}
		}

		return $props;
	}

	private function get_user_id( array $opts ): string {
		$override = (string) ( $opts['user_id_override'] ?? '' );
		if ( $override !== '' ) {
			return $override;
		}
		if ( ! empty( $opts['use_wp_user'] ) && is_user_logged_in() ) {
			return (string) get_current_user_id();
		}
		return '';
	}

	private function get_user_name( array $opts ): string {
		$override = (string) ( $opts['user_name_override'] ?? '' );
		if ( $override !== '' ) {
			return $override;
		}
		if ( ! empty( $opts['use_wp_user'] ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
			return $user->display_name ?: $user->user_login;
		}
		return __( 'Guest', 'connectycube-chat-widget' );
	}

	private function get_user_email(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$user = wp_get_current_user();
		return (string) ( $user->user_email ?? '' );
	}

	private function get_user_avatar(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$url = get_avatar_url( get_current_user_id(), [ 'size' => 128 ] );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * Return inline script that mounts the widget with given props.
	 *
	 * @param array<string, mixed> $props Widget props (must be JSON-serializable).
	 * @return string JavaScript code.
	 */
	private function get_inline_script( array $props ): string {
		$json = wp_json_encode( $props, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( $json === false ) {
			$json = '{}';
		}
		// ConnectyCubeChatWidget is exposed as global by the UMD bundle (Vanilla JS build).
		return sprintf(
			'(function(){ function mount(){ var c = document.getElementById("ConnectyCube_chat-widget"); if (c && window.React && window.ReactDOM && window.ConnectyCubeChatWidget) { var props = %s; ReactDOM.createRoot(c).render(React.createElement(ConnectyCubeChatWidget, props)); } } if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", mount); } else { mount(); } })();',
			$json
		);
	}
}
