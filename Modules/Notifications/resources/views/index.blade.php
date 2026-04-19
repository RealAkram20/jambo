@extends('layouts.app', ['module_title' => 'Notifications'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Notifications</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            <span class="notif-subtitle-inbox" @if ($activeTab !== 'inbox') style="display:none;" @endif>
                                {{ $unreadCount }} unread · {{ $notifications->total() }} total
                            </span>
                            <span class="notif-subtitle-settings" @if ($activeTab !== 'settings') style="display:none;" @endif>
                                Global switches for every notification type. Admin off wins over user preference.
                            </span>
                            <span class="notif-subtitle-broadcast" @if ($activeTab !== 'broadcast') style="display:none;" @endif>
                                Send a one-off announcement to everyone, admins only, or regular users.
                            </span>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="notif-actions-inbox" @if ($activeTab !== 'inbox') style="display:none;" @endif>
                            @if (app()->environment('local'))
                                <form method="POST" action="{{ route('notifications.test-dispatch') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        <i class="ph ph-paper-plane-tilt me-1"></i> Send test
                                    </button>
                                </form>
                            @endif
                            @if ($unreadCount > 0)
                                <button type="button" class="btn btn-primary btn-sm" id="mark-all-read-btn">
                                    <i class="ph ph-check-square me-1"></i> Mark all as read
                                </button>
                            @endif
                        </div>
                        <div class="notif-actions-settings" @if ($activeTab !== 'settings') style="display:none;" @endif>
                            <button type="submit" form="notif-settings-form" class="btn btn-primary btn-sm">
                                <i class="ph ph-floppy-disk me-1"></i> Save preferences
                            </button>
                        </div>
                        <div class="notif-actions-broadcast" @if ($activeTab !== 'broadcast') style="display:none;" @endif>
                            <button type="submit" form="notif-broadcast-form" class="btn btn-primary btn-sm">
                                <i class="ph ph-paper-plane-tilt me-1"></i> Send broadcast
                            </button>
                        </div>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif

                <ul class="nav nav-tabs px-4 mt-3" id="notifTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'inbox' ? 'active' : '' }}"
                                id="inbox-tab" data-bs-toggle="tab" data-bs-target="#tab-inbox"
                                type="button" role="tab" aria-controls="tab-inbox"
                                aria-selected="{{ $activeTab === 'inbox' ? 'true' : 'false' }}">
                            <i class="ph ph-tray me-1"></i> Inbox
                            @if ($unreadCount > 0)
                                <span class="badge bg-primary ms-1" style="font-size:10px;">{{ $unreadCount }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'settings' ? 'active' : '' }}"
                                id="settings-tab" data-bs-toggle="tab" data-bs-target="#tab-settings"
                                type="button" role="tab" aria-controls="tab-settings"
                                aria-selected="{{ $activeTab === 'settings' ? 'true' : 'false' }}">
                            <i class="ph ph-sliders-horizontal me-1"></i> Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'broadcast' ? 'active' : '' }}"
                                id="broadcast-tab" data-bs-toggle="tab" data-bs-target="#tab-broadcast"
                                type="button" role="tab" aria-controls="tab-broadcast"
                                aria-selected="{{ $activeTab === 'broadcast' ? 'true' : 'false' }}">
                            <i class="ph ph-megaphone me-1"></i> Broadcast
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- ════════════════ INBOX TAB ════════════════ --}}
                    <div class="tab-pane fade {{ $activeTab === 'inbox' ? 'show active' : '' }}"
                         id="tab-inbox" role="tabpanel" aria-labelledby="inbox-tab">
                        <div class="p-0">
                            @if ($notifications->isEmpty())
                                <div class="text-center py-5 text-muted">
                                    <i class="ph ph-bell-slash" style="font-size:48px;"></i>
                                    <p class="mb-0 mt-3">No notifications yet.</p>
                                </div>
                            @else
                                <ul class="list-unstyled mb-0">
                                    @foreach ($notifications as $notification)
                                        @php
                                            $d = (array) $notification->data;
                                        @endphp
                                        <li class="d-flex gap-3 px-4 py-3 border-bottom notification-row" data-id="{{ $notification->id }}"
                                            style="{{ $notification->read_at ? '' : 'background: rgba(26, 152, 255, 0.04);' }}">
                                            @if (!empty($d['image']))
                                                <img src="{{ $d['image'] }}" alt=""
                                                    style="width:40px;height:40px;object-fit:cover;border-radius:10px;flex-shrink:0;">
                                            @else
                                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
                                                    style="width:40px;height:40px;background: rgba(26, 152, 255, 0.15); color: var(--bs-primary);">
                                                    <i class="ph {{ $d['icon'] ?? 'ph-bell' }} fs-5"></i>
                                                </div>
                                            @endif
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div class="fw-semibold">{{ $d['title'] ?? 'Notification' }}</div>
                                                        <div class="text-muted" style="font-size:13px;">{{ $d['message'] ?? '' }}</div>
                                                    </div>
                                                    <small class="text-muted flex-shrink-0 ms-3">{{ $notification->created_at?->diffForHumans() }}</small>
                                                </div>
                                                @if (!empty($d['action_url']))
                                                    <a href="{{ $d['action_url'] }}" class="btn btn-ghost btn-sm mt-2">View details →</a>
                                                @endif
                                            </div>
                                            <div class="flex-shrink-0">
                                                @if (!$notification->read_at)
                                                    <span class="badge bg-primary" style="font-size:10px;">NEW</span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        @if ($notifications->hasPages())
                            <div class="card-footer d-flex justify-content-center">
                                {{ $notifications->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- ════════════════ SETTINGS TAB ════════════════ --}}
                    <div class="tab-pane fade {{ $activeTab === 'settings' ? 'show active' : '' }}"
                         id="tab-settings" role="tabpanel" aria-labelledby="settings-tab">
                        {{-- Channel-test banner ============================= --}}
                        <div class="px-4 pt-4">
                            <div class="rounded-3 p-3 d-flex flex-wrap gap-3 align-items-center justify-content-between"
                                 style="background: rgba(26, 152, 255, 0.06); border: 1px solid rgba(26, 152, 255, 0.18);">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold d-flex align-items-center gap-2">
                                        <i class="ph ph-lightning fs-5 text-primary"></i> Test delivery
                                    </div>
                                    <div class="text-muted small">
                                        Fire a one-off test notification to your account through a single channel. Bypasses all switches so you can verify each transport end-to-end.
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.notifications.settings.test', ['channel' => 'system']) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="ph ph-bell me-1"></i> Test system
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.notifications.settings.test', ['channel' => 'push']) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="ph ph-device-mobile me-1"></i> Test push
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.notifications.settings.test', ['channel' => 'email']) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="ph ph-envelope-simple me-1"></i> Test email
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Push subscription setup — lets the admin opt-in, reset, or
                                 disable their browser push subscription without navigating
                                 to the profile hub. Needed because the Test push button
                                 fails if no subscription is registered on this device. --}}
                            <div id="jambo-admin-push-panel"
                                 class="rounded-3 p-3 mt-3 d-flex flex-wrap gap-3 align-items-center justify-content-between"
                                 style="background: rgba(255, 193, 7, 0.06); border: 1px solid rgba(255, 193, 7, 0.22);">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold d-flex align-items-center gap-2">
                                        <i class="ph ph-device-mobile fs-5 text-warning"></i> Push setup for this device
                                    </div>
                                    <div class="text-muted small" id="jambo-push-status">Checking subscription…</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-warning" id="jambo-push-enable" style="display:none;">
                                        <i class="ph ph-bell-ringing me-1"></i> Enable push on this device
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" id="jambo-push-reset" style="display:none;">
                                        <i class="ph ph-arrow-clockwise me-1"></i> Reset subscription
                                    </button>
                                    <button type="button" class="btn btn-sm btn-ghost" id="jambo-push-disable" style="display:none;">
                                        <i class="ph ph-bell-slash me-1"></i> Disable
                                    </button>
                                </div>
                            </div>
                        </div>

                        <form id="notif-settings-form" method="POST"
                              action="{{ route('admin.notifications.settings.update') }}"
                              class="p-4">
                            @csrf @method('PUT')

                            @foreach ($definitions as $groupId => $group)
                                <div class="mb-4">
                                    <h6 class="mb-3 d-flex align-items-center">
                                        <i class="ph {{ $group['icon'] }} me-2 fs-5"></i>
                                        {{ $group['label'] }}
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table custom-table align-middle mb-0">
                                            <thead>
                                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                                    <th>Notification</th>
                                                    <th class="text-center" style="width:110px;">System</th>
                                                    <th class="text-center" style="width:110px;">Push</th>
                                                    <th class="text-center" style="width:110px;">Email</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($group['items'] as $item)
                                                    @php
                                                        $row = $settingRows->get($item['key']);
                                                        $systemOn = $row?->system_enabled ?? true;
                                                        $pushOn   = $row?->push_enabled   ?? false;
                                                        $emailOn  = $row?->email_enabled  ?? true;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-{{ $item['colour'] }}-subtle text-{{ $item['colour'] }}-emphasis"
                                                                     style="width:40px;height:40px;">
                                                                    <i class="ph {{ $item['icon'] }} fs-5"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-semibold d-flex align-items-center gap-2">
                                                                        {{ $item['label'] }}
                                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis"
                                                                              style="font-size:10px;font-weight:500;">{{ $item['audience'] }}</span>
                                                                    </div>
                                                                    <div class="text-muted small">{{ $item['description'] }}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch d-inline-block m-0">
                                                                <input type="checkbox" role="switch"
                                                                       class="form-check-input"
                                                                       name="settings[{{ $item['key'] }}][system]" value="1"
                                                                       @checked($systemOn)>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch d-inline-block m-0">
                                                                <input type="checkbox" role="switch"
                                                                       class="form-check-input"
                                                                       name="settings[{{ $item['key'] }}][push]" value="1"
                                                                       @checked($pushOn)>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch d-inline-block m-0">
                                                                <input type="checkbox" role="switch"
                                                                       class="form-check-input"
                                                                       name="settings[{{ $item['key'] }}][email]" value="1"
                                                                       @checked($emailOn)>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <p class="text-muted mb-0" style="font-size:12px;">
                                    Admin switches apply site-wide. Users can further narrow their own delivery in their profile settings.
                                </p>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ph ph-floppy-disk me-1"></i> Save preferences
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- ════════════════ BROADCAST TAB ════════════════ --}}
                    <div class="tab-pane fade {{ $activeTab === 'broadcast' ? 'show active' : '' }}"
                         id="tab-broadcast" role="tabpanel" aria-labelledby="broadcast-tab">
                        <form id="notif-broadcast-form" method="POST"
                              action="{{ route('admin.notifications.broadcast.send') }}"
                              class="p-4">
                            @csrf

                            <div class="alert alert-info d-flex align-items-start gap-2 mb-4" style="font-size:13px;">
                                <i class="ph ph-info fs-5 flex-shrink-0 mt-1"></i>
                                <div>
                                    Broadcasts respect the <strong>Admin broadcast</strong> row on the Settings tab
                                    and each user's own channel preferences. Flip a channel off there to silence
                                    that transport for every broadcast site-wide.
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="broadcast-subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                           id="broadcast-subject" name="subject"
                                           value="{{ old('subject') }}" maxlength="150" required
                                           placeholder="e.g. Scheduled maintenance on Saturday">
                                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="broadcast-audience" class="form-label">Audience <span class="text-danger">*</span></label>
                                    <select name="audience" id="broadcast-audience"
                                            class="form-select @error('audience') is-invalid @enderror">
                                        <option value="all" @selected(old('audience', 'all') === 'all')>All verified users</option>
                                        <option value="users" @selected(old('audience') === 'users')>Regular users only</option>
                                        <option value="admins" @selected(old('audience') === 'admins')>Admins only</option>
                                    </select>
                                    @error('audience') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label for="broadcast-body" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('body') is-invalid @enderror"
                                              id="broadcast-body" name="body" rows="6"
                                              maxlength="2000" required
                                              placeholder="Plain text. Line breaks are preserved.">{{ old('body') }}</textarea>
                                    @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-8">
                                    <label for="broadcast-link-url" class="form-label">Link URL <span class="text-muted">(optional)</span></label>
                                    <input type="url" class="form-control @error('link_url') is-invalid @enderror"
                                           id="broadcast-link-url" name="link_url"
                                           value="{{ old('link_url') }}" maxlength="500"
                                           placeholder="https://jambo.tv/announcement">
                                    @error('link_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="broadcast-link-label" class="form-label">Link label</label>
                                    <input type="text" class="form-control @error('link_label') is-invalid @enderror"
                                           id="broadcast-link-label" name="link_label"
                                           value="{{ old('link_label') }}" maxlength="60"
                                           placeholder="Learn more">
                                    @error('link_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <p class="text-muted mb-0" style="font-size:12px;">
                                    This will queue one notification per recipient. Large broadcasts can take a moment.
                                </p>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ph ph-paper-plane-tilt me-1"></i> Send broadcast
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const markAllBtn = document.getElementById('mark-all-read-btn');
    const markAllUrl = @json(route('notifications.mark-all-read'));

    if (markAllBtn) {
        markAllBtn.addEventListener('click', async () => {
            markAllBtn.disabled = true;
            await fetch(markAllUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            location.reload();
        });
    }

    document.querySelectorAll('.notification-row').forEach(row => {
        row.addEventListener('click', async (e) => {
            if (e.target.closest('a')) {
                const id = row.dataset.id;
                if (id) {
                    fetch(`/notifications/${id}/read`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                }
            }
        });
    });

    // Swap header subtitle + action buttons to match the active tab so
    // the wrong action button isn't sitting there. Also sync the ?tab=
    // query param so refreshing the page stays on the user's current tab.
    const tabKey = (targetId) => ({
        '#tab-inbox':     'inbox',
        '#tab-settings':  'settings',
        '#tab-broadcast': 'broadcast',
    }[targetId] ?? 'inbox');

    document.querySelectorAll('#notifTabs [data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', (e) => {
            const key = tabKey(e.target.getAttribute('data-bs-target'));
            ['inbox', 'settings', 'broadcast'].forEach(name => {
                const show = name === key;
                document.querySelectorAll('.notif-subtitle-' + name + ', .notif-actions-' + name)
                    .forEach(el => el.style.display = show ? '' : 'none');
            });
            const url = new URL(window.location.href);
            if (key === 'inbox') url.searchParams.delete('tab');
            else url.searchParams.set('tab', key);
            window.history.replaceState({}, '', url);
        });
    });

    /* -----------------------------------------------------------
     * Admin push setup panel
     * -----------------------------------------------------------
     * Mirrors the push toggle logic from profile-hub/notifications
     * so the admin can subscribe / reset / disable without leaving
     * this page. "Reset" unsubscribes and resubscribes in one click
     * — useful when an old endpoint is stale.
     */
    (function () {
        const panel      = document.getElementById('jambo-admin-push-panel');
        const statusEl   = document.getElementById('jambo-push-status');
        const enableBtn  = document.getElementById('jambo-push-enable');
        const resetBtn   = document.getElementById('jambo-push-reset');
        const disableBtn = document.getElementById('jambo-push-disable');
        if (!panel || !statusEl) return;

        const supported = 'serviceWorker' in navigator
            && 'PushManager' in window
            && 'Notification' in window;

        if (!supported) {
            statusEl.innerHTML = '<span class="text-muted">Your browser does not support push notifications.</span>';
            return;
        }

        const vapidPublic = @json(config('webpush.vapid.public_key'));
        const swUrl       = @json(url('/sw.js'));
        const swScope     = @json(rtrim(parse_url(url('/'), PHP_URL_PATH) ?: '/', '/') . '/');
        const subscribeUrl   = @json(route('notifications.push.subscribe'));
        const unsubscribeUrl = @json(route('notifications.push.unsubscribe'));

        if (!vapidPublic) {
            statusEl.innerHTML = '<span class="text-muted">Push is not configured on this server (missing VAPID keys).</span>';
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
            const existing = await navigator.serviceWorker.getRegistration(swScope);
            if (!existing) await navigator.serviceWorker.register(swUrl, { scope: swScope });
            return navigator.serviceWorker.ready;
        }

        async function ensurePermission() {
            if (Notification.permission === 'granted') return;
            if (Notification.permission === 'denied') {
                throw new Error('Notifications are blocked for this site. Click the lock icon in the address bar → Site settings → Notifications → Allow, then reload.');
            }
            const result = await Notification.requestPermission();
            if (result !== 'granted') throw new Error('You need to allow notifications in the browser prompt.');
        }

        async function subscribeFresh() {
            await ensurePermission();
            const reg = await registerSW();
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublic),
            });
            const payload = sub.toJSON();
            const res = await fetch(subscribeUrl, {
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
            if (!res.ok) throw new Error('Server rejected subscription (' + res.status + ').');
        }

        async function unsubscribeCurrent() {
            const reg = await registerSW();
            const sub = await reg.pushManager.getSubscription();
            if (!sub) return;
            const endpoint = sub.endpoint;
            await sub.unsubscribe();
            await fetch(unsubscribeUrl, {
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

        async function refreshStatus() {
            enableBtn.style.display  = 'none';
            resetBtn.style.display   = 'none';
            disableBtn.style.display = 'none';

            if (Notification.permission === 'denied') {
                statusEl.innerHTML = '<span class="text-danger">Blocked by the browser. Allow notifications in site settings and reload.</span>';
                return;
            }

            try {
                const reg = await navigator.serviceWorker.getRegistration(swScope);
                const sub = reg ? await reg.pushManager.getSubscription() : null;

                if (sub) {
                    statusEl.innerHTML = '<span class="text-success">Subscribed on this device.</span>';
                    resetBtn.style.display   = '';
                    disableBtn.style.display = '';
                } else {
                    statusEl.innerHTML = '<span class="text-muted">Not subscribed on this device. Enable to start receiving push tests.</span>';
                    enableBtn.style.display = '';
                }
            } catch (err) {
                statusEl.innerHTML = '<span class="text-danger">Could not check subscription: ' + (err.message || err) + '</span>';
            }
        }

        async function runWithButton(btn, action, okLabel) {
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-spinner-gap me-1"></i> Working…';
            try {
                await action();
                statusEl.innerHTML = '<span class="text-success">' + okLabel + '</span>';
            } catch (err) {
                statusEl.innerHTML = '<span class="text-danger">' + (err.message || 'Action failed.') + '</span>';
            } finally {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                setTimeout(refreshStatus, 400);
            }
        }

        enableBtn.addEventListener('click', () => {
            runWithButton(enableBtn, subscribeFresh, 'Push enabled on this device. Try the Test push button above.');
        });

        resetBtn.addEventListener('click', () => {
            runWithButton(resetBtn, async () => {
                await unsubscribeCurrent();
                await subscribeFresh();
            }, 'Subscription reset. You can now run Test push.');
        });

        disableBtn.addEventListener('click', () => {
            runWithButton(disableBtn, unsubscribeCurrent, 'Push disabled on this device.');
        });

        refreshStatus();
    })();
})();
</script>
@endsection
