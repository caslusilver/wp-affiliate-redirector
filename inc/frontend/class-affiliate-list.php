<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shortcode para exibir lista de links de afiliados.
 *
 * Tag: [war_affiliate_list slug="lista-slug" view="list"]
 * 
 * - slug: slug da taxonomia war_list
 * - view: "list" (padrão) ou "card"
 */
class WAR_Affiliate_List {
	const SHORTCODE_TAG = 'war_affiliate_list';
	const NONCE_ACTION = 'war_link_manager_nonce';
	private static $force_enqueue = false;

	public static function init() {
		add_shortcode(self::SHORTCODE_TAG, [__CLASS__, 'render_shortcode']);
		add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
		
		// Endpoints AJAX públicos para visitantes
		add_action('wp_ajax_nopriv_war_list_get_links', [__CLASS__, 'ajax_get_links']);
		add_action('wp_ajax_war_list_get_links', [__CLASS__, 'ajax_get_links']);
	}

	public static function enqueue_assets() {
		if (!is_singular()) {
			return;
		}

		global $post;
		if (!$post || !isset($post->post_content)) {
			return;
		}

		$has_shortcode = self::$force_enqueue || has_shortcode($post->post_content, self::SHORTCODE_TAG);

		if (!$has_shortcode && function_exists('has_blocks') && has_blocks($post->post_content)) {
			$blocks = parse_blocks($post->post_content);
			foreach ($blocks as $block) {
				if (isset($block['innerHTML']) && strpos($block['innerHTML'], '[' . self::SHORTCODE_TAG) !== false) {
					$has_shortcode = true;
					break;
				}
			}
		}

		if (!$has_shortcode) {
			$elementor_data = (string) get_post_meta((int) $post->ID, '_elementor_data', true);
			if ($elementor_data !== '' && strpos($elementor_data, '[' . self::SHORTCODE_TAG) !== false) {
				$has_shortcode = true;
			}
		}

		if (!$has_shortcode) {
			return;
		}

		// Enqueue Media Library se usuário tiver permissão
		if (current_user_can('manage_options') || current_user_can('upload_files')) {
			wp_enqueue_media();
		}

		$plugin_version = function_exists('WP_AFFILIATE_REDIRECTOR_get_version') ? WP_AFFILIATE_REDIRECTOR_get_version() : '0.0.0';
		$css_file = WAR_PLUGIN_DIR . 'assets/css/affiliate-list.css';
		$js_file = WAR_PLUGIN_DIR . 'assets/js/affiliate-list.js';
		
		$css_ver = file_exists($css_file) ? (string) filemtime($css_file) : $plugin_version;
		$js_ver = file_exists($js_file) ? (string) filemtime($js_file) : $plugin_version;

		wp_enqueue_style('dashicons');
		wp_enqueue_style(
			'war-affiliate-list',
			WAR_PLUGIN_URL . 'assets/css/affiliate-list.css',
			['dashicons'],
			$css_ver
		);

		// Enqueue jQuery UI Sortable se for admin
		if (current_user_can('manage_options')) {
			wp_enqueue_script('jquery-ui-sortable');
		}

		wp_enqueue_script(
			'war-affiliate-list',
			WAR_PLUGIN_URL . 'assets/js/affiliate-list.js',
			['jquery'],
			$js_ver,
			true
		);

		wp_localize_script('war-affiliate-list', 'WARAffiliatelist', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce(self::NONCE_ACTION),
			'is_admin' => current_user_can('manage_options'),
		]);
	}

	public static function render_shortcode($atts) {
		$atts = shortcode_atts([
			'slug' => '',
			'view' => 'list', // list ou card
		], $atts, self::SHORTCODE_TAG);

		$list_slug = sanitize_title($atts['slug']);
		$default_view = in_array($atts['view'], ['list', 'card']) ? $atts['view'] : 'list';

		if (empty($list_slug)) {
			return '<p style="color:#b32d2e;">' . esc_html__('Por favor, informe o slug da lista via parâmetro "slug".', WAR_TEXT_DOMAIN) . '</p>';
		}

		// Buscar termo da taxonomia
		$term = get_term_by('slug', $list_slug, 'war_list');
		if (!$term || is_wp_error($term)) {
			return '<p style="color:#b32d2e;">' . esc_html__('Lista não encontrada.', WAR_TEXT_DOMAIN) . '</p>';
		}

		// Garantir enqueue
		self::$force_enqueue = true;
		self::enqueue_assets();

		// Buscar links associados à lista
		$args = [
			'post_type' => 'war_link',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'tax_query' => [
				[
					'taxonomy' => 'war_list',
					'field' => 'slug',
					'terms' => $list_slug,
				],
			],
		];

		$query = new WP_Query($args);
		$links = [];

		if ($query->have_posts()) {
			foreach ($query->posts as $p) {
				$post_id = (int) $p->ID;
				
				// Obter botões
				$buttons = get_post_meta($post_id, 'war_buttons', true);
				if (!is_array($buttons)) {
					$buttons = [];
				}
				
				// Se não há botões, usar war_redirect_url como fallback
				if (empty($buttons)) {
					$destination = (string) get_post_meta($post_id, 'war_redirect_url', true);
					if ($destination !== '') {
						$buttons = [
							[
								'label' => __('Acessar', WAR_TEXT_DOMAIN),
								'url' => $destination,
							]
						];
					}
				}
				
				$links[] = [
					'id' => $post_id,
					'title' => get_the_title($post_id),
					'slug' => (string) $p->post_name,
					'public_url' => get_permalink($post_id),
					'destination' => (string) get_post_meta($post_id, 'war_redirect_url', true),
					'image_url' => (string) get_post_meta($post_id, 'war_image_url', true),
					'description' => (string) get_post_meta($post_id, 'war_description', true),
					'buttons' => $buttons,
					'menu_order' => (int) $p->menu_order,
				];
			}
		}
		wp_reset_postdata();

		$is_admin = current_user_can('manage_options');

		ob_start();
		?>
		<div class="war-affiliate-list" data-war-list="<?php echo esc_attr($list_slug); ?>" data-war-view="<?php echo esc_attr($default_view); ?>">
			
			<?php if ($is_admin): ?>
			<div class="war-list-controls">
				<div class="war-list-controls__left">
					<h3 class="war-list-title"><?php echo esc_html($term->name); ?></h3>
				</div>
				<div class="war-list-controls__right">
					<button type="button" class="war-view-toggle" data-war-toggle-view="list" aria-label="<?php esc_attr_e('Visualização Lista', WAR_TEXT_DOMAIN); ?>">
						<span class="dashicons dashicons-list-view"></span>
					</button>
					<button type="button" class="war-view-toggle" data-war-toggle-view="card" aria-label="<?php esc_attr_e('Visualização Cards', WAR_TEXT_DOMAIN); ?>">
						<span class="dashicons dashicons-grid-view"></span>
					</button>
				</div>
			</div>
			<?php else: ?>
			<div class="war-list-controls war-list-controls--public">
				<div class="war-list-controls__left">
					<h3 class="war-list-title"><?php echo esc_html($term->name); ?></h3>
				</div>
				<div class="war-list-controls__right">
					<button type="button" class="war-view-toggle" data-war-toggle-view="list" aria-label="<?php esc_attr_e('Visualização Lista', WAR_TEXT_DOMAIN); ?>">
						<span class="dashicons dashicons-list-view"></span>
					</button>
					<button type="button" class="war-view-toggle" data-war-toggle-view="card" aria-label="<?php esc_attr_e('Visualização Cards', WAR_TEXT_DOMAIN); ?>">
						<span class="dashicons dashicons-grid-view"></span>
					</button>
				</div>
			</div>
			<?php endif; ?>

			<div class="war-list-container" data-war-container="1">
				<?php if (empty($links)): ?>
					<p class="war-list-empty"><?php esc_html_e('Nenhum link nesta lista ainda.', WAR_TEXT_DOMAIN); ?></p>
				<?php else: ?>
					<?php foreach ($links as $link): ?>
						<?php 
						$has_image = !empty($link['image_url']);
						$item_class = $has_image ? 'war-list-item' : 'war-list-item war-list-item--no-image';
						?>
						<div class="<?php echo esc_attr($item_class); ?>" data-link-id="<?php echo esc_attr($link['id']); ?>">
							<?php if ($has_image): ?>
								<div class="war-list-item__image" data-war-image-zoom="1">
									<img src="<?php echo esc_url($link['image_url']); ?>" alt="<?php echo esc_attr($link['title']); ?>" />
								</div>
							<?php endif; ?>

							<div class="war-list-item__content">
								<h4 class="war-list-item__title"><?php echo esc_html($link['title']); ?></h4>
								<div class="war-list-item__url">
									<a href="<?php echo esc_url($link['public_url']); ?>" target="_blank" rel="noopener noreferrer">
										<?php echo esc_html(str_replace(home_url('/'), '', $link['public_url'])); ?>
									</a>
								</div>
								
								<?php if (!empty($link['description'])): ?>
								<div class="war-list-item__description" data-war-description="1">
									<p class="war-description-text"><?php echo esc_html($link['description']); ?></p>
								</div>
								<?php endif; ?>
								
								<div class="war-list-item__actions">
									<?php if (!empty($link['buttons'])): ?>
										<?php foreach ($link['buttons'] as $button): ?>
											<a href="<?php echo esc_url($button['url']); ?>" class="war-btn war-btn--primary" target="_blank" rel="noopener noreferrer">
												<?php echo esc_html($button['label']); ?>
											</a>
										<?php endforeach; ?>
									<?php endif; ?>
									<button type="button" class="war-btn" data-war-copy-url="<?php echo esc_attr($link['public_url']); ?>">
										<?php esc_html_e('Copiar', WAR_TEXT_DOMAIN); ?>
									</button>
								</div>
							</div>

							<?php if ($is_admin): ?>
							<div class="war-list-item__admin">
								<button type="button" class="war-admin-btn" data-war-edit-link="<?php echo esc_attr($link['id']); ?>" title="<?php esc_attr_e('Editar', WAR_TEXT_DOMAIN); ?>">
									<span class="dashicons dashicons-edit"></span>
								</button>
								<button type="button" class="war-admin-btn war-admin-btn--danger" data-war-delete-link="<?php echo esc_attr($link['id']); ?>" title="<?php esc_attr_e('Excluir', WAR_TEXT_DOMAIN); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
								<span class="war-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e('Arrastar para reordenar', WAR_TEXT_DOMAIN); ?>"></span>
							</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<!-- Modal de zoom da imagem -->
		<div class="war-image-modal" data-war-modal="1" style="display:none;">
			<div class="war-image-modal__overlay" data-war-modal-close="1"></div>
			<div class="war-image-modal__content">
				<button type="button" class="war-image-modal__close" data-war-modal-close="1">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
				<img class="war-image-modal__img" src="" alt="" />
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Endpoint AJAX público para obter links de uma lista.
	 */
	public static function ajax_get_links() {
		$list_slug = isset($_POST['list_slug']) ? sanitize_title($_POST['list_slug']) : '';
		
		if (empty($list_slug)) {
			wp_send_json_error(['message' => 'Slug da lista é obrigatório.'], 400);
		}

		$args = [
			'post_type' => 'war_link',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'tax_query' => [
				[
					'taxonomy' => 'war_list',
					'field' => 'slug',
					'terms' => $list_slug,
				],
			],
		];

		$query = new WP_Query($args);
		$links = [];

		if ($query->have_posts()) {
			foreach ($query->posts as $p) {
				$post_id = (int) $p->ID;
				
				// Obter botões
				$buttons = get_post_meta($post_id, 'war_buttons', true);
				if (!is_array($buttons)) {
					$buttons = [];
				}
				
				// Se não há botões, usar war_redirect_url como fallback
				if (empty($buttons)) {
					$destination = (string) get_post_meta($post_id, 'war_redirect_url', true);
					if ($destination !== '') {
						$buttons = [
							[
								'label' => __('Acessar', WAR_TEXT_DOMAIN),
								'url' => $destination,
							]
						];
					}
				}
				
				$links[] = [
					'id' => $post_id,
					'title' => get_the_title($post_id),
					'slug' => (string) $p->post_name,
					'public_url' => get_permalink($post_id),
					'image_url' => (string) get_post_meta($post_id, 'war_image_url', true),
					'description' => (string) get_post_meta($post_id, 'war_description', true),
					'buttons' => $buttons,
					'menu_order' => (int) $p->menu_order,
				];
			}
		}
		wp_reset_postdata();

		wp_send_json_success(['links' => $links]);
	}
}
