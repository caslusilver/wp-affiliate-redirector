<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shortcode simples para obter a URL pública do link do CPT.
 *
 * Uso: [war_link id="123"]
 */
function war_shortcode_link($atts) {
	$atts = shortcode_atts([
		'id' => 0,
	], $atts, 'war_link');

	$post_id = absint($atts['id']);
	if (!$post_id) {
		return '';
	}

	if (get_post_type($post_id) !== 'war_link') {
		return '';
	}

	$url = get_permalink($post_id);
	if (!$url) {
		return '';
	}

	return esc_url($url);
}
add_shortcode('war_link', 'war_shortcode_link');


