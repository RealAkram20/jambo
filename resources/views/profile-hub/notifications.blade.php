@extends('profile-hub._layout', ['pageTitle' => 'Notifications', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    {{-- Inbox card ============================================== --}}
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Inbox</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    {{ $unreadCount }} unread · {{ $notifications->total() }} total
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if ($unreadCount > 0)
                    <button type="button" class="btn btn-ghost btn-sm" id="jambo-mark-all-read">
                        <i class="ph ph-check-square me-1"></i> Mark all read
                    </button>
                @endif
                <i class="ph ph-bell fs-2 text-muted"></i>
            </div>
        </div>

        @if ($notifications->isEmpty())
            <div class="text-center py-4">
                <i class="ph ph-bell-slash fs-1 text-muted d-block mb-2"></i>
                <p class="text-muted mb-0">
                    You're all caught up — nothing in your inbox yet.
                </p>
            </div>
        @else
            <ul class="list-unstyled mb-0 jambo-hub-inbox">
                @foreach ($notifications as $n)
                    @php
                        $d = (array) $n->data;
                        $isUnread = is_null($n->read_at);
                        $colour = $d['colour'] ?? 'primary';
                    @endphp
                    <li class="jambo-hub-inbox__row {{ $isUnread ? 'is-unread' : '' }}"
                        data-id="{{ $n->id }}">
                        <div class="jambo-hub-inbox__icon bg-{{ $colour }}-subtle text-{{ $colour }}-emphasis">
                            <i class="ph {{ $d['icon'] ?? 'ph-bell' }}"></i>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="min-width-0">
                                    <div class="fw-semibold text-truncate">{{ $d['title'] ?? 'Notification' }}</div>
                                    <div class="text-muted small">{{ $d['message'] ?? '' }}</div>
                                </div>
                                <small class="text-muted flex-shrink-0">{{ $n->created_at?->diffForHumans() }}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                @if (!empty($d['action_url']))
                                    <a href="{{ $d['action_url'] }}" class="btn btn-primary btn-sm jambo-hub-inbox__open">
                                        View <i class="ph ph-arrow-right ms-1"></i>
                                    </a>
                                @endif
                                @if ($isUnread)
                                    <button type="button" class="btn btn-ghost btn-sm jambo-hub-inbox__read">
                                        <i class="ph ph-check me-1"></i> Mark read
                                    </button>
                                @endif
                                <button type="button" class="btn btn-ghost btn-sm text-danger-emphasis jambo-hub-inbox__delete"
                                        title="Remove">
                                    <i class="ph ph-trash-simple"></i>
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($notifications->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $notifications->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Channel preferences card =============================== --}}
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Delivery preferences</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    Choose which channels we can use to reach you. Security-critical
                    messages may still be delivered by email even if disabled here.
                </p>
            </div>
            <i class="ph ph-paper-plane-tilt fs-2 text-muted"></i>
        </div>

        <form method="POST" action="{{ route('profile.notifications.prefs', ['username' => $user->username]) }}">
            @csrf @method('PUT')

            <div class="d-flex flex-column gap-3">
                <label class="jambo-pref-row" for="pref-in-app">
                    <div class="jambo-pref-row__icon bg-primary-subtle text-primary-emphasis">
                        <i class="ph ph-bell"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">In-app (system)</div>
                        <div class="text-muted small">
                            Bell dropdown in the header + your inbox above.
                        </div>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input type="checkbox" class="form-check-input" role="switch"
                               id="pref-in-app" name="in_app" value="1"
                               @checked($user->in_app_notifications_enabled)>
                    </div>
                </label>

                <label class="jambo-pref-row" for="pref-email">
                    <div class="jambo-pref-row__icon bg-success-subtle text-success-emphasis">
                        <i class="ph ph-envelope-simple"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">
                            Email
                            @if ($user->email_verified_at)
                                <small class="text-success ms-1"><i class="ph ph-check-circle"></i> verified</small>
                            @else
                                <small class="text-warning ms-1"><i class="ph ph-warning-circle"></i> unverified</small>
                            @endif
                        </div>
                        <div class="text-muted small">
                            Sent to <code>{{ $user->email }}</code>.
                        </div>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input type="checkbox" class="form-check-input" role="switch"
                               id="pref-email" name="email" value="1"
                               @checked($user->email_notifications_enabled)>
                    </div>
                </label>

                <label class="jambo-pref-row" for="pref-push">
                    <div class="jambo-pref-row__icon bg-warning-subtle text-warning-emphasis">
                        <i class="ph ph-device-mobile"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">Push</div>
                        <div class="text-muted small">
                            Browser push alerts. Your browser will ask for permission the first time you enable this.
                        </div>
                        <div id="jambo-push-status" class="small mt-1"></div>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input type="checkbox" class="form-check-input" role="switch"
                               id="pref-push" name="push" value="1"
                               @checked($user->push_notifications_enabled)>
                    </div>
                </label>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ph ph-floppy-disk me-1"></i> Save preferences
                </button>
            </div>
        </form>
    </div>

    <style>
        .jambo-hub-inbox { display: flex; flex-direction: column; gap: 0.5rem; }

        .jambo-hub-inbox__row {
            display: flex;
            gap: 0.75rem;
            padding: 0.85rem;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            transition: background 0.15s;
        }
        .jambo-hub-inbox__row:hover { background: rgba(255,255,255,0.03); }
        .jambo-hub-inbox__row.is-unread {
            background: rgba(26, 152, 255, 0.07);
            border-color: rgba(26, 152, 255, 0.18);
        }

        .jambo-hub-inbox__icon {
            flex-shrink: 0;
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
        }

        .jambo-pref-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            cursor: pointer;
            margin: 0;
        }
        .jambo-pref-row:hover { background: rgba(255,255,255,0.03); }

        .jambo-pref-row__icon {
            flex-shrink: 0;
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
        }

        .min-width-0 { min-width: 0; }
    </style>

    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        async function post(url, method) {
            return fetch(url, {
                method: method || 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
        }

        document.getElementById('jambo-mark-all-read')?.addEventListener('click', async (e) => {
            e.preventDefault();
            e.currentTarget.disabled = true;
            await post('{{ route('notifications.mark-all-read') }}');
            location.reload();
        });

        document.querySelectorAll('.jambo-hub-inbox__read').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const row = e.currentTarget.closest('.jambo-hub-inbox__row');
                if (!row) return;
                const id = row.dataset.id;
                btn.disabled = true;
                await post('/notifications/' + id + '/read');
                row.classList.remove('is-unread');
                btn.remove();
            });
        });

        document.querySelectorAll('.jambo-hub-inbox__delete').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (!confirm('Remove this notification?')) return;
                const row = e.currentTarget.closest('.jambo-hub-inbox__row');
                if (!row) return;
                const id = row.dataset.id;
                btn.disabled = true;
                await post('/notifications/' + id, 'DELETE');
                row.style.transition = 'opacity .2s, transform .2s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(8px)';
                setTimeout(() => row.remove(), 200);
            });
        });

        /* -----------------------------------------------------------
         * Browser push: register service worker + subscribe/unsubscribe
         * -----------------------------------------------------------
         * The `Push` switch drives the browser subscription directly,
         * independent of the form submit. Flipping the switch on asks
         * the browser for permission, subscribes, and posts the
         * subscription to the server; flipping it off revokes the
         * subscription on both ends. The form submit still saves the
         * in_app + email flags.
         */
        (function () {
            const pushInput = document.getElementById('pref-push');
            const statusEl  = document.getElementById('jambo-push-status');
            if (!pushInput) return;

            const supported = 'serviceWorker' in navigator
                && 'PushManager' in window
                && 'Notification' in window;

            if (!supported) {
                pushInput.disabled = true;
                pushInput.checked = false;
                statusEl.innerHTML = '<span class="text-muted">Your browser does not support push notifications.</span>';
                return;
            }

            const vapidPublic = @json(config('webpush.vapid.public_key'));
            if (!vapidPublic) {
                pushInput.disabled = true;
                pushInput.checked = false;
                statusEl.innerHTML = '<span class="text-muted">Push is not configured on this server yet.</span>';
                return;
            }

            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                const raw = atob(base64);
                const out = new Uint8Array(raw.length);
                for (let i = 0; i < raw.length; ++i) out[i] = raw.charCodeAt(i);
                return out;
            }

            async function registerSW() {
                const existing = await navigator.serviceWorker.getRegistration('/sw.js');
                return existing || navigator.serviceWorker.register('/sw.js');
            }

            async function subscribe() {
                if (Notification.permission === 'denied') {
                    throw new Error('Notifications are blocked in your browser settings.');
                }
                const reg = await registerSW();
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublic),
                });
                const payload = sub.toJSON();
                const res = await fetch('{{ route('notifications.push.subscribe') }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error('Server rejected subscription.');
            }

            async function unsubscribe() {
                const reg = await registerSW();
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    const endpoint = sub.endpoint;
                    await sub.unsubscribe();
                    await fetch('{{ route('notifications.push.unsubscribe') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ endpoint: endpoint }),
                    });
                }
            }

            pushInput.addEventListener('change', async (e) => {
                statusEl.textContent = '';
                pushInput.disabled = true;
                try {
                    if (e.target.checked) {
                        await subscribe();
                        statusEl.innerHTML = '<span class="text-success">Push enabled on this device.</span>';
                    } else {
                        await unsubscribe();
                        statusEl.innerHTML = '<span class="text-muted">Push disabled on this device.</span>';
                    }
                } catch (err) {
                    pushInput.checked = !e.target.checked;
                    statusEl.innerHTML = '<span class="text-danger">' + (err.message || 'Could not change push state.') + '</span>';
                } finally {
                    pushInput.disabled = false;
                }
            });
        })();
    })();
    </script>
@endsection
