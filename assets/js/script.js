(function () {
  'use strict';

  var cfg = window.WARRedirect || {};
  var destination = typeof cfg.destination === 'string' ? cfg.destination : '';
  var delayMs = typeof cfg.delayMs === 'number' ? cfg.delayMs : 3000;
  var isDebug = !!cfg.debug;

  function ensureDebugUI() {
    if (!isDebug) return null;
    if (document.querySelector('[data-war-redirect-debug="1"]')) {
      return document.querySelector('[data-war-redirect-debug="1"]');
    }

    var wrap = document.createElement('div');
    wrap.setAttribute('data-war-redirect-debug', '1');
    wrap.style.position = 'fixed';
    wrap.style.right = '12px';
    wrap.style.bottom = '12px';
    wrap.style.zIndex = '999999';
    wrap.style.maxWidth = '560px';
    wrap.style.width = 'min(560px, calc(100vw - 24px))';

    wrap.innerHTML =
      '<div style="background:#111;color:#fff;border-radius:10px;padding:10px;border:1px solid rgba(255,255,255,0.12);box-shadow:0 10px 30px rgba(0,0,0,0.35);">' +
        '<div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px;">' +
          '<div style="font-weight:800;">WAR Debug (redirect)</div>' +
          '<div style="display:flex;gap:8px;align-items:center;">' +
            '<button type="button" data-war-rdbg-copy="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Copiar</button>' +
            '<button type="button" data-war-rdbg-clear="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Limpar</button>' +
            '<button type="button" data-war-rdbg-min="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Min</button>' +
          '</div>' +
        '</div>' +
        '<textarea data-war-rdbg-ta="1" style="width:100%;height:220px;background:#0b0b0b;color:#d1d5db;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:10px;box-sizing:border-box;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\\\"Liberation Mono\\\",\\\"Courier New\\\",monospace;font-size:12px;line-height:1.35;resize:vertical;"></textarea>' +
      '</div>';

    document.body.appendChild(wrap);

    var ta = wrap.querySelector('[data-war-rdbg-ta="1"]');
    var minimized = false;
    wrap.addEventListener('click', function (e) {
      var t = e.target;
      if (!(t instanceof HTMLElement)) return;

      if (t.matches('[data-war-rdbg-min="1"]')) {
        minimized = !minimized;
        ta.style.display = minimized ? 'none' : 'block';
      }

      if (t.matches('[data-war-rdbg-clear="1"]')) {
        ta.value = '';
      }

      if (t.matches('[data-war-rdbg-copy="1"]')) {
        var text = ta.value || '';
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text);
        } else {
          ta.select();
          try { document.execCommand('copy'); } catch (_) {}
        }
      }
    });

    return wrap;
  }

  function debugAppend(obj) {
    if (!isDebug) return;
    var ui = ensureDebugUI();
    if (!ui) return;
    var ta = ui.querySelector('[data-war-rdbg-ta="1"]');
    if (!ta) return;
    var line = '';
    try { line = JSON.stringify(obj); } catch (e) { line = String(obj); }
    ta.value = (ta.value ? ta.value + '\n' : '') + line;
    ta.scrollTop = ta.scrollHeight;
  }

  if (!destination) {
    return;
  }

  if (delayMs < 0) {
    delayMs = 0;
  }

  debugAppend({
    ts: Date.now(),
    type: 'redirect_init',
    destination: destination,
    delayMs: delayMs,
    postId: cfg.postId || null,
    slug: cfg.slug || null,
    clicksTotal: cfg.clicksTotal || null
  });

  window.setTimeout(function () {
    debugAppend({ ts: Date.now(), type: 'redirect_now', destination: destination });
    window.location.replace(destination);
  }, delayMs);
})();


