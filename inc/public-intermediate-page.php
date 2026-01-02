<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Intercepta a URL pública do CPT e renderiza uma página intermediária mínima
 * (somente loader) com noindex/nofollow e redirecionamento após ~3s.
 */
function war_render_intermediate_page_if_needed() {
	if (!is_singular('war_link')) {
		return;
	}

	$post_id = get_queried_object_id();
	if (!$post_id) {
		return;
	}

	$dest = (string) get_post_meta($post_id, WAR_META_REDIRECT_URL, true);
	$dest = trim($dest);

	$dest = esc_url_raw($dest, ['http', 'https']);
	if ($dest === '' || !preg_match('#^https?://#i', $dest)) {
		status_header(404);
		nocache_headers();
		exit;
	}

	$version = function_exists('WP_AFFILIATE_REDIRECTOR_get_version') ? WP_AFFILIATE_REDIRECTOR_get_version() : '0.0.0';
	$css_url = WAR_PLUGIN_URL . 'assets/css/style.css?ver=' . rawurlencode($version);
	$js_url = WAR_PLUGIN_URL . 'assets/js/script.js?ver=' . rawurlencode($version);

	// SEO: garantir noindex/nofollow também via header.
	nocache_headers();
	header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);

	$delay_ms = 3000;

	// Página intermediária: somente loader (sem texto/CTA).
	?>
	<!doctype html>
	<html <?php echo get_language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
		<title></title>
		<link rel="stylesheet" href="<?php echo esc_url($css_url); ?>">
	</head>
	<body>
		<div class="war-loader" aria-hidden="true"></div>
		<script>
			window.WARRedirect = <?php echo wp_json_encode([
				'destination' => $dest,
				'delayMs' => $delay_ms,
			]); ?>;
		</script>
		<script src="<?php echo esc_url($js_url); ?>" defer></script>
		<noscript>
			<meta http-equiv="refresh" content="0;url=<?php echo esc_url($dest); ?>">
		</noscript>
	</body>
	</html>
	<?php
	exit;
}
add_action('template_redirect', 'war_render_intermediate_page_if_needed', 0);


