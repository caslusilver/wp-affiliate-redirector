(function ($) {
  'use strict';

  var cfg = window.WARLinkManager || {};
  var ajaxUrl = cfg.ajax_url || '';
  var nonce = cfg.nonce || '';
  var perPage = typeof cfg.per_page === 'number' ? cfg.per_page : 20;
  var strings = cfg.strings || {};

  function $(root, sel) { return root.find(sel); }

  function setStatus(root, text) {
    $('[data-war-status="1"]', root).text(text || '');
  }

  function post(action, data) {
    return $.post(ajaxUrl, $.extend({ action: action, nonce: nonce }, data || {}));
  }

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function (m) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
  }

  function renderRows(root, items) {
    var $tbody = $('[data-war-rows="1"]', root);
    if (!items || !items.length) {
      $tbody.html('<tr><td colspan="4">Nenhum link encontrado.</td></tr>');
      return;
    }

    var html = '';
    items.forEach(function (it) {
      var publicUrl = it.public_url || '';
      var dest = it.destination || '';
      html += '<tr data-id="' + escapeHtml(it.id) + '">' +
        '<td>' + escapeHtml(it.title) + '<br/><span class="war-mono">#' + escapeHtml(it.id) + ' • ' + escapeHtml(it.slug || '') + '</span></td>' +
        '<td>' +
          '<a class="war-mono" href="' + escapeHtml(publicUrl) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(publicUrl) + '</a>' +
          '<div class="war-actions" style="margin-top:6px;">' +
            '<button type="button" class="button button-small" data-war-copy="' + escapeHtml(publicUrl) + '">Copiar</button>' +
          '</div>' +
        '</td>' +
        '<td><span class="war-mono">' + escapeHtml(dest) + '</span></td>' +
        '<td>' +
          '<div class="war-actions">' +
            '<button type="button" class="button button-small" data-war-edit="1">Editar</button>' +
            '<button type="button" class="button button-small button-link-delete" data-war-delete="1">Deletar</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    $tbody.html(html);
  }

  function renderPagination(root, page, totalPages) {
    var $p = $('[data-war-pagination="1"]', root);
    if (!totalPages || totalPages <= 1) {
      $p.html('');
      return;
    }

    var prevDisabled = page <= 1 ? ' disabled' : '';
    var nextDisabled = page >= totalPages ? ' disabled' : '';

    $p.html(
      '<button type="button" class="button" data-war-page="' + (page - 1) + '"' + prevDisabled + '>Anterior</button>' +
      '<span>Página ' + page + ' de ' + totalPages + '</span>' +
      '<button type="button" class="button" data-war-page="' + (page + 1) + '"' + nextDisabled + '>Próxima</button>'
    );
  }

  function loadList(root, page) {
    var search = $('[data-war-search="1"]', root).val() || '';
    setStatus(root, 'Carregando...');

    return post('war_links_list', { page: page || 1, per_page: perPage, search: search })
      .done(function (res) {
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
        setStatus(root, 'Erro ao carregar.');
      });
  }

  function resetForm(root) {
    var $form = $('[data-war-form="1"]', root);
    $('[data-war-field="id"]', $form).val('');
    $('[data-war-field="title"]', $form).val('');
    $('[data-war-field="slug"]', $form).val('').prop('disabled', false);
    $('[data-war-field="destination"]', $form).val('');
    $('[data-war-action="submit"]', $form).text('Criar');
    $('[data-war-action="cancel"]', $form).hide();
  }

  function fillFormForEdit(root, item) {
    var $form = $('[data-war-form="1"]', root);
    $('[data-war-field="id"]', $form).val(item.id);
    $('[data-war-field="title"]', $form).val(item.title || '');
    $('[data-war-field="slug"]', $form).val(item.slug || '').prop('disabled', true);
    $('[data-war-field="destination"]', $form).val(item.destination || '');
    $('[data-war-action="submit"]', $form).text('Atualizar');
    $('[data-war-action="cancel"]', $form).show();
  }

  function findItemFromRow($tr) {
    return {
      id: parseInt($tr.attr('data-id'), 10) || 0,
      title: $tr.find('td').eq(0).contents().first().text().trim(),
      slug: ($tr.find('.war-mono').first().text().split('•')[1] || '').trim(),
      public_url: $tr.find('a').attr('href') || '',
      destination: $tr.find('td').eq(2).text().trim()
    };
  }

  function bind(root) {
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
      var $tr = $(this).closest('tr');
      fillFormForEdit(root, findItemFromRow($tr));
      $('html, body').animate({ scrollTop: root.offset().top - 20 }, 200);
    });

    // delete
    root.on('click', '[data-war-delete="1"]', function () {
      var $tr = $(this).closest('tr');
      var id = parseInt($tr.attr('data-id'), 10) || 0;
      if (!id) return;
      if (!window.confirm(strings.confirm_delete || 'Deletar?')) return;
      setStatus(root, 'Deletando...');
      post('war_links_delete', { id: id })
        .done(function (res) {
          if (!res || !res.success) {
            setStatus(root, (res && res.data && res.data.message) ? res.data.message : 'Erro ao deletar.');
            return;
          }
          resetForm(root);
          loadList(root, root.data('warPage') || 1);
        })
        .fail(function () { setStatus(root, 'Erro ao deletar.'); });
    });

    // cancel edit
    root.on('click', '[data-war-action="cancel"]', function () {
      resetForm(root);
      setStatus(root, '');
    });

    // submit form (create/update)
    root.on('submit', '[data-war-form="1"]', function (e) {
      e.preventDefault();
      var $form = $(this);
      var id = parseInt($('[data-war-field="id"]', $form).val(), 10) || 0;
      var title = ($('[data-war-field="title"]', $form).val() || '').trim();
      var slug = ($('[data-war-field="slug"]', $form).val() || '').trim();
      var destination = ($('[data-war-field="destination"]', $form).val() || '').trim();

      if (!title || !destination) {
        setStatus(root, 'Preencha título e destino.');
        return;
      }

      var action = id ? 'war_links_update' : 'war_links_create';
      var payload = { id: id, title: title, slug: slug, destination: destination };
      setStatus(root, id ? 'Atualizando...' : 'Criando...');

      post(action, payload)
        .done(function (res) {
          if (!res || !res.success) {
            setStatus(root, (res && res.data && res.data.message) ? res.data.message : 'Erro ao salvar.');
            return;
          }
          resetForm(root);
          loadList(root, 1);
          setStatus(root, 'Salvo.');
          window.setTimeout(function () { setStatus(root, ''); }, 1200);
        })
        .fail(function () {
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


