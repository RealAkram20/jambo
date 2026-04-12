@extends('installer::layouts.wizard', ['currentStep' => 5, 'title' => 'Install Jambo — Running'])

@section('content')
    <h1>Running the install</h1>
    <p class="lede">Writing configuration, running migrations, and creating your admin account. This usually takes 10–30 seconds.</p>

    <ul class="step-list" id="step-list">
        @foreach ($stepLabels as $n => $label)
            <li data-step="{{ $n }}">
                <span class="dot"></span>
                <span class="label">{{ $label }}</span>
                <span class="status">waiting…</span>
            </li>
        @endforeach
    </ul>

    <div id="run-notice" style="display:none;"></div>

    <div class="wizard-actions">
        <span></span>
        <div style="display:flex;gap:8px;">
            <button type="button" class="btn btn-ghost" id="retry-btn" style="display:none;">Retry from failed step</button>
            <a href="{{ route('install.complete') }}" class="btn btn-primary" id="finish-btn" style="display:none;">Finish →</a>
        </div>
    </div>

    <script>
        (function () {
            const stepCount = @json($stepCount);
            const baseUrl = @json(url('/install/execute'));
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const notice = document.getElementById('run-notice');
            const finishBtn = document.getElementById('finish-btn');
            const retryBtn = document.getElementById('retry-btn');

            let lastCompleted = 0;

            function setStatus(step, state, text) {
                const li = document.querySelector(`li[data-step="${step}"]`);
                if (!li) return;
                li.classList.remove('running', 'done', 'failed');
                li.classList.add(state);
                li.querySelector('.status').textContent = text || state;
            }

            async function runStep(step) {
                setStatus(step, 'running', 'running…');
                const res = await fetch(`${baseUrl}/${step}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });
                const json = await res.json();
                if (!json.ok) {
                    setStatus(step, 'failed', json.error || 'failed');
                    notice.className = 'notice error';
                    notice.innerHTML = '<strong>Step ' + step + ' failed:</strong> ' + (json.error || 'unknown error');
                    notice.style.display = 'block';
                    retryBtn.style.display = 'inline-flex';
                    return false;
                }
                setStatus(step, 'done', 'done');
                lastCompleted = step;
                return true;
            }

            async function runAll() {
                notice.style.display = 'none';
                retryBtn.style.display = 'none';
                for (let step = lastCompleted + 1; step <= stepCount; step++) {
                    const ok = await runStep(step);
                    if (!ok) return;
                }
                notice.className = 'notice success';
                notice.textContent = 'All steps completed.';
                notice.style.display = 'block';
                finishBtn.style.display = 'inline-flex';
            }

            retryBtn.addEventListener('click', runAll);
            runAll();
        })();
    </script>
@endsection
