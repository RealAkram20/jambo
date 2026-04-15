@extends('layouts.app')

@section('title', __('sidebar.settings'))

@section('content')
    <div class="row">
        <div class="col-12">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- General --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0">General</h4>
                            <small class="text-secondary">Application identity and SEO</small>
                        </div>
                    </div>
                    <div class="card-body">
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

                {{-- Branding --}}
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Branding</h4>
                        <small class="text-secondary">Logo, favicon and preloader</small>
                    </div>
                    <div class="card-body">
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

                <div class="d-flex justify-content-end mt-4 mb-5 gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-check me-1"></i> Save Settings
                    </button>
                </div>
            </form>
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
