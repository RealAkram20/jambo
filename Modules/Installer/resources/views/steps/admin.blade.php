@extends('installer::layouts.wizard', ['currentStep' => 4, 'title' => 'Install Jambo — Admin Account'])

@section('content')
    <h1>Admin account</h1>
    <p class="lede">This will become the first user on the site, with full administrator access.</p>

    <form method="POST" action="{{ route('install.admin.store') }}">
        @csrf

        <div class="grid-2">
            <div class="form-row {{ $errors->has('first_name') ? 'error' : '' }}">
                <label for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $values['first_name'] ?? '') }}" required>
                @error('first_name') <span class="error-text">{{ $message }}</span> @enderror
            </div>
            <div class="form-row {{ $errors->has('last_name') ? 'error' : '' }}">
                <label for="last_name">Last name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $values['last_name'] ?? '') }}" required>
                @error('last_name') <span class="error-text">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-row {{ $errors->has('email') ? 'error' : '' }}">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email', $values['email'] ?? '') }}" required>
            @error('email') <span class="error-text">{{ $message }}</span> @enderror
        </div>

        <div class="grid-2">
            <div class="form-row {{ $errors->has('password') ? 'error' : '' }}">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
                <span class="help">Minimum 8 characters.</span>
                @error('password') <span class="error-text">{{ $message }}</span> @enderror
            </div>
            <div class="form-row">
                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
            </div>
        </div>

        <div class="wizard-actions">
            <a href="{{ route('install.settings') }}" class="btn btn-ghost">← Back</a>
            <button type="submit" class="btn btn-primary">Continue →</button>
        </div>
    </form>
@endsection
