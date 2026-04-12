@extends('installer::layouts.wizard', ['currentStep' => 1, 'title' => 'Install Jambo — Requirements'])

@section('content')
    <h1>Server requirements</h1>
    <p class="lede">Checking that this server has everything Jambo needs before we start.</p>

    <ul class="req-list">
        @foreach ($rows as $row)
            <li>
                <span>{{ $row['label'] }}</span>
                <span class="{{ $row['pass'] ? 'pass' : 'fail' }}">
                    {{ $row['pass'] ? '✓' : '✗' }} {{ $row['detail'] }}
                </span>
            </li>
        @endforeach
    </ul>

    @if (!$allOk)
        <div class="notice error" style="margin-top: 16px;">
            Some requirements are missing. Please fix the red items above before continuing.
            On XAMPP, edit <code>c:/xampp/php/php.ini</code>, enable the missing extensions, and restart Apache.
        </div>
    @endif

    <div class="wizard-actions">
        <span></span>
        <a href="{{ route('install.database') }}" class="btn btn-primary" @if(!$allOk) aria-disabled="true" onclick="event.preventDefault()" style="opacity:.5;cursor:not-allowed" @endif>
            Continue →
        </a>
    </div>
@endsection
