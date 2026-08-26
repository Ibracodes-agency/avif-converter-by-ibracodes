/**
 * Queue runners: the bulk converter and the content URL rewriter share the
 * same shape — fetch a queue, process one item per request (visible
 * progress, no timeouts on slow hosts), keep going past individual
 * failures and report them at the end.
 */
(function () {
  'use strict';

  var config = window.iafConfig;
  if (!config) return;

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

  var runner = function (options) {
    var run = document.querySelector(options.run);
    if (!run) return;

    var progress = document.querySelector(options.progress);
    var bar = document.querySelector(options.bar);
    var label = document.querySelector(options.label);

    run.addEventListener('click', function () {
      run.disabled = true;
      progress.hidden = false;
      label.textContent = '…';

      api(options.queue).then(function (queue) {
        var ids = queue.ids || [];
        if (!ids.length) {
          label.textContent = config.labels.empty;
          return;
        }

        var failed = 0;
        var done = 0;

        var next = function () {
          if (done >= ids.length) {
            bar.style.width = '100%';
            label.textContent = config.labels.done + (failed ? ' ' + sprintf(config.labels.failed, failed) : '');
            return;
          }

          label.textContent = sprintf(config.labels.progress, done + 1, ids.length);

          api(options.endpoint, { method: 'POST', body: JSON.stringify({ id: ids[done] }) })
            .then(options.onResult || function () {})
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
  };

  // bulk conversion — updates the bandwidth-saved line
  var savedOut = document.querySelector('[data-iaf-saved]');
  var savedTotal = savedOut ? parseInt(savedOut.dataset.saved, 10) || 0 : 0;

  runner({
    run: '[data-iaf-run]',
    progress: '[data-iaf-progress]',
    bar: '[data-iaf-bar]',
    label: '[data-iaf-label]',
    queue: 'queue',
    endpoint: 'convert',
    onResult: function (result) {
      savedTotal += result.saved || 0;
      if (savedOut) savedOut.textContent = savedOut.textContent.replace(/[\d.,]+\s*(MB|KB|B)/, human(savedTotal));
    }
  });

  // content URL rewriting — updates the URLs-updated counter
  var rewrittenOut = document.querySelector('[data-iaf-rewritten]');
  var rewrittenTotal = 0;
  if (rewrittenOut) {
    var m = rewrittenOut.textContent.match(/\d+/);
    rewrittenTotal = m ? parseInt(m[0], 10) : 0;
  }

  runner({
    run: '[data-iaf-run-rewrite]',
    progress: '[data-iaf-rewrite-progress]',
    bar: '[data-iaf-rewrite-bar]',
    label: '[data-iaf-rewrite-label]',
    queue: 'rewrite-queue',
    endpoint: 'rewrite',
    onResult: function (result) {
      rewrittenTotal += result.replaced || 0;
      if (rewrittenOut) rewrittenOut.textContent = sprintf(rewrittenOut.dataset.template, rewrittenTotal);
    }
  });
})();
