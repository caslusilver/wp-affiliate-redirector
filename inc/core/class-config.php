<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Config central do plugin.
 *
 * Objetivo: um único lugar para ler flags de runtime (dev/test/prod)
 * sem espalhar constantes/ifs pelo código.
 */
class WAR_Config {
	/**
	 * Retorna se o modo debug está ativo.
	 *
	 * Prioriza a constante global do arquivo principal `WAR_DEBUG_MODE`.
	 * Se não definida, verifica a opção de integrações 'debug_enabled'.
	 * Permite override via filtro `war_debug`.
	 */
	public static function is_debug() {
		// Constante tem prioridade
		if (defined('WAR_DEBUG_MODE') && WAR_DEBUG_MODE) {
			return (bool) apply_filters('war_debug', true);
		}

		// Verificar opção de integrações
		$integrations = get_option('war_integrations_settings', []);
		$debug_enabled = !empty($integrations['debug_enabled']);

		return (bool) apply_filters('war_debug', $debug_enabled);
	}
}


