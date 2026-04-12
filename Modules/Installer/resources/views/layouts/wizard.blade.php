<!DOCTYPE html>
<html lang="en" class="theme-fs-md">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Install Jambo' }}</title>

    @php($appPath = parse_url(url('/'), PHP_URL_PATH) ?: '')

    <link rel="shortcut icon" href="{{ rtrim($appPath, '/') }}/frontend/images/favicon.ico" />

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --jambo-primary: #1A98FF;
            --jambo-primary-hover: #147acc;
            --jambo-bg: #0b0f17;
            --jambo-panel: #111724;
            --jambo-border: #1f2738;
            --jambo-muted: #8791a3;
            --jambo-text: #e7ecf3;
            --jambo-success: #2dd47a;
            --jambo-danger: #ef4444;
            --jambo-warning: #f59e0b;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: var(--jambo-bg);
            color: var(--jambo-text);
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        .wizard-shell {
            max-width: 880px;
            margin: 0 auto;
            padding: 48px 24px 64px;
        }

        .wizard-brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .wizard-brand .logo {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--jambo-primary);
        }

        .wizard-brand .subtitle {
            color: var(--jambo-muted);
            font-size: 14px;
            margin-top: 4px;
        }

        .wizard-steps {
            display: flex;
            gap: 8px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .wizard-steps .step {
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--jambo-panel);
            border: 1px solid var(--jambo-border);
            color: var(--jambo-muted);
            font-size: 12px;
            font-weight: 500;
        }

        .wizard-steps .step.active {
            background: var(--jambo-primary);
            color: #fff;
            border-color: var(--jambo-primary);
        }

        .wizard-steps .step.done {
            background: rgba(45, 212, 122, 0.15);
            border-color: var(--jambo-success);
            color: var(--jambo-success);
        }

        .wizard-panel {
            background: var(--jambo-panel);
            border: 1px solid var(--jambo-border);
            border-radius: 14px;
            padding: 32px 36px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .wizard-panel h1 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 600;
        }

        .wizard-panel .lede {
            margin: 0 0 24px;
            color: var(--jambo-muted);
            font-size: 14px;
        }

        .wizard-panel .form-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 18px;
        }

        .wizard-panel .form-row label {
            font-size: 13px;
            font-weight: 500;
            color: var(--jambo-text);
        }

        .wizard-panel .form-row input,
        .wizard-panel .form-row select {
            background: #0b0f17;
            border: 1px solid var(--jambo-border);
            border-radius: 8px;
            padding: 10px 12px;
            color: var(--jambo-text);
            font-size: 14px;
            font-family: inherit;
            width: 100%;
            transition: border-color 0.15s;
        }

        .wizard-panel .form-row input:focus,
        .wizard-panel .form-row select:focus {
            outline: none;
            border-color: var(--jambo-primary);
        }

        .wizard-panel .form-row .help {
            font-size: 12px;
            color: var(--jambo-muted);
        }

        .wizard-panel .form-row.error input,
        .wizard-panel .form-row.error select {
            border-color: var(--jambo-danger);
        }

        .wizard-panel .form-row .error-text {
            font-size: 12px;
            color: var(--jambo-danger);
        }

        .wizard-panel .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0 16px;
        }

        .wizard-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--jambo-border);
        }

        .btn {
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--jambo-primary);
            color: #fff;
        }

        .btn-primary:hover { background: var(--jambo-primary-hover); }
        .btn-primary:disabled {
            background: var(--jambo-border);
            color: var(--jambo-muted);
            cursor: not-allowed;
        }

        .btn-ghost {
            background: transparent;
            color: var(--jambo-muted);
            border-color: var(--jambo-border);
        }

        .btn-ghost:hover { color: var(--jambo-text); border-color: var(--jambo-muted); }

        .req-list { list-style: none; padding: 0; margin: 0; }
        .req-list li {
            display: flex;
            justify-content: space-between;
            padding: 12px 14px;
            border-bottom: 1px solid var(--jambo-border);
            font-size: 14px;
        }
        .req-list li:last-child { border-bottom: none; }
        .req-list .pass { color: var(--jambo-success); font-weight: 500; }
        .req-list .fail { color: var(--jambo-danger); font-weight: 500; }

        .notice {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .notice.error { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .notice.success { background: rgba(45, 212, 122, 0.1); color: #86efac; border: 1px solid rgba(45, 212, 122, 0.3); }
        .notice.info { background: rgba(26, 152, 255, 0.1); color: #93c5fd; border: 1px solid rgba(26, 152, 255, 0.3); }

        .step-list { list-style: none; padding: 0; margin: 0; }
        .step-list li {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            border: 1px solid var(--jambo-border);
            border-radius: 10px;
            margin-bottom: 8px;
            transition: border-color 0.2s, background 0.2s;
        }
        .step-list li.running { border-color: var(--jambo-primary); background: rgba(26, 152, 255, 0.05); }
        .step-list li.done { border-color: var(--jambo-success); background: rgba(45, 212, 122, 0.05); }
        .step-list li.failed { border-color: var(--jambo-danger); background: rgba(239, 68, 68, 0.05); }
        .step-list .dot {
            width: 10px; height: 10px; border-radius: 999px;
            background: var(--jambo-border); flex-shrink: 0;
        }
        .step-list .running .dot { background: var(--jambo-primary); animation: pulse 1s infinite; }
        .step-list .done .dot { background: var(--jambo-success); }
        .step-list .failed .dot { background: var(--jambo-danger); }
        .step-list .label { flex: 1; font-size: 14px; }
        .step-list .status { font-size: 12px; color: var(--jambo-muted); }

        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    </style>
</head>
<body>
    <div class="wizard-shell">
        <div class="wizard-brand">
            <div class="logo">JAMBO</div>
            <div class="subtitle">Installation Wizard</div>
        </div>

        @php($currentStep = $currentStep ?? 1)
        <div class="wizard-steps">
            @foreach ([1 => 'Requirements', 2 => 'Database', 3 => 'Settings', 4 => 'Admin', 5 => 'Run', 6 => 'Done'] as $n => $label)
                <div class="step {{ $n < $currentStep ? 'done' : ($n === $currentStep ? 'active' : '') }}">
                    {{ $n }}. {{ $label }}
                </div>
            @endforeach
        </div>

        <div class="wizard-panel">
            @if (session('error'))
                <div class="notice error">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="notice success">{{ session('success') }}</div>
            @endif

            @yield('content')
        </div>
    </div>
</body>
</html>
