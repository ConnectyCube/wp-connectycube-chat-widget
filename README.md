# ConnectyCube Chat Widget – WordPress Plugin

WordPress wrapper for the [ConnectyCube Chat Widget](https://developers.connectycube.com/chat-widget/getting-started/) (Vanilla JS). Embed real-time chat, presence, file sharing, and optional voice/video calls and AI features on your site, with all widget options configurable from **Settings → ConnectyCube Chat**.

## Requirements

- WordPress 5.9+
- PHP 7.4+
- ConnectyCube account and application (App ID and Auth Key from [ConnectyCube Dashboard](https://connectycube.com))

## Installation

1. Download or clone this repo into `wp-content/plugins/` so the plugin path is `wp-content/plugins/connectycube-chat-widget/`.
2. In WordPress admin go to **Plugins** and activate **ConnectyCube Chat Widget**.
3. Go to **Settings → ConnectyCube Chat**, enter your **App ID** and **Auth Key**, and configure options as needed.
4. Save. The widget will appear on the frontend according to your display settings (floating button or embedded).

## Features

- **Credentials** – App ID, Auth Key, optional debug mode.
- **User** – Use logged-in WordPress user (ID, name, email, avatar) or override; optional in-widget login/register for guests.
- **Display** – Enable/disable, language (en, el, ua, es), split/mobile/embedded view, hide toggle button, accessibility title, close on outside click/Escape, connection status, logo.
- **Features** – Online users tab, new chat/1-on-1/group options, voice & video calls, user statuses, last seen, content reporting, block list, online badge, URL preview, attachment accept.
- **Notifications** – Mute all, browser notifications, play sound.
- **Single view (support chat)** – One chat with your team, quick actions, terms link.
- **AI** – Optional Google AI API key for message tone and chat summarization.
- **Push** – Web push (VAPID key, service worker path).
- **Styling** – Optional JSON for button, portal, and badge inline styles.

All options map to the [official widget props](https://developers.connectycube.com/chat-widget/getting-started/#props). When **Use logged-in user** is enabled, the current WordPress user’s ID, display name, email, and avatar are passed into the widget.

## Plugin structure

- **connectycube-chat-widget.php** – Bootstrap, constants, activation.
- **includes/class-admin.php** – Settings API, sections/fields, sanitization, defaults.
- **includes/class-frontend.php** – Enqueue React, ReactDOM, ConnectyCube SDK, and widget UMD from CDN; build props from options and current user; mount widget in footer.
- **uninstall.php** – Removes stored options on uninstall.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
