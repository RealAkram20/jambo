<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued variant of Laravel's built-in email-verification notification.
 *
 * Laravel's stock VerifyEmail runs synchronously inside the same request
 * that just inserted the user. Any failure in the mail transport (TLS
 * handshake fails, SMTP host unreachable, auth refused) bubbles up and
 * 500s the signup response — even though the user row was already
 * committed. The visible bug: "account exists, page errors, refresh
 * works, no email sent."
 *
 * Pushing the send to the queue means the request returns cleanly,
 * the worker handles delivery (with retries), and an SMTP outage shows
 * up as a failed_jobs row instead of a broken signup screen.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
