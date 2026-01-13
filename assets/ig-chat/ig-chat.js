(function () {
  'use strict';

  var cfg = window.WARIgChat || {};
  var ajaxUrl = cfg.ajax_url || '';
  var nonce = cfg.nonce || '';
  var pageSlug = cfg.page_slug || '';
  var initialMessage = cfg.initial_message || '';
  var errorMessage = cfg.error_message || 'Tenta de novo.';
  var sendIconUrl = cfg.send_icon_url || '';
  var rateLimitMs = typeof cfg.rate_limit_ms === 'number' ? cfg.rate_limit_ms : 2500;
  var minimizeEnabled = !!cfg.minimize_enabled;
  var isDebug = !!cfg.debug;

  var isSending = false;

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function nowMs() { return Date.now(); }

  function ensureDebugUI() {
    if (!isDebug) return null;
    var existing = qs('[data-war-ig-debug="1"]');
    if (existing) return existing;

    var wrap = document.createElement('div');
    wrap.setAttribute('data-war-ig-debug', '1');
    wrap.style.position = 'fixed';
    wrap.style.left = '12px';
    wrap.style.bottom = '12px';
    wrap.style.zIndex = '999999';
    wrap.style.maxWidth = '560px';
    wrap.style.width = 'min(560px, calc(100vw - 24px))';

    wrap.innerHTML =
      '<div style="background:#111;color:#fff;border-radius:10px;padding:10px;border:1px solid rgba(255,255,255,0.12);box-shadow:0 10px 30px rgba(0,0,0,0.35);">' +
        '<div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px;">' +
          '<div style="font-weight:800;">WAR Chat Debug</div>' +
          '<div style="display:flex;gap:8px;align-items:center;">' +
            '<button type="button" data-war-ig-debug-copy="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Copiar</button>' +
            '<button type="button" data-war-ig-debug-clear="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Limpar</button>' +
            '<button type="button" data-war-ig-debug-min="1" style="border:0;border-radius:999px;padding:6px 10px;font-weight:700;cursor:pointer;">Min</button>' +
          '</div>' +
        '</div>' +
        '<textarea data-war-ig-debug-ta="1" style="width:100%;height:220px;background:#0b0b0b;color:#d1d5db;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:10px;box-sizing:border-box;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\\\"Liberation Mono\\\",\\\"Courier New\\\",monospace;font-size:12px;line-height:1.35;resize:vertical;"></textarea>' +
      '</div>';

    document.body.appendChild(wrap);

    var minimized = false;
    var ta = qs('[data-war-ig-debug-ta="1"]', wrap);
    wrap.addEventListener('click', function (e) {
      var t = e.target;
      if (!t || !t.getAttribute) return;
      if (t.getAttribute('data-war-ig-debug-min') === '1') {
        minimized = !minimized;
        if (ta) ta.style.display = minimized ? 'none' : 'block';
      }
      if (t.getAttribute('data-war-ig-debug-clear') === '1') {
        if (ta) ta.value = '';
      }
      if (t.getAttribute('data-war-ig-debug-copy') === '1') {
        var text = (ta && ta.value) ? ta.value : '';
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text);
        } else if (ta) {
          ta.select();
          try { document.execCommand('copy'); } catch (err) {}
        }
      }
    });

    return wrap;
  }

  function debugAppend(obj) {
    if (!isDebug) return;
    var wrap = ensureDebugUI();
    if (!wrap) return;
    var ta = qs('[data-war-ig-debug-ta="1"]', wrap);
    if (!ta) return;
    var current = ta.value || '';
    var line = '';
    try { line = JSON.stringify(obj); } catch (e) { line = String(obj); }
    ta.value = (current ? current + "\n" : '') + line;
    ta.scrollTop = ta.scrollHeight;
  }

  function storageKey() {
    return 'war_ig_chat:' + (pageSlug || location.pathname || 'default');
  }

  function getSessionId() {
    var key = storageKey() + ':session_id';
    var sid = sessionStorage.getItem(key);
    if (sid) return sid;
    sid = 's_' + Math.random().toString(36).slice(2) + '_' + nowMs();
    sessionStorage.setItem(key, sid);
    return sid;
  }

  function saveHistory(items) {
    try {
      sessionStorage.setItem(storageKey(), JSON.stringify(items || []));
    } catch (e) {}
  }

  function loadHistory() {
    try {
      var raw = sessionStorage.getItem(storageKey());
      if (!raw) return null;
      var parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : null;
    } catch (e) {
      return null;
    }
  }

  function scrollToBottom($messages) {
    if (!$messages) return;
    $messages.scrollTop = $messages.scrollHeight;
  }

  function el(tag, cls) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    return n;
  }

  function renderTyping() {
    var row = el('div', 'war-ig-row war-ig-row--in');
    var bubble = el('div', 'war-ig-bubble war-ig-bubble--in');
    var typing = el('div', 'war-ig-typing');
    typing.innerHTML = '<span></span><span></span><span></span>';
    bubble.appendChild(typing);
    row.appendChild(bubble);
    row.setAttribute('data-war-ig-typing', '1');
    return row;
  }

  function renderOutText(text) {
    var row = el('div', 'war-ig-row war-ig-row--out');
    var bubble = el('div', 'war-ig-bubble war-ig-bubble--out');
    bubble.textContent = text;
    row.appendChild(bubble);
    return row;
  }

  function renderInText(text) {
    var row = el('div', 'war-ig-row war-ig-row--in');
    var bubble = el('div', 'war-ig-bubble war-ig-bubble--in');
    var t = el('div', 'war-ig-text');
    t.textContent = text;
    bubble.appendChild(t);
    row.appendChild(bubble);
    return row;
  }

  function renderInCard(payload) {
    var row = el('div', 'war-ig-row war-ig-row--in');
    var bubble = el('div', 'war-ig-bubble war-ig-bubble--in');

    if (payload && payload.subtitle) {
      var sub = el('div', 'war-ig-muted');
      sub.textContent = payload.subtitle;
      bubble.appendChild(sub);
    }

    var card = el('div', 'war-ig-card');
    var body = el('div', 'war-ig-card__body');

    var headline = el('div', 'war-ig-card__headline');
    headline.textContent = (payload && payload.headline) ? payload.headline : '✅ Link liberado';
    body.appendChild(headline);

    if (payload && payload.subtitle) {
      var subtitle = el('div', 'war-ig-card__subtitle');
      subtitle.textContent = payload.subtitle;
      body.appendChild(subtitle);
    }

    var btn = document.createElement('a');
    btn.className = 'war-ig-card__btn';
    btn.href = (payload && payload.button_url) ? payload.button_url : '#';
    btn.target = '_blank';
    btn.rel = 'noopener noreferrer';
    btn.textContent = (payload && payload.button_text) ? payload.button_text : 'Toque aqui p/ acessar';
    body.appendChild(btn);

    card.appendChild(body);
    bubble.appendChild(card);
    row.appendChild(bubble);
    return row;
  }

  function historyToDom($messages, history) {
    if (!history || !history.length) return;
    history.forEach(function (m) {
      if (!m || !m.type) return;
      if (m.type === 'out') $messages.appendChild(renderOutText(m.text || ''));
      if (m.type === 'in_text') $messages.appendChild(renderInText(m.text || ''));
      if (m.type === 'in_card') $messages.appendChild(renderInCard(m.payload || {}));
    });
  }

  function postAjax(data) {
    debugAppend({ ts: nowMs(), type: 'ajax_request', payload: data });
    return fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams(data).toString()
    }).then(function (r) { return r.json(); }).then(function (json) {
      debugAppend({ ts: nowMs(), type: 'ajax_response', response: json });
      return json;
    });
  }

  function ensureBubble($root) {
    if (!$root) return null;
    var existing = qs('[data-war-ig-bubble="1"]');
    if (existing) return existing;

    var btn = el('button', 'war-ig-bubble');
    btn.type = 'button';
    btn.setAttribute('data-war-ig-bubble', '1');
    btn.setAttribute('aria-label', 'Abrir chat');

    // Prefer avatar do header.
    var $avatarImg = qs('.war-ig-avatar img', $root);
    if ($avatarImg && $avatarImg.getAttribute('src')) {
      var img = document.createElement('img');
      img.src = $avatarImg.getAttribute('src');
      img.alt = 'Chat';
      btn.appendChild(img);
    } else {
      var nameEl = qs('.war-ig-name', $root);
      var initial = (nameEl && nameEl.textContent) ? String(nameEl.textContent).trim().slice(0, 1).toUpperCase() : 'C';
      var fb = el('div', 'war-ig-bubble__fallback');
      fb.textContent = initial;
      btn.appendChild(fb);
    }

    document.body.appendChild(btn);
    return btn;
  }

  function setMinimized($root, minimized) {
    if (!$root) return;
    if (minimized) {
      $root.classList.add('war-ig-chat--minimized');
    } else {
      $root.classList.remove('war-ig-chat--minimized');
    }
  }

  function init() {
    var $root = qs('[data-war-ig-chat="1"]');
    if (!$root) return;
    if (!ajaxUrl || !nonce) return;

    debugAppend({
      ts: nowMs(),
      type: 'build_info',
      plugin_version: cfg.build_version || null,
      js_ver: cfg.build_js_ver || null,
      css_ver: cfg.build_css_ver || null
    });
    debugAppend({ ts: nowMs(), type: 'chat_init', ajaxUrl: !!ajaxUrl, nonce: !!nonce, minimizeEnabled: !!minimizeEnabled });

    var $messages = qs('[data-war-ig-messages="1"]', $root);
    var $input = qs('[data-war-ig-input="1"]', $root);
    var $send = qs('[data-war-ig-send="1"]', $root);
    var $back = qs('.war-ig-back', $root);

    if (sendIconUrl) {
      $send.innerHTML = '<img src="' + sendIconUrl + '" alt="Enviar" />';
    }

    // Minimize UX (opcional, via admin)
    if (minimizeEnabled && $back) {
      $back.disabled = false;
      $back.style.cursor = 'pointer';
      $back.setAttribute('aria-label', 'Minimizar');

      var $bubble = ensureBubble($root);
      if ($bubble) {
        $bubble.addEventListener('click', function () {
          setMinimized($root, false);
          // foco no input ao reabrir
          setTimeout(function () { if ($input) $input.focus(); }, 60);
          debugAppend({ ts: nowMs(), type: 'minimize_restore' });
        });
      }

      $back.addEventListener('click', function () {
        setMinimized($root, true);
        debugAppend({ ts: nowMs(), type: 'minimize_to_bubble' });
      });
    }

    // Load history
    var history = loadHistory();
    if (history && $messages) {
      $messages.innerHTML = '';
      historyToDom($messages, history);
      scrollToBottom($messages);
    } else {
      // keep initial server-rendered message, if any
      scrollToBottom($messages);
    }

    function setSending(state) {
      isSending = !!state;
      if ($send) $send.disabled = isSending;
      if ($input) $input.disabled = isSending;
    }

    function getKeyword() {
      return ($input && $input.value) ? String($input.value).trim() : '';
    }

    function appendAndPersist(entry, domNode) {
      if (domNode) $messages.appendChild(domNode);
      var current = loadHistory() || [];
      current.push(entry);
      saveHistory(current);
      scrollToBottom($messages);
    }

    function removeTyping() {
      var t = qs('[data-war-ig-typing="1"]', $messages);
      if (t && t.parentNode) t.parentNode.removeChild(t);
    }

    function send() {
      var keyword = getKeyword();
      if (!keyword) {
        if ($input) {
          $input.focus();
          $input.style.transform = 'translateX(-2px)';
          setTimeout(function () { $input.style.transform = ''; }, 120);
        }
        return;
      }
      if (isSending) return;

      // rate limit (client-side best-effort)
      var lastKey = storageKey() + ':last_send';
      var last = parseInt(sessionStorage.getItem(lastKey) || '0', 10) || 0;
      if (nowMs() - last < rateLimitMs) return;
      sessionStorage.setItem(lastKey, String(nowMs()));

      setSending(true);
      if ($input) $input.value = '';

      appendAndPersist({ type: 'out', text: keyword, ts: nowMs() }, renderOutText(keyword));
      $messages.appendChild(renderTyping());
      scrollToBottom($messages);

      var payload = {
        action: 'war_ig_chat_send',
        nonce: nonce,
        keyword: keyword,
        page_slug: pageSlug || location.pathname,
        session_id: getSessionId(),
        timestamp: String(nowMs()),
        referrer: document.referrer || ''
      };

      postAjax(payload)
        .then(function (res) {
          removeTyping();
          if (!res || !res.success) {
            appendAndPersist({ type: 'in_text', text: errorMessage, ts: nowMs() }, renderInText(errorMessage));
            return;
          }
          appendAndPersist({ type: 'in_card', payload: res.data || {}, ts: nowMs() }, renderInCard(res.data || {}));
        })
        .catch(function () {
          removeTyping();
          appendAndPersist({ type: 'in_text', text: errorMessage, ts: nowMs() }, renderInText(errorMessage));
        })
        .finally(function () {
          setSending(false);
        });
    }

    $send.addEventListener('click', send);
    $input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        send();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', init);
})();


