(function($) {
	'use strict';

	var config = window.WARAffiliatelist || {};
	var ajaxUrl = config.ajax_url || '';
	var nonce = config.nonce || '';
	var isAdmin = !!config.is_admin;
	var mediaUploader = null;
	var allLinks = []; // Cache de todos os links para o seletor

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
					var currentView = $list.attr('data-war-view') || 'list';
					$list.find('[data-war-toggle-view="' + currentView + '"]').addClass('active');
				}
			} catch (e) {
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
			var url = $(this).attr('data-war-copy-url');
			if (!url) return;

			copyToClipboard(url);
			showCopyFeedback($(this));
		});
	}

	function copyToClipboard(text) {
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).catch(function(err) {
				console.error('Erro ao copiar:', err);
			});
		} else {
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(text).select();
			document.execCommand('copy');
			$temp.remove();
		}
	}

	function showCopyFeedback($btn) {
		var originalText = $btn.text();
		$btn.text('✓ Copiado!').addClass('war-btn--success');
		
		setTimeout(function() {
			$btn.text(originalText).removeClass('war-btn--success');
		}, 1500);
	}

	// ============================================================================
	// EDITAR LINK - EDITOR INLINE
	// ============================================================================

	function initEditLink() {
		if (!isAdmin) return;

		// Carregar todos os links para o seletor de associações
		loadAllLinks();

		$(document).on('click', '[data-war-edit-link]', function(e) {
			e.preventDefault();
			var linkId = $(this).attr('data-war-edit-link');
			if (!linkId) return;

			var $item = $(this).closest('.war-list-item');
			
			// Fechar outros editores abertos
			$('.war-inline-editor').slideUp(300, function() {
				$(this).remove();
			});

			// Abrir editor inline para este item
			openInlineEditor(linkId, $item);
		});
	}

	function loadAllLinks() {
		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'war_get_all_links',
				nonce: nonce
			},
			success: function(response) {
				if (response.success && response.data && response.data.links) {
					allLinks = response.data.links;
				}
			}
		});
	}

	function openInlineEditor(linkId, $item) {
		// Mostrar loading
		$item.after('<div class="war-inline-editor"><div class="war-inline-editor__loading">Carregando...</div></div>');
		var $editor = $item.next('.war-inline-editor');

		// Carregar dados do link
		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'war_links_get',
				nonce: nonce,
				id: linkId
			},
			success: function(response) {
				if (response.success && response.data) {
					renderInlineEditor(response.data, $editor);
				} else {
					$editor.html('<p style="color:red;">Erro ao carregar dados do link.</p>');
				}
			},
			error: function() {
				$editor.html('<p style="color:red;">Erro de conexão.</p>');
			}
		});
	}

	function renderInlineEditor(linkData, $editor) {
		var hasAssociations = linkData.associated_ids && linkData.associated_ids.length > 0;
		var kitActive = linkData.kit_active || false;

		var html = `
			<div class="war-inline-editor__content">
				<input type="hidden" name="link_id" value="${linkData.id}" />
				
				<div class="war-inline-field">
					<label class="war-inline-field__label">Título *</label>
					<input type="text" name="title" class="war-inline-field__input" value="${escapeHtml(linkData.title)}" required />
				</div>

				<div class="war-inline-field">
					<label class="war-inline-field__label">URL Principal *</label>
					<input type="url" name="destination" class="war-inline-field__input" value="${escapeHtml(linkData.destination)}" required />
				</div>

				<div class="war-inline-field">
					<label class="war-inline-field__label">Imagem do Produto</label>
					<div class="war-inline-image-upload">
						<div class="war-inline-image-preview">
							${linkData.image_url ? `<img src="${escapeHtml(linkData.image_url)}" alt="Preview" />` : '<div class="war-inline-image-placeholder">📷 Sem imagem</div>'}
						</div>
						<input type="hidden" name="image_url" value="${escapeHtml(linkData.image_url)}" />
						<div class="war-inline-image-actions">
							<button type="button" class="war-btn war-btn--secondary" data-war-inline-select-image="1">Selecionar Imagem</button>
							<button type="button" class="war-btn" data-war-inline-remove-image="1" ${!linkData.image_url ? 'style="display:none;"' : ''}>Remover</button>
						</div>
					</div>
				</div>

				<div class="war-inline-field">
					<label class="war-inline-field__label">Descrição</label>
					<textarea name="description" class="war-inline-field__textarea" rows="3">${escapeHtml(linkData.description)}</textarea>
				</div>

				<div class="war-inline-field">
					<label class="war-inline-field__label">Botões de Destino</label>
					<div class="war-inline-buttons-editor">
						<div class="war-inline-buttons-list"></div>
						<button type="button" class="war-btn war-btn--secondary" data-war-inline-add-button="1">+ Adicionar Botão</button>
					</div>
				</div>

				<div class="war-inline-field">
					<label class="war-inline-field__label">Links Associados (Kit)</label>
					<div class="war-inline-associations">
						<input type="text" class="war-inline-search" placeholder="🔍 Buscar links..." />
						<div class="war-inline-associations-list"></div>
					</div>
				</div>

				<div class="war-inline-field war-inline-kit-toggle" style="display:${hasAssociations ? 'block' : 'none'};">
					<label class="war-inline-field__label">Status do Kit</label>
					<div class="war-toggle-switch">
						<input type="checkbox" name="kit_active" id="kit_active_${linkData.id}" ${kitActive ? 'checked' : ''} />
						<label for="kit_active_${linkData.id}" class="war-toggle-label">
							<span class="war-toggle-inner"></span>
							<span class="war-toggle-switch-btn"></span>
						</label>
						<span class="war-toggle-text">${kitActive ? 'Ativo (kit visível, componentes ocultos)' : 'Inativo (kit oculto, componentes visíveis)'}</span>
					</div>
				</div>

				<div class="war-inline-actions">
					<button type="button" class="war-btn" data-war-inline-cancel="1">Cancelar</button>
					<button type="button" class="war-btn war-btn--primary" data-war-inline-save="1">Salvar</button>
				</div>
			</div>
		`;

		$editor.html(html);
		$editor.slideDown(300);

		// Popular botões existentes
		populateButtons(linkData.buttons || [], $editor);

		// Popular associações
		populateAssociations(linkData.associated_ids || [], linkData.id, $editor);

		// Inicializar eventos do editor
		initInlineEditorEvents($editor);
	}

	function populateButtons(buttons, $editor) {
		var $list = $editor.find('.war-inline-buttons-list');
		$list.empty();

		if (buttons.length === 0) {
			$list.html('<p class="war-inline-no-buttons">Nenhum botão configurado</p>');
			return;
		}

		buttons.forEach(function(button, index) {
			var buttonHtml = `
				<div class="war-inline-button-item">
					<input type="text" placeholder="Label do botão" value="${escapeHtml(button.label)}" data-button-label="${index}" />
					<input type="url" placeholder="URL" value="${escapeHtml(button.url)}" data-button-url="${index}" />
					<button type="button" class="war-btn-icon" data-remove-button="${index}">🗑️</button>
				</div>
			`;
			$list.append(buttonHtml);
		});
	}

	function populateAssociations(selectedIds, currentLinkId, $editor) {
		var $list = $editor.find('.war-inline-associations-list');
		$list.empty();

		// Filtrar links (não mostrar o próprio link)
		var availableLinks = allLinks.filter(function(link) {
			return link.id !== parseInt(currentLinkId);
		});

		if (availableLinks.length === 0) {
			$list.html('<p class="war-inline-no-links">Nenhum outro link disponível</p>');
			return;
		}

		availableLinks.forEach(function(link) {
			var isChecked = selectedIds.indexOf(link.id) !== -1;
			var checkboxHtml = `
				<label class="war-inline-checkbox">
					<input type="checkbox" name="associated_ids[]" value="${link.id}" ${isChecked ? 'checked' : ''} />
					<span>${escapeHtml(link.title)}</span>
				</label>
			`;
			$list.append(checkboxHtml);
		});
	}

	function initInlineEditorEvents($editor) {
		// Cancelar
		$editor.find('[data-war-inline-cancel]').on('click', function() {
			$editor.slideUp(300, function() {
				$editor.remove();
			});
		});

		// Salvar
		$editor.find('[data-war-inline-save]').on('click', function() {
			saveInlineEditor($editor);
		});

		// Selecionar imagem
		$editor.find('[data-war-inline-select-image]').on('click', function() {
			openMediaLibrary($editor);
		});

		// Remover imagem
		$editor.find('[data-war-inline-remove-image]').on('click', function() {
			$editor.find('[name="image_url"]').val('');
			$editor.find('.war-inline-image-preview').html('<div class="war-inline-image-placeholder">📷 Sem imagem</div>');
			$(this).hide();
		});

		// Adicionar botão
		$editor.find('[data-war-inline-add-button]').on('click', function() {
			addButtonRow($editor);
		});

		// Remover botão
		$editor.on('click', '[data-remove-button]', function() {
			$(this).closest('.war-inline-button-item').remove();
			updateButtonsPlaceholder($editor);
		});

		// Busca de links
		$editor.find('.war-inline-search').on('input', function() {
			var search = $(this).val().toLowerCase();
			$editor.find('.war-inline-checkbox').each(function() {
				var text = $(this).text().toLowerCase();
				$(this).toggle(text.indexOf(search) !== -1);
			});
		});

		// Toggle de kit (mostrar/ocultar baseado em seleção)
		$editor.on('change', '[name="associated_ids[]"]', function() {
			var hasSelected = $editor.find('[name="associated_ids[]"]:checked').length > 0;
			$editor.find('.war-inline-kit-toggle').toggle(hasSelected);
		});

		// Atualizar texto do toggle
		$editor.on('change', '[name="kit_active"]', function() {
			var isActive = $(this).is(':checked');
			var $text = $(this).closest('.war-toggle-switch').find('.war-toggle-text');
			$text.text(isActive ? 'Ativo (kit visível, componentes ocultos)' : 'Inativo (kit oculto, componentes visíveis)');
		});
	}

	function addButtonRow($editor) {
		var $list = $editor.find('.war-inline-buttons-list');
		var $noButtons = $list.find('.war-inline-no-buttons');
		
		if ($noButtons.length) {
			$noButtons.remove();
		}

		var index = $list.find('.war-inline-button-item').length;
		var buttonHtml = `
			<div class="war-inline-button-item">
				<input type="text" placeholder="Label do botão" value="" data-button-label="${index}" />
				<input type="url" placeholder="URL" value="" data-button-url="${index}" />
				<button type="button" class="war-btn-icon" data-remove-button="${index}">🗑️</button>
			</div>
		`;
		$list.append(buttonHtml);
	}

	function updateButtonsPlaceholder($editor) {
		var $list = $editor.find('.war-inline-buttons-list');
		if ($list.find('.war-inline-button-item').length === 0) {
			$list.html('<p class="war-inline-no-buttons">Nenhum botão configurado</p>');
		}
	}

	function openMediaLibrary($editor) {
		if (!window.wp || !window.wp.media) {
			alert('Media Library não disponível.');
			return;
		}

		if (mediaUploader) {
			mediaUploader.open();
			return;
		}

		mediaUploader = window.wp.media({
			title: 'Selecionar Imagem',
			button: { text: 'Usar esta imagem' },
			multiple: false
		});

		mediaUploader.on('select', function() {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$editor.find('[name="image_url"]').val(attachment.url);
			$editor.find('.war-inline-image-preview').html(`<img src="${attachment.url}" alt="Preview" />`);
			$editor.find('[data-war-inline-remove-image]').show();
		});

		mediaUploader.open();
	}

	function saveInlineEditor($editor) {
		var linkId = $editor.find('[name="link_id"]').val();
		var title = $editor.find('[name="title"]').val();
		var destination = $editor.find('[name="destination"]').val();
		var imageUrl = $editor.find('[name="image_url"]').val();
		var description = $editor.find('[name="description"]').val();
		
		// Coletar botões
		var buttons = [];
		$editor.find('.war-inline-button-item').each(function() {
			var label = $(this).find('[data-button-label]').val();
			var url = $(this).find('[data-button-url]').val();
			if (label && url) {
				buttons.push({ label: label, url: url });
			}
		});

		// Coletar associações
		var associatedIds = [];
		$editor.find('[name="associated_ids[]"]:checked').each(function() {
			associatedIds.push(parseInt($(this).val()));
		});

		// Status do kit
		var kitActive = $editor.find('[name="kit_active"]').is(':checked');

		// Validação básica
		if (!title || !destination) {
			alert('Título e URL Principal são obrigatórios.');
			return;
		}

		// Mostrar loading
		var $saveBtn = $editor.find('[data-war-inline-save]');
		var originalText = $saveBtn.text();
		$saveBtn.prop('disabled', true).text('Salvando...');

		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'war_links_update',
				nonce: nonce,
				id: linkId,
				title: title,
				destination: destination,
				image_url: imageUrl,
				description: description,
				buttons: JSON.stringify(buttons),
				associated_ids: JSON.stringify(associatedIds),
				kit_active: kitActive ? '1' : '0'
			},
			success: function(response) {
				if (response.success) {
					// Fechar editor
					$editor.slideUp(300, function() {
						$editor.remove();
					});
					
					// Recarregar lista
					location.reload();
				} else {
					alert('Erro ao salvar: ' + (response.data && response.data.message ? response.data.message : 'Erro desconhecido'));
					$saveBtn.prop('disabled', false).text(originalText);
				}
			},
			error: function() {
				alert('Erro de conexão.');
				$saveBtn.prop('disabled', false).text(originalText);
			}
		});
	}

	function escapeHtml(text) {
		if (!text) return '';
		var map = {
			'&': '&',
			'<': '<',
			'>': '>',
			'"': '"',
			"'": '&#039;'
		};
		return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// ============================================================================
	// EXCLUIR LINK
	// ============================================================================

	function initDeleteLink() {
		if (!isAdmin) return;

		$(document).on('click', '[data-war-delete-link]', function(e) {
			e.preventDefault();
			var linkId = $(this).attr('data-war-delete-link');
			if (!linkId) return;

			if (!confirm('Tem certeza que deseja excluir este link?')) return;

			var $item = $(this).closest('.war-list-item');

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'war_links_delete',
					nonce: nonce,
					id: linkId
				},
				success: function(response) {
					if (response.success) {
						$item.fadeOut(300, function() {
							$(this).remove();
						});
					} else {
						alert('Erro ao excluir: ' + (response.data && response.data.message ? response.data.message : 'Erro desconhecido'));
					}
				},
				error: function() {
					alert('Erro de conexão.');
				}
			});
		});
	}

	// ============================================================================
	// DRAG AND DROP (REORDENAÇÃO)
	// ============================================================================

	function initDragDrop() {
		if (!isAdmin || typeof $.fn.sortable === 'undefined') return;

		$('.war-list-container').sortable({
			handle: '.war-drag-handle',
			placeholder: 'war-list-item--placeholder',
			update: function() {
				saveLinkOrder($(this));
			}
		});
	}

	function saveLinkOrder($container) {
		var order = [];
		$container.find('.war-list-item').each(function() {
			var linkId = $(this).attr('data-link-id');
			if (linkId) {
				order.push(parseInt(linkId));
			}
		});

		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'war_links_save_order',
				nonce: nonce,
				order: order
			}
		});
	}

	// ============================================================================
	// INICIALIZAÇÃO
	// ============================================================================

	$(document).ready(function() {
		initViewToggle();
		initImageZoom();
		initCopyUrl();
		initEditLink();
		initDeleteLink();
		initDragDrop();
	});

})(jQuery);
