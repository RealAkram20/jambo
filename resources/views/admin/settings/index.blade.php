@extends('layouts.app')

@section('title', __('sidebar.settings'))

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Maintenance mode ===================================== --}}
            {{-- Lives at the top because it's the most safety-critical
                 toggle on the page: flipping it on takes the public site
                 offline for non-admin visitors. Admins keep working. --}}
            <form action="{{ route('admin.settings.maintenance') }}" method="POST">
                @csrf

                @php
                    $maintenanceOn = (bool) setting('maintenance_enabled');
                @endphp

                <div class="card border-{{ $maintenanceOn ? 'warning' : '' }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">
                                <i class="ph ph-warning-circle me-1"></i> Maintenance mode
                                @if ($maintenanceOn)
                                    <span class="badge bg-warning text-dark ms-2">Active</span>
                                @endif
                            </h4>
                            <small class="text-secondary">
                                When ON, only admins can use the site. Everyone else sees the maintenance page.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ph ph-check me-1"></i> Save maintenance
                        </button>
                    </div>
                    <div class="card-body">
                        @if (session('status_maintenance'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status_maintenance') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="maintenance_enabled" value="0">
                                    <input type="checkbox" class="form-check-input" role="switch"
                                        id="maintenance_enabled" name="maintenance_enabled" value="1"
                                        {{ $maintenanceOn ? 'checked' : '' }}>
                                    <label class="form-check-label" for="maintenance_enabled">
                                        Enable maintenance mode
                                    </label>
                                </div>
                                <small class="text-secondary d-block mt-1">
                                    The /login page stays open so you can sign back in if you log out by mistake.
                                </small>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label" for="maintenance_message">
                                    Message shown to visitors
                                </label>
                                <textarea name="maintenance_message" id="maintenance_message" rows="3"
                                    class="form-control @error('maintenance_message') is-invalid @enderror"
                                    placeholder="We're updating Jambo with some improvements. Be right back.">{{ old('maintenance_message', setting('maintenance_message')) }}</textarea>
                                @error('maintenance_message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-secondary">
                                    Plain text. Shown below the headline on the maintenance page.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="maintenance_until">
                                    Back by <span class="text-secondary">(optional)</span>
                                </label>
                                <input type="datetime-local" name="maintenance_until" id="maintenance_until"
                                    class="form-control @error('maintenance_until') is-invalid @enderror"
                                    value="{{ old('maintenance_until', setting('maintenance_until')) }}">
                                @error('maintenance_until')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-secondary">
                                    Drives the countdown on the maintenance page. Leave blank for "soon".
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- General ============================================ --}}
            <form action="{{ route('admin.settings.general') }}" method="POST" class="mt-4">
                @csrf

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">General</h4>
                            <small class="text-secondary">Application identity and SEO</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ph ph-check me-1"></i> Save general
                        </button>
                    </div>
                    <div class="card-body">
                        @if (session('status_general'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status_general') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="app_name">
                                    Application Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="app_name" id="app_name"
                                    class="form-control @error('app_name') is-invalid @enderror"
                                    value="{{ old('app_name', setting('app_name') ?? config('app.name')) }}" required>
                                @error('app_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="meta_description">Meta Description</label>
                                <textarea name="meta_description" id="meta_description" rows="3"
                                    class="form-control @error('meta_description') is-invalid @enderror"
                                    placeholder="Short site description used in <meta name='description'> and social previews.">{{ old('meta_description', setting('meta_description')) }}</textarea>
                                @error('meta_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-secondary">Recommended: 150–160 characters.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Branding =========================================== --}}
            <form action="{{ route('admin.settings.branding') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">Branding</h4>
                            <small class="text-secondary">Logo, favicon and preloader</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ph ph-check me-1"></i> Save branding
                        </button>
                    </div>
                    <div class="card-body">
                        @if (session('status_branding'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status_branding') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="row g-4">
                            @php
                                $fields = [
                                    [
                                        'key' => 'logo',
                                        'label' => 'Logo',
                                        'hint' => 'PNG / SVG / WEBP · up to 2 MB',
                                        'fallback' => 'dashboard/images/logo-full.png',
                                        'bg' => 'bg-dark',
                                        'accept' => ['png', 'jpg', 'jpeg', 'svg', 'webp'],
                                    ],
                                    [
                                        'key' => 'favicon',
                                        'label' => 'Favicon',
                                        'hint' => 'ICO / PNG / SVG · up to 512 KB',
                                        'fallback' => 'dashboard/images/favicon.ico',
                                        'bg' => 'bg-body-tertiary',
                                        'accept' => ['ico', 'png', 'svg'],
                                    ],
                                    [
                                        'key' => 'preloader',
                                        'label' => 'Preloader',
                                        'hint' => 'GIF / PNG / SVG · up to 2 MB',
                                        'fallback' => 'dashboard/images/loader.gif',
                                        'bg' => 'bg-dark',
                                        'accept' => ['gif', 'png', 'svg', 'webp'],
                                    ],
                                ];
                            @endphp

                            @foreach ($fields as $f)
                                <div class="col-md-4">
                                    <label class="form-label">{{ $f['label'] }}</label>
                                    <div class="border rounded p-3 text-center {{ $f['bg'] }}"
                                        style="min-height: 140px; display:flex; align-items:center; justify-content:center;">
                                        <img src="{{ branding_asset($f['key'], $f['fallback']) }}"
                                            alt="{{ $f['label'] }}" class="img-fluid"
                                            data-branding-preview="{{ $f['key'] }}"
                                            style="max-height: 110px; object-fit: contain;">
                                    </div>

                                    <div class="input-group mt-2">
                                        <input type="text" name="{{ $f['key'] }}_url"
                                            id="{{ $f['key'] }}_url"
                                            class="form-control @error($f['key'] . '_url') is-invalid @enderror"
                                            value="{{ old($f['key'] . '_url', setting($f['key'])) }}"
                                            placeholder="/storage/media/... or paste URL"
                                            data-branding-url="{{ $f['key'] }}">
                                        <button type="button" class="btn btn-primary"
                                            onclick='JamboMediaPicker.open({ target: "{{ $f['key'] }}_url", preview: "[data-branding-preview={{ $f['key'] }}]", accept: @json($f['accept']) })'>
                                            <i class="ph ph-folder-open me-1"></i> Browse
                                        </button>
                                    </div>

                                    <details class="mt-2">
                                        <summary class="small text-secondary" style="cursor:pointer;">
                                            or upload directly from your computer
                                        </summary>
                                        <input type="file" name="{{ $f['key'] }}" id="{{ $f['key'] }}"
                                            class="form-control mt-2 @error($f['key']) is-invalid @enderror">
                                    </details>

                                    <small class="text-secondary d-block mt-1">{{ $f['hint'] }}</small>
                                    @error($f['key'])
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error($f['key'] . '_url')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>

            {{-- SMTP =============================================== --}}
            <form action="{{ route('admin.settings.smtp') }}" method="POST">
                @csrf

                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">Email (SMTP)</h4>
                            <small class="text-secondary">
                                Outgoing mail server used for user notifications, receipts, and password resets.
                                Leave the password blank when re-saving other fields to keep the existing password.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ph ph-check me-1"></i> Save SMTP
                        </button>
                    </div>
                    <div class="card-body">
                        @if (session('status_smtp'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ph ph-check-circle me-1"></i> {{ session('status_smtp') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @if (session('smtp_status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ph ph-check-circle me-1"></i> {{ session('smtp_status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @if (session('smtp_error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="ph ph-warning-circle me-1"></i> {{ session('smtp_error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="mail_host">SMTP Host</label>
                                <input type="text" name="mail_host" id="mail_host"
                                    class="form-control @error('mail_host') is-invalid @enderror"
                                    value="{{ old('mail_host', setting('mail_host')) }}"
                                    placeholder="smtp.gmail.com">
                                @error('mail_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="mail_port">Port</label>
                                <input type="number" name="mail_port" id="mail_port"
                                    class="form-control @error('mail_port') is-invalid @enderror"
                                    value="{{ old('mail_port', setting('mail_port')) }}"
                                    placeholder="587">
                                @error('mail_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mail_username">Username</label>
                                <input type="text" name="mail_username" id="mail_username"
                                    class="form-control @error('mail_username') is-invalid @enderror"
                                    value="{{ old('mail_username', setting('mail_username')) }}"
                                    placeholder="user@example.com"
                                    autocomplete="off">
                                @error('mail_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mail_password">Password</label>
                                <x-password-input
                                    name="mail_password"
                                    placeholder="{{ setting('mail_password') ? '•••••••• (leave blank to keep current)' : 'SMTP password or app-specific password' }}"
                                    autocomplete="new-password"
                                    class="@error('mail_password') is-invalid @enderror"
                                />
                                @error('mail_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="mail_encryption">Encryption</label>
                                <select name="mail_encryption" id="mail_encryption"
                                    class="form-select @error('mail_encryption') is-invalid @enderror">
                                    @php $enc = old('mail_encryption', setting('mail_encryption') ?: 'tls'); @endphp
                                    <option value="tls" @selected($enc === 'tls')>TLS (port 587)</option>
                                    <option value="ssl" @selected($enc === 'ssl')>SSL (port 465)</option>
                                    <option value="none" @selected($enc === 'none')>None (port 25)</option>
                                </select>
                                @error('mail_encryption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="mail_from_address">From Address</label>
                                <input type="email" name="mail_from_address" id="mail_from_address"
                                    class="form-control @error('mail_from_address') is-invalid @enderror"
                                    value="{{ old('mail_from_address', setting('mail_from_address')) }}"
                                    placeholder="noreply@yourdomain.com">
                                @error('mail_from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="mail_from_name">From Name</label>
                                <input type="text" name="mail_from_name" id="mail_from_name"
                                    class="form-control @error('mail_from_name') is-invalid @enderror"
                                    value="{{ old('mail_from_name', setting('mail_from_name') ?: config('app.name')) }}"
                                    placeholder="{{ config('app.name') }}">
                                @error('mail_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top">
                            <small class="text-secondary flex-grow-1">
                                Save first, then send a test to verify delivery.
                            </small>
                            <button type="submit"
                                    formaction="{{ route('admin.settings.smtp-test') }}"
                                    class="btn btn-outline-primary btn-sm">
                                <i class="ph ph-paper-plane-tilt me-1"></i> Send test email
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Web Push (VAPID) =================================== --}}
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-0">Web Push (VAPID)</h4>
                        <small class="text-secondary">
                            Credentials used to sign browser push payloads. Paste a pair you already generated,
                            or use the server-side generator. Changing the public key invalidates every existing
                            subscription — users must re-enable push on their devices.
                        </small>
                    </div>
                    <form action="{{ route('admin.settings.vapid-generate') }}" method="POST"
                          onsubmit="return confirm('Generate fresh VAPID keys? All existing push subscriptions will stop working until users resubscribe.');"
                          class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="ph ph-magic-wand me-1"></i> Generate new keys
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    @if (session('status_vapid'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ph ph-check-circle me-1"></i> {{ session('status_vapid') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session('vapid_error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ph ph-warning-circle me-1"></i> {{ session('vapid_error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.settings.vapid') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="vapid_public_key">
                                    Public key <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="vapid_public_key" id="vapid_public_key"
                                    class="form-control font-monospace @error('vapid_public_key') is-invalid @enderror"
                                    style="font-size:12px;"
                                    value="{{ old('vapid_public_key', setting('webpush_vapid_public_key') ?: config('webpush.vapid.public_key')) }}"
                                    required>
                                @error('vapid_public_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="vapid_subject">
                                    Subject <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="vapid_subject" id="vapid_subject"
                                    class="form-control @error('vapid_subject') is-invalid @enderror"
                                    value="{{ old('vapid_subject', setting('webpush_vapid_subject') ?: config('webpush.vapid.subject')) }}"
                                    placeholder="mailto:admin@example.com"
                                    required>
                                @error('vapid_subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-secondary" style="font-size:11px;">Must start with <code>mailto:</code> or <code>https://</code>.</small>
                            </div>
                            <div class="col-12">
                                @php
                                    $privateStored = (bool) setting('webpush_vapid_private_key') || !empty(config('webpush.vapid.private_key'));
                                @endphp
                                <label class="form-label" for="vapid_private_key">
                                    Private key
                                    @if ($privateStored)
                                        <small class="text-success" style="font-size:11px;">(already stored — leave blank to keep)</small>
                                    @else
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <x-password-input
                                    name="vapid_private_key"
                                    placeholder="{{ $privateStored ? '•••••••••••••••••••••••• (leave blank to keep existing)' : 'paste the 43-char base64url private key' }}"
                                    autocomplete="new-password"
                                    :required="!$privateStored"
                                    class="font-monospace @error('vapid_private_key') is-invalid @enderror"
                                    style="font-size:12px;"
                                />
                                @error('vapid_private_key')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <small class="text-secondary" style="font-size:11px;">
                                    Stored encrypted at rest (Laravel Crypt). Never leaves the server after save.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top">
                            <small class="text-secondary flex-grow-1">
                                After saving, open <a href="{{ route('notifications.index', ['tab' => 'settings']) }}">Notifications → Settings</a> to run a push test.
                            </small>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ph ph-check me-1"></i> Save VAPID
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- reCAPTCHA ============================================ --}}
            {{-- Optional bot protection on register / login / forgot-
                 password forms. Off by default — the honeypot field
                 and rate limit defences are unconditional and usually
                 enough. Enable here if you start seeing organised
                 signup spam that bypasses both. --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Google reCAPTCHA</h4>
                    @php $rc = (bool) setting('recaptcha_enabled'); @endphp
                    <span class="badge bg-{{ $rc ? 'success' : 'secondary' }}">
                        {{ $rc ? 'enabled' : 'disabled' }}
                    </span>
                </div>

                @if (session('status_recaptcha'))
                    <div class="alert alert-success rounded-0 mb-0">
                        <i class="ph ph-check-circle me-1"></i> {{ session('status_recaptcha') }}
                    </div>
                @endif

                <div class="card-body">
                    <p class="text-secondary small mb-3">
                        Get keys from
                        <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">Google reCAPTCHA admin</a>.
                        Pick <strong>v2 "I'm not a robot"</strong> for a visible checkbox, or
                        <strong>v3</strong> for invisible scoring (no UX friction).
                    </p>

                    <form action="{{ route('admin.settings.recaptcha') }}" method="POST">
                        @csrf

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="recaptcha_enabled" value="0">
                            <input type="checkbox"
                                   class="form-check-input"
                                   id="recaptcha_enabled"
                                   name="recaptcha_enabled"
                                   value="1"
                                   {{ setting('recaptcha_enabled') ? 'checked' : '' }}>
                            <label class="form-check-label" for="recaptcha_enabled">
                                Enable reCAPTCHA on register / login / forgot-password forms
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Version</label>
                            @php $rcVersion = setting('recaptcha_version') === 'v3' ? 'v3' : 'v2'; @endphp
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="recaptcha_version"
                                           id="recaptcha_v2" value="v2" {{ $rcVersion === 'v2' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="recaptcha_v2">
                                        v2 ("I'm not a robot" checkbox)
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="recaptcha_version"
                                           id="recaptcha_v3" value="v3" {{ $rcVersion === 'v3' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="recaptcha_v3">
                                        v3 (invisible, score-based)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="recaptcha_site_key" class="form-label">Site key (public)</label>
                                <input type="text"
                                       class="form-control"
                                       id="recaptcha_site_key"
                                       name="recaptcha_site_key"
                                       value="{{ setting('recaptcha_site_key') }}"
                                       placeholder="6Lc...">
                            </div>
                            <div class="col-md-6">
                                <label for="recaptcha_secret_key" class="form-label">
                                    Secret key
                                    @if (setting('recaptcha_secret_key'))
                                        <small class="text-success">(currently set — leave blank to keep)</small>
                                    @endif
                                </label>
                                <input type="password"
                                       class="form-control"
                                       id="recaptcha_secret_key"
                                       name="recaptcha_secret_key"
                                       value=""
                                       placeholder="6Lc..."
                                       autocomplete="off">
                            </div>
                            <div class="col-md-6" id="recaptcha-threshold-row" style="{{ $rcVersion === 'v3' ? '' : 'display:none' }}">
                                <label for="recaptcha_score_threshold" class="form-label">
                                    v3 score threshold
                                </label>
                                <input type="number"
                                       step="0.05"
                                       min="0.1"
                                       max="0.9"
                                       class="form-control"
                                       id="recaptcha_score_threshold"
                                       name="recaptcha_score_threshold"
                                       value="{{ setting('recaptcha_score_threshold', '0.5') }}">
                                <small class="text-secondary">
                                    Submissions scoring below this are rejected. Google's recommended default is 0.5.
                                </small>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ph ph-check me-1"></i> Save reCAPTCHA
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mb-5"></div>
        </div>
    </div>

    <script>
        // Show / hide v3 score threshold based on version radio
        document.querySelectorAll('input[name="recaptcha_version"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var row = document.getElementById('recaptcha-threshold-row');
                if (row) row.style.display = this.value === 'v3' ? '' : 'none';
            });
        });

        document.querySelectorAll('[data-branding-url]').forEach(function (input) {
            input.addEventListener('input', function () {
                const key = input.getAttribute('data-branding-url');
                const preview = document.querySelector('[data-branding-preview="' + key + '"]');
                if (preview && input.value.trim()) preview.src = input.value.trim();
            });
        });
    </script>
@endsection
