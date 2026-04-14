<?php

namespace Modules\Notifications\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Payments\app\Models\PaymentOrder;

/**
 * Fired when the Payments module's `payment.completed` event lands.
 * Delivered to the paying user and to every admin via the `database`
 * (bell dropdown) and `mail` channels.
 *
 * Per-user opt-outs respected:
 *   in_app_notifications_enabled  → database
 *   email_notifications_enabled   → mail
 *
 * Admins and other recipients without those columns fall through the
 * null-coalesce and receive on both channels by default.
 */
class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly PaymentOrder $order)
    {
    }

    public function via($notifiable): array
    {
        $channels = [];

        if ($notifiable->in_app_notifications_enabled ?? true) {
            $channels[] = 'database';
        }

        if (($notifiable->email_notifications_enabled ?? true) && !empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels ?: ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Payment received',
            'message' => sprintf(
                'Payment of %s %s (ref %s) was received.',
                $this->order->currency,
                number_format((float) $this->order->amount, 2),
                $this->order->merchant_reference,
            ),
            'icon' => 'ph-credit-card',
            'colour' => 'success',
            'action_url' => route('payment.complete', ['ref' => $this->order->merchant_reference]),
            'order_id' => $this->order->id,
            'merchant_reference' => $this->order->merchant_reference,
            'amount' => (float) $this->order->amount,
            'currency' => $this->order->currency,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $amount = sprintf('%s %s', $this->order->currency, number_format((float) $this->order->amount, 2));
        $isBuyer = $this->order->user_id === ($notifiable->id ?? null);

        $greeting = $isBuyer
            ? 'Thanks for your payment — it came through.'
            : 'A new payment was received on Jambo.';

        $mail = (new MailMessage())
            ->subject($isBuyer ? "Payment received — {$amount}" : "New payment — {$amount}")
            ->greeting($greeting)
            ->line("Amount: {$amount}")
            ->line("Reference: {$this->order->merchant_reference}");

        if ($this->order->payment_method) {
            $mail->line("Method: {$this->order->payment_method}");
        }

        if ($isBuyer) {
            $mail->action('View receipt', route('payment.complete', ['ref' => $this->order->merchant_reference]));
            $mail->line('If anything looks off, just reply to this email.');
        } else {
            $mail->action('Open in admin', url('/admin/payments'));
        }

        return $mail;
    }
}
