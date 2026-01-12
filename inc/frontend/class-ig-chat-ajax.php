<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * AJAX proxy para o webhook do Chat IG.
 *
 * Actions:
 * - wp_ajax_nopriv_war_ig_chat_send
 * - wp_ajax_war_ig_chat_send
 */
class WAR_IG_Chat_Ajax {
	const NONCE_ACTION = 'war_ig_chat_nonce';

	public static function init() {
		add_action('wp_ajax_nopriv_war_ig_chat_send', [__CLASS__, 'handle_send']);
		add_action('wp_ajax_war_ig_chat_send', [__CLASS__, 'handle_send']);
	}

	private static function settings() {
		if (class_exists('WAR_Admin_Integrations')) {
			return WAR_Admin_Integrations::get_settings();
		}
		return [
			'webhook_url' => '',
			'secret' => '',
			'rate_limit_ms' => 2500,
		];
	}

	private static function guard_rate_limit($session_id, $rate_limit_ms) {
		$session_id = (string) $session_id;
		if ($session_id === '' || $rate_limit_ms <= 0) {
			return;
		}

		$key = 'war_ig_rl_' . md5($session_id);
		$last = (int) get_transient($key);
		$now = (int) round(microtime(true) * 1000);

		if ($last && ($now - $last) < $rate_limit_ms) {
			wp_send_json_error(['message' => 'Aguarde um instante e tente novamente.'], 429);
		}

		set_transient($key, $now, 30);
	}

	private static function normalize_response($raw) {
		$raw = is_array($raw) ? $raw : [];

		$headline = isset($raw['headline']) ? sanitize_text_field((string) $raw['headline']) : '✅ Link liberado';
		$subtitle = isset($raw['subtitle']) ? sanitize_textarea_field((string) $raw['subtitle']) : '';
		$button_text = isset($raw['button_text']) ? sanitize_text_field((string) $raw['button_text']) : 'Toque aqui p/ acessar';
		$button_url = isset($raw['button_url']) ? esc_url_raw((string) $raw['button_url'], ['http', 'https']) : '';

		if ($button_url === '') {
			return null;
		}

		return [
			'headline' => $headline,
			'subtitle' => $subtitle,
			'button_text' => $button_text,
			'button_url' => $button_url,
			'status_badge' => isset($raw['status_badge']) ? sanitize_text_field((string) $raw['status_badge']) : '',
			'type' => isset($raw['type']) ? sanitize_key((string) $raw['type']) : 'card',
		];
	}

	public static function handle_send() {
		check_ajax_referer(self::NONCE_ACTION, 'nonce');

		$keyword = isset($_POST['keyword']) ? sanitize_text_field((string) wp_unslash($_POST['keyword'])) : '';
		$page_slug = isset($_POST['page_slug']) ? sanitize_text_field((string) wp_unslash($_POST['page_slug'])) : '';
		$session_id = isset($_POST['session_id']) ? sanitize_text_field((string) wp_unslash($_POST['session_id'])) : '';
		$timestamp = isset($_POST['timestamp']) ? sanitize_text_field((string) wp_unslash($_POST['timestamp'])) : '';
		$referrer = isset($_POST['referrer']) ? esc_url_raw((string) wp_unslash($_POST['referrer'])) : '';

		WAR_Debug::log('IG Chat: Recebida requisição', [
			'keyword' => $keyword,
			'session_id' => $session_id,
			'page_slug' => $page_slug,
		]);

		if ($keyword === '') {
			WAR_Debug::log('IG Chat: Keyword vazia');
			wp_send_json_error(['message' => 'Keyword vazia.'], 400);
		}

		$settings = self::settings();
		$rate_limit_ms = (int) ($settings['rate_limit_ms'] ?? 2500);
		self::guard_rate_limit($session_id, $rate_limit_ms);

		// ========================================
		// NOVO: Busca local por keyword primeiro
		// ========================================
		WAR_Debug::log('IG Chat: Buscando keyword localmente', ['keyword' => $keyword]);
		$local_result = war_find_link_by_keyword($keyword);
		if ($local_result) {
			WAR_Debug::log('IG Chat: Link encontrado localmente', ['result' => $local_result]);
			wp_send_json_success($local_result);
			return; // Encerra aqui, sem chamar webhook
		}

		WAR_Debug::log('IG Chat: Nenhum link local encontrado, tentando webhook');

		// ========================================
		// FALLBACK: Chama webhook se configurado
		// ========================================
		$webhook_url = (string) ($settings['webhook_url'] ?? '');
		if ($webhook_url === '') {
			WAR_Debug::log('IG Chat: Webhook não configurado');
			wp_send_json_error(['message' => 'Nenhum link encontrado para essa palavra-chave.'], 404);
		}

		$payload = [
			'keyword' => $keyword,
			'page_slug' => $page_slug,
			'session_id' => $session_id,
			'timestamp' => $timestamp,
			'referrer' => $referrer,
		];

		$body = wp_json_encode($payload);
		$secret = (string) ($settings['secret'] ?? '');
		$signature = $secret !== '' ? hash_hmac('sha256', (string) $body, $secret) : '';

		$args = [
			'timeout' => 12,
			'headers' => array_filter([
				'Content-Type' => 'application/json',
				'X-WAR-Timestamp' => (string) $timestamp,
				'X-WAR-Signature' => $signature,
			]),
			'body' => $body,
		];

		WAR_Debug::log('IG Chat: Chamando webhook', ['webhook_url' => $webhook_url]);
		$res = wp_remote_post($webhook_url, $args);
		if (is_wp_error($res)) {
			WAR_Debug::log('IG Chat: Erro no webhook', ['error' => $res->get_error_message()]);
			wp_send_json_error(['message' => $res->get_error_message()], 502);
		}

		$code = (int) wp_remote_retrieve_response_code($res);
		$raw_body = (string) wp_remote_retrieve_body($res);
		WAR_Debug::log('IG Chat: Resposta do webhook', ['code' => $code, 'body_length' => strlen($raw_body)]);

		$json = json_decode($raw_body, true);
		if (!is_array($json)) {
			WAR_Debug::log('IG Chat: Resposta inválida do webhook', ['raw_body' => substr($raw_body, 0, 200)]);
			wp_send_json_error(['message' => 'Resposta inválida do webhook.'], 502);
		}

		$normalized = self::normalize_response($json);
		if (!$normalized) {
			WAR_Debug::log('IG Chat: Payload incompleto do webhook', ['json' => $json]);
			wp_send_json_error(['message' => 'Webhook retornou payload incompleto.'], 502);
		}

		if ($code < 200 || $code >= 300) {
			WAR_Debug::log('IG Chat: Webhook retornou erro', ['code' => $code]);
			wp_send_json_error(['message' => 'Webhook retornou erro.'], 502);
		}

		WAR_Debug::log('IG Chat: Sucesso via webhook', ['normalized' => $normalized]);
		wp_send_json_success($normalized);
	}
}


