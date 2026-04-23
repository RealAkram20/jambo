{{-- Wires the Streaming tabs: source badge, tab-switch tracking, Local display sync. --}}
<script>
    (function () {
        const sourceInput   = document.getElementById('video_source');
        if (!sourceInput) return;

        const sourceBadge   = document.getElementById('streaming-source-badge');
        const sourceLabel   = document.getElementById('streaming-source-label');
        const videoLocal    = document.getElementById('video_local');
        const localDisplay  = document.getElementById('video_local_display');
        const localClearBtn = document.getElementById('video_local_clear');

        const meta = {
            url:     { label: 'URL',     icon: 'ph-link',         cls: 'bg-info-subtle text-info' },
            local:   { label: 'Local',   icon: 'ph-folder-open',  cls: 'bg-primary-subtle text-primary' },
            dropbox: { label: 'Dropbox', icon: 'ph-dropbox-logo', cls: 'bg-warning-subtle text-warning' },
            none:    { label: 'None',    icon: 'ph-minus-circle', cls: 'bg-body-secondary text-secondary' },
        };

        function paint(key) {
            const m = meta[key] || meta.none;
            sourceBadge.className = 'badge rounded-pill d-inline-flex align-items-center gap-1 ' + m.cls;
            const icon = sourceBadge.querySelector('i');
            if (icon) icon.className = 'ph ' + m.icon;
            sourceLabel.textContent = m.label;
        }

        function setSource(key) { sourceInput.value = key; paint(key); }

        // Clear values from the inactive tabs so stale data can't break
        // validation on submit. Each tab's field has its own regex and
        // a leftover full-URL in video_local, or a local path left in
        // video_url after switching sources, would fail validation even
        // though the user isn't using that tab. video_source tracks
        // which tab is authoritative; everything else should be empty.
        function clearInactiveFields(activeTab) {
            const urlInput     = document.getElementById('video_url');
            const localInput   = document.getElementById('video_local');
            const dropboxInput = document.getElementById('dropbox_path');
            if (activeTab !== 'url' && urlInput)         urlInput.value = '';
            if (activeTab !== 'local' && localInput) {
                localInput.value = '';
                localInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (activeTab !== 'dropbox' && dropboxInput) dropboxInput.value = '';
        }

        document.querySelectorAll('#streamingTabs [data-bs-toggle="tab"]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function () {
                const target = btn.getAttribute('data-bs-target');
                let tabKey = 'url';
                if (target === '#pane-stream-url')          tabKey = 'url';
                else if (target === '#pane-stream-local')   tabKey = 'local';
                else if (target === '#pane-stream-dropbox') tabKey = 'dropbox';
                setSource(tabKey);
                clearInactiveFields(tabKey);
            });
        });

        function syncLocalDisplay() {
            if (!videoLocal || !localDisplay) return;
            const v = videoLocal.value.trim();
            if (v) {
                localDisplay.innerHTML = 'Selected: <code></code>';
                localDisplay.querySelector('code').textContent = v;
                if (localClearBtn) localClearBtn.classList.remove('d-none');
            } else {
                localDisplay.textContent = 'No local file selected';
                if (localClearBtn) localClearBtn.classList.add('d-none');
            }
        }
        if (videoLocal) videoLocal.addEventListener('input', syncLocalDisplay);
        if (localClearBtn) {
            localClearBtn.addEventListener('click', function () {
                if (!videoLocal) return;
                videoLocal.value = '';
                videoLocal.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }
    })();
</script>
