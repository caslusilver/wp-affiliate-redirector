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
	private static $force_enqueue = false;

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

		$has_shortcode = self::$force_enqueue || has_shortcode($post->post_content, self::SHORTCODE_TAG);

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

		// Elementor: o conteúdo pode estar em post_meta (_elementor_data), não em post_content.
		if (!$has_shortcode) {
			$elementor_data = (string) get_post_meta((int) $post->ID, '_elementor_data', true);
			if ($elementor_data !== '' && strpos($elementor_data, '[' . self::SHORTCODE_TAG . ']') !== false) {
				$has_shortcode = true;
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

		$is_debug = class_exists('WAR_Config') ? (bool) WAR_Config::is_debug() : false;

		$icon_copy = 'assets/icons/copy.webp';
		$icon_graph = 'assets/icons/contador_clicks.PNG';
		$icon_edit = 'assets/icons/editing.png';
		$icon_delete = 'assets/icons/delete.png';
		$icon_qrcode = 'assets/icons/qr-code.png';

		wp_localize_script('war-link-manager', 'WARLinkManager', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce(self::NONCE_ACTION),
			'qrcode_nonce' => wp_create_nonce('war_qrcode_nonce'),
			'per_page' => 20,
			'debug' => $is_debug,
			'go_base' => esc_url_raw(home_url('/go/')),
			// Só define URLs locais se os assets existirem (senão, o JS usa fallbacks externos).
			'copy_icon_url' => file_exists(WAR_PLUGIN_DIR . $icon_copy) ? esc_url_raw(WAR_PLUGIN_URL . $icon_copy) : '',
			'graph_icon_url' => file_exists(WAR_PLUGIN_DIR . $icon_graph) ? esc_url_raw(WAR_PLUGIN_URL . $icon_graph) : '',
			'edit_icon_url' => file_exists(WAR_PLUGIN_DIR . $icon_edit) ? esc_url_raw(WAR_PLUGIN_URL . $icon_edit) : '',
			'delete_icon_url' => file_exists(WAR_PLUGIN_DIR . $icon_delete) ? esc_url_raw(WAR_PLUGIN_URL . $icon_delete) : '',
			'qrcode_icon_url' => file_exists(WAR_PLUGIN_DIR . $icon_qrcode) ? esc_url_raw(WAR_PLUGIN_URL . $icon_qrcode) : '',
			'strings' => [
				'no_permission' => 'Você não tem permissão para usar este painel.',
				'confirm_delete' => 'Tem certeza que deseja deletar este link?',
				'copy_ok' => 'URL copiada!',
				'copy_fail' => 'Falha ao copiar.',
				'btn_create' => 'Criar',
				'btn_update' => 'Atualizar',
				'status_fill' => 'Preencha título e destino.',
				'status_loading' => 'Carregando...',
				'status_created' => 'Criando...',
				'status_updated' => 'Atualizando...',
				'status_deleted' => 'Deletando...',
				'status_saved' => 'Salvo.',
				'status_load_err' => 'Erro ao carregar.',
				'status_save_err' => 'Erro ao salvar.',
				'status_delete_err' => 'Erro ao deletar.',
			],
		]);
	}

	public static function render_shortcode() {
		if (!current_user_can('manage_options')) {
			return '<p>' . esc_html__('Você não tem permissão para visualizar este painel.', WAR_TEXT_DOMAIN) . '</p>';
		}

		// Garante enqueue em builders (ex: Elementor), onde a detecção por post_content pode falhar.
		self::$force_enqueue = true;
		self::enqueue_assets();

		$ui_settings = class_exists('WAR_Admin_Settings') ? WAR_Admin_Settings::get_settings() : [
			'text_color' => '#1d2327',
			'muted_color' => '#50575e',
			'button_text_color' => '#1d2327',
			'link_color' => '#2271b1',
		];

		$war_manager_style = sprintf(
			'--war-text:%s;--war-muted:%s;--war-btn-text:%s;--war-link:%s;',
			esc_attr($ui_settings['text_color']),
			esc_attr($ui_settings['muted_color']),
			esc_attr($ui_settings['button_text_color']),
			esc_attr($ui_settings['link_color'])
		);

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


