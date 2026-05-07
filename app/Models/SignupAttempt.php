<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * One row per public signup attempt — success OR failure. Lets
 * support triage "I tried to sign up and got an error" reports with
 * a single admin-page lookup instead of guessing which of five
 * failure modes hit the user.
 *
 * See database/migrations/..._create_signup_attempts_table.php for
 * the full rationale and privacy notes.
 *
 * Outcome enumeration intentionally lives here as constants so the
 * controller / handler / admin view all reference the same names —
 * a typo in any one place becomes a 0-row filter on the admin page,
 * which would be a silent bug.
 *
 * @property int     $id
 * @property ?string $ip
 * @property ?string $user_agent
 * @property ?string $email_attempted
 * @property ?string $username_attempted
 * @property string  $outcome
 * @property ?array  $details
 * @property \Illuminate\Support\Carbon $created_at
 */
class SignupAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip',
        'user_agent',
        'email_attempted',
        'username_attempted',
        'outcome',
        'details',
        'created_at',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    /** Successful signup — User row created. */
    public const OUTCOME_SUCCESS = 'success';

    /** Hidden honeypot field was filled — likely a bot OR a password
     *  manager auto-filling every input. The controller silently
     *  redirects to / with a fake welcome flash, so without this row
     *  we'd have no way to know it happened. */
    public const OUTCOME_HONEYPOT = 'honeypot';

    /** reCAPTCHA verification failed (bad token, score too low, etc.). */
    public const OUTCOME_RECAPTCHA_FAIL = 'recaptcha_fail';

    /** Standard Laravel validation failed (email taken, password too
     *  weak, username reserved, etc.). Details column carries the
     *  per-field messages. */
    public const OUTCOME_VALIDATION = 'validation_error';

    /** CSRF token expired — user opened the form, left it for hours,
     *  then submitted. Caught in App\Exceptions\Handler so we have
     *  visibility on this otherwise opaque failure. */
    public const OUTCOME_CSRF_EXPIRED = 'csrf_expired';

    /** throttle:5,10 middleware kicked in — 5 register POSTs per
     *  10 minutes per IP exceeded. Common on shared NAT (carriers,
     *  schools). Caught in the handler too. */
    public const OUTCOME_THROTTLE = 'throttle';

    /** Anything else that exploded — DB outage, queue connection
     *  failure, mail send during signup, etc. Details column carries
     *  the exception class + message. */
    public const OUTCOME_EXCEPTION = 'exception';

    /**
     * Cheap, safe logger. Designed to be called from anywhere in the
     * signup flow — controller branches, exception handlers, the
     * throttle middleware — without each call site knowing where the
     * email/username live on the request. Failures here are swallowed
     * so a diagnostic write never breaks the actual signup flow.
     */
    public static function log(Request $request, string $outcome, array $details = []): void
    {
        try {
            self::create([
                'ip'                 => $request->ip(),
                'user_agent'         => substr((string) $request->userAgent(), 0, 500),
                'email_attempted'    => is_string($request->input('email')) ? $request->input('email') : null,
                'username_attempted' => is_string($request->input('username')) ? $request->input('username') : null,
                'outcome'            => $outcome,
                'details'            => $details ?: null,
                'created_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            // Diagnostic write must never crash the request it's
            // describing. Log to the laravel.log so the operator can
            // find a missing-table / migration-not-run scenario, but
            // don't propagate.
            try {
                \Illuminate\Support\Facades\Log::warning('[signup-attempt-log] write failed', [
                    'error' => $e->getMessage(),
                    'outcome' => $outcome,
                ]);
            } catch (\Throwable) {
                // Even logging is broken — give up silently.
            }
        }
    }
}
