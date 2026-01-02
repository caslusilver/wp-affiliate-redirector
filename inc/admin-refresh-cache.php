<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Git Updater: link "Atualizar Cache" na lista de ações do plugin.
 * Mantém o padrão do framework, mas com guard para evitar fatal se Git Updater não existir.
 */
function war_is_git_updater_available() {
	return class_exists('Fragen\\Singleton') && class_exists('Fragen\\Git_Updater\\Settings');
}

add_filter('plugin_action_links_' . plugin_basename(WAR_PLUGIN_FILE), function ($links) {
	if (!war_is_git_updater_available()) {
		return $links;
	}

	$nonce = wp_create_nonce('gu-refresh-cache');
	$links[] = '<a href="#" class="gu-refresh-cache-btn" data-nonce="' . esc_attr($nonce) . '">Atualizar Cache</a>';
	return $links;
});

add_action('admin_enqueue_scripts', function ($hook) {
	if (!war_is_git_updater_available()) {
		return;
	}

	if ($hook !== 'plugins.php') {
		return;
	}

	wp_enqueue_script(
		'gu-refresh-cache-js',
		WAR_PLUGIN_URL . 'assets/js/admin-refresh-cache.js',
		['jquery'],
		WP_AFFILIATE_REDIRECTOR_get_version(),
		true
	);

	wp_localize_script('gu-refresh-cache-js', 'GURefreshCache', [
		'ajax_url' => admin_url('admin-ajax.php'),
	]);
});

add_action('wp_ajax_gu_refresh_cache', function () {
	if (!war_is_git_updater_available()) {
		wp_send_json_error('Git Updater não está disponível.');
	}

	if (!current_user_can('manage_options')) {
		wp_send_json_error('Sem permissão para executar esta ação.');
	}

	check_ajax_referer('gu-refresh-cache');

	try {
		$settings = Fragen\Singleton::get_instance(
			'Fragen\Git_Updater\Settings',
			new stdClass()
		);

		$settings->delete_all_cached_data();
		wp_cron();

		wp_send_json_success('Cache atualizado com sucesso!');
	} catch (Exception $e) {
		wp_send_json_error($e->getMessage());
	}
});


