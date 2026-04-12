@extends('installer::layouts.wizard', ['currentStep' => 3, 'title' => 'Install Jambo — Settings'])

@section('content')
    <h1>Application settings</h1>
    <p class="lede">Basic identity and environment for this Jambo install.</p>

    <form method="POST" action="{{ route('install.settings.store') }}">
        @csrf

        <div class="form-row {{ $errors->has('app_name') ? 'error' : '' }}">
            <label for="app_name">Application name</label>
            <input type="text" id="app_name" name="app_name" value="{{ old('app_name', $values['app_name']) }}" required>
            <span class="help">Shown in the header, page titles, and emails.</span>
            @error('app_name') <span class="error-text">{{ $message }}</span> @enderror
        </div>

        <div class="form-row {{ $errors->has('app_url') ? 'error' : '' }}">
            <label for="app_url">Application URL</label>
            <input type="url" id="app_url" name="app_url" value="{{ old('app_url', $values['app_url']) }}" required>
            <span class="help">Fully-qualified URL without trailing slash — e.g. <code>https://jambo.co</code>.</span>
            @error('app_url') <span class="error-text">{{ $message }}</span> @enderror
        </div>

        <div class="form-row {{ $errors->has('app_env') ? 'error' : '' }}">
            <label for="app_env">Environment</label>
            <select id="app_env" name="app_env" required>
                <option value="local" @selected(old('app_env', $values['app_env']) === 'local')>Local (development)</option>
                <option value="production" @selected(old('app_env', $values['app_env']) === 'production')>Production</option>
            </select>
            <span class="help">Production disables debug output and sets stricter session cookies.</span>
            @error('app_env') <span class="error-text">{{ $message }}</span> @enderror
        </div>

        <div class="wizard-actions">
            <a href="{{ route('install.database') }}" class="btn btn-ghost">← Back</a>
            <button type="submit" class="btn btn-primary">Continue →</button>
        </div>
    </form>
@endsection
