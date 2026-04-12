@extends('installer::layouts.wizard', ['currentStep' => 2, 'title' => 'Install Jambo — Database'])

@section('content')
    <h1>Database connection</h1>
    <p class="lede">Enter the MySQL/MariaDB credentials Jambo should use. We'll create the database if it doesn't exist yet.</p>

    <form method="POST" action="{{ route('install.database.store') }}" id="db-form">
        @csrf

        <div class="grid-2">
            <div class="form-row {{ $errors->has('host') ? 'error' : '' }}">
                <label for="host">Host</label>
                <input type="text" id="host" name="host" value="{{ old('host', $values['host']) }}" required>
                @error('host') <span class="error-text">{{ $message }}</span> @enderror
            </div>
            <div class="form-row {{ $errors->has('port') ? 'error' : '' }}">
                <label for="port">Port</label>
                <input type="text" id="port" name="port" value="{{ old('port', $values['port']) }}" required>
                @error('port') <span class="error-text">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-row {{ $errors->has('database') ? 'error' : '' }}">
            <label for="database">Database name</label>
            <input type="text" id="database" name="database" value="{{ old('database', $values['database']) }}" required>
            <span class="help">Will be created automatically if it doesn't exist.</span>
            @error('database') <span class="error-text">{{ $message }}</span> @enderror
        </div>

        <div class="grid-2">
            <div class="form-row {{ $errors->has('username') ? 'error' : '' }}">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username', $values['username']) }}" required>
                @error('username') <span class="error-text">{{ $message }}</span> @enderror
            </div>
            <div class="form-row">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="{{ old('password', $values['password']) }}">
            </div>
        </div>

        <div id="test-notice" style="display:none;"></div>

        <div class="wizard-actions">
            <a href="{{ route('install.requirements') }}" class="btn btn-ghost">← Back</a>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-ghost" id="test-btn">Test connection</button>
                <button type="submit" class="btn btn-primary">Continue →</button>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const form = document.getElementById('db-form');
            const btn = document.getElementById('test-btn');
            const notice = document.getElementById('test-notice');

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                btn.textContent = 'Testing…';
                notice.style.display = 'none';

                const fd = new FormData(form);
                try {
                    const res = await fetch(@json(route('install.database.validate')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });
                    const json = await res.json();
                    notice.className = 'notice ' + (json.ok ? 'success' : 'error');
                    notice.textContent = json.message || (json.ok ? 'Connection OK.' : 'Connection failed.');
                    notice.style.display = 'block';
                } catch (e) {
                    notice.className = 'notice error';
                    notice.textContent = 'Request failed: ' + e.message;
                    notice.style.display = 'block';
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Test connection';
                }
            });
        })();
    </script>
@endsection
