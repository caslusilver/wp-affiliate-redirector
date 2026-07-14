<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * CPT: Links de Afiliado (base /go/{slug})
 */
function war_register_affiliate_link_cpt() {
	$labels = [
		'name' => 'Links de Afiliado',
		'singular_name' => 'Link de Afiliado',
		'menu_name' => 'Links Afiliado',
		'add_new' => 'Adicionar novo',
		'add_new_item' => 'Adicionar novo link',
		'edit_item' => 'Editar link',
		'new_item' => 'Novo link',
		'view_item' => 'Ver link',
		'search_items' => 'Buscar links',
		'not_found' => 'Nenhum link encontrado',
		'not_found_in_trash' => 'Nenhum link na lixeira',
	];

	register_post_type('war_link', [
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => true,
		'show_ui' => true,
		// Mostra o CPT sob o menu topo do plugin (Affiliate Links).
		'show_in_menu' => 'war-affiliate-links',
		'menu_icon' => 'dashicons-randomize',
		'supports' => ['title'],
		'has_archive' => false,
		'rewrite' => [
			'slug' => 'go',
			'with_front' => false,
		],
		'capability_type' => 'post',
		'map_meta_cap' => true,
	]);
}

add_action('init', 'war_register_affiliate_link_cpt');

/**
 * Taxonomia: Listas de Afiliados
 * Permite agrupar links em listas/categorias para exibição via shortcode.
 */
function war_register_affiliate_list_taxonomy() {
	$labels = [
		'name' => 'Listas',
		'singular_name' => 'Lista',
		'menu_name' => 'Listas',
		'all_items' => 'Todas as Listas',
		'edit_item' => 'Editar Lista',
		'view_item' => 'Ver Lista',
		'update_item' => 'Atualizar Lista',
		'add_new_item' => 'Adicionar Nova Lista',
		'new_item_name' => 'Novo Nome de Lista',
		'search_items' => 'Buscar Listas',
		'popular_items' => 'Listas Populares',
		'not_found' => 'Nenhuma lista encontrada',
	];

	register_taxonomy('war_list', ['war_link'], [
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_admin_column' => true,
		'hierarchical' => true,
		'rewrite' => [
			'slug' => 'lista-afiliado',
			'with_front' => false,
		],
		'show_in_rest' => true,
	]);
}

add_action('init', 'war_register_affiliate_list_taxonomy');


