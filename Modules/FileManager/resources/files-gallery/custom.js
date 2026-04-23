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

  // Strip every Files Gallery UI control that distracts from the one job the
  // picker has: browse folders, click a file, confirm in the parent modal.
  //
  // Critical lesson: Files Gallery's actual file-preview layer is a
  // PhotoSwipe lightbox at `.pswp` (classes like `pswp--open`), NOT
  // `#modal_preview`. `#modal_preview` is an unrelated code/text viewer
  // that `pswp` ignores. When the admin clicks a file FG calls
  // `pswp.open(...)` which toggles `.pswp--open` on the lightbox root
  // and `.popup-open` on `<body>`. Everything below needs to address
  // the real `.pswp` tree, not the legacy `#modal_preview` shell.
  var style = document.createElement('style');
  style.textContent = [
    // 1. The actual preview lightbox (PhotoSwipe). Hiding .pswp kills the
    //    whole image/video/panorama/audio viewer chain — nothing visible,
    //    nothing interactive, no media to autoplay.
    '.pswp, .pswp--open { display: none !important; visibility: hidden !important; pointer-events: none !important; }',
    '.pswp video, .pswp audio, .pswp iframe, .pswp .pswp__video { display: none !important; }',

    // 2. When pswp is open FG locks body scroll with `.popup-open`. Keep
    //    scroll unlocked so the sidebar stays usable even if any part of
    //    the lightbox flashes on-screen before we remove it.
    'body.popup-open { overflow: auto !important; }',

    // 3. Every popup UI overlay pswp mounts — filename, exif, location,
    //    description, keywords, panorama/video UI.
    '.popup-title, .popup-basename, .popup-date, .popup-description,' +
      ' .popup-exif, .popup-image-meta, .popup-keywords, .popup-location,' +
      ' .popup-owner, .popup-pano-placeholder, .popup-counter-separator,' +
      ' .popup-ui-pano, .popup-ui-video, .popup-video { display: none !important; }',

    // 4. Legacy code/text preview modal — kept hidden for safety.
    '#modal_preview { display: none !important; visibility: hidden !important; pointer-events: none !important; }',
    '#modal_preview video, #modal_preview audio, #modal_preview iframe { display: none !important; }',
    '#audioplayer, [class*="audioplayer"] { display: none !important; }',

    // 5. File-details popover in topbar (maximize/fullscreen/more button).
    '#topbar-info { display: none !important; }',

    // 6. FG\'s own selection-action bar (bulk delete/zip/move).
    '.topbar-select, .buttons-selected, #select-mode-button { display: none !important; }',

    // 7. Top-right chrome — fullscreen toggle, theme, language, user settings.
    '#topbar-fullscreen, #change-theme, #change-lang, #user-settings { display: none !important; }',

    // 8. Right-click context menu.
    '#contextmenu { display: none !important; }',

    // 9. Per-tile play overlay that signals "click plays this".
    '.play { display: none !important; }',

    // 10. FG operation notifications.
    '#files-notifications { display: none !important; }',
  ].join('\n');
  (document.head || document.documentElement).appendChild(style);

  function killMediaIn(node) {
    if (!node || !node.querySelectorAll) return;
    node.querySelectorAll('video, audio').forEach(function (media) {
      try { media.pause(); media.removeAttribute('src'); media.load(); } catch (_) {}
    });
  }

  function closePswpIfOpen() {
    var body = document.body;
    if (body && body.classList) body.classList.remove('popup-open');
    document.querySelectorAll('.pswp').forEach(function (el) {
      el.classList.remove('pswp--open', 'pswp--animate', 'pswp--animated-in');
      killMediaIn(el);
      el.style.display = 'none';
    });
    var legacy = document.getElementById('modal_preview');
    if (legacy) killMediaIn(legacy);
  }

  // Primary defence: intercept every file-anchor click at the capture phase,
  // BEFORE Files Gallery's own click listener fires. FG delegates clicks off
  // document in the bubble phase, so a capture-phase listener on the window
  // (highest possible spot in event flow) gets there first. preventDefault +
  // stopImmediatePropagation stops both the <a href="..."> navigation AND
  // FG's preview-open logic — click becomes pure selection, nothing else.
  // FG's file-grid anchor template produces the same base class for both
  // folders AND files (`files-a files-a-svg` for anything that falls back
  // to an SVG icon — which includes folders). There's no `files-a-dir`
  // suffix, so class-based detection alone can't tell them apart. The
  // reliable differentiator is the path: folders have no file extension.
  //
  // Sidebar tree links (`.menu-a`) are always folders.
  // Explicit `data-is_dir="1"` wins when FG's code (or ours) sets it.
  // Otherwise: look at the basename. An extension like ".mp4" / ".webp"
  // means file; its absence means folder.
  function isFolderAnchor(a) {
    if (!a || !a.classList) return false;
    if (a.classList.contains('menu-a')) return true;
    if (a.classList.contains('folder')) return true;
    if (a.dataset && (a.dataset.is_dir === 'true' || a.dataset.is_dir === '1')) return true;

    var path = (a.dataset && a.dataset.path) || '';
    if (path) {
      var basename = path.split('/').pop() || '';
      // No extension → folder. `.gitignore`-style dotfiles also match
      // this pattern but they're files, not folders — the .ext regex
      // requires 1-8 trailing alphanumerics AFTER a dot so a bare
      // `.gitignore` (all-extension) would still be treated as a file.
      return !/\.[a-zA-Z0-9]{1,8}$/.test(basename);
    }

    // No data-path — last-resort: inspect the href for folder-style URLs.
    var href = a.getAttribute('href') || '';
    if (href.indexOf('?path=') !== -1) return true;
    if (href.slice(-1) === '/') return true;
    return false;
  }

  function handleFileClick(e) {
    var a = e.target && e.target.closest ? e.target.closest('.files-a, .menu-a') : null;
    if (!a) return;

    // Folders / menu links must still navigate.
    if (isFolderAnchor(a)) return;

    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();

    // Emulate FG's selection visually: single-select — clear others first.
    document.querySelectorAll('.files-a[data-selected]').forEach(function (other) {
      if (other !== a) delete other.dataset.selected;
    });
    a.dataset.selected = '1';

    // Expose the pick to the parent modal via a DOM signal it can read without
    // needing access to FG's closure-local `ye` object.
    try {
      document.documentElement.dataset.jamboLastPick = a.dataset.path || a.getAttribute('href') || '';
    } catch (_) {}
  }

  // window > document > body — window capture is earliest possible.
  window.addEventListener('click', handleFileClick, true);
  document.addEventListener('click', handleFileClick, true);

  // Same for mousedown, in case FG wires preview on mousedown for performance.
  window.addEventListener('mousedown', handleFileClick, true);
  document.addEventListener('mousedown', handleFileClick, true);

  // Also block keyboard activation (Space/Enter on focused file anchor).
  window.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var target = e.target;
    if (target && target.closest && target.closest('.files-a')) {
      var a = target.closest('.files-a');
      if (!isFolderAnchor(a)) {
        e.preventDefault();
        e.stopImmediatePropagation();
        handleFileClick({
          target: a,
          preventDefault: function () {},
          stopImmediatePropagation: function () {},
          stopPropagation: function () {},
        });
      }
    }
  }, true);

  // Poll during FG boot to catch any pswp the CSS missed.
  var pollTimer = setInterval(closePswpIfOpen, 120);
  setTimeout(function () { clearInterval(pollTimer); }, 8000);

  new MutationObserver(function (mutations) {
    for (var i = 0; i < mutations.length; i++) {
      var m = mutations[i];
      if (m.type === 'attributes') {
        var t = m.target;
        if (t && t.classList) {
          if (t.classList.contains('pswp--open') || t.classList.contains('popup-open')) {
            closePswpIfOpen();
            continue;
          }
        }
      }
      for (var j = 0; j < (m.addedNodes || []).length; j++) {
        var n = m.addedNodes[j];
        if (n.nodeType !== 1) continue;
        if (n.classList && n.classList.contains('pswp')) {
          closePswpIfOpen();
          continue;
        }
        if (n.tagName === 'VIDEO' || n.tagName === 'AUDIO') {
          try { n.pause(); n.removeAttribute('src'); n.load(); } catch (_) {}
          continue;
        }
        if (n.querySelector) {
          killMediaIn(n);
          if (n.querySelector('.pswp')) closePswpIfOpen();
        }
      }
    }
  }).observe(document.documentElement, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['class']
  });
})();
