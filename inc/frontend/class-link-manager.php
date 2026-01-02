<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shortcode de gestão de links no front (admin only).
 *
 * Tag: [war_link_manager]
 */
class WAR_Front_Link_Manager {
	const SHORTCODE_TAG = 'war_link_manager';
	const NONCE_ACTION = 'war_link_manager_nonce';

	public static function init() {
		add_shortcode(self::SHORTCODE_TAG, [__CLASS__, 'render_shortcode']);
		add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
	}

	public static function enqueue_assets() {
		if (!is_singular()) {
			return;
		}

		global $post;
		if (!$post || !isset($post->post_content)) {
			return;
		}

		$has_shortcode = has_shortcode($post->post_content, self::SHORTCODE_TAG);

		// Fallback simples para Gutenberg (quando has_shortcode falha em alguns builders/blocos)
		if (!$has_shortcode && function_exists('has_blocks') && has_blocks($post->post_content)) {
			$blocks = parse_blocks($post->post_content);
			foreach ($blocks as $block) {
				if (isset($block['innerHTML']) && strpos($block['innerHTML'], '[' . self::SHORTCODE_TAG . ']') !== false) {
					$has_shortcode = true;
					break;
				}
			}
		}

		if (!$has_shortcode) {
			return;
		}

		$version = function_exists('WP_AFFILIATE_REDIRECTOR_get_version') ? WP_AFFILIATE_REDIRECTOR_get_version() : '0.0.0';

		wp_enqueue_style('dashicons');
		wp_enqueue_style(
			'war-link-manager',
			WAR_PLUGIN_URL . 'assets/css/manager.css',
			['dashicons'],
			$version
		);

		wp_enqueue_script(
			'war-link-manager',
			WAR_PLUGIN_URL . 'assets/js/manager.js',
			['jquery'],
			$version,
			true
		);

		wp_localize_script('war-link-manager', 'WARLinkManager', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce(self::NONCE_ACTION),
			'per_page' => 20,
			'strings' => [
				'no_permission' => 'Você não tem permissão para usar este painel.',
				'confirm_delete' => 'Tem certeza que deseja deletar este link?',
				'copy_ok' => 'URL copiada!',
				'copy_fail' => 'Falha ao copiar.',
			],
		]);
	}

	public static function render_shortcode() {
		if (!current_user_can('manage_options')) {
			return '<p>' . esc_html__('Você não tem permissão para visualizar este painel.', WAR_TEXT_DOMAIN) . '</p>';
		}

		$template = WAR_PLUGIN_DIR . 'templates/link-manager.php';

		ob_start();
		if (file_exists($template)) {
			include $template;
		} else {
			echo '<p style="color:#b32d2e;">' . esc_html__('Erro: template do painel não encontrado.', WAR_TEXT_DOMAIN) . '</p>';
		}
		return (string) ob_get_clean();
	}
}


