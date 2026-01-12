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
			$items[] = [
				'id' => $post_id,
				'title' => get_the_title($post_id),
				'slug' => (string) $p->post_name,
				'public_url' => get_permalink($post_id),
				'destination' => (string) get_post_meta($post_id, $meta_key, true),
				'clicks_total' => (int) get_post_meta($post_id, 'war_clicks_total', true),
				'keywords' => (string) get_post_meta($post_id, 'war_keywords', true),
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

		WAR_Debug::log('AJAX Links: Criando', [
			'title' => $title,
			'slug' => $slug,
			'destination' => $destination,
			'keywords' => $keywords,
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

		if ($keywords !== '') {
			update_post_meta($post_id, 'war_keywords', trim($keywords));
			WAR_Debug::log('AJAX Links: Keywords salvas', ['post_id' => $post_id, 'keywords' => $keywords]);
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

		WAR_Debug::log('AJAX Links: Atualizando', [
			'post_id' => $post_id,
			'title' => $title,
			'destination' => $destination,
			'keywords' => $keywords,
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

		if ($keywords !== '') {
			update_post_meta($post_id, 'war_keywords', trim($keywords));
			WAR_Debug::log('AJAX Links: Keywords atualizadas', ['post_id' => $post_id, 'keywords' => $keywords]);
		} else {
			delete_post_meta($post_id, 'war_keywords');
			WAR_Debug::log('AJAX Links: Keywords removidas', ['post_id' => $post_id]);
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
}


