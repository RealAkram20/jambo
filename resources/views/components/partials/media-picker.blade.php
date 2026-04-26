{{--
    Jambo Media Picker — reusable modal that opens the File Manager as a picker.

    Usage (any admin form):
        <button type="button" onclick="JamboMediaPicker.open({ target: 'logo_url', preview: '[data-branding-preview=logo]' })">
            Browse
        </button>

        @include('components.partials.media-picker')   // once per page

    Options:
        target   — input id OR selector to receive the chosen URL
        preview  — (optional) selector to an <img> whose src should update
        onSelect — (optional) JS function(url, meta) instead of target/preview
--}}

<div class="modal fade" id="jamboMediaPickerModal" tabindex="-1" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="height: 85vh;">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ph ph-folder-open"></i> Select a file
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative" style="background: var(--bs-body-bg);">
                <div id="jamboMediaPickerLoading"
                    class="position-absolute top-50 start-50 translate-middle text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small text-secondary">Loading File Manager…</div>
                </div>
                <iframe id="jamboMediaPickerFrame" title="File Manager"
                    style="width:100%; height:100%; border:0; display:block;"></iframe>
            </div>
            <div class="modal-footer justify-content-between align-items-center">
                <div id="jamboMediaPickerStatus" class="small text-secondary flex-grow-1 me-3"
                    style="min-height:1.5em;">
                    <i class="ph ph-info"></i> Click a file in the gallery, then press <strong>Select</strong>.
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="jamboMediaPickerSelect" disabled>
                        <i class="ph ph-check-circle me-1"></i> Select
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        if (window.JamboMediaPicker) return;

        const FM_BASE = @json(url('storage/media/index.php'));
        // The /storage/media/index.php drop-in is .htaccess-gated on
        // the JAMBO_FM_SESSION cookie. That cookie is only issued
        // when the admin hits /admin/file-manager (the Laravel route).
        // Open the picker without ever visiting that page and you get
        // a 403 from LiteSpeed before the iframe even renders. Pre-warm
        // by fetching the route once before each modal open so the
        // cookie is sitting in the jar when the iframe load begins.
        const FM_WARMUP_URL = @json(route('admin.file-manager.index'));
        let warmupPromise = null;
        function warmFileManagerCookie() {
            if (!warmupPromise) {
                warmupPromise = fetch(FM_WARMUP_URL, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'text/html' },
                }).then(function () {}).catch(function () {
                    // Failure here just means the iframe will hit its
                    // 403 path and the user can retry — better than
                    // blocking the modal entirely on a transient error.
                    warmupPromise = null;
                });
            }
            return warmupPromise;
        }
        let modalEl = null;
        let modalInstance = null;
        let currentOpts = null;
        let pollHandle = null;

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        // Read the Files Gallery selection by scanning the iframe's DOM for
        // .files-a anchors marked with [data-selected]. FG's `ye.selected()`
        // function is closure-local — not reachable from outside — so DOM
        // scanning is the most portable way to read selection state. In
        // picker mode, custom.js intercepts clicks and stamps data-selected
        // directly; in non-picker mode this still works because FG itself
        // toggles the same attribute when files are picked.
        function readIframeSelection() {
            const frame = document.getElementById('jamboMediaPickerFrame');
            if (!frame || !frame.contentDocument) return [];
            try {
                const doc = frame.contentDocument;
                const nodes = doc.querySelectorAll('.files-a[data-selected]');
                return Array.from(nodes).map(function (el) {
                    const path = el.dataset.path || '';
                    const href = el.getAttribute('href') || '';
                    const basename = path.split('/').pop() || el.getAttribute('title') || '';
                    const extMatch = basename.match(/\.([a-z0-9]+)$/i);
                    const isFolder =
                        (el.classList && (el.classList.contains('folder') || el.classList.contains('dir'))) ||
                        el.dataset.is_dir === 'true' ||
                        el.dataset.is_dir === '1';
                    return {
                        path: path,
                        basename: basename,
                        ext: extMatch ? extMatch[1].toLowerCase() : '',
                        url_path: href,
                        is_dir: isFolder,
                    };
                });
            } catch (_) {
                return [];
            }
        }

        // Normalise the accept list from the form field. Empty list = accept any file.
        function normalisedAccept() {
            if (!currentOpts || !Array.isArray(currentOpts.accept)) return [];
            return currentOpts.accept
                .map(e => String(e).toLowerCase().replace(/^\./, '').trim())
                .filter(Boolean);
        }

        function isAcceptable(item) {
            const accept = normalisedAccept();
            if (!accept.length) return true; // no restriction → any file ok
            const ext = String(item.ext || '').toLowerCase().replace(/^\./, '');
            return accept.indexOf(ext) !== -1;
        }

        function refreshPickerStatus() {
            const btn = document.getElementById('jamboMediaPickerSelect');
            const status = document.getElementById('jamboMediaPickerStatus');
            if (!btn || !status) return;
            const items = readIframeSelection().filter(it => it && !it.is_dir);
            const count = items.length;

            if (count === 0) {
                btn.disabled = true;
                status.innerHTML = '<i class="ph ph-info"></i> Click a file in the gallery, then press <strong>Select</strong>.';
                return;
            }

            const first = items[0];
            const name = escapeHtml(first.basename || first.name || 'selected file');
            const accept = normalisedAccept();

            if (!isAcceptable(first)) {
                btn.disabled = true;
                const allowed = accept.length ? accept.join(', ').toUpperCase() : '';
                status.innerHTML = '<i class="ph ph-warning-circle text-warning"></i> ' +
                    '<strong>' + name + '</strong> isn\'t a supported type' +
                    (allowed ? '. Allowed: <span class="text-uppercase">' + escapeHtml(allowed) + '</span>' : '.');
                return;
            }

            btn.disabled = false;
            status.innerHTML = count === 1
                ? '<i class="ph ph-check-circle text-success"></i> Ready to use: <strong>' + name + '</strong>'
                : '<i class="ph ph-check-circle text-success"></i> ' + count + ' selected — <strong>' + name + '</strong> will be used';
        }

        function startPolling() {
            stopPolling();
            refreshPickerStatus();
            pollHandle = setInterval(refreshPickerStatus, 400);
        }

        function stopPolling() {
            if (pollHandle) clearInterval(pollHandle);
            pollHandle = null;
        }

        // Resolve the chosen file to a **domain-root-relative path** starting
        // with `/` — e.g. `/Jambo/storage/gallery/Movies/foo.mp4` on XAMPP or
        // `/storage/gallery/Movies/foo.mp4` on a domain-root VPS. That's the
        // value Jambo's form validators want (regex /^\// on video_local,
        // regex /^(https?:\/\/|\/)/ on poster_url etc.) AND what the player
        // can feed straight into `<video src="...">` without any url() /
        // asset() wrapping.
        //
        // Files Gallery's anchors use relative hrefs like
        // `../gallery/Movies/foo.mp4`. We resolve those against the iframe's
        // own location to get the absolute URL's pathname — which, because
        // the iframe itself lives under the app (e.g. /Jambo/storage/media/
        // on XAMPP), naturally includes whatever app prefix is in play.
        // Same code path works on every deploy without hardcoding a base.
        function resolveSelectedUrl(item) {
            const frame = document.getElementById('jamboMediaPickerFrame');
            const fg = frame && frame.contentWindow;
            const base = (fg && fg.location && fg.location.href) || window.location.href;

            if (item.url_path) {
                try {
                    return new URL(item.url_path, base).pathname;
                } catch (_) {
                    return item.url_path.replace(/^https?:\/\/[^\/]+/i, '');
                }
            }
            if (item.path && fg && fg.location) {
                // Fallback: route through Files Gallery's PHP proxy for files
                // outside the public symlink. The pathname already carries
                // the app prefix.
                return fg.location.pathname + '?action=file&file=' + encodeURIComponent(item.path);
            }
            return '';
        }

        function ensureModal() {
            if (modalEl) return;
            modalEl = document.getElementById('jamboMediaPickerModal');
            if (!modalEl) return;
            modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);

            modalEl.addEventListener('shown.bs.modal', startPolling);
            modalEl.addEventListener('hidden.bs.modal', () => {
                stopPolling();
                const frame = document.getElementById('jamboMediaPickerFrame');
                if (frame) frame.src = 'about:blank';
                currentOpts = null;
                const btn = document.getElementById('jamboMediaPickerSelect');
                if (btn) btn.disabled = true;
            });

            // Hide the loading spinner the moment the iframe finishes loading,
            // regardless of any postMessage protocol. The iframe's `load` event
            // fires once the Files Gallery document has fully rendered, which
            // is a far more reliable ready signal than waiting on a custom
            // message FG may never send.
            const initialFrame = document.getElementById('jamboMediaPickerFrame');
            if (initialFrame) {
                initialFrame.addEventListener('load', function () {
                    if (initialFrame.src === 'about:blank' || !initialFrame.src) return;
                    const loading = document.getElementById('jamboMediaPickerLoading');
                    if (loading) loading.style.display = 'none';
                });
            }

            const selectBtn = document.getElementById('jamboMediaPickerSelect');
            if (selectBtn) {
                selectBtn.addEventListener('click', function () {
                    const items = readIframeSelection().filter(it => it && !it.is_dir);
                    if (!items.length) return;
                    const item = items[0];
                    if (!isAcceptable(item)) return; // safety re-check in case polling lagged
                    const url = resolveSelectedUrl(item);
                    if (!url) return;
                    applySelection(url, {
                        filename: item.basename || '',
                        ext: item.ext || '',
                    });
                });
            }
        }

        function applySelection(url, meta) {
            if (!currentOpts) return;
            if (typeof currentOpts.onSelect === 'function') {
                currentOpts.onSelect(url, meta);
            }
            if (currentOpts.target) {
                const sel = currentOpts.target.startsWith('#') || currentOpts.target.includes('[')
                    ? currentOpts.target
                    : '#' + currentOpts.target;
                const input = document.querySelector(sel);
                if (input) {
                    input.value = url;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            if (currentOpts.preview) {
                const img = document.querySelector(currentOpts.preview);
                if (img) img.src = url;
            }
            if (modalInstance) modalInstance.hide();
        }

        window.addEventListener('message', function (e) {
            if (!e.data || typeof e.data !== 'object') return;
            if (e.data.type === 'jambo:picker-ready') {
                const loading = document.getElementById('jamboMediaPickerLoading');
                if (loading) loading.style.display = 'none';
            } else if (e.data.type === 'jambo:file-selected') {
                applySelection(e.data.url, { filename: e.data.filename, ext: e.data.ext });
            } else if (e.data.type === 'jambo:picker-cancel') {
                if (modalInstance) modalInstance.hide();
            }
        });

        window.JamboMediaPicker = {
            open(opts) {
                ensureModal();
                if (!modalInstance) {
                    console.error('JamboMediaPicker: include components.partials.media-picker in this view.');
                    return;
                }
                currentOpts = opts || {};
                const loading = document.getElementById('jamboMediaPickerLoading');
                if (loading) loading.style.display = '';
                const accept = Array.isArray(currentOpts.accept) && currentOpts.accept.length
                    ? currentOpts.accept.map(e => String(e).toLowerCase().replace(/^\./, '')).join(',')
                    : '';
                const params = new URLSearchParams({ picker: '1', _t: String(Date.now()) });
                if (accept) params.set('accept', accept);
                const frame = document.getElementById('jamboMediaPickerFrame');
                modalInstance.show();
                // Wait for the cookie warmup before pointing the iframe.
                // Safe to await even when the cookie was already set on
                // a prior open — fetch is fast and the server re-issues
                // a fresh 4-hour TTL each call.
                warmFileManagerCookie().finally(function () {
                    if (frame) frame.src = FM_BASE + '?' + params.toString();
                });
            },
        };
    })();
</script>
