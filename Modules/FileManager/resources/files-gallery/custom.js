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
