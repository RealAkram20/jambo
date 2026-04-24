<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Payments\app\Models\PaymentOrder;

class PaymentFailedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly PaymentOrder $order,
        public readonly ?string $reason = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'payment_failed';
    }

    public function toDatabase($notifiable): array
    {
        $amount = sprintf('%s %s', $this->order->currency, number_format((float) $this->order->amount, 2));
        $isBuyer = $this->order->user_id === ($notifiable->id ?? null);

        return [
            'title'        => $isBuyer ? "Payment failed — {$amount}" : "A payment failed — {$amount}",
            'message'      => $isBuyer
                ? "We couldn't capture your payment for order {$this->order->merchant_reference}." . ($this->reason ? " Reason: {$this->reason}." : '')
                : "Order {$this->order->merchant_reference} failed at capture." . ($this->reason ? " Reason: {$this->reason}." : ''),
            'icon'         => 'ph-warning-octagon',
            'colour'       => 'danger',
            'image'        => null,
            'action_url'   => $isBuyer
                ? route('payment.complete', ['ref' => $this->order->merchant_reference])
                : route('admin.payments.orders.show', $this->order),
            'action_label' => $isBuyer ? 'Try again' : 'Open in admin',
            'order_id'     => $this->order->id,
            'reference'    => $this->order->merchant_reference,
        ];
    }
}
