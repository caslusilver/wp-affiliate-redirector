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

  var isSending = false;

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function nowMs() { return Date.now(); }

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
    return fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams(data).toString()
    }).then(function (r) { return r.json(); });
  }

  function init() {
    var $root = qs('[data-war-ig-chat="1"]');
    if (!$root) return;
    if (!ajaxUrl || !nonce) return;

    var $messages = qs('[data-war-ig-messages="1"]', $root);
    var $input = qs('[data-war-ig-input="1"]', $root);
    var $send = qs('[data-war-ig-send="1"]', $root);

    if (sendIconUrl) {
      $send.innerHTML = '<img src="' + sendIconUrl + '" alt="Enviar" />';
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


