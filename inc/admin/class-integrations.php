<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Integrações do plugin (wp-admin).
 *
 * Tela: Affiliate Links -> Integrações
 * Objetivo: configurar webhook e identidade do chat IG.
 */
class WAR_Admin_Integrations {
	const OPTION_KEY = 'war_integrations_settings';
	const PAGE_SLUG = 'war-affiliate-links-integrations';

	public static function defaults() {
		return [
			'webhook_url' => '',
			'header_name' => 'Lucas Andrade',
			'header_status' => 'Online agora',
			'header_avatar_url' => '',
			'send_icon_url' => '',
			'initial_message' => "Digite uma palavra-chave para liberar o link.\nEx: PDF",
			'error_message' => 'Tenta de novo.',
			'secret' => '',
			'rate_limit_ms' => 2500,
		];
	}

	public static function get_settings() {
		$raw = get_option(self::OPTION_KEY, []);
		$raw = is_array($raw) ? $raw : [];
		$defaults = self::defaults();
		$merged = array_merge($defaults, $raw);

		return [
			'webhook_url' => esc_url_raw((string) $merged['webhook_url']),
			'header_name' => sanitize_text_field((string) $merged['header_name']),
			'header_status' => sanitize_text_field((string) $merged['header_status']),
			'header_avatar_url' => esc_url_raw((string) $merged['header_avatar_url']),
			'send_icon_url' => esc_url_raw((string) $merged['send_icon_url']),
			'initial_message' => sanitize_textarea_field((string) $merged['initial_message']),
			'error_message' => sanitize_text_field((string) $merged['error_message']),
			'secret' => sanitize_text_field((string) $merged['secret']),
			'rate_limit_ms' => max(0, (int) $merged['rate_limit_ms']),
		];
	}

	public static function register_settings() {
		register_setting(
			'war_affiliate_links_integrations',
			self::OPTION_KEY,
			[
				'type' => 'array',
				'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
				'default' => self::defaults(),
			]
		);

		add_settings_section(
			'war_integrations_section',
			__('Integrações (Chat IG)', WAR_TEXT_DOMAIN),
			function () {
				echo '<p>' . esc_html__('Configure o webhook e a identidade visual do simulador de Direct.', WAR_TEXT_DOMAIN) . '</p>';
			},
			'war_affiliate_links_integrations'
		);

		self::add_text_field('webhook_url', __('Webhook URL', WAR_TEXT_DOMAIN), 'url', 'https://...');
		self::add_text_field('header_name', __('Nome exibido', WAR_TEXT_DOMAIN), 'text', '');
		self::add_text_field('header_status', __('Status', WAR_TEXT_DOMAIN), 'text', __('Online agora', WAR_TEXT_DOMAIN));
		self::add_text_field('header_avatar_url', __('Foto do perfil (URL)', WAR_TEXT_DOMAIN), 'url', 'https://...');
		self::add_text_field('send_icon_url', __('Ícone do botão enviar (URL)', WAR_TEXT_DOMAIN), 'url', 'https://...');
		self::add_textarea_field('initial_message', __('Mensagem inicial', WAR_TEXT_DOMAIN));
		self::add_text_field('error_message', __('Mensagem de erro', WAR_TEXT_DOMAIN), 'text', __('Tenta de novo.', WAR_TEXT_DOMAIN));
		self::add_text_field('secret', __('Secret (HMAC)', WAR_TEXT_DOMAIN), 'text', '');
		self::add_number_field('rate_limit_ms', __('Rate limit (ms)', WAR_TEXT_DOMAIN), 0, 60000, 100);
	}

	private static function add_text_field($key, $label, $type = 'text', $placeholder = '') {
		add_settings_field(
			'war_int_' . $key,
			$label,
			function () use ($key, $type, $placeholder) {
				$settings = self::get_settings();
				$name = self::OPTION_KEY . '[' . $key . ']';
				printf(
					'<input type="%s" class="regular-text" name="%s" value="%s" placeholder="%s" />',
					esc_attr($type),
					esc_attr($name),
					esc_attr((string) ($settings[$key] ?? '')),
					esc_attr($placeholder)
				);
			},
			'war_affiliate_links_integrations',
			'war_integrations_section'
		);
	}

	private static function add_textarea_field($key, $label) {
		add_settings_field(
			'war_int_' . $key,
			$label,
			function () use ($key) {
				$settings = self::get_settings();
				$name = self::OPTION_KEY . '[' . $key . ']';
				printf(
					'<textarea class="large-text" rows="4" name="%s">%s</textarea>',
					esc_attr($name),
					esc_textarea((string) ($settings[$key] ?? ''))
				);
			},
			'war_affiliate_links_integrations',
			'war_integrations_section'
		);
	}

	private static function add_number_field($key, $label, $min, $max, $step) {
		add_settings_field(
			'war_int_' . $key,
			$label,
			function () use ($key, $min, $max, $step) {
				$settings = self::get_settings();
				$name = self::OPTION_KEY . '[' . $key . ']';
				printf(
					'<input type="number" name="%s" value="%s" min="%d" max="%d" step="%d" />',
					esc_attr($name),
					esc_attr((string) ($settings[$key] ?? 0)),
					(int) $min,
					(int) $max,
					(int) $step
				);
			},
			'war_affiliate_links_integrations',
			'war_integrations_section'
		);
	}

	public static function sanitize_settings($value) {
		$value = is_array($value) ? $value : [];
		$defaults = self::defaults();
		$merged = [];
		foreach (array_keys($defaults) as $key) {
			$merged[$key] = $value[$key] ?? $defaults[$key];
		}

		// Sanitiza e retorna o array final (o WP persiste automaticamente).
		return [
			'webhook_url' => esc_url_raw((string) $merged['webhook_url']),
			'header_name' => sanitize_text_field((string) $merged['header_name']),
			'header_status' => sanitize_text_field((string) $merged['header_status']),
			'header_avatar_url' => esc_url_raw((string) $merged['header_avatar_url']),
			'send_icon_url' => esc_url_raw((string) $merged['send_icon_url']),
			'initial_message' => sanitize_textarea_field((string) $merged['initial_message']),
			'error_message' => sanitize_text_field((string) $merged['error_message']),
			'secret' => sanitize_text_field((string) $merged['secret']),
			'rate_limit_ms' => max(0, (int) $merged['rate_limit_ms']),
		];
	}

	public static function render_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('Affiliate Links — Integrações', WAR_TEXT_DOMAIN); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('war_affiliate_links_integrations');
				do_settings_sections('war_affiliate_links_integrations');
				submit_button(__('Salvar alterações', WAR_TEXT_DOMAIN));
				?>
			</form>
		</div>
		<?php
	}
}


