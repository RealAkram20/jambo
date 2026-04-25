{{--
    Push opt-in soft prompt — fixed bottom banner that asks the visitor
    whether they want notifications BEFORE we trigger the browser's
    permanent permission dialog. Only the user's "Yes" click invokes
    Notification.requestPermission(), avoiding hard-deny states.

    Shown when:
      - Push is supported, not denied, no current subscription.
      - Not already running as an installed PWA.
      - Not previously dismissed (localStorage key).
    First reveal happens 6s after page load so the visitor can orient.
--}}
<div id="jambo-push-prompt"
     style="position:fixed;left:50%;bottom:16px;transform:translateX(-50%) translateY(140%);
            transition:transform .35s ease;z-index:1080;
            max-width:520px;width:calc(100% - 32px);
            background:#10131c;color:#fff;border:1px solid rgba(255,255,255,.08);
            border-radius:14px;box-shadow:0 18px 40px rgba(0,0,0,.45);
            padding:16px 18px;display:none;font-family:Roboto,system-ui,sans-serif;">
    <div style="display:flex;gap:14px;align-items:flex-start;">
        <div style="flex:0 0 auto;width:42px;height:42px;border-radius:12px;
                    background:rgba(26,152,255,.15);color:#1A98FF;
                    display:flex;align-items:center;justify-content:center;font-size:22px;">
            <i class="ph-fill ph-bell-ringing" aria-hidden="true"></i>
        </div>
        <div style="flex:1 1 auto;min-width:0;">
            <div style="font-weight:600;font-size:15px;margin-bottom:4px;">
                Stay in the loop
            </div>
            <div style="font-size:13px;line-height:1.45;color:#c2c8d4;">
                Get a heads-up the moment a new movie or series drops on Jambo.
                You can turn it off anytime.
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" id="jambo-push-prompt-allow"
                        style="background:#1A98FF;color:#fff;border:0;border-radius:8px;
                               padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;">
                    Yes, notify me
                </button>
                <button type="button" id="jambo-push-prompt-dismiss"
                        style="background:transparent;color:#c2c8d4;border:1px solid rgba(255,255,255,.14);
                               border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer;">
                    Not now
                </button>
            </div>
        </div>
        <button type="button" id="jambo-push-prompt-close" aria-label="Dismiss"
                style="background:transparent;color:#7d8493;border:0;font-size:18px;
                       cursor:pointer;padding:0;line-height:1;align-self:flex-start;">
            &times;
        </button>
    </div>
</div>
<script>
(function () {
    var STORAGE_KEY = 'jambo_push_prompt_state';
    var DELAY_MS = 6000;
    var prompt = document.getElementById('jambo-push-prompt');
    if (!prompt || !window.JamboPWA) return;

    var pwa = window.JamboPWA;

    function persist(state) {
        try { localStorage.setItem(STORAGE_KEY, state); } catch (e) {}
    }
    function readState() {
        try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
    }

    function show() {
        prompt.style.display = 'block';
        // Allow display:block to apply before transitioning the transform.
        requestAnimationFrame(function () {
            prompt.style.transform = 'translateX(-50%) translateY(0)';
        });
    }
    function hide() {
        prompt.style.transform = 'translateX(-50%) translateY(140%)';
        setTimeout(function () { prompt.style.display = 'none'; }, 400);
    }

    function shouldShow() {
        if (!pwa.supported()) return false;
        if (pwa.isStandalone()) return false;
        if (pwa.permission() === 'denied') return false;
        if (pwa.permission() === 'granted') return false;
        if (readState() === 'dismissed' || readState() === 'subscribed') return false;
        return true;
    }

    function attach() {
        document.getElementById('jambo-push-prompt-allow').addEventListener('click', function () {
            hide();
            pwa.requestPermissionAndSubscribe()
                .then(function () { persist('subscribed'); })
                .catch(function (err) {
                    // 'denied' / 'dismissed' / 'unsupported' — record so we
                    // don't keep prompting from this device.
                    persist(err && err.message === 'denied' ? 'browser-denied' : 'dismissed');
                });
        });
        document.getElementById('jambo-push-prompt-dismiss').addEventListener('click', function () {
            hide(); persist('dismissed');
        });
        document.getElementById('jambo-push-prompt-close').addEventListener('click', function () {
            hide(); persist('dismissed');
        });
    }

    function evaluate() {
        if (!shouldShow()) return;
        // Confirm there's no existing subscription before showing —
        // matters for the "permission already granted but no record on
        // this device" case after a browser data wipe.
        pwa.currentSubscription().then(function (sub) {
            if (sub) { persist('subscribed'); return; }
            attach();
            show();
        }).catch(function () {
            attach();
            show();
        });
    }

    if (document.readyState === 'complete') {
        setTimeout(evaluate, DELAY_MS);
    } else {
        window.addEventListener('load', function () { setTimeout(evaluate, DELAY_MS); });
    }
})();
</script>
