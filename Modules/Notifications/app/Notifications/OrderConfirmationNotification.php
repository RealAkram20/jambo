<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Payments\app\Models\PaymentOrder;

class OrderConfirmationNotification extends ChannelGatedNotification
{
    public function __construct(public readonly PaymentOrder $order)
    {
    }

    protected function settingKey(): string
    {
        return 'order_confirmation';
    }

    public function toDatabase($notifiable): array
    {
        $amount = sprintf('%s %s', $this->order->currency, number_format((float) $this->order->amount, 2));

        return [
            'title'        => "Order placed — {$amount}",
            'message'      => "Your order (ref {$this->order->merchant_reference}) is pending payment capture.",
            'icon'         => 'ph-receipt',
            'colour'       => 'primary',
            'image'        => null,
            'action_url'   => route('payment.complete', ['ref' => $this->order->merchant_reference]),
            'action_label' => 'View order',
            'order_id'     => $this->order->id,
            'reference'    => $this->order->merchant_reference,
        ];
    }
}
