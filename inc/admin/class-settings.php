<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Settings do plugin (wp-admin).
 *
 * Tela: Affiliate Links -> Styles
 * Objetivo: controlar cores do painel do shortcode via WP Color Picker.
 */
class WAR_Admin_Settings {
	const OPTION_KEY = 'war_ui_settings';
	const MENU_SLUG = 'war-affiliate-links';
	const STYLES_SLUG = 'war-affiliate-links-styles';
	const INTEGRATIONS_SLUG = 'war-affiliate-links-integrations';

	public static function init() {
		add_action('admin_menu', [__CLASS__, 'register_menu']);
		add_action('admin_init', [__CLASS__, 'register_settings']);
		add_action('admin_init', [__CLASS__, 'register_integrations_settings']);
		add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
	}

	public static function defaults() {
		return [
			'text_color' => '#1d2327',
			'muted_color' => '#50575e',
			'button_text_color' => '#1d2327',
			'link_color' => '#2271b1',
		];
	}

	public static function get_settings() {
		$raw = get_option(self::OPTION_KEY, []);
		$raw = is_array($raw) ? $raw : [];
		$defaults = self::defaults();
		$merged = array_merge($defaults, $raw);

		return [
			'text_color' => self::sanitize_hex_color($merged['text_color']),
			'muted_color' => self::sanitize_hex_color($merged['muted_color']),
			'button_text_color' => self::sanitize_hex_color($merged['button_text_color']),
			'link_color' => self::sanitize_hex_color($merged['link_color']),
		];
	}

	private static function sanitize_hex_color($value) {
		$value = is_string($value) ? trim($value) : '';
		$san = sanitize_hex_color($value);
		return $san ? $san : '#000000';
	}

	public static function register_menu() {
		// Menu topo do plugin.
		add_menu_page(
			__('Affiliate Links', WAR_TEXT_DOMAIN),
			__('Affiliate Links', WAR_TEXT_DOMAIN),
			'manage_options',
			self::MENU_SLUG,
			[__CLASS__, 'redirect_to_links'],
			'dashicons-admin-links',
			58
		);

		// Submenu: Links (tela nativa do CPT).
		add_submenu_page(
			self::MENU_SLUG,
			__('Links', WAR_TEXT_DOMAIN),
			__('Links', WAR_TEXT_DOMAIN),
			'manage_options',
			'edit.php?post_type=war_link'
		);

		// Submenu: Styles (esta página).
		add_submenu_page(
			self::MENU_SLUG,
			__('Styles', WAR_TEXT_DOMAIN),
			__('Styles', WAR_TEXT_DOMAIN),
			'manage_options',
			self::STYLES_SLUG,
			[__CLASS__, 'render_page']
		);

		// Submenu: Integrações (Chat IG).
		add_submenu_page(
			self::MENU_SLUG,
			__('Integrações', WAR_TEXT_DOMAIN),
			__('Integrações', WAR_TEXT_DOMAIN),
			'manage_options',
			self::INTEGRATIONS_SLUG,
			function () {
				if (class_exists('WAR_Admin_Integrations')) {
					WAR_Admin_Integrations::render_page();
				}
			}
		);
	}

	public static function redirect_to_links() {
		if (!current_user_can('manage_options')) {
			return;
		}
		wp_safe_redirect(admin_url('edit.php?post_type=war_link'));
		exit;
	}

	public static function register_settings() {
		register_setting(
			'war_affiliate_links',
			self::OPTION_KEY,
			[
				'type' => 'array',
				'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
				'default' => self::defaults(),
			]
		);

		add_settings_section(
			'war_ui_section',
			__('Aparência do painel', WAR_TEXT_DOMAIN),
			function () {
				echo '<p>' . esc_html__('Defina as cores usadas no painel do shortcode [war_link_manager].', WAR_TEXT_DOMAIN) . '</p>';
			},
			'war_affiliate_links'
		);

		self::add_color_field('text_color', __('Cor do texto', WAR_TEXT_DOMAIN));
		self::add_color_field('muted_color', __('Cor do texto secundário', WAR_TEXT_DOMAIN));
		self::add_color_field('button_text_color', __('Cor do texto dos botões', WAR_TEXT_DOMAIN));
		self::add_color_field('link_color', __('Cor dos links/ações', WAR_TEXT_DOMAIN));
	}

	public static function register_integrations_settings() {
		if (class_exists('WAR_Admin_Integrations')) {
			WAR_Admin_Integrations::register_settings();
		}
	}

	private static function add_color_field($key, $label) {
		add_settings_field(
			'war_' . $key,
			$label,
			function () use ($key) {
				$settings = self::get_settings();
				$name = self::OPTION_KEY . '[' . $key . ']';
				printf(
					'<input type="text" class="war-color-field" name="%s" value="%s" data-default-color="%s" />',
					esc_attr($name),
					esc_attr($settings[$key]),
					esc_attr(self::defaults()[$key])
				);
			},
			'war_affiliate_links',
			'war_ui_section'
		);
	}

	public static function sanitize_settings($value) {
		$value = is_array($value) ? $value : [];
		$defaults = self::defaults();

		$out = [];
		foreach (array_keys($defaults) as $key) {
			$out[$key] = self::sanitize_hex_color(isset($value[$key]) ? $value[$key] : $defaults[$key]);
		}

		return $out;
	}

	public static function enqueue_assets($hook) {
		// Hook varia por slug; usamos o parâmetro page para ser resiliente.
		$page = isset($_GET['page']) ? sanitize_text_field((string) wp_unslash($_GET['page'])) : '';
		if ($page !== self::STYLES_SLUG) {
			return;
		}

		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');

		// Init mínimo sem arquivo extra (mantém plugin enxuto).
		wp_add_inline_script('wp-color-picker', 'jQuery(function($){ $(".war-color-field").wpColorPicker(); });');
	}

	public static function render_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('Affiliate Links — Styles', WAR_TEXT_DOMAIN); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('war_affiliate_links');
				do_settings_sections('war_affiliate_links');
				submit_button(__('Salvar alterações', WAR_TEXT_DOMAIN));
				?>
			</form>
		</div>
		<?php
	}
}


