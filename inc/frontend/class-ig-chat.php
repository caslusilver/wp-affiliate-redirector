<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shortcode do simulador de Direct (Instagram-like).
 *
 * Tag: [war_ig_chat]
 */
class WAR_IG_Chat_Shortcode {
	const SHORTCODE_TAG = 'war_ig_chat';
	const NONCE_ACTION = 'war_ig_chat_nonce';

	public static function init() {
		add_shortcode(self::SHORTCODE_TAG, [__CLASS__, 'render_shortcode']);
	}

	private static function get_integrations_settings() {
		if (class_exists('WAR_Admin_Integrations')) {
			return WAR_Admin_Integrations::get_settings();
		}

		return [
			'webhook_url' => '',
			'header_name' => 'Chat',
			'header_status' => 'Online agora',
			'header_avatar_url' => '',
			'send_icon_url' => '',
			'initial_message' => '',
			'error_message' => 'Tenta de novo.',
			'rate_limit_ms' => 2500,
		];
	}

	private static function enqueue_assets(array $settings, array $context) {
		$version = function_exists('WP_AFFILIATE_REDIRECTOR_get_version') ? WP_AFFILIATE_REDIRECTOR_get_version() : '0.0.0';

		wp_enqueue_style(
			'war-ig-chat',
			WAR_PLUGIN_URL . 'assets/ig-chat/ig-chat.css',
			[],
			$version
		);

		wp_enqueue_script(
			'war-ig-chat',
			WAR_PLUGIN_URL . 'assets/ig-chat/ig-chat.js',
			[],
			$version,
			true
		);

		wp_localize_script('war-ig-chat', 'WARIgChat', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce(self::NONCE_ACTION),
			'page_slug' => isset($context['page_slug']) ? (string) $context['page_slug'] : '',
			'initial_message' => (string) ($settings['initial_message'] ?? ''),
			'error_message' => (string) ($settings['error_message'] ?? 'Tenta de novo.'),
			'send_icon_url' => esc_url_raw((string) ($settings['send_icon_url'] ?? '')),
			'rate_limit_ms' => (int) ($settings['rate_limit_ms'] ?? 2500),
		]);
	}

	public static function render_shortcode($atts = []) {
		$settings = self::get_integrations_settings();

		$page_slug = '';
		if (is_singular()) {
			$page_slug = (string) get_post_field('post_name', get_queried_object_id());
		}

		self::enqueue_assets($settings, [
			'page_slug' => $page_slug ?: (string) wp_parse_url(home_url(add_query_arg([])), PHP_URL_PATH),
		]);

		$template = WAR_PLUGIN_DIR . 'templates/ig-chat.php';

		$chat_header = [
			'name' => (string) ($settings['header_name'] ?? 'Chat'),
			'status' => (string) ($settings['header_status'] ?? 'Online agora'),
			'avatar_url' => (string) ($settings['header_avatar_url'] ?? ''),
		];

		$initial_message = (string) ($settings['initial_message'] ?? '');

		ob_start();
		if (file_exists($template)) {
			include $template;
		} else {
			echo '<p style="color:#b32d2e;">' . esc_html__('Erro: template do chat não encontrado.', WAR_TEXT_DOMAIN) . '</p>';
		}
		return (string) ob_get_clean();
	}
}


