# Signup diagnostics

This doc explains the `signup_attempts` table, where it gets written
from, and how to use it when a community member reports a signup
problem.

## The problem this exists to solve

The public signup form has **six** distinct outcomes — one happy path
plus five failure modes:

| Outcome | What it means | Where it's logged from |
|---|---|---|
| `success` | User row created, verification email queued | `RegisteredUserController::store` after `Auth::login` |
| `honeypot` | Hidden `website` field was filled (bot OR password manager) | `RegisteredUserController::store` line ~50 |
| `recaptcha_fail` | Server-side reCAPTCHA verification rejected the token | `RegisteredUserController::store` line ~70 |
| `validation_error` | Email taken, password too weak, username reserved, etc. | `RegisteredUserController::store` (try/catch around `validate()`) |
| `csrf_expired` | User left `/register` open too long; submitted with a stale token | `App\Exceptions\Handler` (the controller never runs) |
| `throttle` | `throttle:5,10` middleware kicked in (5 POSTs per 10 min per IP) | `App\Exceptions\Handler` |
| `exception` | Something genuinely broke — DB outage, queue, etc. | `RegisteredUserController::store` (try/catch around `User::create`) |

Before this table existed, **none of the failure modes were
distinguishable from outside.** A user said "I got an error", and we
had no way to know whether their session expired, their network
blocked the captcha, their password manager filled the honeypot, or
their email was already taken. The only correct response was to ask
them to try again, which is bad support.

Now every POST writes a single row recording exactly which outcome
fired, with enough context to reproduce it.

## Where the writes happen

There's one logging entry point — `App\Models\SignupAttempt::log(Request, outcome, details)` — used from three places:

1. **`RegisteredUserController::store`** — every branch (honeypot,
   reCAPTCHA, validation try/catch, User::create try/catch, success).
2. **`App\Exceptions\Handler::register()`** — `TokenMismatchException`
   and `ThrottleRequestsException` renderable callbacks. These fire
   *before* the controller runs (middleware-level) so the controller
   can't catch them.
3. **(Future)** any new branch added to the signup flow should call
   `SignupAttempt::log()` so the diagnostic surface stays complete.

The logger is intentionally fail-safe — it wraps the `create()` call
in `try/catch \Throwable` and silently logs to `laravel.log` if the
DB write fails. **A diagnostic write must never crash the actual
signup it's describing.**

## Where to read the writes

`/admin/diagnostics/signups` (admin role, CyberPanel + Hostinger
production). The page shows:

- **Last-7-day summary** with per-outcome counts and a success rate.
  At a glance: "Are signups trending well, or is some failure mode
  spiking?"
- **Filterable list** of every attempt, paginated 50 per page,
  filterable by outcome and free-text search across
  email/username/IP. Each row's `Details` column has a JSON expander
  for context.

## Triage checklist

When a user reports "I tried to sign up and got an error":

1. Open `/admin/diagnostics/signups`.
2. Filter by their email address (or IP if they shared it). One row
   should appear, recent.
3. Look at the **outcome** column:

| Outcome | What to tell the user / fix |
|---|---|
| `csrf_expired` | "Your session timed out. Reload the page and try again." (We already render this message via the friendly retry path — they shouldn't see a 419 page.) |
| `throttle` | "You've tried too many times in the last 10 minutes — please wait a bit." If lots of unrelated IPs hit this, the limit is too tight; bump in `routes/auth.php`. |
| `recaptcha_fail` | "Make sure you tick the 'I'm not a robot' checkbox." If recurring from real-looking IPs, check whether their browser/region is blocking Google. |
| `validation_error` | Details column has the per-field errors. Most common: email already in use → prompt them to log in instead. |
| `honeypot` | If the user looks legitimate (non-bot IP, real-looking name), their **password manager** filled the hidden field. Acknowledge to user, consider dropping the honeypot field — reCAPTCHA + throttle + the login screen's brute-force protections are sufficient. |
| `exception` | Details column has the exception class + message. This is a real bug; investigate the underlying cause. |
| `success` | Their account exists. The "error" they reported is something downstream — most likely the verification email landed in their spam folder. Direct them to check spam OR resend via the bell. |

## Privacy notes

- `email_attempted`, `username_attempted`, `ip`, and `user_agent` are
  PII. The admin page is gated to `role:admin` and these columns are
  not exposed via any API.
- Passwords are NEVER logged — the column doesn't exist on the table.
- No retention policy yet. Past a few months, prune the table via a
  scheduled command. The pattern in `Modules/Notifications/app/Console/Commands/PruneOldNotifications`
  is the precedent to follow when we add this.

## File map

| File | Role |
|---|---|
| [`database/migrations/2026_05_07_120000_create_signup_attempts_table.php`](../../database/migrations/2026_05_07_120000_create_signup_attempts_table.php) | The table |
| [`app/Models/SignupAttempt.php`](../../app/Models/SignupAttempt.php) | Model + outcome constants + the `log()` helper |
| [`app/Http/Controllers/Auth/RegisteredUserController.php`](../../app/Http/Controllers/Auth/RegisteredUserController.php) | Logs success / honeypot / reCAPTCHA / validation / exception |
| [`app/Exceptions/Handler.php`](../../app/Exceptions/Handler.php) | Logs `csrf_expired` / `throttle`; renders the friendly 419 redirect |
| [`resources/views/auth/register.blade.php`](../../resources/views/auth/register.blade.php) | Renders the 'error' flash from the 419 redirect |
| [`app/Http/Controllers/Admin/SystemDiagnosticsController.php`](../../app/Http/Controllers/Admin/SystemDiagnosticsController.php) | `signupsIndex` action |
| [`resources/views/admin/diagnostics/signups.blade.php`](../../resources/views/admin/diagnostics/signups.blade.php) | The admin page |
| [`routes/web.php`](../../routes/web.php) | Route registration (`admin.diagnostics.signups`) |
