<?php

namespace Modules\Notifications\app\Notifications;

class SubscriptionCancelledNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $username = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'subscription_cancelled';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'Subscription cancelled',
            'message'      => "{$this->username}'s subscription was cancelled and will not auto-renew.",
            'icon'         => 'ph-prohibit',
            'colour'       => 'danger',
            'image'        => null,
            'action_url'   => route('dashboard.user-list'),
            'action_label' => 'Open user list',
            'user_id'      => $this->userId,
        ];
    }
}
