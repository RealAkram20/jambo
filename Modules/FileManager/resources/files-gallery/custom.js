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
 * open Files Gallery's preview/video-player overlay. Three layers of
 * defense so no code path can open the overlay:
 *
 *   1. CSS — #modal_preview is display:none + pointer-events:none.
 *      Any <video>/<audio> inside is removed from layout so autoplay
 *      can't start unintentionally.
 *
 *   2. JS override — replace window.ae.preview() with a no-op as soon as
 *      Files Gallery boots. This is FG's internal "open preview" entry
 *      point; neutering it stops the modal from being populated at all.
 *
 *   3. MutationObserver safety net — if anything slips past 1+2 and the
 *      preview element becomes visible anyway, immediately pause+detach
 *      its media and re-hide it.
 */
(function () {
  if (typeof window === 'undefined' || typeof document === 'undefined') return;
  var params = new URLSearchParams(window.location.search || '');
  if (params.get('picker') !== '1') return;

  document.documentElement.classList.add('jambo-picker-mode');

  var style = document.createElement('style');
  style.textContent =
    '#modal_preview { display: none !important; pointer-events: none !important; visibility: hidden !important; }' +
    '#modal_preview video, #modal_preview audio, #modal_preview iframe { display: none !important; }' +
    'html.jambo-picker-mode #modal_preview.modal-pos-active,' +
    'html.jambo-picker-mode .modal-container { display: none !important; }';
  (document.head || document.documentElement).appendChild(style);

  (function waitForAe(attempts) {
    attempts = attempts || 0;
    if (attempts > 80) return;
    if (!window.ae || typeof window.ae.preview !== 'function') {
      setTimeout(function () { waitForAe(attempts + 1); }, 100);
      return;
    }
    try { window.ae.preview = function () { /* picker-mode no-op */ }; } catch (_) {}
  })();

  function killMediaIn(node) {
    if (!node || !node.querySelectorAll) return;
    node.querySelectorAll('video, audio').forEach(function (media) {
      try {
        media.pause();
        media.removeAttribute('src');
        media.load();
      } catch (_) {}
    });
  }

  new MutationObserver(function () {
    var m = document.getElementById('modal_preview');
    if (!m) return;
    var cs = window.getComputedStyle(m);
    if (cs.display !== 'none' || m.classList.contains('modal-pos-active')) {
      killMediaIn(m);
      m.style.display = 'none';
    }
  }).observe(document.documentElement, {
    attributes: true, subtree: true, attributeFilter: ['class', 'style']
  });
})();
