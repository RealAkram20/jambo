@props([
    'action' => 'submit',  // v3 action label — used by the JS on submit
])

@php
    $recaptchaEnabled = \App\Services\RecaptchaService::isEnabled();
    $recaptchaSiteKey = \App\Services\RecaptchaService::siteKey();
    $recaptchaVersion = \App\Services\RecaptchaService::version();
@endphp

{{-- Honeypot. Hidden from humans (CSS + tabindex=-1 + autocomplete=off
     + aria-hidden), visible in the DOM so naive bots fill it. The
     server controllers (Auth\RegisteredUserController,
     PasswordResetLinkController, AuthenticatedSessionController) check
     for a non-empty value and silently abort. Do NOT change the field
     name without updating those checks. --}}
<div style="position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
    <label for="website">Leave this field blank</label>
    <input type="text"
           name="website"
           id="website"
           value=""
           tabindex="-1"
           autocomplete="off">
</div>

@if ($recaptchaEnabled && $recaptchaSiteKey)
    @if ($recaptchaVersion === 'v3')
        {{-- v3: invisible. Token written into the hidden input on submit. --}}
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
        <script>
            (function () {
                var siteKey = @json($recaptchaSiteKey);
                var action  = @json($action);

                // Find the form this component lives in and fetch a
                // fresh token at submit time. Prevents token expiry on
                // long-loaded pages.
                document.addEventListener('DOMContentLoaded', function () {
                    var hidden = document.getElementById('g-recaptcha-response');
                    if (!hidden) return;
                    var form = hidden.closest('form');
                    if (!form) return;

                    form.addEventListener('submit', function (e) {
                        if (form.dataset.recaptchaResolved === '1') {
                            return; // already have a token, allow native submit
                        }
                        e.preventDefault();
                        if (typeof grecaptcha === 'undefined' || !grecaptcha.ready) {
                            // Library failed to load — submit anyway; server-side
                            // verify will reject if reCAPTCHA is enabled.
                            form.dataset.recaptchaResolved = '1';
                            form.submit();
                            return;
                        }
                        grecaptcha.ready(function () {
                            grecaptcha.execute(siteKey, { action: action }).then(function (token) {
                                hidden.value = token;
                                form.dataset.recaptchaResolved = '1';
                                form.submit();
                            }).catch(function () {
                                // On any error, still submit so the server
                                // can return a sensible message rather than
                                // leaving the user stuck on a dead form.
                                form.dataset.recaptchaResolved = '1';
                                form.submit();
                            });
                        });
                    }, false);
                });
            })();
        </script>
    @else
        {{-- v2 widget: visible "I'm not a robot" checkbox. --}}
        <div class="g-recaptcha mb-3" data-sitekey="{{ $recaptchaSiteKey }}"></div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
@endif
