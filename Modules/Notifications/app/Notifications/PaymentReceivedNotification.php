<?php

namespace Modules\Notifications\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Payments\app\Models\PaymentOrder;

/**
 * Fired when the Payments module's `payment.completed` event lands.
 * Delivered to the paying user and to every admin.
 *
 * For v1 this notification only delivers via the database channel —
 * the mail channel will be layered on in the next session by adding
 * `'mail'` to via() and implementing toMail().
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

        // `mail` channel — added when email support lands.
        // if ($notifiable->email_notifications_enabled ?? true) {
        //     $channels[] = 'mail';
        // }

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
}
