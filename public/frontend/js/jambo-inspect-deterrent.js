/*
 * jambo-inspect-deterrent.js
 *
 * Blocks right-click + the common "open devtools / view source" shortcuts
 * on public-facing pages for non-admin users. This is a DETERRENT, not a
 * defense — anyone with 30 seconds of determination can:
 *
 *   • Pick "Developer tools" from the browser menu
 *   • Use a browser extension that re-enables right-click
 *   • Run the page with JS disabled
 *   • Read the HTML over the network tab
 *   • Save the page with Ctrl+S (some OSes reach this before JS can cancel)
 *
 * The stream URLs that appear in the HTML are ALREADY auth-gated proxy
 * routes (behind `tier_gate` middleware), so copying them doesn't grant
 * content access without a valid session. True piracy resistance would
 * require signed, short-lived URLs and/or encrypted HLS — out of scope
 * here.
 *
 * So what does this file actually buy us?
 *   • Raises the friction for ~80% of casual users who would otherwise
 *     hit F12 and start poking around out of curiosity.
 *   • Cleaner look on cards / posters that shouldn't surface a "Save
 *     image as…" native menu.
 *
 * The master layout only loads this script when the current user is not
 * an admin. Admins keep full browser tooling for debugging.
 */
(function () {
    'use strict';

    // Cancel the browser's default context menu. Inputs + textareas keep
    // their native menu so users can still paste / spellcheck.
    document.addEventListener('contextmenu', function (e) {
        var t = e.target;
        if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) {
            return;
        }
        e.preventDefault();
    }, { capture: true });

    // Swallow the common devtools / view-source shortcuts. Browsers may
    // still honour some of these via menus — this only blocks the
    // keyboard path.
    document.addEventListener('keydown', function (e) {
        var k = (e.key || '').toLowerCase();

        // F12
        if (k === 'f12') {
            e.preventDefault();
            return;
        }

        // Ctrl+U — view source
        if (e.ctrlKey && !e.shiftKey && !e.altKey && k === 'u') {
            e.preventDefault();
            return;
        }

        // Ctrl+S — save page (best-effort; browsers often intercept
        // before JS sees the event)
        if (e.ctrlKey && !e.shiftKey && !e.altKey && k === 's') {
            e.preventDefault();
            return;
        }

        // Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+Shift+C — devtools panels
        if (e.ctrlKey && e.shiftKey && (k === 'i' || k === 'j' || k === 'c')) {
            e.preventDefault();
            return;
        }

        // Cmd+Opt+I (macOS devtools)
        if (e.metaKey && e.altKey && (k === 'i' || k === 'j' || k === 'c')) {
            e.preventDefault();
            return;
        }
    }, { capture: true });

    // Block native drag on <img> and <video> so users can't drag posters
    // to their desktop. Same deterrent caveat as above.
    document.addEventListener('dragstart', function (e) {
        var t = e.target;
        if (t && (t.tagName === 'IMG' || t.tagName === 'VIDEO')) {
            e.preventDefault();
        }
    }, { capture: true });
})();
