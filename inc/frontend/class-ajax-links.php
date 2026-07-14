<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Endpoints AJAX (admin-ajax.php) para CRUD do CPT `war_link`.
 *
 * Segurança:
 * - Apenas usuários logados (wp_ajax_*)
 * - Apenas admins (manage_options)
 * - Nonce obrigatório
 */
class WAR_Ajax_Links {
	const NONCE_ACTION = 'war_link_manager_nonce';

	public static function init() {
		add_action('wp_ajax_war_links_list', [__CLASS__, 'list_links']);
		add_action('wp_ajax_war_links_create', [__CLASS__, 'create_link']);
		add_action('wp_ajax_war_links_update', [__CLASS__, 'update_link']);
		add_action('wp_ajax_war_links_delete', [__CLASS__, 'delete_link']);
		add_action('wp_ajax_war_links_save_order', [__CLASS__, 'save_order']);
		add_action('wp_ajax_war_get_lists', [__CLASS__, 'get_lists']);
	}

	private static function guard() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Sem permissão.'], 403);
		}

		check_ajax_referer(self::NONCE_ACTION, 'nonce');
	}

	private static function meta_key_redirect_url() {
		if (defined('WAR_META_REDIRECT_URL')) {
			return WAR_META_REDIRECT_URL;
		}

		return 'war_redirect_url';
	}

	private static function sanitize_destination($raw) {
		$raw = (string) $raw;
		$raw = trim($raw);
		if ($raw === '') {
			return '';
		}

		$url = esc_url_raw($raw, ['http', 'https']);
		if ($url === '' || !preg_match('#^https?://#i', $url)) {
			return '';
		}

		return $url;
	}

	private static function sanitize_buttons($raw) {
		// Se for JSON string, decodificar
		if (is_string($raw)) {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				$raw = $decoded;
			} else {
				return [];
			}
		}

		if (!is_array($raw)) {
			return [];
		}

		$buttons_clean = [];
		foreach ($raw as $button) {
			if (!is_array($button)) {
				continue;
			}

			$label = isset($button['label']) ? sanitize_text_field($button['label']) : '';
			$url = isset($button['url']) ? esc_url_raw($button['url'], ['http', 'https']) : '';

			if ($label !== '' && $url !== '' && preg_match('#^https?://#i', $url)) {
				$buttons_clean[] = [
					'label' => $label,
					'url' => $url,
				];
			}
		}

		return $buttons_clean;
	}

	public static function list_links() {
		self::guard();

		$page = isset($_POST['page']) ? max(1, absint($_POST['page'])) : 1;
		$per_page = isset($_POST['per_page']) ? max(1, min(100, absint($_POST['per_page']))) : 20;
		$search = isset($_POST['search']) ? sanitize_text_field((string) wp_unslash($_POST['search'])) : '';

		WAR_Debug::log('AJAX Links: Listando', ['page' => $page, 'per_page' => $per_page, 'search' => $search]);

		$args = [
			'post_type' => 'war_link',
			'post_status' => 'publish',
			'posts_per_page' => $per_page,
			'paged' => $page,
			's' => $search,
			'orderby' => 'date',
			'order' => 'DESC',
		];

		$q = new WP_Query($args);

		$items = [];
		$meta_key = self::meta_key_redirect_url();

		foreach ($q->posts as $p) {
			$post_id = (int) $p->ID;
			
			// Obter listas (taxonomia war_list)
			$lists = wp_get_post_terms($post_id, 'war_list', ['fields' => 'all']);
			$list_names = [];
			$list_ids = [];
			if (!is_wp_error($lists) && !empty($lists)) {
				foreach ($lists as $term) {
					$list_names[] = $term->name;
					$list_ids[] = $term->term_id;
				}
			}
			
			// Obter botões
			$buttons = get_post_meta($post_id, 'war_buttons', true);
			if (!is_array($buttons)) {
				$buttons = [];
			}
			
			$items[] = [
				'id' => $post_id,
				'title' => get_the_title($post_id),
				'slug' => (string) $p->post_name,
				'public_url' => get_permalink($post_id),
				'destination' => (string) get_post_meta($post_id, $meta_key, true),
				'image_url' => (string) get_post_meta($post_id, 'war_image_url', true),
				'description' => (string) get_post_meta($post_id, 'war_description', true),
				'buttons' => $buttons,
				'clicks_total' => (int) get_post_meta($post_id, 'war_clicks_total', true),
				'keywords' => (string) get_post_meta($post_id, 'war_keywords', true),
				'lists' => $list_names,
				'list_ids' => $list_ids,
				'menu_order' => (int) $p->menu_order,
			];
		}

		WAR_Debug::log('AJAX Links: Lista gerada', ['count' => count($items), 'total' => $q->found_posts]);

		$total = (int) $q->found_posts;
		$total_pages = (int) $q->max_num_pages;

		wp_send_json_success([
			'items' => $items,
			'page' => $page,
			'per_page' => $per_page,
			'total' => $total,
			'total_pages' => max(1, $total_pages),
		]);
	}

	public static function create_link() {
		self::guard();

		$title = isset($_POST['title']) ? sanitize_text_field((string) wp_unslash($_POST['title'])) : '';
		$slug = isset($_POST['slug']) ? sanitize_title((string) wp_unslash($_POST['slug'])) : '';
		$destination = isset($_POST['destination']) ? self::sanitize_destination(wp_unslash($_POST['destination'])) : '';
		$keywords = isset($_POST['keywords']) ? sanitize_textarea_field((string) wp_unslash($_POST['keywords'])) : '';
		$image_url = isset($_POST['image_url']) ? self::sanitize_destination(wp_unslash($_POST['image_url'])) : '';
		$description = isset($_POST['description']) ? sanitize_textarea_field((string) wp_unslash($_POST['description'])) : '';
		$buttons_raw = isset($_POST['buttons']) ? wp_unslash($_POST['buttons']) : '';
		$list_ids = isset($_POST['list_ids']) ? array_map('absint', (array) $_POST['list_ids']) : [];

		// Sanitizar botões
		$buttons = self::sanitize_buttons($buttons_raw);

		WAR_Debug::log('AJAX Links: Criando', [
			'title' => $title,
			'slug' => $slug,
			'destination' => $destination,
			'keywords' => $keywords,
			'image_url' => $image_url,
			'description' => $description,
			'buttons' => $buttons,
			'list_ids' => $list_ids,
		]);

		if ($title === '') {
			wp_send_json_error(['message' => 'Título é obrigatório.'], 400);
		}

		if ($destination === '') {
			wp_send_json_error(['message' => 'URL de destino inválida. Use http/https.'], 400);
		}

		$postarr = [
			'post_type' => 'war_link',
			'post_status' => 'publish',
			'post_title' => $title,
		];

		// Se o slug vier vazio, o WP gera automaticamente.
		if ($slug !== '') {
			$postarr['post_name'] = $slug;
		}

		$post_id = wp_insert_post($postarr, true);
		if (is_wp_error($post_id)) {
			WAR_Debug::log('AJAX Links: Erro ao criar', ['error' => $post_id->get_error_message()]);
			wp_send_json_error(['message' => $post_id->get_error_message()], 500);
		}

		update_post_meta($post_id, self::meta_key_redirect_url(), $destination);

		if ($image_url !== '') {
			update_post_meta($post_id, 'war_image_url', $image_url);
		}

		if ($keywords !== '') {
			update_post_meta($post_id, 'war_keywords', trim($keywords));
			WAR_Debug::log('AJAX Links: Keywords salvas', ['post_id' => $post_id, 'keywords' => $keywords]);
		}

		if ($description !== '') {
			update_post_meta($post_id, 'war_description', $description);
		}

		if (!empty($buttons)) {
			update_post_meta($post_id, 'war_buttons', $buttons);
			// Sincronizar war_redirect_url com primeiro botão
			update_post_meta($post_id, self::meta_key_redirect_url(), $buttons[0]['url']);
		}

		// Associar listas (taxonomia)
		if (!empty($list_ids)) {
			wp_set_object_terms($post_id, $list_ids, 'war_list');
			WAR_Debug::log('AJAX Links: Listas associadas', ['post_id' => $post_id, 'list_ids' => $list_ids]);
		}

		WAR_Debug::log('AJAX Links: Criado com sucesso', ['post_id' => $post_id]);

		wp_send_json_success([
			'id' => (int) $post_id,
		]);
	}

	public static function update_link() {
		self::guard();

		$post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
		if (!$post_id || get_post_type($post_id) !== 'war_link') {
			wp_send_json_error(['message' => 'Link inválido.'], 400);
		}

		if (!current_user_can('edit_post', $post_id)) {
			wp_send_json_error(['message' => 'Sem permissão para editar este link.'], 403);
		}

		$title = isset($_POST['title']) ? sanitize_text_field((string) wp_unslash($_POST['title'])) : '';
		$destination = isset($_POST['destination']) ? self::sanitize_destination(wp_unslash($_POST['destination'])) : '';
		$keywords = isset($_POST['keywords']) ? sanitize_textarea_field((string) wp_unslash($_POST['keywords'])) : '';
		$image_url = isset($_POST['image_url']) ? self::sanitize_destination(wp_unslash($_POST['image_url'])) : '';
		$description = isset($_POST['description']) ? sanitize_textarea_field((string) wp_unslash($_POST['description'])) : '';
		$buttons_raw = isset($_POST['buttons']) ? wp_unslash($_POST['buttons']) : '';
		$list_ids = isset($_POST['list_ids']) ? array_map('absint', (array) $_POST['list_ids']) : [];

		// Sanitizar botões
		$buttons = self::sanitize_buttons($buttons_raw);

		WAR_Debug::log('AJAX Links: Atualizando', [
			'post_id' => $post_id,
			'title' => $title,
			'destination' => $destination,
			'keywords' => $keywords,
			'image_url' => $image_url,
			'description' => $description,
			'buttons' => $buttons,
			'list_ids' => $list_ids,
		]);

		if ($title === '') {
			wp_send_json_error(['message' => 'Título é obrigatório.'], 400);
		}

		if ($destination === '') {
			wp_send_json_error(['message' => 'URL de destino inválida. Use http/https.'], 400);
		}

		$res = wp_update_post([
			'ID' => $post_id,
			'post_title' => $title,
		], true);

		if (is_wp_error($res)) {
			WAR_Debug::log('AJAX Links: Erro ao atualizar', ['error' => $res->get_error_message()]);
			wp_send_json_error(['message' => $res->get_error_message()], 500);
		}

		update_post_meta($post_id, self::meta_key_redirect_url(), $destination);

		// Atualizar ou remover imagem
		if ($image_url !== '') {
			update_post_meta($post_id, 'war_image_url', $image_url);
		} else {
			delete_post_meta($post_id, 'war_image_url');
		}

		if ($keywords !== '') {
			update_post_meta($post_id, 'war_keywords', trim($keywords));
			WAR_Debug::log('AJAX Links: Keywords atualizadas', ['post_id' => $post_id, 'keywords' => $keywords]);
		} else {
			delete_post_meta($post_id, 'war_keywords');
			WAR_Debug::log('AJAX Links: Keywords removidas', ['post_id' => $post_id]);
		}

		// Atualizar descrição
		if ($description !== '') {
			update_post_meta($post_id, 'war_description', $description);
		} else {
			delete_post_meta($post_id, 'war_description');
		}

		// Atualizar botões
		if (!empty($buttons)) {
			update_post_meta($post_id, 'war_buttons', $buttons);
			// Sincronizar war_redirect_url com primeiro botão
			update_post_meta($post_id, self::meta_key_redirect_url(), $buttons[0]['url']);
		} else {
			delete_post_meta($post_id, 'war_buttons');
			// Se não há botões, usar destination como war_redirect_url
			if ($destination !== '') {
				update_post_meta($post_id, self::meta_key_redirect_url(), $destination);
			}
		}

		// Atualizar listas (taxonomia)
		if (!empty($list_ids)) {
			wp_set_object_terms($post_id, $list_ids, 'war_list');
			WAR_Debug::log('AJAX Links: Listas atualizadas', ['post_id' => $post_id, 'list_ids' => $list_ids]);
		} else {
			// Remove todas as listas se array vazio
			wp_set_object_terms($post_id, [], 'war_list');
			WAR_Debug::log('AJAX Links: Listas removidas', ['post_id' => $post_id]);
		}

		WAR_Debug::log('AJAX Links: Atualizado com sucesso', ['post_id' => $post_id]);

		wp_send_json_success(['id' => $post_id]);
	}

	public static function delete_link() {
		self::guard();

		$post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
		if (!$post_id || get_post_type($post_id) !== 'war_link') {
			wp_send_json_error(['message' => 'Link inválido.'], 400);
		}

		if (!current_user_can('delete_post', $post_id)) {
			wp_send_json_error(['message' => 'Sem permissão para deletar este link.'], 403);
		}

		$deleted = wp_delete_post($post_id, true);
		if (!$deleted) {
			wp_send_json_error(['message' => 'Falha ao deletar.'], 500);
		}

		wp_send_json_success(['id' => $post_id]);
	}

	/**
	 * Salvar ordem dos links (drag and drop).
	 */
	public static function save_order() {
		self::guard();

		$order = isset($_POST['order']) ? $_POST['order'] : [];
		if (!is_array($order) || empty($order)) {
			wp_send_json_error(['message' => 'Ordem inválida.'], 400);
		}

		$updated = 0;
		foreach ($order as $index => $post_id) {
			$post_id = absint($post_id);
			if (!$post_id || get_post_type($post_id) !== 'war_link') {
				continue;
			}

			wp_update_post([
				'ID' => $post_id,
				'menu_order' => $index,
			]);
			$updated++;
		}

		WAR_Debug::log('AJAX Links: Ordem salva', ['updated' => $updated, 'total' => count($order)]);

		wp_send_json_success([
			'updated' => $updated,
			'message' => sprintf('%d link(s) reordenado(s).', $updated),
		]);
	}

	/**
	 * Obter todas as listas (taxonomia war_list).
	 */
	public static function get_lists() {
		self::guard();

		$terms = get_terms([
			'taxonomy' => 'war_list',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC',
		]);

		if (is_wp_error($terms)) {
			wp_send_json_error(['message' => 'Erro ao buscar listas.'], 500);
		}

		$lists = [];
		foreach ($terms as $term) {
			$lists[] = [
				'id' => (int) $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'count' => (int) $term->count,
			];
		}

		WAR_Debug::log('AJAX Links: Listas obtidas', ['count' => count($lists)]);

		wp_send_json_success([
			'lists' => $lists,
		]);
	}
}


