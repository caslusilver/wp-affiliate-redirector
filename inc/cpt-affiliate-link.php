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
		'show_in_menu' => true,
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


