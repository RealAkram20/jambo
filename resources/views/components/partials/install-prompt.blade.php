{{--
    Install-the-app prompt — fires ~5 minutes into the visitor's first
    session. Two flavours:

      - Android Chrome / Edge: catches `beforeinstallprompt`, suppresses
        the native banner, then triggers the saved event when the user
        clicks "Install".
      - iOS Safari: no `beforeinstallprompt` exists, so we explain the
        Share-button → "Add to Home Screen" steps with a small visual.

    Skipped entirely when:
      - Already in standalone mode (already installed).
      - The user dismissed the prompt before (localStorage).
      - The browser doesn't support either path.

    The 5-minute timer is cumulative across navigation: first visit
    timestamp is held in sessionStorage so reading multiple pages in a
    single session still triggers the prompt right around 5 min mark.
--}}
<div id="jambo-install-prompt"
     style="position:fixed;inset:0;background:rgba(8,10,16,.78);z-index:1090;
            display:none;align-items:center;justify-content:center;padding:18px;
            opacity:0;transition:opacity .25s ease;font-family:Roboto,system-ui,sans-serif;">
    <div style="background:#10131c;color:#fff;border-radius:18px;
                box-shadow:0 22px 60px rgba(0,0,0,.55);max-width:440px;width:100%;
                border:1px solid rgba(255,255,255,.07);overflow:hidden;">
        <div style="display:flex;align-items:center;gap:14px;padding:20px 22px 0;">
            <img src="{{ asset('icons/jambo-192.png') }}" alt="Jambo"
                 style="width:56px;height:56px;border-radius:14px;flex:0 0 auto;
                        background:#fff;padding:4px;">
            <div>
                <div style="font-weight:700;font-size:17px;">Install Jambo</div>
                <div style="font-size:13px;color:#c2c8d4;margin-top:2px;">
                    Add it to your home screen for quick access.
                </div>
            </div>
        </div>

        {{-- Android / desktop content. JamboPWA fires this when a
             beforeinstallprompt event has been captured. --}}
        <div id="jambo-install-android" style="display:none;padding:18px 22px 22px;">
            <p style="font-size:13.5px;line-height:1.55;color:#c2c8d4;margin:0 0 14px;">
                Tap "Install" to add Jambo to your apps. It launches like any
                other app — no browser bar, full screen.
            </p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" id="jambo-install-android-go"
                        style="background:#1A98FF;color:#fff;border:0;border-radius:9px;
                               padding:10px 16px;font-size:14px;font-weight:600;cursor:pointer;flex:1 1 auto;">
                    Install
                </button>
                <button type="button" data-jambo-install-dismiss="true"
                        style="background:transparent;color:#c2c8d4;border:1px solid rgba(255,255,255,.14);
                               border-radius:9px;padding:10px 16px;font-size:14px;cursor:pointer;">
                    Not now
                </button>
            </div>
        </div>

        {{-- iOS content with Share-button illustration. --}}
        <div id="jambo-install-ios" style="display:none;padding:18px 22px 22px;">
            <ol style="margin:0 0 14px;padding-left:18px;font-size:13.5px;line-height:1.7;color:#c2c8d4;">
                <li style="margin-bottom:6px;">
                    Tap the <strong style="color:#fff;">Share</strong> button
                    <span aria-hidden="true"
                          style="display:inline-flex;align-items:center;justify-content:center;
                                 width:22px;height:22px;border:1.5px solid #1A98FF;border-radius:5px;
                                 vertical-align:-5px;margin:0 2px;">
                        <svg width="12" height="14" viewBox="0 0 12 14" fill="none">
                            <path d="M6 10V1.5M6 1.5L3 4.5M6 1.5L9 4.5"
                                  stroke="#1A98FF" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 8v4a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V8"
                                  stroke="#1A98FF" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    in Safari's toolbar.
                </li>
                <li style="margin-bottom:6px;">
                    Scroll and tap <strong style="color:#fff;">Add to Home Screen</strong>.
                </li>
                <li>Tap <strong style="color:#fff;">Add</strong> in the top-right.</li>
            </ol>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" data-jambo-install-dismiss="true"
                        style="background:#1A98FF;color:#fff;border:0;border-radius:9px;
                               padding:10px 16px;font-size:14px;font-weight:600;cursor:pointer;flex:1 1 auto;">
                    Got it
                </button>
            </div>
        </div>

        <button type="button" data-jambo-install-dismiss="true" aria-label="Close"
                style="position:absolute;top:14px;right:16px;background:transparent;color:#7d8493;
                       border:0;font-size:22px;cursor:pointer;line-height:1;">
            &times;
        </button>
    </div>
</div>
<script>
(function () {
    var STORAGE_KEY = 'jambo_install_prompt_state';
    var SESSION_KEY = 'jambo_install_first_seen';
    var DELAY_MS = 5 * 60 * 1000;
    var modal = document.getElementById('jambo-install-prompt');
    if (!modal || !window.JamboPWA) return;

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
        var androidPanel = document.getElementById('jambo-install-android');
        var iosPanel = document.getElementById('jambo-install-ios');
        if (kind === 'ios') { iosPanel.style.display = 'block'; }
        else { androidPanel.style.display = 'block'; }
        modal.style.display = 'flex';
        requestAnimationFrame(function () { modal.style.opacity = '1'; });
    }
    function hide() {
        modal.style.opacity = '0';
        setTimeout(function () { modal.style.display = 'none'; }, 280);
    }

    function attach() {
        Array.prototype.forEach.call(modal.querySelectorAll('[data-jambo-install-dismiss]'), function (btn) {
            btn.addEventListener('click', function () { hide(); persist('dismissed'); });
        });
        modal.addEventListener('click', function (e) {
            // Click on the dim backdrop (modal element itself, not the card).
            if (e.target === modal) { hide(); persist('dismissed'); }
        });
        var androidGo = document.getElementById('jambo-install-android-go');
        if (androidGo) {
            androidGo.addEventListener('click', function () {
                if (!deferredPrompt) { hide(); return; }
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function (choice) {
                    persist(choice.outcome === 'accepted' ? 'installed' : 'dismissed');
                    deferredPrompt = null;
                    hide();
                });
            });
        }
    }

    function evaluate() {
        if (pwa.isStandalone()) return;
        if (readState() === 'dismissed' || readState() === 'installed') return;

        var ios = pwa.isIOS();
        // On Android the prompt is only useful once we've captured a
        // beforeinstallprompt; if the browser hasn't fired one (e.g.
        // criteria not yet met), bail rather than showing a button
        // that won't work.
        if (!ios && !deferredPrompt) return;

        attach();
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
