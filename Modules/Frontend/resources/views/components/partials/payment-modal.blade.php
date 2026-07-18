{{--
    Jambo checkout modal — hosts PesaPal's redirect_url inside an iframe
    so the user never leaves the site. Reusable: drop this partial into
    any page, and any `<form class="jambo-subscribe-form">` that POSTs
    to `/payment/create-order` will open inside the modal instead of
    navigating away.

    UX constraints (from product):
      • Only the explicit X button closes the modal.
      • Backdrop click does NOT close — prevents accidental dismissal
        mid-payment (especially on touch devices where the gateway page
        is a large scrollable area and users often tap outside to
        dismiss keyboards).
      • Escape key does NOT close — same reason.
      • Polling the server for status transitions means we auto-close
        and route to the complete page as soon as PesaPal confirms.

    Style lives inline so the partial is one-file drop-in — these rules
    are scoped under .jambo-payment-modal so they can't leak into the
    rest of the site's Bootstrap chrome.
--}}

<div id="jambo-payment-modal"
     class="jambo-payment-modal"
     hidden
     role="dialog"
     aria-modal="true"
     aria-labelledby="jambo-payment-modal-title">

    <div class="jambo-payment-modal__backdrop" aria-hidden="true"></div>

    <div class="jambo-payment-modal__shell">
        <header class="jambo-payment-modal__header">
            <div class="jambo-payment-modal__title-group">
                <h5 class="jambo-payment-modal__title" id="jambo-payment-modal-title" data-role="title">
                    Complete your payment
                </h5>
                <p class="jambo-payment-modal__subtitle" data-role="subtitle">
                    <i class="ph ph-shield-check"></i>
                    <span>Secure checkout — you stay on Jambo</span>
                </p>
            </div>
            <button type="button"
                    class="jambo-payment-modal__close"
                    data-role="close"
                    aria-label="Cancel payment and close">
                <i class="ph ph-x"></i>
            </button>
        </header>

        <div class="jambo-payment-modal__body">
            {{-- Coupon step. Shown BEFORE the order is created, and only
                 when the including page sets window.JamboCoupon (i.e. the
                 viewer could still attach a referral code — first payment
                 not made, no code applied yet). No code? "Continue to
                 payment" goes straight to the gateway. --}}
            <div class="jambo-payment-modal__state jambo-payment-modal__state--coupon" data-role="coupon" hidden>
                <i class="ph ph-ticket" style="font-size:44px;color:var(--bs-primary);"></i>
                <p class="jambo-payment-modal__state-title">Have a referral code?</p>
                <p class="jambo-payment-modal__state-subtitle" data-role="coupon-hint"></p>
                <div class="jambo-payment-modal__coupon-row">
                    <input type="text" maxlength="50" data-role="coupon-input"
                           placeholder="Referral code" autocomplete="off">
                    <button type="button" class="btn btn-outline-primary" data-role="coupon-apply">Apply</button>
                </div>
                <div class="jambo-payment-modal__coupon-msg" data-role="coupon-msg" hidden></div>
                <button type="button" class="btn btn-primary jambo-payment-modal__coupon-continue" data-role="coupon-continue">
                    Continue to payment
                </button>
            </div>

            {{-- Initial state while waiting for the gateway handshake. --}}
            <div class="jambo-payment-modal__state jambo-payment-modal__state--loading" data-role="loading">
                <div class="jambo-payment-modal__spinner" role="status" aria-live="polite">
                    <div class="spinner-border text-primary" aria-hidden="true"></div>
                </div>
                <p class="jambo-payment-modal__state-title">Connecting to PesaPal…</p>
                <p class="jambo-payment-modal__state-subtitle">This usually takes a couple of seconds.</p>
            </div>

            {{-- The iframe itself. `allow="payment"` hints browsers to
                 let the embedded page use the Payment Request API. --}}
            <iframe class="jambo-payment-modal__iframe"
                    data-role="iframe"
                    title="PesaPal secure checkout"
                    allow="payment"
                    hidden></iframe>

            {{-- Fallback: if the iframe can't load (X-Frame-Options from
                 PesaPal, CSP, bad network), show a clear "open in new
                 tab" action so the user isn't stuck. Auto-revealed
                 after the iframe fails to fire `load` within ~8s. --}}
            <div class="jambo-payment-modal__state jambo-payment-modal__state--fallback" data-role="fallback" hidden>
                <i class="ph ph-warning-circle" style="font-size:44px;color:var(--bs-warning, #f59e0b);"></i>
                <p class="jambo-payment-modal__state-title">Checkout couldn't load in this window</p>
                <p class="jambo-payment-modal__state-subtitle">Open it in a new tab to complete payment. Your order stays active until you pay or close it.</p>
                <a href="#" class="btn btn-primary" data-role="fallback-link" target="_blank" rel="noopener">
                    <i class="ph ph-arrow-square-out me-1"></i>
                    Open checkout in a new tab
                </a>
            </div>
        </div>

        <footer class="jambo-payment-modal__footer">
            <div class="jambo-payment-modal__status" data-role="status" aria-live="polite">
                <span class="jambo-payment-modal__status-dot" data-role="status-dot"></span>
                <span class="jambo-payment-modal__status-text" data-role="status-text">Waiting for payment…</span>
            </div>
            <p class="jambo-payment-modal__help">Keep this window open until your payment completes.</p>
        </footer>
    </div>
</div>

<style>
    /* ---------------------------------------------------------------
       Scoped under .jambo-payment-modal so nothing leaks into the rest
       of the site's Bootstrap styles. Colour palette matches the
       admin cards so the modal looks native.
       --------------------------------------------------------------- */
    .jambo-payment-modal {
        position: fixed;
        inset: 0;
        z-index: 2147483000; /* above everything, including header */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    .jambo-payment-modal[hidden] { display: none; }

    .jambo-payment-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(5, 8, 14, 0.82);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        pointer-events: none; /* click-through disabled — X is the only dismiss */
    }

    .jambo-payment-modal__shell {
        position: relative;
        width: 100%;
        max-width: 960px;
        height: min(85vh, 780px);
        background: #141923;
        border: 1px solid #1f2738;
        border-radius: 14px;
        box-shadow: 0 32px 72px -16px rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: jambo-payment-modal-in .18s ease-out;
    }
    @keyframes jambo-payment-modal-in {
        from { opacity: 0; transform: translateY(12px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .jambo-payment-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 22px;
        border-bottom: 1px solid #1f2738;
        background: linear-gradient(180deg, #181e2a 0%, #141923 100%);
        flex-shrink: 0;
    }
    .jambo-payment-modal__title {
        margin: 0;
        font-size: 17px;
        font-weight: 600;
        line-height: 1.2;
        color: #f5f6f8;
    }
    .jambo-payment-modal__subtitle {
        margin: 4px 0 0;
        font-size: 12px;
        color: #8791a3;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .jambo-payment-modal__subtitle i {
        color: var(--bs-success, #2dd47a);
        font-size: 14px;
    }

    .jambo-payment-modal__close {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid transparent;
        background: rgba(255, 255, 255, 0.04);
        color: #adafb8;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: color .15s ease, background .15s ease, border-color .15s ease;
    }
    .jambo-payment-modal__close:hover,
    .jambo-payment-modal__close:focus-visible {
        background: rgba(239, 68, 68, 0.12);
        border-color: rgba(239, 68, 68, 0.32);
        color: #ef4444;
        outline: none;
    }
    .jambo-payment-modal__close:focus-visible {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.18);
    }

    .jambo-payment-modal__body {
        position: relative;
        flex: 1;
        min-height: 0; /* allow iframe to shrink within flex */
        background: #0b0f17;
    }
    .jambo-payment-modal__iframe {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
    }
    .jambo-payment-modal__iframe[hidden] { display: none; }

    .jambo-payment-modal__state {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px;
        text-align: center;
        color: #d3d6dc;
    }
    .jambo-payment-modal__state[hidden] { display: none; }
    .jambo-payment-modal__spinner { margin-bottom: 16px; }
    .jambo-payment-modal__state-title {
        margin: 0;
        font-size: 15px;
        font-weight: 500;
        color: #f5f6f8;
    }
    .jambo-payment-modal__state-subtitle {
        margin: 6px 0 0;
        font-size: 13px;
        color: #8791a3;
        max-width: 440px;
    }
    .jambo-payment-modal__state--fallback .btn {
        margin-top: 20px;
    }

    .jambo-payment-modal__coupon-row {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        width: 100%;
        max-width: 380px;
    }
    .jambo-payment-modal__coupon-row input {
        flex: 1;
        min-width: 0;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid #1f2738;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        color: #f5f6f8;
    }
    .jambo-payment-modal__coupon-row input::placeholder { color: #6c727b; }
    .jambo-payment-modal__coupon-row input:focus {
        outline: none;
        border-color: var(--bs-primary, #1A98FF);
        box-shadow: 0 0 0 3px rgba(26, 152, 255, 0.15);
    }
    .jambo-payment-modal__coupon-row input:disabled { opacity: .55; }
    .jambo-payment-modal__coupon-msg {
        margin-top: 10px;
        font-size: 13px;
    }
    .jambo-payment-modal__coupon-msg[hidden] { display: none; }
    .jambo-payment-modal__coupon-msg[data-variant="success"] { color: var(--bs-success, #2dd47a); }
    .jambo-payment-modal__coupon-msg[data-variant="error"] { color: var(--bs-danger, #ef4444); }
    .jambo-payment-modal__coupon-continue {
        margin-top: 22px;
        min-width: 240px;
    }

    .jambo-payment-modal__footer {
        padding: 14px 22px;
        border-top: 1px solid #1f2738;
        background: #10151f;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-shrink: 0;
    }
    .jambo-payment-modal__status {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: #d3d6dc;
    }
    .jambo-payment-modal__status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--bs-warning, #f59e0b);
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.6);
        animation: jambo-payment-modal-pulse 1.6s ease-out infinite;
    }
    .jambo-payment-modal__status-dot[data-variant="success"] {
        background: var(--bs-success, #2dd47a);
        animation: none;
    }
    .jambo-payment-modal__status-dot[data-variant="error"] {
        background: var(--bs-danger, #ef4444);
        animation: none;
    }
    @keyframes jambo-payment-modal-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
        70%  { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
        100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }
    .jambo-payment-modal__help {
        margin: 0;
        font-size: 11px;
        color: #6c727b;
        text-align: right;
    }

    /* Mobile: fill the viewport, remove the rounded shell. */
    @media (max-width: 640px) {
        .jambo-payment-modal { padding: 0; }
        .jambo-payment-modal__shell {
            height: 100vh;
            max-width: none;
            border-radius: 0;
            border: 0;
        }
        .jambo-payment-modal__help { display: none; }
    }

    /* Lock page scroll while the modal is open. Applied via JS. */
    body.jambo-payment-modal-open { overflow: hidden; }
</style>

<script>
/**
 * Jambo checkout modal controller.
 *
 * Exposes a small global (`window.JamboPayment`) so other pages (not
 * just /pricing) can reuse the modal by calling
 * JamboPayment.openFromForm(formElement).
 *
 * Design constraints from product:
 *   - Only the X button closes the modal. Backdrop is pointer-events:
 *     none (CSS) and no Escape key handler is registered.
 *   - Polling is the single source of truth for "did the payment land"
 *     — the iframe is cross-origin so we can't read its navigation.
 *   - On X close, we navigate to /payment/complete?result=pending so
 *     the user sees their order and can retry. We don't force-cancel
 *     because M-Pesa / MTN MoMo can still confirm out-of-band via IPN.
 */
(function () {
    const STATUS_ENDPOINT_TEMPLATE = @json(route('payment.status', ['ref' => '__REF__']));
    const COMPLETE_URL = @json(route('payment.complete'));
    const POLL_INTERVAL_MS = 3500;
    const IFRAME_LOAD_TIMEOUT_MS = 8000;

    const modal = document.getElementById('jambo-payment-modal');
    if (!modal) return;

    const elements = {
        shell:        modal.querySelector('.jambo-payment-modal__shell'),
        close:        modal.querySelector('[data-role="close"]'),
        title:        modal.querySelector('[data-role="title"]'),
        subtitle:     modal.querySelector('[data-role="subtitle"] span'),
        loading:      modal.querySelector('[data-role="loading"]'),
        iframe:       modal.querySelector('[data-role="iframe"]'),
        fallback:     modal.querySelector('[data-role="fallback"]'),
        fallbackLink: modal.querySelector('[data-role="fallback-link"]'),
        statusText:   modal.querySelector('[data-role="status-text"]'),
        statusDot:    modal.querySelector('[data-role="status-dot"]'),
        coupon:         modal.querySelector('[data-role="coupon"]'),
        couponHint:     modal.querySelector('[data-role="coupon-hint"]'),
        couponInput:    modal.querySelector('[data-role="coupon-input"]'),
        couponApply:    modal.querySelector('[data-role="coupon-apply"]'),
        couponMsg:      modal.querySelector('[data-role="coupon-msg"]'),
        couponContinue: modal.querySelector('[data-role="coupon-continue"]'),
    };

    let pollHandle = null;
    let iframeLoadHandle = null;
    let currentRef = null;
    let pendingForm = null;    // form parked behind the coupon step
    let couponApplied = false; // page prices are stale once true — reload on close

    function setStatus(text, variant) {
        if (elements.statusText) elements.statusText.textContent = text;
        if (elements.statusDot) {
            if (variant) elements.statusDot.setAttribute('data-variant', variant);
            else elements.statusDot.removeAttribute('data-variant');
        }
    }

    function showLoading() {
        if (elements.coupon) elements.coupon.hidden = true;
        elements.loading.hidden = false;
        elements.iframe.hidden = true;
        elements.fallback.hidden = true;
    }

    function showIframe() {
        if (elements.coupon) elements.coupon.hidden = true;
        elements.loading.hidden = true;
        elements.iframe.hidden = false;
        elements.fallback.hidden = true;
    }

    function showFallback(url) {
        if (elements.coupon) elements.coupon.hidden = true;
        elements.loading.hidden = true;
        elements.iframe.hidden = true;
        elements.fallback.hidden = false;
        if (elements.fallbackLink && url) elements.fallbackLink.href = url;
    }

    function showCoupon() {
        elements.coupon.hidden = false;
        elements.loading.hidden = true;
        elements.iframe.hidden = true;
        elements.fallback.hidden = true;
    }

    function stopPolling() {
        if (pollHandle) {
            clearInterval(pollHandle);
            pollHandle = null;
        }
        if (iframeLoadHandle) {
            clearTimeout(iframeLoadHandle);
            iframeLoadHandle = null;
        }
    }

    function redirectToComplete(result, ref) {
        stopPolling();
        const url = COMPLETE_URL + '?result=' + encodeURIComponent(result) + '&ref=' + encodeURIComponent(ref);
        window.location.href = url;
    }

    function statusEndpointFor(ref) {
        return STATUS_ENDPOINT_TEMPLATE.replace('__REF__', encodeURIComponent(ref));
    }

    function startPolling(ref) {
        stopPolling();
        pollHandle = setInterval(async function () {
            try {
                const res = await fetch(statusEndpointFor(ref), {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (!data.ok) return;

                if (data.status === 'completed') {
                    setStatus('Payment received — redirecting…', 'success');
                    setTimeout(function () { redirectToComplete('success', ref); }, 700);
                } else if (data.status === 'failed') {
                    setStatus('Payment failed', 'error');
                    setTimeout(function () { redirectToComplete('error', ref); }, 700);
                } else if (data.status === 'cancelled') {
                    setStatus('Payment cancelled', 'error');
                    setTimeout(function () { redirectToComplete('cancelled', ref); }, 700);
                }
            } catch (e) {
                // Transient network error — keep polling.
                console.warn('[jambo-payment] status poll failed', e);
            }
        }, POLL_INTERVAL_MS);
    }

    function open(redirectUrl, ref, options) {
        options = options || {};
        currentRef = ref;

        if (options.title) elements.title.textContent = options.title;
        if (options.subtitle) elements.subtitle.textContent = options.subtitle;

        showLoading();
        setStatus('Waiting for payment…');

        // Iframe-load watchdog: if PesaPal's page doesn't fire `load`
        // within the timeout, show the fallback "open in new tab"
        // rather than leaving the user staring at a blank box.
        iframeLoadHandle = setTimeout(function () { showFallback(redirectUrl); }, IFRAME_LOAD_TIMEOUT_MS);
        elements.iframe.onload = function () {
            if (iframeLoadHandle) { clearTimeout(iframeLoadHandle); iframeLoadHandle = null; }
            showIframe();
        };
        elements.iframe.src = redirectUrl;
        elements.fallbackLink.href = redirectUrl;

        modal.hidden = false;
        document.body.classList.add('jambo-payment-modal-open');

        startPolling(ref);
    }

    function close() {
        stopPolling();
        modal.hidden = true;
        document.body.classList.remove('jambo-payment-modal-open');
        // Clear the iframe so its audio / network requests stop.
        if (elements.iframe) elements.iframe.src = 'about:blank';
    }

    function cancelAndExit() {
        const ref = currentRef;
        close();
        if (ref) {
            // Pending on purpose — if M-Pesa / MTN confirms on the
            // user's phone after they close, IPN will land later and
            // the subscription still activates. The complete page's
            // pending copy explains the wait.
            window.location.href = COMPLETE_URL + '?result=pending&ref=' + encodeURIComponent(ref);
        } else if (couponApplied) {
            // Closed at the coupon step after attaching a code — the
            // page's prices are stale, so reload to show the discount.
            window.location.reload();
        }
    }

    /* ------------------------------------------------------------------
       Coupon step — active only when the page defines window.JamboCoupon
       (viewer can still attach a referral code). Shows before the order
       is created; "Continue to payment" hands off to openFromForm().
       ------------------------------------------------------------------ */

    function couponConfig() {
        return (typeof window.JamboCoupon === 'object' && window.JamboCoupon) || null;
    }

    function formatPrice(form, amount) {
        const currency = form.dataset.tierCurrency || '';
        const period = form.dataset.tierPeriod || '';
        const rounded = Math.round(amount).toLocaleString();
        return (currency + ' ' + rounded + (period ? ' / ' + period : '')).trim();
    }

    function showCouponMsg(text, ok) {
        elements.couponMsg.textContent = text;
        elements.couponMsg.setAttribute('data-variant', ok ? 'success' : 'error');
        elements.couponMsg.hidden = false;
    }

    function openCouponStep(form) {
        pendingForm = form;
        currentRef = null;

        const cfg = couponConfig();
        const tierName = form.dataset.tierName || 'your plan';
        elements.title.textContent = 'Complete your payment — ' + tierName;
        elements.subtitle.textContent = (form.dataset.tierPrice ? form.dataset.tierPrice + ' · ' : '') + 'Secure checkout — you stay on Jambo';
        if (elements.couponHint && cfg) {
            elements.couponHint.textContent = 'Apply a code for −' + cfg.percent + '% off, or continue straight to payment.';
        }

        elements.couponMsg.hidden = true;
        elements.couponInput.value = '';
        elements.couponInput.disabled = false;
        elements.couponApply.disabled = false;

        showCoupon();
        setStatus('Checkout not started yet');

        modal.hidden = false;
        document.body.classList.add('jambo-payment-modal-open');
        elements.couponInput.focus();
    }

    async function applyCoupon() {
        const cfg = couponConfig();
        if (!cfg || !pendingForm) return;
        const code = (elements.couponInput.value || '').trim();
        if (!code) return;

        elements.couponApply.disabled = true;

        try {
            const tokenInput = pendingForm.querySelector('[name=_token]');
            const res = await fetch(cfg.applyUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': tokenInput?.value || '',
                },
                body: JSON.stringify({ code: code }),
            });
            const data = await res.json();

            if (data && data.ok) {
                couponApplied = true;
                // Discounted price for the header + the gateway handoff.
                // The server recomputes it authoritatively at order time;
                // this is display only.
                const amount = parseFloat(pendingForm.dataset.tierAmount || '0');
                const discounted = amount * (1 - cfg.percent / 100);
                const priceText = formatPrice(pendingForm, discounted);
                const savedText = ((pendingForm.dataset.tierCurrency || '') + ' ' + Math.round(amount - discounted).toLocaleString()).trim();
                pendingForm.dataset.tierPrice = priceText;
                elements.subtitle.textContent = priceText + ' · Secure checkout — you stay on Jambo';
                showCouponMsg('−' + cfg.percent + '% applied — you save ' + savedText + ' · now ' + priceText, true);
                elements.couponInput.disabled = true;
            } else {
                const text = (data && (data.message || (data.errors && data.errors.code && data.errors.code[0])))
                    || 'That referral code could not be applied.';
                showCouponMsg(text, false);
                elements.couponApply.disabled = false;
            }
        } catch (e) {
            console.warn('[jambo-payment] coupon apply failed', e);
            showCouponMsg('Something went wrong. Please try again.', false);
            elements.couponApply.disabled = false;
        }
    }

    /**
     * Intercept Subscribe form submits: POST via fetch, open the modal
     * with the gateway's redirect URL. Falls back to a normal form
     * submit if anything unexpected happens (old browser, etc.).
     */
    async function openFromForm(form) {
        const btn = form.querySelector('.jambo-subscribe-btn');
        const label = btn?.querySelector('.label');
        const spinner = btn?.querySelector('.spinner');
        const originalLabel = label?.textContent;

        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (label) label.textContent = 'Preparing…';

        const tierName = form.dataset.tierName || 'your plan';
        const tierPrice = form.dataset.tierPrice || '';

        try {
            const tokenInput = form.querySelector('[name=_token]');
            const res = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': tokenInput?.value || '',
                },
                body: new URLSearchParams(new FormData(form)),
            });
            const data = await res.json();

            if (!res.ok || !data.ok) {
                throw new Error(data.error || 'Payment could not start.');
            }

            open(data.redirect_url, data.merchant_reference, {
                title: 'Complete your payment — ' + tierName,
                subtitle: tierPrice
                    ? (tierPrice + ' · Secure checkout — you stay on Jambo')
                    : 'Secure checkout — you stay on Jambo',
            });
        } catch (err) {
            console.error('[jambo-payment] createOrder failed', err);
            alert(err.message || 'Could not start payment. Please try again.');
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (label && originalLabel) label.textContent = originalLabel;
        }
    }

    // Wire the X button (only dismissal). No Escape, no backdrop.
    elements.close.addEventListener('click', cancelAndExit);

    // Coupon step wiring (elements exist even when the step is unused).
    if (elements.couponApply) {
        elements.couponApply.addEventListener('click', applyCoupon);
        elements.couponInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); }
        });
        elements.couponContinue.addEventListener('click', async function () {
            if (!pendingForm) return;
            elements.couponContinue.disabled = true;
            try { await openFromForm(pendingForm); } finally { elements.couponContinue.disabled = false; }
        });
    }

    // Auto-bind any Subscribe forms on the page. When the page enables
    // the coupon step, it runs once before the first order; after a code
    // is attached (or when the viewer can't use one) submits go straight
    // to the gateway.
    document.querySelectorAll('.jambo-subscribe-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (couponConfig() && !couponApplied && elements.coupon) {
                openCouponStep(form);
            } else {
                openFromForm(form);
            }
        });
    });

    // Expose for pages that want to open the modal programmatically.
    window.JamboPayment = { open, close, openFromForm };
})();
</script>
