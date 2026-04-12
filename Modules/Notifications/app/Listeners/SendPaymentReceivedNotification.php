<?php

namespace Modules\Notifications\app\Listeners;

use Modules\Notifications\app\Contracts\NotificationDispatcher;
use Modules\Notifications\app\Notifications\PaymentReceivedNotification;

/**
 * Listens on the Payments module's `payment.completed` event and fans
 * out a PaymentReceivedNotification to the user who paid and every
 * admin. Registered in NotificationsServiceProvider::boot().
 *
 * The event payload is `[$order, $source]` as defined in
 * Modules/Payments/app/Http/Controllers/PaymentController.php
 * (dispatchActivation method).
 */
class SendPaymentReceivedNotification
{
    public function __construct(private readonly NotificationDispatcher $dispatcher)
    {
    }

    /**
     * @param  array{0: \Modules\Payments\app\Models\PaymentOrder, 1: string}  $payload
     */
    public function handle(string $event, array $payload): void
    {
        [$order, $source] = $payload + [null, 'unknown'];

        if (!$order) {
            return;
        }

        $notification = new PaymentReceivedNotification($order);

        if ($order->user) {
            $this->dispatcher->toUser($order->user, $notification);
        }

        $this->dispatcher->toAdmins($notification);
    }
}
