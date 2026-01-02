(function ($) {
  'use strict';

  var cfg = window.WARLinkManager || {};
  var ajaxUrl = cfg.ajax_url || '';
  var nonce = cfg.nonce || '';
  var perPage = typeof cfg.per_page === 'number' ? cfg.per_page : 20;
  var strings = cfg.strings || {};
  var isDebug = !!cfg.debug;
  var goBase = cfg.go_base || '';

  function $find(root, sel) { return root.find(sel); }

  function setStatus(root, text) {
    $find(root, '[data-war-status="1"]').text(text || '');
  }

  function setView(root, view) {
    var $list = root.find('[data-war-view="list"]');
    var $create = root.find('[data-war-view="create"]');
    if (view === 'create') {
      $list.prop('hidden', true);
      $create.prop('hidden', false);
    } else {
      $create.prop('hidden', true);
      $list.prop('hidden', false);
    }
  }

  function ensureDebugUI() {
    if (!isDebug) return null;
    if ($('[data-war-debug="1"]').length) return $('[data-war-debug="1"]');

    var html =
      '<div data-war-debug="1" style="position:fixed;right:12px;bottom:12px;z-index:999999;max-width:560px;width:min(560px,calc(100vw - 24px));">' +
        '<div style="background:#111;color:#fff;border-radius:10px;padding:10px;border:1px solid rgba(255,255,255,0.12);box-shadow:0 10px 30px rgba(0,0,0,0.35);">' +
          '<div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px;">' +
            '<div style="font-weight:800;">WAR Debug</div>' +
            '<div style="display:flex;gap:8px;align-items:center;">' +
              '<button type="button" data-war-debug-copy="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Copiar</button>' +
              '<button type="button" data-war-debug-clear="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Limpar</button>' +
              '<button type="button" data-war-debug-min="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Min</button>' +
            '</div>' +
          '</div>' +
          '<textarea data-war-debug-ta="1" style="width:100%;height:220px;background:#0b0b0b;color:#d1d5db;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:10px;box-sizing:border-box;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\\\"Liberation Mono\\\",\\\"Courier New\\\",monospace;font-size:12px;line-height:1.35;resize:vertical;"></textarea>' +
        '</div>' +
      '</div>';

    $('body').append(html);
    var $dbg = $('[data-war-debug="1"]');
    var $ta = $dbg.find('[data-war-debug-ta="1"]');
    var minimized = false;

    $dbg.on('click', '[data-war-debug-min="1"]', function () {
      minimized = !minimized;
      $ta.css('display', minimized ? 'none' : 'block');
    });

    $dbg.on('click', '[data-war-debug-clear="1"]', function () {
      $ta.val('');
    });

    $dbg.on('click', '[data-war-debug-copy="1"]', function () {
      var text = $ta.val() || '';
      if (!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text);
      } else {
        $ta[0].select();
        try { document.execCommand('copy'); } catch (e) {}
      }
    });

    return $dbg;
  }

  function debugAppend(obj) {
    if (!isDebug) return;
    var $dbg = ensureDebugUI();
    if (!$dbg || !$dbg.length) return;
    var $ta = $dbg.find('[data-war-debug-ta="1"]');
    var current = $ta.val() || '';
    var line = '';
    try { line = JSON.stringify(obj); } catch (e) { line = String(obj); }
    $ta.val((current ? current + "\n" : '') + line);
    $ta.scrollTop($ta[0].scrollHeight);
  }

  function post(action, data) {
    var payload = $.extend({ action: action, nonce: nonce }, data || {});
    debugAppend({ ts: Date.now(), type: 'ajax_request', action: action, payload: payload });
    return $.post(ajaxUrl, payload);
  }

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function (m) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
  }

  function renderRows(root, items) {
    var $tbody = $find(root, '[data-war-rows="1"]');
    if (!items || !items.length) {
      $tbody.html('<div class="war-list__empty">Nenhum link encontrado.</div>');
      return;
    }

    var html = '';
    items.forEach(function (it) {
      var publicUrl = it.public_url || '';
      var dest = it.destination || '';
      var clicks = typeof it.clicks_total === 'number' ? it.clicks_total : parseInt(it.clicks_total, 10) || 0;
      html += '<div class="war-item" data-id="' + escapeHtml(it.id) + '">' +
        '<div class="war-item__left">' +
          '<div class="war-item__title">' + escapeHtml(it.title) + '</div>' +
          '<a class="war-item__link war-mono" href="' + escapeHtml(publicUrl) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(publicUrl) + '</a>' +
          '<div class="war-item__meta">' +
            '<div class="war-mono">#' + escapeHtml(it.id) + ' • ' + escapeHtml(it.slug || '') + '</div>' +
            '<div class="war-mono">' + escapeHtml(dest) + '</div>' +
          '</div>' +
          '<div class="war-item__actions">' +
            '<button type="button" class="war-btn" data-war-copy="' + escapeHtml(publicUrl) + '">Copy</button>' +
            '<button type="button" class="war-btn" data-war-edit="1">Edit</button>' +
            '<button type="button" class="war-btn" data-war-delete="1">Delete</button>' +
          '</div>' +
        '</div>' +
        '<div class="war-item__right">' +
          '<div class="war-clicks__count">' + escapeHtml(clicks) + '</div>' +
          '<div class="war-clicks__label">clicks</div>' +
        '</div>' +
      '</div>';
    });

    $tbody.html(html);
  }

  function renderPagination(root, page, totalPages) {
    var $p = $find(root, '[data-war-pagination="1"]');
    if (!totalPages || totalPages <= 1) {
      $p.html('');
      return;
    }

    var prevDisabled = page <= 1 ? ' disabled' : '';
    var nextDisabled = page >= totalPages ? ' disabled' : '';

    $p.html(
      '<button type="button" class="war-btn" data-war-page="' + (page - 1) + '"' + prevDisabled + '>Anterior</button>' +
      '<span>Página ' + page + ' de ' + totalPages + '</span>' +
      '<button type="button" class="war-btn" data-war-page="' + (page + 1) + '"' + nextDisabled + '>Próxima</button>'
    );
  }

  function loadList(root, page) {
    var search = $find(root, '[data-war-search="1"]').val() || '';
    setStatus(root, 'Carregando...');

    return post('war_links_list', { page: page || 1, per_page: perPage, search: search })
      .done(function (res) {
        debugAppend({ ts: Date.now(), type: 'ajax_response', action: 'war_links_list', response: res });
        if (!res || !res.success) {
          setStatus(root, (res && res.data && res.data.message) ? res.data.message : 'Erro ao carregar.');
          return;
        }
        renderRows(root, res.data.items || []);
        renderPagination(root, res.data.page || 1, res.data.total_pages || 1);
        setStatus(root, '');
        root.data('warPage', res.data.page || 1);
      })
      .fail(function () {
        debugAppend({ ts: Date.now(), type: 'ajax_error', action: 'war_links_list' });
        setStatus(root, 'Erro ao carregar.');
      });
  }

  function resetForm(root) {
    var $form = $find(root, '[data-war-form="1"]');
    $find($form, '[data-war-field="id"]').val('');
    $find($form, '[data-war-field="title"]').val('');
    $find($form, '[data-war-field="slug"]').val('').prop('disabled', false);
    $find($form, '[data-war-field="destination"]').val('');
    $find($form, '[data-war-action="submit"]').text('Create');
    $find($form, '[data-war-action="cancel"]').hide();
    $find(root, '[data-war-form-title="1"]').text('Create');
  }

  function fillFormForEdit(root, item) {
    var $form = $find(root, '[data-war-form="1"]');
    $find($form, '[data-war-field="id"]').val(item.id);
    $find($form, '[data-war-field="title"]').val(item.title || '');
    $find($form, '[data-war-field="slug"]').val(item.slug || '').prop('disabled', true);
    $find($form, '[data-war-field="destination"]').val(item.destination || '');
    $find($form, '[data-war-action="submit"]').text('Update');
    $find($form, '[data-war-action="cancel"]').show();
    $find(root, '[data-war-form-title="1"]').text('Edit');
  }

  function findItemFromRow($tr) {
    return {
      id: parseInt($tr.attr('data-id'), 10) || 0,
      title: $tr.find('.war-item__title').text().trim(),
      slug: ($tr.find('.war-item__meta .war-mono').first().text().split('•')[1] || '').trim(),
      public_url: $tr.find('a').attr('href') || '',
      destination: $tr.find('.war-item__meta .war-mono').eq(1).text().trim()
    };
  }

  function bind(root) {
    ensureDebugUI();
    debugAppend({ ts: Date.now(), type: 'manager_init', ajaxUrl: !!ajaxUrl, nonce: !!nonce, go_base: goBase });

    // initial load
    loadList(root, 1);

    // refresh
    root.on('click', '[data-war-refresh="1"]', function () {
      loadList(root, root.data('warPage') || 1);
    });

    // search
    var searchTimer = null;
    root.on('input', '[data-war-search="1"]', function () {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(function () {
        loadList(root, 1);
      }, 250);
    });

    // open/close create
    root.on('click', '[data-war-open-create="1"]', function () {
      resetForm(root);
      setView(root, 'create');
    });
    root.on('click', '[data-war-close-create="1"]', function () {
      resetForm(root);
      setView(root, 'list');
    });

    // pagination
    root.on('click', '[data-war-page]', function () {
      var p = parseInt($(this).attr('data-war-page'), 10) || 1;
      loadList(root, p);
    });

    // copy
    root.on('click', '[data-war-copy]', function () {
      var text = $(this).attr('data-war-copy') || '';
      if (!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text)
          .then(function () { setStatus(root, strings.copy_ok || 'Copiado!'); })
          .catch(function () { setStatus(root, strings.copy_fail || 'Falha ao copiar.'); });
      } else {
        // fallback
        var $tmp = $('<input>').val(text).appendTo('body').select();
        try { document.execCommand('copy'); setStatus(root, strings.copy_ok || 'Copiado!'); } catch (e) { setStatus(root, strings.copy_fail || 'Falha ao copiar.'); }
        $tmp.remove();
      }
    });

    // edit
    root.on('click', '[data-war-edit="1"]', function () {
      var $tr = $(this).closest('[data-id]');
      fillFormForEdit(root, findItemFromRow($tr));
      setView(root, 'create');
      $('html, body').animate({ scrollTop: root.offset().top - 10 }, 200);
    });

    // delete
    root.on('click', '[data-war-delete="1"]', function () {
      var $tr = $(this).closest('[data-id]');
      var id = parseInt($tr.attr('data-id'), 10) || 0;
      if (!id) return;
      if (!window.confirm(strings.confirm_delete || 'Deletar?')) return;
      setStatus(root, 'Deletando...');
      post('war_links_delete', { id: id })
        .done(function (res) {
          debugAppend({ ts: Date.now(), type: 'ajax_response', action: 'war_links_delete', response: res });
          if (!res || !res.success) {
            setStatus(root, (res && res.data && res.data.message) ? res.data.message : 'Erro ao deletar.');
            return;
          }
          resetForm(root);
          loadList(root, root.data('warPage') || 1);
        })
        .fail(function () { debugAppend({ ts: Date.now(), type: 'ajax_error', action: 'war_links_delete' }); setStatus(root, 'Erro ao deletar.'); });
    });

    // cancel edit
    root.on('click', '[data-war-action="cancel"]', function () {
      resetForm(root);
      setStatus(root, '');
      setView(root, 'list');
    });

    // submit form (create/update)
    root.on('submit', '[data-war-form="1"]', function (e) {
      e.preventDefault();
      var $form = $(this);
      var id = parseInt($find($form, '[data-war-field="id"]').val(), 10) || 0;
      var title = ($find($form, '[data-war-field="title"]').val() || '').trim();
      var slug = ($find($form, '[data-war-field="slug"]').val() || '').trim();
      var destination = ($find($form, '[data-war-field="destination"]').val() || '').trim();

      if (!title || !destination) {
        setStatus(root, 'Preencha título e destino.');
        return;
      }

      var action = id ? 'war_links_update' : 'war_links_create';
      var payload = { id: id, title: title, slug: slug, destination: destination };
      setStatus(root, id ? 'Atualizando...' : 'Criando...');

      post(action, payload)
        .done(function (res) {
          debugAppend({ ts: Date.now(), type: 'ajax_response', action: action, response: res });
          if (!res || !res.success) {
            setStatus(root, (res && res.data && res.data.message) ? res.data.message : 'Erro ao salvar.');
            return;
          }
          resetForm(root);
          loadList(root, 1);
          setStatus(root, 'Salvo.');
          setView(root, 'list');
          window.setTimeout(function () { setStatus(root, ''); }, 1200);
        })
        .fail(function () {
          debugAppend({ ts: Date.now(), type: 'ajax_error', action: action });
          setStatus(root, 'Erro ao salvar.');
        });
    });
  }

  $(function () {
    if (!ajaxUrl || !nonce) {
      return;
    }
    var $root = $('[data-war-manager="1"]');
    if (!$root.length) return;
    bind($root);
  });
})(jQuery);


