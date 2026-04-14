<?php

namespace Modules\Notifications\app\Listeners;

use Modules\Notifications\app\Contracts\NotificationDispatcher;
use Modules\Notifications\app\Notifications\PaymentReceivedNotification;
use Modules\Payments\app\Models\PaymentOrder;

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

    public function handle(PaymentOrder $order, string $source = 'unknown'): void
    {
        $notification = new PaymentReceivedNotification($order);

        if ($order->user) {
            $this->dispatcher->toUser($order->user, $notification);
        }

        $this->dispatcher->toAdmins($notification);
    }
}
