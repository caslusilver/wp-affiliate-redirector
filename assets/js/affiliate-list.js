(function($) {
	'use strict';

	var config = window.WARAffiliatelist || {};
	var ajaxUrl = config.ajax_url || '';
	var nonce = config.nonce || '';
	var isAdmin = !!config.is_admin;

	// ============================================================================
	// ALTERNÂNCIA DE VISUALIZAÇÃO (LIST/CARD)
	// ============================================================================

	function initViewToggle() {
		$(document).on('click', '[data-war-toggle-view]', function() {
			var $btn = $(this);
			var view = $btn.attr('data-war-toggle-view');
			var $list = $btn.closest('.war-affiliate-list');

			if (!$list.length) return;

			// Atualizar atributo de visualização
			$list.attr('data-war-view', view);

			// Atualizar estado dos botões
			$list.find('[data-war-toggle-view]').removeClass('active');
			$btn.addClass('active');

			// Salvar preferência no localStorage
			try {
				localStorage.setItem('war_list_view_preference', view);
			} catch (e) {}
		});

		// Restaurar preferência salva
		$('.war-affiliate-list').each(function() {
			var $list = $(this);
			try {
				var savedView = localStorage.getItem('war_list_view_preference');
				if (savedView && (savedView === 'list' || savedView === 'card')) {
					$list.attr('data-war-view', savedView);
					$list.find('[data-war-toggle-view="' + savedView + '"]').addClass('active');
				} else {
					// Ativar o botão da view atual
					var currentView = $list.attr('data-war-view') || 'list';
					$list.find('[data-war-toggle-view="' + currentView + '"]').addClass('active');
				}
			} catch (e) {
				// Ativar o botão da view atual como fallback
				var currentView = $list.attr('data-war-view') || 'list';
				$list.find('[data-war-toggle-view="' + currentView + '"]').addClass('active');
			}
		});
	}

	// ============================================================================
	// MODAL DE ZOOM DA IMAGEM
	// ============================================================================

	function initImageZoom() {
		$(document).on('click', '[data-war-image-zoom]', function(e) {
			e.preventDefault();
			var $img = $(this).find('img');
			if (!$img.length) return;

			var imgSrc = $img.attr('src');
			var imgAlt = $img.attr('alt') || '';

			var $modal = $('[data-war-modal="1"]');
			if (!$modal.length) return;

			$modal.find('.war-image-modal__img').attr('src', imgSrc).attr('alt', imgAlt);
			$modal.fadeIn(200);
			$('body').css('overflow', 'hidden');
		});

		$(document).on('click', '[data-war-modal-close]', function(e) {
			e.preventDefault();
			closeImageModal();
		});

		// Fechar com ESC
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				closeImageModal();
			}
		});
	}

	function closeImageModal() {
		var $modal = $('[data-war-modal="1"]');
		$modal.fadeOut(200);
		$('body').css('overflow', '');
	}

	// ============================================================================
	// COPIAR URL
	// ============================================================================

	function initCopyUrl() {
		$(document).on('click', '[data-war-copy-url]', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var url = $btn.attr('data-war-copy-url');

			if (!url) return;

			var originalText = $btn.text();

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url)
					.then(function() {
						$btn.text('✓ Copiado!');
						setTimeout(function() {
							$btn.text(originalText);
						}, 2000);
					})
					.catch(function() {
						fallbackCopy(url, $btn, originalText);
					});
			} else {
				fallbackCopy(url, $btn, originalText);
			}
		});
	}

	function fallbackCopy(text, $btn, originalText) {
		var $temp = $('<textarea>').val(text).appendTo('body').select();
		try {
			document.execCommand('copy');
			$btn.text('✓ Copiado!');
			setTimeout(function() {
				$btn.text(originalText);
			}, 2000);
		} catch (e) {
			$btn.text('✗ Erro');
			setTimeout(function() {
				$btn.text(originalText);
			}, 2000);
		}
		$temp.remove();
	}

	// ============================================================================
	// DRAG AND DROP (SORTABLE) - APENAS ADMIN
	// ============================================================================

	function initSortable() {
		if (!isAdmin) return;

		$('.war-list-container').each(function() {
			var $container = $(this);
			var $list = $container.closest('.war-affiliate-list');
			var listSlug = $list.attr('data-war-list');

			if (!listSlug) return;

			// Inicializar sortable
			$container.sortable({
				items: '.war-list-item',
				handle: '.war-drag-handle',
				placeholder: 'ui-sortable-placeholder',
				cursor: 'move',
				opacity: 0.8,
				tolerance: 'pointer',
				stop: function(event, ui) {
					saveOrder($container, listSlug);
				}
			});
		});
	}

	function saveOrder($container, listSlug) {
		var order = [];
		$container.find('.war-list-item').each(function() {
			var linkId = parseInt($(this).attr('data-link-id'), 10);
			if (linkId) {
				order.push(linkId);
			}
		});

		if (order.length === 0) return;

		$.post(ajaxUrl, {
			action: 'war_links_save_order',
			nonce: nonce,
			order: order
		})
		.done(function(response) {
			console.log('Order saved:', response);
		})
		.fail(function(xhr) {
			console.error('Failed to save order:', xhr);
			alert('Erro ao salvar ordem. Por favor, recarregue a página.');
		});
	}

	// ============================================================================
	// EDITAR LINK - REDIRECIONA PARA ADMIN (SIMPLIFICADO)
	// ============================================================================

	function initEditLink() {
		if (!isAdmin) return;

		$(document).on('click', '[data-war-edit-link]', function(e) {
			e.preventDefault();
			var linkId = $(this).attr('data-war-edit-link');
			if (!linkId) return;

			// Redirecionar para a página de edição no admin
			var editUrl = '/wp-admin/post.php?post=' + linkId + '&action=edit';
			window.open(editUrl, '_blank');
		});
	}

	// ============================================================================
	// DELETAR LINK
	// ============================================================================

	function initDeleteLink() {
		if (!isAdmin) return;

		$(document).on('click', '[data-war-delete-link]', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var linkId = $btn.attr('data-war-delete-link');
			
			if (!linkId) return;

			if (!confirm('Tem certeza que deseja deletar este link?')) {
				return;
			}

			var $item = $btn.closest('.war-list-item');

			$.post(ajaxUrl, {
				action: 'war_links_delete',
				nonce: nonce,
				id: linkId
			})
			.done(function(response) {
				if (response.success) {
					$item.fadeOut(300, function() {
						$(this).remove();
						
						// Verificar se a lista está vazia
						var $container = $item.parent();
						if ($container.find('.war-list-item').length === 0) {
							$container.html('<p class="war-list-empty">Nenhum link nesta lista ainda.</p>');
						}
					});
				} else {
					alert('Erro ao deletar link: ' + (response.data && response.data.message ? response.data.message : 'Erro desconhecido'));
				}
			})
			.fail(function(xhr) {
				console.error('Failed to delete link:', xhr);
				alert('Erro ao deletar link. Por favor, tente novamente.');
			});
		});
	}

	// ============================================================================
	// INICIALIZAÇÃO
	// ============================================================================

	$(document).ready(function() {
		initViewToggle();
		initImageZoom();
		initCopyUrl();
		initSortable();
		initEditLink();
		initDeleteLink();

		console.log('WAR Affiliate List initialized', {
			isAdmin: isAdmin,
			hasAjax: !!ajaxUrl,
			hasNonce: !!nonce
		});
	});

})(jQuery);
