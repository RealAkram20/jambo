<?php

namespace Modules\Notifications\app\Notifications;

class SubscriptionExpiringNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly string $planName = 'Your',
        public readonly int $daysRemaining = 7,
    ) {
    }

    protected function settingKey(): string
    {
        return 'subscription_expiring';
    }

    public function toDatabase($notifiable): array
    {
        $d = $this->daysRemaining;
        $unit = $d === 1 ? 'day' : 'days';

        return [
            'title'        => 'Subscription expiring soon',
            'message'      => "{$this->planName} subscription ends in {$d} {$unit}. Renew to avoid interruption.",
            'icon'         => 'ph-hourglass-medium',
            'colour'       => 'warning',
            'image'        => null,
            'action_url'   => route('profile.membership', ['username' => $notifiable->username]),
            'action_label' => 'Renew now',
            'days_remaining' => $d,
        ];
    }
}
