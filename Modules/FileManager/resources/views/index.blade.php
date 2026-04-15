@extends('layouts.app', ['module_title' => 'File Manager'])

@section('content')
@if ($state === 'ready')
    <div class="fm-fullframe">
        <iframe id="fm-iframe"
                src="{{ url('storage/media/index.php') }}"
                title="File Manager"></iframe>
    </div>

    <style>
        #page_layout { padding: 0 !important; }
        .fm-fullframe {
            height: calc(100vh - var(--fm-topbar, 70px));
            min-height: 560px;
            background: var(--bs-body-bg, #0d0d0d);
        }
        .fm-fullframe iframe { width: 100%; height: 100%; border: 0; display: block; }
    </style>

    <script>
        (function () {
            const iframe = document.getElementById('fm-iframe');
            const html = document.documentElement;
            const currentTheme = () =>
                html.getAttribute('data-bs-theme') === 'light' ? 'light' : 'dark';

            function applyTheme() {
                try {
                    const doc = iframe.contentDocument;
                    const win = iframe.contentWindow;
                    if (!doc || !win) return;
                    const t = currentTheme();
                    doc.documentElement.dataset.theme = t;
                    try { win.localStorage.setItem('files:theme', t); } catch (_) {}
                } catch (_) {}
            }
            iframe.addEventListener('load', applyTheme);
            new MutationObserver(applyTheme).observe(html, {
                attributes: true, attributeFilter: ['data-bs-theme'],
            });
        })();
    </script>
@elseif ($state === 'install')
    <div class="container-fluid">
        <div class="alert alert-warning d-flex align-items-start gap-3 m-4" role="alert">
            <i class="ph ph-wrench fs-3 mt-1"></i>
            <div>
                <strong>Files Gallery setup required.</strong>
                <p class="mb-2" style="font-size:13px;">
                    Launch the setup wizard, then rename
                    <code>storage/app/public/media/install.php</code> → <code>index.php</code>.
                </p>
                <a href="{{ url('storage/media/install.php') }}" target="_blank" rel="noopener"
                   class="btn btn-warning btn-sm">
                    <i class="ph ph-arrow-square-out me-1"></i> Launch setup wizard
                </a>
            </div>
        </div>
    </div>
@else
    <div class="container-fluid">
        <div class="alert alert-danger m-4">Files Gallery is not installed. Expected
            <code>storage/app/public/media/index.php</code>.</div>
    </div>
@endif
@endsection
