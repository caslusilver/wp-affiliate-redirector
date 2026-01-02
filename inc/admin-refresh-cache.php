<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Git Updater: link "Atualizar Cache" abaixo da descrição do plugin (plugin_row_meta),
 * no mesmo padrão do packing-panel-woo-dev (dashicons + spinner + JS dedicado).
 *
 * Observação:
 * - Mantém guards para evitar fatal se Git Updater não existir.
 */
function war_is_git_updater_available() {
	return class_exists('Fragen\\Singleton') && class_exists('Fragen\\Git_Updater\\Settings');
}

add_filter('plugin_row_meta', function ($plugin_meta, $plugin_file) {
	if (!war_is_git_updater_available()) {
		return $plugin_meta;
	}

	$expected_basename = plugin_basename(WAR_PLUGIN_FILE);
	if ($plugin_file !== $expected_basename) {
		return $plugin_meta;
	}

	$nonce = wp_create_nonce('gu-refresh-cache');
	$plugin_meta[] = sprintf(
		'<a href="#" class="gu-refresh-cache-btn" data-nonce="%s">
			<span class="dashicons dashicons-update" style="font-size: 16px; vertical-align: middle; margin-right: 3px;"></span>
			<span class="gu-refresh-text">Atualizar Cache</span>
			<span class="spinner" style="float: none; margin: 0 0 0 5px; visibility: hidden;"></span>
		</a>',
		esc_attr($nonce)
	);

	return $plugin_meta;
}, 10, 2);

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

	check_ajax_referer('gu-refresh-cache', '_ajax_nonce');

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


