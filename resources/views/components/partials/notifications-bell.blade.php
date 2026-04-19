{{-- Admin header notification bell, powered by the Notifications module.
     Polls /notifications/dropdown every 60s for live counts + recent
     items. Click-through hits /notifications/{id}/read before navigating,
     "Mark all as read" hits /notifications/mark-all-read. Shipped in
     commit after Payments — see docs/modules/notifications.md. --}}

@if (auth()->check() && Route::has('notifications.dropdown'))
    <li class="nav-item dropdown" id="jambo-bell-root">
        <a href="#" class="nav-link position-relative" id="notification-drop" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ph-fill ph-bell fs-4 align-middle"></i>
            <span
                class="bg-danger position-absolute badge rounded-pill"
                id="jambo-bell-count"
                style="top: 4px; right: 2px; font-size: 9px; padding: 2px 5px; display: none;">
                0
            </span>
        </a>
        <ul class="p-0 sub-drop dropdown-menu dropdown-menu-end" aria-labelledby="notification-drop" style="min-width: 340px;">
            <li class="p-0">
                <div class="p-3 card-header d-flex justify-content-between align-items-center bg-primary rounded-top">
                    <div class="header-title">
                        <h5 class="mb-0 text-white">Notifications</h5>
                    </div>
                    <button type="button" class="btn btn-sm btn-light" id="jambo-bell-mark-all"
                        style="font-size: 11px;">
                        Mark all read
                    </button>
                </div>
                <div class="p-0 card-body all-notification" id="jambo-bell-list" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center text-muted py-4" id="jambo-bell-empty" style="font-size: 13px;">
                        Loading…
                    </div>
                </div>
                <a href="{{ route('notifications.index') }}" class="d-block text-center p-2 border-top"
                    style="font-size: 12px; text-decoration: none;">
                    View all notifications →
                </a>
            </li>
        </ul>
    </li>

    <template id="jambo-bell-row-template">
        <a href="#" class="iq-sub-card jambo-bell-row" data-read="false">
            <div class="d-flex align-items-start gap-2">
                <div class="flex-shrink-0 bell-media"
                    style="width: 36px; height: 36px;">
                    {{-- Filled by render() with either an <img> (when the
                         notification payload includes an image, e.g. a
                         movie poster) or a Phosphor icon fallback. --}}
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-0 bell-title" style="font-size: 13px;"></h6>
                    <p class="mb-0 bell-message text-muted" style="font-size: 11px; line-height: 1.4;"></p>
                    <small class="float-end text-muted bell-time" style="font-size: 10px;"></small>
                </div>
            </div>
        </a>
    </template>

    <script>
    (function () {
        const dropdownUrl = @json(route('notifications.dropdown'));
        const markReadUrlTemplate = @json(url('/notifications/__id__/read'));
        const markAllUrl = @json(route('notifications.mark-all-read'));
        const csrfMeta = document.querySelector('meta[name=csrf-token]');
        if (!csrfMeta) return;
        const csrf = csrfMeta.content;

        const countEl = document.getElementById('jambo-bell-count');
        const listEl = document.getElementById('jambo-bell-list');
        const emptyEl = document.getElementById('jambo-bell-empty');
        const markAllBtn = document.getElementById('jambo-bell-mark-all');
        const rowTemplate = document.getElementById('jambo-bell-row-template');

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        function render(payload) {
            const count = payload.unread_count || 0;
            if (count > 0) {
                countEl.textContent = count > 99 ? '99+' : count;
                countEl.style.display = 'inline-block';
            } else {
                countEl.style.display = 'none';
            }

            listEl.innerHTML = '';

            if (!payload.recent || payload.recent.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'text-center text-muted py-4';
                empty.style.fontSize = '13px';
                empty.textContent = 'No notifications yet.';
                listEl.appendChild(empty);
                return;
            }

            payload.recent.forEach(n => {
                const frag = rowTemplate.content.cloneNode(true);
                const row = frag.querySelector('.jambo-bell-row');
                row.dataset.id = n.id;
                row.dataset.read = n.read_at ? 'true' : 'false';
                if (n.action_url) row.href = n.action_url;

                const media = row.querySelector('.bell-media');
                if (n.image) {
                    const img = document.createElement('img');
                    img.src = n.image;
                    img.alt = '';
                    img.loading = 'lazy';
                    img.style.cssText = 'width:36px;height:36px;object-fit:cover;border-radius:8px;';
                    media.appendChild(img);
                } else {
                    media.classList.add('d-flex', 'align-items-center', 'justify-content-center', 'rounded-circle');
                    media.style.background = 'rgba(26, 152, 255, 0.15)';
                    media.style.color = 'var(--bs-primary)';
                    const icon = document.createElement('i');
                    icon.className = 'ph ' + (n.icon || 'ph-bell') + ' fs-6';
                    media.appendChild(icon);
                }
                row.querySelector('.bell-title').textContent = n.title || 'Notification';
                row.querySelector('.bell-message').textContent = n.message || '';
                row.querySelector('.bell-time').textContent = n.created_at_human || '';

                if (!n.read_at) {
                    row.style.background = 'rgba(26, 152, 255, 0.06)';
                }

                row.addEventListener('click', async (e) => {
                    if (row.dataset.read === 'false') {
                        fetch(markReadUrlTemplate.replace('__id__', n.id), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        });
                    }
                    // Follow link normally; if no action_url, prevent navigation
                    if (!n.action_url) e.preventDefault();
                });

                listEl.appendChild(frag);
            });
        }

        async function refresh() {
            try {
                const res = await fetch(dropdownUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const json = await res.json();
                render(json);
            } catch (e) {
                // Fail silently; next poll will retry.
            }
        }

        markAllBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            markAllBtn.disabled = true;
            try {
                await fetch(markAllUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                refresh();
            } finally {
                markAllBtn.disabled = false;
            }
        });

        refresh();
        setInterval(refresh, 60_000);
    })();
    </script>
@endif
