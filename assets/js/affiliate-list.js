(function($) {
	'use strict';

	var config = window.WARAffiliatelist || {};
	var ajaxUrl = config.ajax_url || '';
	var nonce = config.nonce || '';
	var isAdmin = !!config.is_admin;
	var mediaUploader = null;

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
				closeEditModal();
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
	// EDITAR LINK - MODAL INLINE
	// ============================================================================

	function initEditLink() {
		if (!isAdmin) return;

		// Criar modal de edição se não existir
		if ($('#war-edit-modal').length === 0) {
			createEditModal();
		}

		$(document).on('click', '[data-war-edit-link]', function(e) {
			e.preventDefault();
			var linkId = $(this).attr('data-war-edit-link');
			if (!linkId) return;

			openEditModal(linkId);
		});
	}

	function createEditModal() {
		var modalHtml = `
			<div id="war-edit-modal" class="war-edit-modal" style="display:none;">
				<div class="war-edit-modal__overlay" data-war-edit-modal-close="1"></div>
				<div class="war-edit-modal__content">
					<div class="war-edit-modal__header">
						<h2 class="war-edit-modal__title">Editar Link de Afiliado</h2>
						<button type="button" class="war-edit-modal__close" data-war-edit-modal-close="1">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
					<div class="war-edit-modal__body">
						<form id="war-edit-form">
							<input type="hidden" name="link_id" id="war-edit-link-id" />
							
							<div class="war-edit-field">
								<label class="war-edit-field__label">Título *</label>
								<input type="text" name="title" id="war-edit-title" class="war-edit-field__input" required />
							</div>

							<div class="war-edit-field">
								<label class="war-edit-field__label">Imagem do Produto</label>
								<div class="war-edit-image-upload" data-war-edit-image-upload="1">
									<div class="war-edit-image-preview" data-war-edit-image-preview="1"></div>
									<input type="hidden" name="image_url" id="war-edit-image-url" data-war-edit-image-input="1" />
									<button type="button" class="war-btn war-btn--secondary" data-war-edit-select-image="1">Selecionar Imagem</button>
									<button type="button" class="war-btn war-btn--secondary" data-war-edit-remove-image="1" style="display:none;">Remover</button>
								</div>
							</div>

							<div class="war-edit-field">
								<label class="war-edit-field__label">Descrição</label>
								<textarea name="description" id="war-edit-description" class="war-edit-field__textarea" rows="4"></textarea>
							</div>

							<div class="war-edit-field">
								<label class="war-edit-field__label">Listas</label>
								<div class="war-edit-lists-checkboxes" data-war-edit-lists-container="1">
									<p class="war-edit-lists-loading">Carregando listas...</p>
								</div>
							</div>

							<div class="war-edit-field">
								<label class="war-edit-field__label">Botões de Destino</label>
								<div class="war-edit-buttons-editor" data-war-edit-buttons-editor="1">
									<div class="war-edit-buttons-list" data-war-edit-buttons-list="1"></div>
									<button type="button" class="war-btn war-btn--secondary" data-war-edit-add-button="1">+ Adicionar Botão</button>
								</div>
							</div>

							<div class="war-edit-field">
								<label class="war-edit-field__label">Keywords (Opcionais)</label>
								<textarea name="keywords" id="war-edit-keywords" class="war-edit-field__textarea" rows="2"></textarea>
							</div>
						</form>
					</div>
					<div class="war-edit-modal__footer">
						<button type="button" class="war-btn war-btn--secondary" data-war-edit-modal-close="1">Cancelar</button>
						<button type="button" class="war-btn war-btn--primary" id="war-edit-save">Salvar</button>
					</div>
				</div>
			</div>
		`;
		$('body').append(modalHtml);

		// Event listeners do modal
		$(document).on('click', '[data-war-edit-modal-close]', function(e) {
			e.preventDefault();
			closeEditModal();
		});

		$(document).on('click', '#war-edit-save', function(e) {
			e.preventDefault();
			saveEditedLink();
		});

		// Imagem
		$(document).on('click', '[data-war-edit-select-image]', function(e) {
			e.preventDefault();
			if (typeof window.wp === 'undefined' || !window.wp.media) {
				alert('Media Library não disponível.');
				return;
			}

			mediaUploader = window.wp.media({
				title: 'Selecionar Imagem do Produto',
				button: { text: 'Usar esta imagem' },
				multiple: false
			});

			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				$('#war-edit-image-url').val(attachment.url);
				setEditImagePreview(attachment.url);
			});

			mediaUploader.open();
		});

		$(document).on('click', '[data-war-edit-remove-image]', function(e) {
			e.preventDefault();
			$('#war-edit-image-url').val('');
			resetEditImagePreview();
		});

		// Botões
		$(document).on('click', '[data-war-edit-add-button]', function(e) {
			e.preventDefault();
			addEditButton('', '');
		});

		$(document).on('click', '[data-war-edit-remove-button]', function(e) {
			e.preventDefault();
			$(this).closest('.war-edit-button-row').remove();
		});
	}

	function openEditModal(linkId) {
		var $modal = $('#war-edit-modal');
		
		// Buscar dados do link
		$.post(ajaxUrl, {
			action: 'war_links_list',
			nonce: nonce,
			per_page: 1,
			page: 1
		})
		.done(function(response) {
			if (!response.success || !response.data.items) {
				alert('Erro ao carregar link.');
				return;
			}

			// Encontrar o link específico
			var link = null;
			for (var i = 0; i < response.data.items.length; i++) {
				if (response.data.items[i].id == linkId) {
					link = response.data.items[i];
					break;
				}
			}

			// Se não encontrou, fazer nova busca
			if (!link) {
				// Buscar todos os links (não filtrados)
				$.post(ajaxUrl, {
					action: 'war_links_list',
					nonce: nonce,
					per_page: 100,
					page: 1
				})
				.done(function(resp2) {
					if (resp2.success && resp2.data.items) {
						for (var j = 0; j < resp2.data.items.length; j++) {
							if (resp2.data.items[j].id == linkId) {
								link = resp2.data.items[j];
								break;
							}
						}
					}
					if (link) {
						fillEditForm(link);
						$modal.fadeIn(200);
						$('body').css('overflow', 'hidden');
					} else {
						alert('Link não encontrado.');
					}
				});
				return;
			}

			fillEditForm(link);
			$modal.fadeIn(200);
			$('body').css('overflow', 'hidden');
		})
		.fail(function() {
			alert('Erro ao carregar link.');
		});
	}

	function fillEditForm(link) {
		$('#war-edit-link-id').val(link.id);
		$('#war-edit-title').val(link.title || '');
		$('#war-edit-description').val(link.description || '');
		$('#war-edit-keywords').val(link.keywords || '');
		
		// Imagem
		if (link.image_url) {
			$('#war-edit-image-url').val(link.image_url);
			setEditImagePreview(link.image_url);
		} else {
			resetEditImagePreview();
		}

		// Botões
		$('[data-war-edit-buttons-list]').empty();
		if (link.buttons && link.buttons.length > 0) {
			link.buttons.forEach(function(btn) {
				addEditButton(btn.label, btn.url);
			});
		} else if (link.destination) {
			// Fallback: criar botão padrão
			addEditButton('Acessar', link.destination);
		}

		// Listas
		loadEditLists(link.list_ids || []);
	}

	function setEditImagePreview(url) {
		var $preview = $('[data-war-edit-image-preview]');
		$preview.html('<img src="' + url + '" alt="Preview" />').show();
		$('[data-war-edit-remove-image]').show();
	}

	function resetEditImagePreview() {
		$('[data-war-edit-image-preview]').empty().hide();
		$('[data-war-edit-remove-image]').hide();
	}

	function addEditButton(label, url) {
		var html = `
			<div class="war-edit-button-row">
				<input type="text" placeholder="Label do botão" class="war-edit-button-label" value="${label}" />
				<input type="url" placeholder="https://exemplo.com" class="war-edit-button-url" value="${url}" />
				<button type="button" class="war-btn war-btn--danger" data-war-edit-remove-button="1">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
		`;
		$('[data-war-edit-buttons-list]').append(html);
	}

	function loadEditLists(selectedIds) {
		var $container = $('[data-war-edit-lists-container]');
		$container.html('<p class="war-edit-lists-loading">Carregando listas...</p>');

		$.post(ajaxUrl, {
			action: 'war_get_lists',
			nonce: nonce
		})
		.done(function(response) {
			if (response.success && response.data.lists) {
				var html = '';
				response.data.lists.forEach(function(list) {
					var checked = selectedIds.indexOf(list.id) !== -1 ? 'checked' : '';
					html += `
						<label class="war-edit-list-checkbox">
							<input type="checkbox" name="list_ids[]" value="${list.id}" ${checked} />
							<span>${list.name}</span>
						</label>
					`;
				});
				$container.html(html || '<p>Nenhuma lista disponível.</p>');
			} else {
				$container.html('<p>Erro ao carregar listas.</p>');
			}
		})
		.fail(function() {
			$container.html('<p>Erro ao carregar listas.</p>');
		});
	}

	function collectEditButtons() {
		var buttons = [];
		$('[data-war-edit-buttons-list] .war-edit-button-row').each(function() {
			var label = $(this).find('.war-edit-button-label').val().trim();
			var url = $(this).find('.war-edit-button-url').val().trim();
			if (label && url) {
				buttons.push({ label: label, url: url });
			}
		});
		return buttons;
	}

	function collectEditListIds() {
		var ids = [];
		$('[data-war-edit-lists-container] input[name="list_ids[]"]:checked').each(function() {
			ids.push(parseInt($(this).val(), 10));
		});
		return ids;
	}

	function saveEditedLink() {
		var linkId = $('#war-edit-link-id').val();
		var title = $('#war-edit-title').val().trim();
		var imageUrl = $('#war-edit-image-url').val().trim();
		var description = $('#war-edit-description').val().trim();
		var keywords = $('#war-edit-keywords').val().trim();
		var buttons = collectEditButtons();
		var listIds = collectEditListIds();

		if (!title) {
			alert('Título é obrigatório.');
			return;
		}

		if (buttons.length === 0) {
			alert('Adicione pelo menos um botão de destino.');
			return;
		}

		var $saveBtn = $('#war-edit-save');
		$saveBtn.prop('disabled', true).text('Salvando...');

		$.post(ajaxUrl, {
			action: 'war_links_update',
			nonce: nonce,
			id: linkId,
			title: title,
			destination: buttons[0].url, // Primeira URL como destination
			image_url: imageUrl,
			description: description,
			keywords: keywords,
			buttons: JSON.stringify(buttons),
			list_ids: JSON.stringify(listIds)
		})
		.done(function(response) {
			if (response.success) {
				closeEditModal();
				// Recarregar a página para mostrar mudanças
				location.reload();
			} else {
				alert('Erro ao salvar: ' + (response.data && response.data.message ? response.data.message : 'Erro desconhecido'));
			}
		})
		.fail(function(xhr) {
			console.error('Failed to update link:', xhr);
			alert('Erro ao salvar link. Por favor, tente novamente.');
		})
		.always(function() {
			$saveBtn.prop('disabled', false).text('Salvar');
		});
	}

	function closeEditModal() {
		var $modal = $('#war-edit-modal');
		$modal.fadeOut(200);
		$('body').css('overflow', '');
		// Resetar formulário
		$('#war-edit-form')[0].reset();
		resetEditImagePreview();
		$('[data-war-edit-buttons-list]').empty();
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
