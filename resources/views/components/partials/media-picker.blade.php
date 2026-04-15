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
            <div class="modal-footer justify-content-between">
                <small class="text-secondary">
                    <i class="ph ph-info"></i> Upload new files from inside the manager, then click to select.
                </small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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

        function ensureModal() {
            if (modalEl) return;
            modalEl = document.getElementById('jamboMediaPickerModal');
            if (!modalEl) return;
            modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalEl.addEventListener('hidden.bs.modal', () => {
                const frame = document.getElementById('jamboMediaPickerFrame');
                if (frame) frame.src = 'about:blank';
                currentOpts = null;
            });
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
