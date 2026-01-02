<?php
if (!defined('ABSPATH')) {
	exit;
}

const WAR_META_REDIRECT_URL = 'war_redirect_url';

function war_add_redirect_url_metabox() {
	add_meta_box(
		'war_redirect_url',
		'URL de redirecionamento',
		'war_render_redirect_url_metabox',
		'war_link',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'war_add_redirect_url_metabox');

function war_render_redirect_url_metabox($post) {
	$value = (string) get_post_meta($post->ID, WAR_META_REDIRECT_URL, true);

	wp_nonce_field('war_save_redirect_url', 'war_redirect_url_nonce');
	?>
	<p>
		<label for="war_redirect_url_field" style="display:block;margin-bottom:6px;">
			Informe a URL final (http/https) para onde este slug irá redirecionar após ~3s.
		</label>
		<input
			type="url"
			id="war_redirect_url_field"
			name="war_redirect_url_field"
			value="<?php echo esc_attr($value); ?>"
			placeholder="https://exemplo.com/minha-oferta"
			style="width:100%;max-width:720px;"
			required
		/>
	</p>
	<?php
}

function war_save_redirect_url_meta($post_id) {
	if (get_post_type($post_id) !== 'war_link') {
		return;
	}

	if (!isset($_POST['war_redirect_url_nonce']) || !wp_verify_nonce($_POST['war_redirect_url_nonce'], 'war_save_redirect_url')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw = isset($_POST['war_redirect_url_field']) ? (string) wp_unslash($_POST['war_redirect_url_field']) : '';
	$raw = trim($raw);

	if ($raw === '') {
		delete_post_meta($post_id, WAR_META_REDIRECT_URL);
		return;
	}

	$url = esc_url_raw($raw, ['http', 'https']);

	// esc_url_raw pode retornar string vazia se inválido.
	if ($url === '' || !preg_match('#^https?://#i', $url)) {
		return;
	}

	update_post_meta($post_id, WAR_META_REDIRECT_URL, $url);
}
add_action('save_post', 'war_save_redirect_url_meta');


