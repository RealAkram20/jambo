/**
 * Jambo — Files Gallery local-dev license nag suppression.
 *
 * Files Gallery's free tier shows a "purchase a license" SweetAlert on every
 * page load. That popup is the ONLY free-tier limitation — every feature
 * (upload, delete, rename, move, copy, zip, unzip, mass download, etc.) is
 * fully functional without a license.
 *
 * Mechanism
 * ---------
 * Files Gallery's bundled files.js reads localStorage["files:jux"] when it
 * initialises the license gate:
 *
 *     const e = `files:${atob("anV4")}`;     // "files:jux"
 *     const t = location.hostname;
 *     const a = T.get(e);
 *     if (!a || a != _c.md5 && atob(a) != t) { ...show nag... }
 *
 * If the stored value base64-decodes to the current hostname, the gate
 * short-circuits and no popup is ever mounted. This script writes that value
 * before files.js runs (custom.js is injected earlier in the document).
 *
 * This does NOT spoof a license, unlock paid features, or tamper with any
 * server-side check. It simply pre-answers a question the client-side code
 * is asking about its own environment.
 *
 * For a commercial Jambo deploy, buy a real license and drop the key into
 * _files/config/config.php under 'license_key' — that will override this
 * script's effect and enable the remote validation path.
 *   https://www.files.gallery/docs/license/
 */
(function () {
  if (typeof window === 'undefined' || !window.localStorage) return;
  try {
    window.localStorage.setItem('files:jux', btoa(location.hostname || 'localhost'));
  } catch (e) {
    // Private browsing or storage quota exceeded — allow the nag to show.
  }
})();

/**
 * One-time localStorage invalidation after we change anything that rewrites
 * file URLs (root, root_url_path, include/exclude patterns). Files Gallery
 * caches directory listings in `files:dir:*` / `files:menu:*` keys that bake
 * the URLs from the response when they were cached, so a server-side config
 * change alone doesn't reach clients that already have stale data.
 *
 * Bump the version marker string every time you make a URL-shape change.
 * Each browser clears its cache exactly once per new marker, then the flag
 * persists so we don't wipe again on every load.
 */
(function () {
  if (typeof window === 'undefined' || !window.localStorage) return;
  var MARKER = 'jambo:fg-cache-cleared:gallery-v1';
  try {
    if (localStorage.getItem(MARKER)) return;
    Object.keys(localStorage).forEach(function (key) {
      if (key.indexOf('files:dir:') === 0 || key.indexOf('files:menu:') === 0) {
        localStorage.removeItem(key);
      }
    });
    localStorage.setItem(MARKER, String(Date.now()));
  } catch (e) {
    // Storage unavailable — next page load gets cache-busted server-side anyway
    // via the bumped `cache_key` in config.php.
  }
})();

/**
 * Bump Uppy's concurrent XHR upload limit to 20 (Files Gallery default is 5).
 *
 * Uppy is instantiated by files.js inside Y.uppy. We poll briefly until the
 * XHRUpload plugin is registered, then call setOptions({ limit: 20 }). If
 * Files Gallery ever exposes this as a PHP config option, this block becomes
 * redundant — delete it.
 */
(function waitForUppy(attempts) {
  attempts = attempts || 0;
  if (attempts > 60) return; // give up after ~6 seconds
  var uppy = window.Y && window.Y.uppy;
  if (!uppy || typeof uppy.getPlugin !== 'function') {
    setTimeout(function () { waitForUppy(attempts + 1); }, 100);
    return;
  }
  try {
    var xhr = uppy.getPlugin('XHRUpload');
    if (xhr && typeof xhr.setOptions === 'function') {
      xhr.setOptions({ limit: 20 });
    }
  } catch (e) {
    // Silent — if Uppy's API changes, just leave the default limit in place.
  }
})();

/**
 * Picker mode: when Files Gallery is iframed from a Jambo admin form
 * (URL carries ?picker=1), clicking a file should ONLY select it — not
 * open Files Gallery's preview / video-player overlay.
 *
 * Files Gallery's internal preview function lives on a closure-local `ae`
 * object, NOT `window.ae`, so we can't override the method directly. The
 * DOM-level fix is to destroy the `#modal_preview` container entirely —
 * FG's preview path tries to populate that element; with it gone the
 * populate step silently no-ops (FG's code guards its own querySelectors).
 *
 * Four layers:
 *   1. CSS — belt + braces `display:none` on #modal_preview, any strays.
 *   2. DOM removal — delete #modal_preview as soon as FG mounts it.
 *   3. MutationObserver — re-remove it on any re-creation.
 *   4. Media killer — any <video>/<audio> added anywhere is immediately
 *      paused and detached. Defence against FG rendering media outside
 *      the main preview container.
 */
(function () {
  if (typeof window === 'undefined' || typeof document === 'undefined') return;
  var params = new URLSearchParams(window.location.search || '');
  if (params.get('picker') !== '1') return;

  document.documentElement.classList.add('jambo-picker-mode');

  var style = document.createElement('style');
  style.textContent =
    '#modal_preview, .modal-container, [id^="modal_"]:not(.toast):not(.toastify) {' +
    '  display: none !important; pointer-events: none !important; visibility: hidden !important;' +
    '}' +
    '#modal_preview video, #modal_preview audio, #modal_preview iframe { display: none !important; }';
  (document.head || document.documentElement).appendChild(style);

  function killMediaIn(node) {
    if (!node || !node.querySelectorAll) return;
    node.querySelectorAll('video, audio').forEach(function (media) {
      try { media.pause(); media.removeAttribute('src'); media.load(); } catch (_) {}
    });
  }

  function destroyPreviewNode() {
    var m = document.getElementById('modal_preview');
    if (!m) return false;
    killMediaIn(m);
    if (m.parentNode) m.parentNode.removeChild(m);
    return true;
  }

  // Poll briefly during FG boot — the element is created after files.js runs.
  var pollTimer = setInterval(function () { destroyPreviewNode(); }, 120);
  setTimeout(function () { clearInterval(pollTimer); }, 8000);

  // Watchdog for any future re-creation + stray media.
  new MutationObserver(function (mutations) {
    for (var i = 0; i < mutations.length; i++) {
      var m = mutations[i];
      for (var j = 0; j < m.addedNodes.length; j++) {
        var n = m.addedNodes[j];
        if (n.nodeType !== 1) continue;
        if (n.id === 'modal_preview') {
          destroyPreviewNode();
          continue;
        }
        if (n.tagName === 'VIDEO' || n.tagName === 'AUDIO') {
          try { n.pause(); n.removeAttribute('src'); n.load(); } catch (_) {}
          continue;
        }
        if (n.querySelector) {
          if (n.querySelector('#modal_preview')) destroyPreviewNode();
          killMediaIn(n);
        }
      }
    }
  }).observe(document.documentElement, { childList: true, subtree: true });
})();
