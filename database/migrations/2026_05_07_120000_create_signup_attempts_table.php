<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic log for every public signup attempt — successful or not.
 *
 * Background: real users on the community were reporting "I tried to
 * sign up and got an error" without enough detail for support to
 * triage. The signup controller has five distinct failure modes
 * (honeypot, reCAPTCHA, validation, CSRF/419, throttle) plus the
 * happy path, and prior to this table we had no way to tell which
 * one a given user actually hit. Now every attempt writes one row
 * here with its outcome and details — admin triage becomes a single
 * filtered query at /admin/diagnostics/signups.
 *
 * Privacy notes:
 *   - We log email_attempted because reproducing a failure starts
 *     with knowing whose attempt failed; that's also a PII column,
 *     so the admin page is gated to role:admin and we don't expose
 *     the table via API.
 *   - We never log the password (the column doesn't exist).
 *   - IP + user-agent are kept indefinitely for now; if/when the
 *     project formalises a data-retention policy, prune older rows
 *     via a scheduled command. The PruneOldNotifications pattern in
 *     Modules/Notifications is the precedent.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('signup_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('email_attempted', 255)->nullable()->index();
            $table->string('username_attempted', 100)->nullable();
            // Outcome — see App\Models\SignupAttempt::OUTCOME_* for
            // the list of valid values. Indexed because the admin
            // diagnostics page filters by outcome heavily.
            $table->string('outcome', 32)->index();
            // JSON blob with whatever extra context the failure path
            // wanted to record — validation messages, exception
            // class+message, recaptcha error codes, etc.
            $table->json('details')->nullable();
            // Use a single created_at column rather than full
            // timestamps — the row is immutable, no updated_at meaning.
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_attempts');
    }
};
