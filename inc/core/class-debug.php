<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Logger simples do plugin.
 *
 * Evita espalhar `error_log` e facilita desligar em produção.
 */
class WAR_Debug {
	public static function log($message, array $context = []) {
		if (!class_exists('WAR_Config') || !WAR_Config::is_debug()) {
			return;
		}

		$prefix = 'WAR: ';
		$suffix = $context ? ' | ' . wp_json_encode($context) : '';
		error_log($prefix . (string) $message . $suffix);
	}
}


