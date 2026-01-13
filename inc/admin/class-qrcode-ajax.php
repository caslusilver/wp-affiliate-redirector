<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * AJAX handler para geração de QR Code dos links de redirecionamento.
 *
 * Action: wp_ajax_war_generate_qrcode
 */
class WAR_QRCode_Ajax {
	const NONCE_ACTION = 'war_qrcode_nonce';

	public static function init() {
		add_action('wp_ajax_war_generate_qrcode', [__CLASS__, 'handle_generate']);
	}

	public static function handle_generate() {
		WAR_Debug::log('QRCode: Iniciando geração', ['post_data' => $_POST]);
		$is_debug = class_exists('WAR_Config') ? (bool) WAR_Config::is_debug() : false;

		check_ajax_referer(self::NONCE_ACTION, 'nonce');

		if (!current_user_can('manage_options')) {
			WAR_Debug::log('QRCode: Sem permissão', ['user_id' => get_current_user_id()]);
			wp_send_json_error([
				'message' => 'Sem permissão.',
				'debug' => $is_debug ? ['step' => 'capability_check'] : null,
			], 403);
		}

		$link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
		WAR_Debug::log('QRCode: Link ID recebido', ['link_id' => $link_id]);

		if (!$link_id || get_post_type($link_id) !== 'war_link') {
			WAR_Debug::log('QRCode: Link inválido', ['link_id' => $link_id, 'post_type' => get_post_type($link_id)]);
			wp_send_json_error([
				'message' => 'Link inválido.',
				'debug' => $is_debug ? ['step' => 'validate_link', 'link_id' => $link_id] : null,
			], 400);
		}

		$permalink = get_permalink($link_id);
		WAR_Debug::log('QRCode: Permalink obtido', ['permalink' => $permalink]);

		if (!$permalink) {
			WAR_Debug::log('QRCode: Permalink não encontrado', ['link_id' => $link_id]);
			wp_send_json_error([
				'message' => 'Permalink não encontrado.',
				'debug' => $is_debug ? ['step' => 'get_permalink', 'link_id' => $link_id] : null,
			], 404);
		}

		$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($permalink);
		WAR_Debug::log('QRCode: URL da API', ['qr_url' => $qr_url]);

		$response = wp_remote_get($qr_url, ['timeout' => 15]);
		if (is_wp_error($response)) {
			WAR_Debug::log('QRCode: Erro na requisição', ['error' => $response->get_error_message()]);
			wp_send_json_error([
				'message' => 'Erro ao gerar QR Code.',
				'debug' => $is_debug ? ['step' => 'wp_remote_get', 'qr_url' => $qr_url, 'error' => $response->get_error_message()] : null,
			], 502);
		}

		$code = wp_remote_retrieve_response_code($response);
		WAR_Debug::log('QRCode: Código de resposta', ['code' => $code]);

		if ($code !== 200) {
			WAR_Debug::log('QRCode: Código de resposta inválido', ['code' => $code]);
			wp_send_json_error([
				'message' => 'Erro ao gerar QR Code.',
				'debug' => $is_debug ? ['step' => 'remote_status', 'qr_url' => $qr_url, 'status_code' => $code] : null,
			], 502);
		}

		$image_data = wp_remote_retrieve_body($response);
		$image_size = strlen($image_data);
		WAR_Debug::log('QRCode: Imagem recebida', ['size_bytes' => $image_size]);

		if (empty($image_data)) {
			WAR_Debug::log('QRCode: Imagem vazia');
			wp_send_json_error([
				'message' => 'Erro ao gerar QR Code.',
				'debug' => $is_debug ? ['step' => 'empty_body', 'qr_url' => $qr_url, 'status_code' => $code] : null,
			], 502);
		}

		$base64 = base64_encode($image_data);
		$slug = get_post_field('post_name', $link_id);
		$filename = 'qrcode-' . $slug . '.png';

		WAR_Debug::log('QRCode: Sucesso', [
			'link_id' => $link_id,
			'slug' => $slug,
			'filename' => $filename,
			'base64_length' => strlen($base64),
		]);

		wp_send_json_success([
			'image' => 'data:image/png;base64,' . $base64,
			'filename' => $filename,
			'debug' => $is_debug ? [
				'step' => 'success',
				'link_id' => $link_id,
				'permalink' => $permalink,
				'qr_url' => $qr_url,
				'remote_status' => $code,
				'size_bytes' => $image_size,
			] : null,
		]);
	}
}
