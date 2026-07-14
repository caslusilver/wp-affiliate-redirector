<?php
if (!defined('ABSPATH')) {
	exit;
}

const WAR_META_REDIRECT_URL = 'war_redirect_url';
const WAR_META_IMAGE_URL = 'war_image_url';
const WAR_META_DESCRIPTION = 'war_description';
const WAR_META_BUTTONS = 'war_buttons';

function war_add_redirect_url_metabox() {
	add_meta_box(
		'war_redirect_url',
		'URL de redirecionamento',
		'war_render_redirect_url_metabox',
		'war_link',
		'normal',
		'high'
	);

	add_meta_box(
		'war_image_url',
		'Imagem do Produto',
		'war_render_image_url_metabox',
		'war_link',
		'side',
		'default'
	);
}
add_action('add_meta_boxes', 'war_add_redirect_url_metabox');

/**
 * Enfileira wp.media para o metabox de imagem funcionar.
 */
function war_enqueue_media_uploader() {
	$screen = get_current_screen();
	if ($screen && $screen->post_type === 'war_link' && in_array($screen->base, ['post', 'post-new'])) {
		wp_enqueue_media();
	}
}
add_action('admin_enqueue_scripts', 'war_enqueue_media_uploader');

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
// METABOX DE IMAGEM DO PRODUTO
// ============================================================================

function war_render_image_url_metabox($post) {
	$image_url = (string) get_post_meta($post->ID, WAR_META_IMAGE_URL, true);
	wp_nonce_field('war_save_image_url', 'war_image_url_nonce');
	?>
	<div class="war-image-upload-wrap">
		<div class="war-image-preview" style="margin-bottom:10px;">
			<?php if ($image_url): ?>
				<img src="<?php echo esc_url($image_url); ?>" alt="Preview" style="max-width:100%;height:auto;border:1px solid #ddd;border-radius:4px;" />
			<?php else: ?>
				<div style="background:#f0f0f1;border:1px dashed #c3c4c7;border-radius:4px;padding:40px 20px;text-align:center;color:#50575e;">
					<span class="dashicons dashicons-format-image" style="font-size:48px;width:48px;height:48px;"></span>
					<p style="margin:10px 0 0;">Nenhuma imagem selecionada</p>
				</div>
			<?php endif; ?>
		</div>

		<input
			type="hidden"
			id="war_image_url_field"
			name="war_image_url_field"
			value="<?php echo esc_attr($image_url); ?>"
		/>

		<button type="button" class="button button-secondary" id="war_upload_image_button" style="width:100%;margin-bottom:6px;">
			<?php _e('Selecionar Imagem', WAR_TEXT_DOMAIN); ?>
		</button>

		<button type="button" class="button button-link-delete" id="war_remove_image_button" style="width:100%;color:#b32d2e;" <?php echo $image_url ? '' : 'disabled'; ?>>
			<?php _e('Remover Imagem', WAR_TEXT_DOMAIN); ?>
		</button>

		<p class="description" style="margin-top:10px;">
			<?php _e('Imagem do produto que aparecerá nas listas e cards.', WAR_TEXT_DOMAIN); ?>
		</p>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var mediaUploader;

		$('#war_upload_image_button').on('click', function(e) {
			e.preventDefault();

			if (mediaUploader) {
				mediaUploader.open();
				return;
			}

			mediaUploader = wp.media({
				title: '<?php _e('Selecionar Imagem do Produto', WAR_TEXT_DOMAIN); ?>',
				button: {
					text: '<?php _e('Usar esta imagem', WAR_TEXT_DOMAIN); ?>'
				},
				multiple: false
			});

			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				$('#war_image_url_field').val(attachment.url);
				$('.war-image-preview').html('<img src="' + attachment.url + '" alt="Preview" style="max-width:100%;height:auto;border:1px solid #ddd;border-radius:4px;" />');
				$('#war_remove_image_button').prop('disabled', false);
			});

			mediaUploader.open();
		});

		$('#war_remove_image_button').on('click', function(e) {
			e.preventDefault();
			$('#war_image_url_field').val('');
			$('.war-image-preview').html('<div style="background:#f0f0f1;border:1px dashed #c3c4c7;border-radius:4px;padding:40px 20px;text-align:center;color:#50575e;"><span class="dashicons dashicons-format-image" style="font-size:48px;width:48px;height:48px;"></span><p style="margin:10px 0 0;">Nenhuma imagem selecionada</p></div>');
			$(this).prop('disabled', true);
		});
	});
	</script>
	<?php
}

function war_save_image_url_meta($post_id) {
	if (get_post_type($post_id) !== 'war_link') {
		return;
	}

	if (!isset($_POST['war_image_url_nonce']) || !wp_verify_nonce($_POST['war_image_url_nonce'], 'war_save_image_url')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw = isset($_POST['war_image_url_field']) ? (string) wp_unslash($_POST['war_image_url_field']) : '';
	$raw = trim($raw);

	if ($raw === '') {
		delete_post_meta($post_id, WAR_META_IMAGE_URL);
		return;
	}

	$url = esc_url_raw($raw, ['http', 'https']);

	if ($url === '' || !preg_match('#^https?://#i', $url)) {
		return;
	}

	update_post_meta($post_id, WAR_META_IMAGE_URL, $url);
}
add_action('save_post', 'war_save_image_url_meta');

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

	add_meta_box(
		'war_description',
		__('Descrição do Produto', WAR_TEXT_DOMAIN),
		'war_render_description_metabox',
		'war_link',
		'normal',
		'default'
	);

	add_meta_box(
		'war_buttons',
		__('Botões de Destino', WAR_TEXT_DOMAIN),
		'war_render_buttons_metabox',
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
// METABOX DE DESCRIÇÃO DO PRODUTO
// ============================================================================

function war_render_description_metabox($post) {
	$description = (string) get_post_meta($post->ID, WAR_META_DESCRIPTION, true);
	wp_nonce_field('war_description_save', 'war_description_nonce');
	?>
	<p>
		<label for="war_description_field" style="display:block;margin-bottom:6px;">
			<?php _e('Descrição curta do produto:', WAR_TEXT_DOMAIN); ?>
		</label>
	</p>
	<textarea
		id="war_description_field"
		name="war_description_field"
		rows="4"
		class="large-text"
		placeholder="Digite uma breve descrição do produto..."
		style="width:100%;max-width:720px;"
	><?php echo esc_textarea($description); ?></textarea>
	<p class="description">
		<?php _e('Esta descrição aparecerá nas listas/cards. Se longa, será exibido "Ver mais".', WAR_TEXT_DOMAIN); ?>
	</p>
	<?php
}

function war_save_description_meta($post_id) {
	if (get_post_type($post_id) !== 'war_link') {
		return;
	}

	if (!isset($_POST['war_description_nonce']) || !wp_verify_nonce($_POST['war_description_nonce'], 'war_description_save')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw = isset($_POST['war_description_field']) ? sanitize_textarea_field((string) wp_unslash($_POST['war_description_field'])) : '';
	$raw = trim($raw);

	if ($raw === '') {
		delete_post_meta($post_id, WAR_META_DESCRIPTION);
		return;
	}

	update_post_meta($post_id, WAR_META_DESCRIPTION, $raw);
}
add_action('save_post', 'war_save_description_meta');

// ============================================================================
// METABOX DE BOTÕES DE DESTINO
// ============================================================================

function war_render_buttons_metabox($post) {
	$buttons = get_post_meta($post->ID, WAR_META_BUTTONS, true);
	if (!is_array($buttons)) {
		$buttons = [];
	}

	wp_nonce_field('war_buttons_save', 'war_buttons_nonce');
	?>
	<div class="war-buttons-editor">
		<p class="description" style="margin-bottom:12px;">
			<?php _e('Configure múltiplos botões de destino para este produto. Se nenhum botão for configurado, será usado o campo "URL de redirecionamento" principal.', WAR_TEXT_DOMAIN); ?>
		</p>

		<div id="war-buttons-list" style="margin-bottom:12px;">
			<?php if (empty($buttons)): ?>
				<p class="war-no-buttons" style="color:#50575e;font-style:italic;padding:12px;background:#f6f7f7;border-radius:4px;">
					<?php _e('Nenhum botão configurado. Clique em "Adicionar Botão" para criar o primeiro.', WAR_TEXT_DOMAIN); ?>
				</p>
			<?php else: ?>
				<?php foreach ($buttons as $index => $button): ?>
					<div class="war-button-item" style="background:#f6f7f7;padding:12px;margin-bottom:8px;border-radius:4px;border-left:3px solid #2271b1;">
						<div style="margin-bottom:8px;">
							<label style="display:block;font-weight:600;margin-bottom:4px;">
								<?php _e('Label do Botão:', WAR_TEXT_DOMAIN); ?>
							</label>
							<input
								type="text"
								name="war_buttons[<?php echo $index; ?>][label]"
								value="<?php echo esc_attr($button['label'] ?? ''); ?>"
								placeholder="Ex: Comprar na Amazon"
								class="regular-text"
								style="width:100%;"
							/>
						</div>
						<div style="margin-bottom:8px;">
							<label style="display:block;font-weight:600;margin-bottom:4px;">
								<?php _e('URL de Destino:', WAR_TEXT_DOMAIN); ?>
							</label>
							<input
								type="url"
								name="war_buttons[<?php echo $index; ?>][url]"
								value="<?php echo esc_attr($button['url'] ?? ''); ?>"
								placeholder="https://exemplo.com/produto"
								class="regular-text"
								style="width:100%;"
							/>
						</div>
						<button type="button" class="button button-link-delete war-remove-button" style="color:#b32d2e;">
							<?php _e('Remover Botão', WAR_TEXT_DOMAIN); ?>
						</button>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<button type="button" id="war-add-button" class="button button-secondary">
			<?php _e('+ Adicionar Botão', WAR_TEXT_DOMAIN); ?>
		</button>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var buttonIndex = <?php echo count($buttons); ?>;

		$('#war-add-button').on('click', function() {
			$('.war-no-buttons').remove();

			var template = `
				<div class="war-button-item" style="background:#f6f7f7;padding:12px;margin-bottom:8px;border-radius:4px;border-left:3px solid #2271b1;">
					<div style="margin-bottom:8px;">
						<label style="display:block;font-weight:600;margin-bottom:4px;">
							<?php _e('Label do Botão:', WAR_TEXT_DOMAIN); ?>
						</label>
						<input
							type="text"
							name="war_buttons[${buttonIndex}][label]"
							placeholder="Ex: Comprar na Amazon"
							class="regular-text"
							style="width:100%;"
						/>
					</div>
					<div style="margin-bottom:8px;">
						<label style="display:block;font-weight:600;margin-bottom:4px;">
							<?php _e('URL de Destino:', WAR_TEXT_DOMAIN); ?>
						</label>
						<input
							type="url"
							name="war_buttons[${buttonIndex}][url]"
							placeholder="https://exemplo.com/produto"
							class="regular-text"
							style="width:100%;"
						/>
					</div>
					<button type="button" class="button button-link-delete war-remove-button" style="color:#b32d2e;">
						<?php _e('Remover Botão', WAR_TEXT_DOMAIN); ?>
					</button>
				</div>
			`;

			$('#war-buttons-list').append(template);
			buttonIndex++;
		});

		$(document).on('click', '.war-remove-button', function() {
			$(this).closest('.war-button-item').remove();

			if ($('.war-button-item').length === 0) {
				$('#war-buttons-list').html('<p class="war-no-buttons" style="color:#50575e;font-style:italic;padding:12px;background:#f6f7f7;border-radius:4px;"><?php _e('Nenhum botão configurado. Clique em "Adicionar Botão" para criar o primeiro.', WAR_TEXT_DOMAIN); ?></p>');
			}
		});
	});
	</script>
	<?php
}

function war_save_buttons_meta($post_id) {
	if (get_post_type($post_id) !== 'war_link') {
		return;
	}

	if (!isset($_POST['war_buttons_nonce']) || !wp_verify_nonce($_POST['war_buttons_nonce'], 'war_buttons_save')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$buttons_raw = isset($_POST['war_buttons']) ? (array) wp_unslash($_POST['war_buttons']) : [];
	$buttons_clean = [];

	foreach ($buttons_raw as $button) {
		if (!is_array($button)) {
			continue;
		}

		$label = isset($button['label']) ? sanitize_text_field((string) $button['label']) : '';
		$url = isset($button['url']) ? esc_url_raw((string) $button['url'], ['http', 'https']) : '';

		// Valida que ambos existem e URL é válida
		if ($label !== '' && $url !== '' && preg_match('#^https?://#i', $url)) {
			$buttons_clean[] = [
				'label' => $label,
				'url' => $url,
			];
		}
	}

	// Se não há botões válidos, remove o meta
	if (empty($buttons_clean)) {
		delete_post_meta($post_id, WAR_META_BUTTONS);
		return;
	}

	// Atualiza war_buttons
	update_post_meta($post_id, WAR_META_BUTTONS, $buttons_clean);

	// Mantém war_redirect_url sempre como a primeira URL válida
	$first_url = $buttons_clean[0]['url'];
	update_post_meta($post_id, WAR_META_REDIRECT_URL, $first_url);
}
add_action('save_post', 'war_save_buttons_meta');

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


