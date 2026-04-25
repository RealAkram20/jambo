{{--
    PWA bootstrap — included on every page (frontend + admin shell).

    Responsibilities:
      - Register /sw.js so push + install criteria are satisfied.
      - Expose `window.JamboPWA` with helpers for subscription state,
        permission requests, and modal scheduling. The soft-prompt
        and install-prompt partials build their UI on top of this.

    Anonymous users hit /notifications/push/subscribe just like logged-
    in users; the controller branches on auth state internally.
--}}
<script>
(function () {
    if (typeof window === 'undefined') return;

    // VAPID public key, exposed via the existing webpush config so the
    // same key powers both the profile-hub flow and the soft-prompt.
    var VAPID_PUBLIC = @json(config('webpush.vapid.public_key'));
    var SUBSCRIBE_URL   = @json(route('notifications.push.subscribe'));
    var UNSUBSCRIBE_URL = @json(route('notifications.push.unsubscribe'));
    var CSRF = document.querySelector('meta[name="csrf-token"]');
    CSRF = CSRF ? CSRF.getAttribute('content') : '';

    function urlBase64ToUint8Array(base64) {
        var padding = '='.repeat((4 - base64.length % 4) % 4);
        var base = (base64 + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = atob(base);
        var out = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
        return out;
    }

    function supported() {
        return 'serviceWorker' in navigator
            && 'PushManager' in window
            && 'Notification' in window;
    }

    function isStandalone() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
            || window.navigator.standalone === true;
    }

    function isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }

    function getReg() {
        if (!supported()) return Promise.reject(new Error('unsupported'));
        return navigator.serviceWorker.register('/sw.js', { scope: '/' });
    }

    function currentSubscription() {
        return getReg().then(function (reg) {
            return reg.pushManager.getSubscription();
        });
    }

    function subscribe() {
        if (!VAPID_PUBLIC) return Promise.reject(new Error('no-vapid-key'));
        return getReg().then(function (reg) {
            return reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC),
            });
        }).then(function (sub) {
            return fetch(SUBSCRIBE_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(sub),
            }).then(function (r) {
                if (!r.ok) throw new Error('subscribe-failed');
                return sub;
            });
        });
    }

    function requestPermissionAndSubscribe() {
        if (!supported()) return Promise.reject(new Error('unsupported'));
        if (Notification.permission === 'denied') {
            return Promise.reject(new Error('denied'));
        }
        return Notification.requestPermission().then(function (result) {
            if (result !== 'granted') throw new Error(result || 'dismissed');
            return subscribe();
        });
    }

    // Try to register the SW eagerly so install + push criteria can
    // resolve while the user reads the page. Failures are silent —
    // we only care about happy-path browsers here.
    if (supported()) {
        // Defer to idle so we don't fight first-paint.
        if ('requestIdleCallback' in window) {
            requestIdleCallback(function () { getReg().catch(function () {}); });
        } else {
            setTimeout(function () { getReg().catch(function () {}); }, 1500);
        }
    }

    window.JamboPWA = {
        supported: supported,
        isStandalone: isStandalone,
        isIOS: isIOS,
        currentSubscription: currentSubscription,
        subscribe: subscribe,
        requestPermissionAndSubscribe: requestPermissionAndSubscribe,
        permission: function () { return supported() ? Notification.permission : 'unsupported'; },
    };
})();
</script>
