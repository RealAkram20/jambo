@extends('layouts.app')

@section('title', __('sidebar.settings'))

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- General ============================================ --}}
            <form action="{{ route('admin.settings.general') }}" method="POST">
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
                                <input type="password" name="mail_password" id="mail_password"
                                    class="form-control @error('mail_password') is-invalid @enderror"
                                    placeholder="{{ setting('mail_password') ? '•••••••• (leave blank to keep current)' : 'SMTP password or app-specific password' }}"
                                    autocomplete="new-password">
                                @error('mail_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

            <div class="mb-5"></div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-branding-url]').forEach(function (input) {
            input.addEventListener('input', function () {
                const key = input.getAttribute('data-branding-url');
                const preview = document.querySelector('[data-branding-preview="' + key + '"]');
                if (preview && input.value.trim()) preview.src = input.value.trim();
            });
        });
    </script>
@endsection
