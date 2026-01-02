(function () {
  'use strict';

  var cfg = window.WARRedirect || {};
  var destination = typeof cfg.destination === 'string' ? cfg.destination : '';
  var delayMs = typeof cfg.delayMs === 'number' ? cfg.delayMs : 3000;

  if (!destination) {
    return;
  }

  if (delayMs < 0) {
    delayMs = 0;
  }

  window.setTimeout(function () {
    window.location.replace(destination);
  }, delayMs);
})();


