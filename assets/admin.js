/**
 * Bulk converter runner: fetch the queue, convert one attachment per
 * request (visible progress, no timeouts on slow hosts), keep going past
 * individual failures and report them at the end.
 */
(function () {
  'use strict';

  var config = window.iafConfig;
  var run = document.querySelector('[data-iaf-run]');
  if (!run || !config) return;

  var progress = document.querySelector('[data-iaf-progress]');
  var bar = document.querySelector('[data-iaf-bar]');
  var label = document.querySelector('[data-iaf-label]');
  var savedOut = document.querySelector('[data-iaf-saved]');

  var sprintf = function (template) {
    var args = Array.prototype.slice.call(arguments, 1);
    var i = 0;
    return template.replace(/%(\d+\$)?d/g, function (m, pos) {
      return pos ? args[parseInt(pos, 10) - 1] : args[i++];
    });
  };

  var human = function (bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
    return bytes + ' B';
  };

  var api = function (path, options) {
    options = options || {};
    options.headers = Object.assign({ 'X-WP-Nonce': config.nonce, 'Content-Type': 'application/json' }, options.headers);
    return fetch(config.root + path, options).then(function (response) {
      if (!response.ok) throw new Error(String(response.status));
      return response.json();
    });
  };

  run.addEventListener('click', function () {
    run.disabled = true;
    progress.hidden = false;
    label.textContent = '…';

    api('queue').then(function (queue) {
      var ids = queue.ids || [];
      if (!ids.length) {
        label.textContent = config.labels.empty;
        return;
      }

      var saved = parseInt(savedOut ? savedOut.dataset.saved : '0', 10) || 0;
      var failed = 0;
      var done = 0;

      var next = function () {
        if (done >= ids.length) {
          bar.style.width = '100%';
          label.textContent = config.labels.done + (failed ? ' ' + sprintf(config.labels.failed, failed) : '');
          return;
        }

        label.textContent = sprintf(config.labels.progress, done + 1, ids.length);

        api('convert', { method: 'POST', body: JSON.stringify({ id: ids[done] }) })
          .then(function (result) {
            saved += result.saved || 0;
            if (savedOut) savedOut.textContent = savedOut.textContent.replace(/[\d.,]+\s*(MB|KB|B)/, human(saved));
          })
          .catch(function () { failed++; })
          .finally(function () {
            done++;
            bar.style.width = Math.round((done / ids.length) * 100) + '%';
            next();
          });
      };

      next();
    }).catch(function () {
      label.textContent = 'API error';
      run.disabled = false;
    });
  });
})();
