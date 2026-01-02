<?php
/**
 * Plugin Name: WP Affiliate Redirector
 * Plugin URI: https://github.com/caslusilver/wp-affiliate-redirector
 * Description: Gerenciador de links de afiliado com página intermediária (loader) e redirecionamento configurável.
 * Version: 0.2.3
 * Author: Lucas Andrade / AI
 * Author URI: https://github.com/caslusilver
 * License: GPL2
 * Text Domain: wp-affiliate-redirector
 *
 * GitHub Plugin URI: caslusilver/wp-affiliate-redirector
 * Primary Branch: develop
 */

if (!defined('ABSPATH')) {
	exit;
}

define('WAR_PLUGIN_FILE', __FILE__);
define('WAR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAR_TEXT_DOMAIN', 'wp-affiliate-redirector');

/**
 * Toggle de debug (dev/test: true | prod: false).
 *
 * Dica: para ativar sem editar o plugin, defina em wp-config.php:
 * define('WAR_DEBUG_MODE', true);
 */
if (!defined('WAR_DEBUG_MODE')) {
	define('WAR_DEBUG_MODE', true);
}

// Exibe erros PHP no output quando o debug do plugin estiver ativo.
// Atenção: em produção, mantenha WAR_DEBUG_MODE=false (ou use filtro `war_debug`).
if (defined('WAR_DEBUG_MODE') && WAR_DEBUG_MODE) {
	error_reporting(E_ALL);
	@ini_set('display_errors', '1');
	@ini_set('display_startup_errors', '1');
}

/**
 * Retorna automaticamente a versão do plugin lendo o cabeçalho.
 * Mantém compatibilidade com Git Updater, auto-tag e auto-release.
 */
function WP_AFFILIATE_REDIRECTOR_get_version() {
	if (!function_exists('get_file_data')) {
		require_once ABSPATH . 'wp-includes/functions.php';
	}

	$plugin_data = get_file_data(__FILE__, [
		'Version' => 'Version',
	]);

	return isset($plugin_data['Version']) ? $plugin_data['Version'] : '0.0.0';
}

/**
 * Carregamento de CSS e JS utilizando versão dinâmica (padrão do framework).
 * Observação: a página intermediária é renderizada com HTML mínimo próprio,
 * mas manter este enqueue facilita evolução futura sem mudar a arquitetura.
 */
add_action('wp_enqueue_scripts', function () {
	if (!is_singular('war_link')) {
		return;
	}

	$version = WP_AFFILIATE_REDIRECTOR_get_version();

	wp_enqueue_style(
		'wp-affiliate-redirector-style',
		WAR_PLUGIN_URL . 'assets/css/style.css',
		[],
		$version
	);

	wp_enqueue_script(
		'wp-affiliate-redirector-script',
		WAR_PLUGIN_URL . 'assets/js/script.js',
		[],
		$version,
		true
	);
});

/**
 * Ativação: garante rewrite rules para /go/{slug}.
 */
register_activation_hook(__FILE__, function () {
	require_once WAR_PLUGIN_DIR . 'inc/cpt-affiliate-link.php';
	war_register_affiliate_link_cpt();
	flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
	flush_rewrite_rules();
});

/**
 * Carrega arquivos adicionais do plugin
 */
require_once WAR_PLUGIN_DIR . 'inc/core/class-config.php';
require_once WAR_PLUGIN_DIR . 'inc/core/class-debug.php';
require_once WAR_PLUGIN_DIR . 'inc/frontend/class-link-manager.php';
require_once WAR_PLUGIN_DIR . 'inc/frontend/class-ajax-links.php';
require_once WAR_PLUGIN_DIR . 'inc/shortcode.php';
require_once WAR_PLUGIN_DIR . 'inc/admin/class-settings.php';
require_once WAR_PLUGIN_DIR . 'inc/admin-refresh-cache.php';
require_once WAR_PLUGIN_DIR . 'inc/cpt-affiliate-link.php';
require_once WAR_PLUGIN_DIR . 'inc/meta-redirect-url.php';
require_once WAR_PLUGIN_DIR . 'inc/public-intermediate-page.php';

// Inicializa módulos do front (painel via shortcode + AJAX).
if (class_exists('WAR_Front_Link_Manager')) {
	WAR_Front_Link_Manager::init();
}
if (class_exists('WAR_Ajax_Links')) {
	WAR_Ajax_Links::init();
}

if (is_admin() && class_exists('WAR_Admin_Settings')) {
	WAR_Admin_Settings::init();
}


