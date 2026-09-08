<?php

namespace App\Exceptions;

use App\Http\Api\ApiErrorCode;
use App\Http\Api\ApiResponse;
use App\Models\SignupAttempt;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

        // ── /api/v1 answers in one envelope, always ──────────────────
        //
        // Scoped to `api/*` on purpose. The website's own AJAX endpoints also
        // live under /api/v1/ for historical reasons (watchlist, heartbeat,
        // player-data) but they are session routes returning their own JSON
        // shapes, and the frontend JS reads those shapes. Rewriting them here
        // would break the live site, so every handler below checks that the
        // request is actually a token request before it touches the response.
        //
        // Without these, a validation failure reaches the app as Laravel's
        // default {"message":..., "errors":...} and a missing token as a
        // redirect to /login, neither of which the app can read.

        $this->renderable(function (ValidationException $e, $request) {
            if (! $this->isTokenApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                $e->getMessage(),
                $e->errors(),
            );
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if (! $this->isTokenApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                ApiErrorCode::Unauthenticated,
                'Sign in to continue.',
            );
        });

        $this->renderable(function (NotFoundHttpException|ModelNotFoundException $e, $request) {
            if (! $this->isTokenApiRequest($request)) {
                return null;
            }

            return ApiResponse::error(
                ApiErrorCode::NotFound,
                'Not found.',
            );
        });

        // Everything else. Without this, any exception the three above do not
        // name - a mail transport down, a database gone away, a bug - reaches
        // the app as Laravel's default 500: not in the envelope, and in debug
        // mode carrying the exception class and message. The contract says
        // every response is enveloped and SERVER_ERROR is in the enum for
        // exactly this; found on 2026-09-08 when forgot-password hit a dead
        // SMTP host and answered with a bare TransportException.
        //
        // HttpException subclasses (403, 429, 503...) keep their own status,
        // because those are deliberate answers rather than failures. The
        // message is only exposed in debug: a production stack trace is not
        // something to hand a phone.
        $this->renderable(function (Throwable $e, $request) {
            if (! $this->isTokenApiRequest($request)) {
                return null;
            }

            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof NotFoundHttpException
                || $e instanceof ModelNotFoundException) {
                return null; // handled above
            }

            if ($e instanceof HttpExceptionInterface) {
                $code = match ($e->getStatusCode()) {
                    401 => ApiErrorCode::Unauthenticated,
                    403 => ApiErrorCode::Forbidden,
                    404 => ApiErrorCode::NotFound,
                    429 => ApiErrorCode::RateLimited,
                    default => ApiErrorCode::ServerError,
                };

                return ApiResponse::error(
                    $code,
                    $e->getMessage() ?: 'Request refused.',
                    status: $e->getStatusCode(),
                );
            }

            return ApiResponse::error(
                ApiErrorCode::ServerError,
                config('app.debug')
                    ? get_class($e) . ': ' . $e->getMessage()
                    : 'Something went wrong on our side. Please try again in a moment.',
            );
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

    /**
     * Is this a request to the token API, as opposed to one of the website's
     * own session-authenticated JSON endpoints that happen to sit under the
     * same /api/v1 prefix?
     *
     * The distinguishing mark is the absence of a web session: the app sends
     * a bearer token and no cookie. Checked this way rather than by route
     * name so a new endpoint gets the envelope without having to remember to
     * opt in.
     */
    private function isTokenApiRequest($request): bool
    {
        return $request->is('api/*') && ! $request->hasSession();
    }
}
