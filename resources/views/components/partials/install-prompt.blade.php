{{--
    Install-the-app prompt — bottom banner that mirrors the push
    soft-prompt UX. Fires once the visitor has been on the site for
    ~45 seconds (cumulative across navigation, sessionStorage-tracked).

      - Android Chrome / desktop Chrome / Edge: catches the
        `beforeinstallprompt` event, shows our own banner, and on
        Install click fires the saved native event.
      - iOS Safari: no `beforeinstallprompt`, so the banner expands
        inline with the Share-button → "Add to Home Screen" steps.

    Suppressed when:
      - Already in standalone mode (already installed).
      - The user dismissed the banner before (localStorage).
      - On Android, no `beforeinstallprompt` was captured (criteria
        not yet met — manifest, https, valid icons all required).
--}}
<div id="jambo-install-prompt"
     style="position:fixed;left:50%;bottom:16px;transform:translateX(-50%) translateY(160%);
            transition:transform .35s ease;z-index:1075;
            max-width:520px;width:calc(100% - 32px);
            background:#10131c;color:#fff;border:1px solid rgba(255,255,255,.08);
            border-radius:14px;box-shadow:0 18px 40px rgba(0,0,0,.45);
            padding:16px 18px;display:none;font-family:Roboto,system-ui,sans-serif;">
    <div style="display:flex;gap:14px;align-items:flex-start;">
        <img src="{{ asset('icons/jambo-192.png') }}" alt="Jambo"
             style="flex:0 0 auto;width:42px;height:42px;border-radius:11px;
                    background:#fff;padding:3px;">
        <div style="flex:1 1 auto;min-width:0;">
            <div style="font-weight:600;font-size:15px;margin-bottom:4px;">
                Install Jambo
            </div>
            <div id="jambo-install-copy" style="font-size:13px;line-height:1.45;color:#c2c8d4;">
                Add it to your home screen for faster access — no browser bar, full screen.
            </div>

            {{-- iOS-only inline instructions, hidden by default. --}}
            <ol id="jambo-install-ios-steps" style="display:none;margin:10px 0 0;padding-left:18px;
                       font-size:12.5px;line-height:1.65;color:#c2c8d4;">
                <li>
                    Tap the <strong style="color:#fff;">Share</strong>
                    <span aria-hidden="true"
                          style="display:inline-flex;align-items:center;justify-content:center;
                                 width:18px;height:18px;border:1.4px solid #1A98FF;border-radius:4px;
                                 vertical-align:-4px;margin:0 2px;">
                        <svg width="10" height="12" viewBox="0 0 12 14" fill="none">
                            <path d="M6 10V1.5M6 1.5L3 4.5M6 1.5L9 4.5"
                                  stroke="#1A98FF" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 8v4a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V8"
                                  stroke="#1A98FF" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    button in Safari.
                </li>
                <li>Choose <strong style="color:#fff;">Add to Home Screen</strong>.</li>
                <li>Tap <strong style="color:#fff;">Add</strong>.</li>
            </ol>

            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" id="jambo-install-go"
                        style="background:#1A98FF;color:#fff;border:0;border-radius:8px;
                               padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;">
                    Install
                </button>
                <button type="button" id="jambo-install-dismiss"
                        style="background:transparent;color:#c2c8d4;border:1px solid rgba(255,255,255,.14);
                               border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer;">
                    Not now
                </button>
            </div>
        </div>
        <button type="button" id="jambo-install-close" aria-label="Dismiss"
                style="background:transparent;color:#7d8493;border:0;font-size:18px;
                       cursor:pointer;padding:0;line-height:1;align-self:flex-start;">
            &times;
        </button>
    </div>
</div>
<script>
(function () {
    var STORAGE_KEY = 'jambo_install_prompt_state';
    var SESSION_KEY = 'jambo_install_first_seen';
    var DELAY_MS = 45 * 1000;
    var banner = document.getElementById('jambo-install-prompt');
    if (!banner || !window.JamboPWA) return;

    var pwa = window.JamboPWA;
    var deferredPrompt = null;

    function persist(state) {
        try { localStorage.setItem(STORAGE_KEY, state); } catch (e) {}
    }
    function readState() {
        try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
    }
    function firstSeen() {
        try {
            var v = sessionStorage.getItem(SESSION_KEY);
            if (v) return parseInt(v, 10);
            var now = Date.now();
            sessionStorage.setItem(SESSION_KEY, String(now));
            return now;
        } catch (e) { return Date.now(); }
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
    });

    function show(kind) {
        if (kind === 'ios') {
            document.getElementById('jambo-install-ios-steps').style.display = 'block';
            document.getElementById('jambo-install-copy').textContent =
                'Add Jambo to your home screen for full-screen access.';
            var goBtn = document.getElementById('jambo-install-go');
            goBtn.textContent = 'Got it';
        }
        banner.style.display = 'block';
        requestAnimationFrame(function () {
            banner.style.transform = 'translateX(-50%) translateY(0)';
        });
    }
    function hide() {
        banner.style.transform = 'translateX(-50%) translateY(160%)';
        setTimeout(function () { banner.style.display = 'none'; }, 400);
    }

    function attach(kind) {
        document.getElementById('jambo-install-go').addEventListener('click', function () {
            if (kind === 'ios') {
                hide(); persist('dismissed'); return;
            }
            if (!deferredPrompt) { hide(); return; }
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (choice) {
                persist(choice.outcome === 'accepted' ? 'installed' : 'dismissed');
                deferredPrompt = null;
                hide();
            });
        });
        document.getElementById('jambo-install-dismiss').addEventListener('click', function () {
            hide(); persist('dismissed');
        });
        document.getElementById('jambo-install-close').addEventListener('click', function () {
            hide(); persist('dismissed');
        });
    }

    function evaluate() {
        if (pwa.isStandalone()) return;
        if (readState() === 'dismissed' || readState() === 'installed') return;

        var ios = pwa.isIOS();
        // On Android / desktop the banner needs a captured
        // beforeinstallprompt to function; if none has fired, the
        // browser doesn't think we're installable yet.
        if (!ios && !deferredPrompt) return;

        attach(ios ? 'ios' : 'android');
        show(ios ? 'ios' : 'android');
    }

    function schedule() {
        var elapsed = Date.now() - firstSeen();
        var remaining = Math.max(0, DELAY_MS - elapsed);
        if (remaining === 0) {
            evaluate();
        } else {
            setTimeout(evaluate, remaining);
        }
    }

    if (document.readyState === 'complete') {
        schedule();
    } else {
        window.addEventListener('load', schedule);
    }
})();
</script>
