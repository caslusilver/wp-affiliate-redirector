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

// ============================================================================
// METABOX DE KEYWORDS (Chat IG)
// ============================================================================

const WAR_META_KEYWORDS = 'war_keywords';

function war_add_keywords_metabox() {
	add_meta_box(
		'war_keywords',
		__('Keywords (Chat IG)', WAR_TEXT_DOMAIN),
		'war_render_keywords_metabox',
		'war_link',
		'normal',
		'default'
	);
}
add_action('add_meta_boxes', 'war_add_keywords_metabox');

function war_render_keywords_metabox($post) {
	$keywords = (string) get_post_meta($post->ID, WAR_META_KEYWORDS, true);
	wp_nonce_field('war_keywords_save', 'war_keywords_nonce');
	?>
	<p>
		<label for="war_keywords_field" style="display:block;margin-bottom:6px;">
			<?php _e('Palavras-chave que acionam este link no Chat IG:', WAR_TEXT_DOMAIN); ?>
		</label>
	</p>
	<textarea
		id="war_keywords_field"
		name="war_keywords_field"
		rows="3"
		class="large-text"
		placeholder="pdf, ebook, baixar, download"
		style="width:100%;max-width:720px;"
	><?php echo esc_textarea($keywords); ?></textarea>
	<p class="description">
		<?php _e('Separe as palavras por vírgula. Não diferencia maiúsculas/minúsculas.', WAR_TEXT_DOMAIN); ?>
	</p>
	<?php
}

function war_save_keywords_meta($post_id) {
	if (get_post_type($post_id) !== 'war_link') {
		return;
	}

	if (!isset($_POST['war_keywords_nonce']) || !wp_verify_nonce($_POST['war_keywords_nonce'], 'war_keywords_save')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw = isset($_POST['war_keywords_field']) ? sanitize_textarea_field((string) wp_unslash($_POST['war_keywords_field'])) : '';
	$raw = trim($raw);

	WAR_Debug::log('Keywords: Salvando', [
		'post_id' => $post_id,
		'raw' => $raw,
		'length' => strlen($raw),
	]);

	if ($raw === '') {
		delete_post_meta($post_id, WAR_META_KEYWORDS);
		WAR_Debug::log('Keywords: Removido (vazio)', ['post_id' => $post_id]);
		return;
	}

	update_post_meta($post_id, WAR_META_KEYWORDS, $raw);
	WAR_Debug::log('Keywords: Salvo', ['post_id' => $post_id, 'keywords' => $raw]);
}
add_action('save_post', 'war_save_keywords_meta');

// ============================================================================
// FUNÇÃO DE BUSCA POR KEYWORD
// ============================================================================

/**
 * Busca um link por keyword (case-insensitive, match exato).
 *
 * @param string $keyword Palavra-chave a buscar.
 * @return array|null Array com dados do link ou null se não encontrado.
 */
function war_find_link_by_keyword($keyword) {
	$keyword = strtolower(trim((string) $keyword));
	if ($keyword === '') {
		WAR_Debug::log('Keywords: Busca com keyword vazia');
		return null;
	}

	WAR_Debug::log('Keywords: Iniciando busca', ['keyword' => $keyword]);

	$args = [
		'post_type' => 'war_link',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'meta_query' => [
			[
				'key' => WAR_META_KEYWORDS,
				'value' => $keyword,
				'compare' => 'LIKE',
			],
		],
	];

	$query = new WP_Query($args);
	WAR_Debug::log('Keywords: Query executada', [
		'found_posts' => $query->found_posts,
		'post_count' => $query->post_count,
	]);

	if (!$query->have_posts()) {
		WAR_Debug::log('Keywords: Nenhum link encontrado', ['keyword' => $keyword]);
		return null;
	}

	$post = $query->posts[0];
	$post_id = (int) $post->ID;
	$keywords_meta = (string) get_post_meta($post_id, WAR_META_KEYWORDS, true);
	$keywords_list = array_map('trim', explode(',', strtolower($keywords_meta)));

	// Verifica match exato (não substring)
	$found = false;
	foreach ($keywords_list as $kw) {
		if ($kw === $keyword) {
			$found = true;
			break;
		}
	}

	if (!$found) {
		WAR_Debug::log('Keywords: Match exato não encontrado', [
			'keyword' => $keyword,
			'keywords_list' => $keywords_list,
		]);
		return null;
	}

	$redirect_url = (string) get_post_meta($post_id, WAR_META_REDIRECT_URL, true);
	$permalink = get_permalink($post_id);

	WAR_Debug::log('Keywords: Link encontrado', [
		'post_id' => $post_id,
		'title' => get_the_title($post_id),
		'permalink' => $permalink,
		'redirect_url' => $redirect_url,
	]);

	// Retorna no formato esperado pelo Chat IG
	return [
		'headline' => '✅ Link liberado',
		'subtitle' => get_the_title($post_id),
		'button_text' => 'Toque aqui p/ acessar',
		'button_url' => $permalink,
		'status_badge' => '',
		'type' => 'card',
	];
}


