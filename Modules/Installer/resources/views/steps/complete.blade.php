@extends('installer::layouts.wizard', ['currentStep' => 6, 'title' => 'Install Jambo — Complete'])

@section('content')
    <div style="text-align: center; padding: 20px 0;">
        <div style="font-size: 48px; color: var(--jambo-success); margin-bottom: 8px;">✓</div>
        <h1 style="margin-bottom: 8px;">Installation complete</h1>
        <p class="lede" style="margin-bottom: 28px;">
            Jambo is ready. You can sign in with the admin account you just created.
        </p>

        <a href="{{ $loginUrl }}" class="btn btn-primary">Go to sign in →</a>
    </div>

    <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--jambo-border); font-size: 13px; color: var(--jambo-muted);">
        <strong>Next steps:</strong>
        <ul style="margin: 10px 0 0; padding-left: 18px;">
            <li>Review the admin dashboard and change the default Prime Video theme if you'd like.</li>
            <li>Configure payment credentials under Settings → Payments when the Payments module ships.</li>
            <li>The wizard is now locked. To re-run it on a developer machine, run <code>php artisan jambo:reset-install</code>.</li>
        </ul>
    </div>
@endsection
