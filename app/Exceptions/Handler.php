<?php

namespace App\Exceptions;

use App\Models\SignupAttempt;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // CSRF / 419 on the public signup form. Real users hit this
        // when they leave /register open for hours and submit after
        // their session token has rolled. Without this handler the
        // generic Laravel 419 "Page Expired" view is what they see,
        // which they reasonably interpret as "the site is broken"
        // and abandon. Catch it for the signup endpoint specifically,
        // log a SignupAttempt row so support has visibility, and
        // bounce them to a friendly retry page that explains exactly
        // what happened. See docs/architecture/signup-diagnostics.md.
        $this->renderable(function (TokenMismatchException $e, $request) {
            if ($request->isMethod('POST') && $request->is('register')) {
                SignupAttempt::log($request, SignupAttempt::OUTCOME_CSRF_EXPIRED, [
                    'message' => 'CSRF token expired or missing on signup POST',
                ]);
                return redirect()->route('register')
                    ->with('error', 'Your session expired before you could sign up — please try again. Your previous entries weren\'t saved.');
            }
            // For every other CSRF mismatch fall through to Laravel's
            // default handling (419 page, or whatever's wired up
            // elsewhere). Forms in the app generally refresh the
            // token via meta tag + the SectionDataComposer cycle so
            // this is rare outside the long-idle-tab signup case.
        });

        // Throttle hits on the signup endpoint. The middleware
        // already returns 429, but the message is a generic "Too
        // Many Attempts" — log it so we can correlate with user
        // reports of "I tried twice and got an error" and decide
        // whether the limit is too tight for shared-NAT users.
        $this->renderable(function (ThrottleRequestsException $e, $request) {
            if ($request->isMethod('POST') && $request->is('register')) {
                SignupAttempt::log($request, SignupAttempt::OUTCOME_THROTTLE, [
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                ]);
            }
            // Don't return a custom response — let Laravel's default
            // 429 surface so the throttle behaviour is unchanged.
        });
    }
}
