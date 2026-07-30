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
		add_action('wp_ajax_war_links_get', [__CLASS__, 'get_link']);
		add_action('wp_ajax_war_links_create', [__CLASS__, 'create_link']);
		add_action('wp_ajax_war_links_update', [__CLASS__, 'update_link']);
		add_action('wp_ajax_war_links_delete', [__CLASS__, 'delete_link']);
		add_action('wp_ajax_war_links_save_order', [__CLASS__, 'save_order']);
		add_action('wp_ajax_war_links_toggle_visibility', [__CLASS__, 'toggle_visibility']);
		add_action('wp_ajax_war_get_lists', [__CLASS__, 'get_lists']);
		add_action('wp_ajax_war_get_all_links', [__CLASS__, 'get_all_links']);
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

	private static function sanitize_list_ids($raw) {
		// Aceita array ou JSON string
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

		// Limpar e validar IDs
		$ids_clean = [];
		foreach ($raw as $id) {
			$id = absint($id);
			if ($id > 0) {
				$ids_clean[] = $id;
			}
		}

		// Remover duplicados
		return array_values(array_unique($ids_clean));
	}

	/**
	 * Validar e sanitizar IDs de links associados (para kits).
	 * Impede associação a si mesmo e dependência circular.
	 */
	private static function validate_associated_ids($post_id, $raw) {
		// Aceita array ou JSON string
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

		$ids_clean = [];
		foreach ($raw as $id) {
			$id = absint($id);
			if ($id <= 0) {
				continue;
			}

			// Impedir associação a si mesmo
			if ($post_id > 0 && $id === $post_id) {
				continue;
			}

			// Verificar se o link existe
			if (get_post_type($id) !== 'war_link') {
				continue;
			}

			// Impedir dependência circular: se o link B já contém o link A,
			// o link A não pode conter o link B
			$reverse_associations = get_post_meta($id, 'war_associated_ids', true);
			if (is_array($reverse_associations) && in_array($post_id, $reverse_associations, true)) {
				WAR_Debug::log('AJAX Links: Associação circular bloqueada', [
					'post_id' => $post_id,
					'blocked_id' => $id,
				]);
				continue;
			}

			$ids_clean[] = $id;
		}

		// Remover duplicados
		return array_values(array_unique($ids_clean));
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
			
			// Obter associações de kit
			$associated_ids = get_post_meta($post_id, 'war_associated_ids', true);
			if (!is_array($associated_ids)) {
				$associated_ids = [];
			}
			$kit_active = (bool) get_post_meta($post_id, 'war_kit_active', true);
			
			// Obter visibilidade global com fallback
			$visible_meta = get_post_meta($post_id, 'war_link_visible', true);
			if ($visible_meta === '') {
				// Fallback: usar war_kit_active se existir, caso contrário padrão true
				$visible = get_post_meta($post_id, 'war_kit_active', true);
				$visible = $visible !== '' ? (bool) $visible : true;
			} else {
				$visible = (bool) $visible_meta;
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
				'associated_ids' => $associated_ids,
				'kit_active' => $kit_active,
				'visible' => $visible,
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

	/**
	 * Obter um link específico por ID.
	 * Usado pelo editor inline para carregar dados completos do link.
	 */
	public static function get_link() {
		self::guard();

		$post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
		if (!$post_id) {
			wp_send_json_error(['message' => 'ID é obrigatório.'], 400);
		}

		if (get_post_type($post_id) !== 'war_link') {
			wp_send_json_error(['message' => 'Link inválido.'], 400);
		}

		$post = get_post($post_id);
		if (!$post) {
			wp_send_json_error(['message' => 'Link não encontrado.'], 404);
		}

		$meta_key = self::meta_key_redirect_url();

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

		// Obter associações de kit
		$associated_ids = get_post_meta($post_id, 'war_associated_ids', true);
		if (!is_array($associated_ids)) {
			$associated_ids = [];
		}
		$kit_active = (bool) get_post_meta($post_id, 'war_kit_active', true);

		// Obter visibilidade global com fallback
		$visible_meta = get_post_meta($post_id, 'war_link_visible', true);
		if ($visible_meta === '') {
			// Fallback: usar war_kit_active se existir, caso contrário padrão true
			$visible = get_post_meta($post_id, 'war_kit_active', true);
			$visible = $visible !== '' ? (bool) $visible : true;
		} else {
			$visible = (bool) $visible_meta;
		}

		$item = [
			'id' => $post_id,
			'title' => get_the_title($post_id),
			'slug' => (string) $post->post_name,
			'public_url' => get_permalink($post_id),
			'destination' => (string) get_post_meta($post_id, $meta_key, true),
			'image_url' => (string) get_post_meta($post_id, 'war_image_url', true),
			'description' => (string) get_post_meta($post_id, 'war_description', true),
			'buttons' => $buttons,
			'clicks_total' => (int) get_post_meta($post_id, 'war_clicks_total', true),
			'keywords' => (string) get_post_meta($post_id, 'war_keywords', true),
			'lists' => $list_names,
			'list_ids' => $list_ids,
			'menu_order' => (int) $post->menu_order,
			'associated_ids' => $associated_ids,
			'kit_active' => $kit_active,
			'visible' => $visible,
		];

		WAR_Debug::log('AJAX Links: Link obtido', ['post_id' => $post_id]);

		wp_send_json_success($item);
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
		$list_ids_raw = isset($_POST['list_ids']) ? wp_unslash($_POST['list_ids']) : [];

		// Sanitizar botões e listas
		$buttons = self::sanitize_buttons($buttons_raw);
		$list_ids = self::sanitize_list_ids($list_ids_raw);

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

		// Processar associações de kit (se enviadas)
		if (isset($_POST['associated_ids'])) {
			$associated_ids_raw = wp_unslash($_POST['associated_ids']);
			$associated_ids = self::validate_associated_ids($post_id, $associated_ids_raw);
			
			if (!empty($associated_ids)) {
				update_post_meta($post_id, 'war_associated_ids', $associated_ids);
				WAR_Debug::log('AJAX Links: Associações de kit salvas', ['post_id' => $post_id, 'associated_ids' => $associated_ids]);
			}
		}

		// Processar status do kit (se enviado)
		if (isset($_POST['kit_active'])) {
			$kit_active = (bool) $_POST['kit_active'];
			update_post_meta($post_id, 'war_kit_active', $kit_active ? 1 : 0);
			WAR_Debug::log('AJAX Links: Status do kit salvo', ['post_id' => $post_id, 'kit_active' => $kit_active]);
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
		
		// Campos opcionais: só processar se enviados explicitamente
		$has_keywords = isset($_POST['keywords']);
		$keywords = $has_keywords ? sanitize_textarea_field((string) wp_unslash($_POST['keywords'])) : null;
		
		$has_image = isset($_POST['image_url']);
		$image_url = $has_image ? self::sanitize_destination(wp_unslash($_POST['image_url'])) : null;
		
		$has_description = isset($_POST['description']);
		$description = $has_description ? sanitize_textarea_field((string) wp_unslash($_POST['description'])) : null;
		
		$has_buttons = isset($_POST['buttons']);
		$buttons_raw = $has_buttons ? wp_unslash($_POST['buttons']) : null;
		$buttons = $has_buttons ? self::sanitize_buttons($buttons_raw) : null;
		
		$has_lists = isset($_POST['list_ids']);
		$list_ids_raw = $has_lists ? wp_unslash($_POST['list_ids']) : null;
		$list_ids = $has_lists ? self::sanitize_list_ids($list_ids_raw) : null;

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

		// Atualizar imagem: só se campo foi enviado
		if ($has_image) {
			if ($image_url !== '') {
				update_post_meta($post_id, 'war_image_url', $image_url);
			} else {
				// Campo veio vazio = remover imagem
				delete_post_meta($post_id, 'war_image_url');
			}
		}
		// Se $has_image = false, preserva valor existente

		// Atualizar keywords: só se campo foi enviado
		if ($has_keywords) {
			if ($keywords !== '') {
				update_post_meta($post_id, 'war_keywords', trim($keywords));
				WAR_Debug::log('AJAX Links: Keywords atualizadas', ['post_id' => $post_id, 'keywords' => $keywords]);
			} else {
				delete_post_meta($post_id, 'war_keywords');
				WAR_Debug::log('AJAX Links: Keywords removidas', ['post_id' => $post_id]);
			}
		}

		// Atualizar descrição: só se campo foi enviado
		if ($has_description) {
			if ($description !== '') {
				update_post_meta($post_id, 'war_description', $description);
			} else {
				delete_post_meta($post_id, 'war_description');
			}
		}

		// Atualizar botões: só se campo foi enviado
		if ($has_buttons) {
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
		}

		// Atualizar listas: só se campo foi enviado
		if ($has_lists) {
			if (!empty($list_ids)) {
				wp_set_object_terms($post_id, $list_ids, 'war_list');
				WAR_Debug::log('AJAX Links: Listas atualizadas', ['post_id' => $post_id, 'list_ids' => $list_ids]);
			} else {
				// Array vazio = remover todas as listas
				wp_set_object_terms($post_id, [], 'war_list');
				WAR_Debug::log('AJAX Links: Listas removidas', ['post_id' => $post_id]);
			}
		}

		// Atualizar associações de kit: só se campo foi enviado
		if (isset($_POST['associated_ids'])) {
			$associated_ids_raw = wp_unslash($_POST['associated_ids']);
			$associated_ids = self::validate_associated_ids($post_id, $associated_ids_raw);
			
			if (!empty($associated_ids)) {
				update_post_meta($post_id, 'war_associated_ids', $associated_ids);
				WAR_Debug::log('AJAX Links: Associações de kit atualizadas', ['post_id' => $post_id, 'associated_ids' => $associated_ids]);
			} else {
				// Array vazio = remover associações
				delete_post_meta($post_id, 'war_associated_ids');
				WAR_Debug::log('AJAX Links: Associações removidas', ['post_id' => $post_id]);
			}
		}

		// Atualizar status do kit: só se campo foi enviado
		if (isset($_POST['kit_active'])) {
			$kit_active = (bool) $_POST['kit_active'];
			update_post_meta($post_id, 'war_kit_active', $kit_active ? 1 : 0);
			WAR_Debug::log('AJAX Links: Status do kit atualizado', ['post_id' => $post_id, 'kit_active' => $kit_active]);
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
	 * Alternar visibilidade global de um link (toggle rápido).
	 * Endpoint otimizado que altera apenas o campo de visibilidade.
	 */
	public static function toggle_visibility() {
		self::guard();

		$post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
		if (!$post_id || get_post_type($post_id) !== 'war_link') {
			wp_send_json_error(['message' => 'Link inválido.'], 400);
		}

		if (!current_user_can('edit_post', $post_id)) {
			wp_send_json_error(['message' => 'Sem permissão para editar este link.'], 403);
		}

		// Obter estado atual
		$current_visible = get_post_meta($post_id, 'war_link_visible', true);
		
		// Se não existe, usar fallback do war_kit_active ou padrão true
		if ($current_visible === '') {
			$current_visible = get_post_meta($post_id, 'war_kit_active', true);
			$current_visible = $current_visible !== '' ? (bool) $current_visible : true;
		} else {
			$current_visible = (bool) $current_visible;
		}

		// Inverter estado
		$new_visible = !$current_visible;

		// Salvar novo estado
		update_post_meta($post_id, 'war_link_visible', $new_visible ? 1 : 0);

		WAR_Debug::log('AJAX Links: Visibilidade alterada', [
			'post_id' => $post_id,
			'old_visible' => $current_visible,
			'new_visible' => $new_visible,
		]);

		wp_send_json_success([
			'id' => $post_id,
			'visible' => $new_visible,
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

	/**
	 * Obter todos os links (para seletor de associações no editor inline).
	 */
	public static function get_all_links() {
		self::guard();

		$args = [
			'post_type' => 'war_link',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
		];

		$query = new WP_Query($args);
		$links = [];

		foreach ($query->posts as $p) {
			$links[] = [
				'id' => (int) $p->ID,
				'title' => get_the_title($p->ID),
			];
		}
		wp_reset_postdata();

		WAR_Debug::log('AJAX Links: Todos os links obtidos', ['count' => count($links)]);

		wp_send_json_success([
			'links' => $links,
		]);
	}
}


