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
                    <i class="ph ph-info"></i> Click a file inside the gallery, then press <strong>Use selected file</strong>.
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="jamboMediaPickerSelect" disabled>
                        <i class="ph ph-check-circle me-1"></i> Use selected file
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
        let modalEl = null;
        let modalInstance = null;
        let currentOpts = null;
        let pollHandle = null;

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        // Read the Files Gallery selection from the iframe. Same origin, so we
        // can call the frame's global `ye.selected()` directly — that's Files
        // Gallery's internal grid-state API. If it ever changes, the try/catch
        // falls through to an empty array so the modal doesn't throw.
        function readIframeSelection() {
            const frame = document.getElementById('jamboMediaPickerFrame');
            if (!frame || !frame.contentWindow) return [];
            try {
                const fg = frame.contentWindow;
                if (fg.ye && typeof fg.ye.selected === 'function') {
                    const items = fg.ye.selected();
                    return Array.isArray(items) ? items : [];
                }
            } catch (_) { /* iframe still loading or cross-origin guard */ }
            return [];
        }

        function refreshPickerStatus() {
            const btn = document.getElementById('jamboMediaPickerSelect');
            const status = document.getElementById('jamboMediaPickerStatus');
            if (!btn || !status) return;
            const items = readIframeSelection().filter(it => it && !it.is_dir);
            const count = items.length;
            btn.disabled = count === 0;
            if (count === 0) {
                status.innerHTML = '<i class="ph ph-info"></i> Click a file inside the gallery, then press <strong>Use selected file</strong>.';
                return;
            }
            const first = items[0];
            const name = escapeHtml(first.basename || first.name || 'selected file');
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

        // Resolve an absolute URL for the chosen file. Files Gallery returns
        // `url_path` as root-relative (e.g. `/Jambo/storage/gallery/foo.mp4`)
        // when the file is inside the public symlink. When it's not, we fall
        // back to Files Gallery's own PHP proxy endpoint.
        function resolveSelectedUrl(item) {
            const frame = document.getElementById('jamboMediaPickerFrame');
            const fg = frame && frame.contentWindow;
            const origin = (fg && fg.location && fg.location.origin) || window.location.origin;
            if (item.url_path) {
                return /^https?:/i.test(item.url_path) ? item.url_path : origin + item.url_path;
            }
            if (item.path && fg && fg.location) {
                return origin + fg.location.pathname + '?action=file&file=' + encodeURIComponent(item.path);
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

            const selectBtn = document.getElementById('jamboMediaPickerSelect');
            if (selectBtn) {
                selectBtn.addEventListener('click', function () {
                    const items = readIframeSelection().filter(it => it && !it.is_dir);
                    if (!items.length) return;
                    const item = items[0];
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
                if (frame) frame.src = FM_BASE + '?' + params.toString();
                modalInstance.show();
            },
        };
    })();
</script>
