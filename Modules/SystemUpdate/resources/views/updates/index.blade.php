@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">System updates</h4>
                        <p class="text-muted mb-0 mt-1" style="font-size:13px;">Check for, review, and apply releases of Jambo.</p>
                    </div>
                    <div>
                        <span class="badge bg-primary" id="current-version-badge">v{{ $status['current'] }}</span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded border h-100" style="background: rgba(26, 152, 255, 0.05); border-color: rgba(26, 152, 255, 0.2) !important;">
                                <div class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Installed version</div>
                                <div class="fw-bold mt-1" style="font-size:22px;" id="current-version">{{ $status['current'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded border h-100" style="background: rgba(45, 212, 122, 0.05); border-color: rgba(45, 212, 122, 0.2) !important;">
                                <div class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Latest available</div>
                                <div class="fw-bold mt-1" style="font-size:22px;" id="latest-version">
                                    {{ $status['latest'] ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="status-panel" class="mt-4">
                        @if ($status['has_update'])
                            <div class="alert alert-success mb-3">
                                <strong>Update available:</strong> {{ $status['latest'] }}
                                @if (!empty($status['manifest']['description']))
                                    <div class="mt-1" style="font-size:13px;">{{ $status['manifest']['description'] }}</div>
                                @endif
                            </div>
                        @elseif ($status['latest'] === null)
                            <div class="alert alert-warning mb-3">
                                Could not reach the release manifest. Check <code>config/systemupdate.php</code> and your network.
                            </div>
                        @else
                            <div class="alert alert-info mb-3">
                                Your installation is up to date.
                                @if (!empty($status['manifest']['description']))
                                    <div class="mt-1" style="font-size:13px;">{{ $status['manifest']['description'] }}</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary" id="check-btn">
                            <i class="ph ph-arrows-clockwise me-1"></i> Check for updates
                        </button>
                        <button type="button" class="btn btn-primary" id="run-btn" @disabled(!$status['has_update'])>
                            <i class="ph ph-download-simple me-1"></i> Install update
                        </button>
                    </div>

                    <div id="run-panel" class="mt-4" style="display:none;">
                        <h6 class="mb-2">Update log</h6>
                        <pre id="run-log" class="p-3 rounded" style="background:#0b0f17;color:#e7ecf3;font-size:12px;max-height:400px;overflow:auto;border:1px solid #1f2738;"></pre>
                    </div>
                </div>

                <div class="card-footer text-muted" style="font-size:12px;">
                    Running the installer is destructive — it overwrites files, runs migrations, and restarts the app.
                    Back up the database and file system before major upgrades.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const checkUrl = @json($checkUrl);
    const runUrl = @json($runUrl);

    const checkBtn = document.getElementById('check-btn');
    const runBtn = document.getElementById('run-btn');
    const statusPanel = document.getElementById('status-panel');
    const latest = document.getElementById('latest-version');
    const currentEl = document.getElementById('current-version');
    const badge = document.getElementById('current-version-badge');
    const runPanel = document.getElementById('run-panel');
    const runLog = document.getElementById('run-log');

    function renderStatus(status) {
        latest.textContent = status.latest || '—';
        currentEl.textContent = status.current;
        badge.textContent = 'v' + status.current;
        let html;
        if (status.has_update) {
            const desc = status.manifest && status.manifest.description
                ? '<div class="mt-1" style="font-size:13px;">' + escapeHtml(status.manifest.description) + '</div>' : '';
            html = '<div class="alert alert-success mb-3"><strong>Update available:</strong> ' + escapeHtml(status.latest) + desc + '</div>';
            runBtn.disabled = false;
        } else if (status.latest === null || status.latest === undefined) {
            html = '<div class="alert alert-warning mb-3">Could not reach the release manifest.</div>';
            runBtn.disabled = true;
        } else {
            html = '<div class="alert alert-info mb-3">Your installation is up to date.</div>';
            runBtn.disabled = true;
        }
        statusPanel.innerHTML = html;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    checkBtn.addEventListener('click', async () => {
        checkBtn.disabled = true;
        checkBtn.innerHTML = '<i class="ph ph-arrows-clockwise me-1"></i> Checking…';
        try {
            const res = await fetch(checkUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const json = await res.json();
            renderStatus(json);
        } catch (e) {
            statusPanel.innerHTML = '<div class="alert alert-danger mb-3">Failed: ' + escapeHtml(e.message) + '</div>';
        } finally {
            checkBtn.disabled = false;
            checkBtn.innerHTML = '<i class="ph ph-arrows-clockwise me-1"></i> Check for updates';
        }
    });

    runBtn.addEventListener('click', async () => {
        if (!confirm('This will put the site into maintenance mode, overwrite files, and run migrations. Continue?')) {
            return;
        }
        runBtn.disabled = true;
        checkBtn.disabled = true;
        runPanel.style.display = 'block';
        runLog.textContent = 'Starting…\n';

        try {
            const res = await fetch(runUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const json = await res.json();
            runLog.textContent = (json.messages || []).join('\n') + '\n';
            if (!json.ok) {
                runLog.textContent += '\nERROR: ' + (json.error || 'unknown') + '\n';
                runBtn.disabled = false;
                checkBtn.disabled = false;
            } else {
                runLog.textContent += '\nDone. Reloading in 3s…\n';
                setTimeout(() => location.reload(), 3000);
            }
        } catch (e) {
            runLog.textContent += '\nRequest failed: ' + e.message + '\n';
            runBtn.disabled = false;
            checkBtn.disabled = false;
        }
    });
})();
</script>
@endsection
