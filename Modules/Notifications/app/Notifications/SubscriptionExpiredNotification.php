<?php

namespace Modules\Notifications\app\Notifications;

class SubscriptionExpiredNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $planName = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'subscription_expired';
    }

    public function toDatabase($notifiable): array
    {
        $isOwner = $this->userId === ($notifiable->id ?? null);
        $plan = $this->planName ?: 'The';

        return [
            'title'        => $isOwner ? 'Subscription expired' : 'A subscription expired',
            'message'      => $isOwner
                ? "{$plan} subscription has ended. Renew to regain premium access."
                : "User #{$this->userId}'s subscription has expired.",
            'icon'         => 'ph-x-circle',
            'colour'       => 'danger',
            'image'        => null,
            'action_url'   => $isOwner
                ? route('profile.membership', ['username' => $notifiable->username])
                : route('dashboard.user-list'),
            'action_label' => $isOwner ? 'Renew' : 'Open user list',
        ];
    }
}
