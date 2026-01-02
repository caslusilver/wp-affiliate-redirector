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
	 * Permite override via filtro `war_debug`.
	 */
	public static function is_debug() {
		if (defined('WAR_DEBUG_MODE')) {
			return (bool) apply_filters('war_debug', WAR_DEBUG_MODE);
		}

		return (bool) apply_filters('war_debug', false);
	}
}


