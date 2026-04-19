<?php

namespace Modules\Notifications\app\Notifications;

class AccountDeactivatedNotification extends ChannelGatedNotification
{
    public function __construct(public readonly string $reason = 'user_requested')
    {
    }

    protected function settingKey(): string
    {
        return 'account_deactivated';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'Your account was deactivated',
            'message'      => 'Your Jambo account is no longer active. If this was a mistake, contact support to restore access.',
            'icon'         => 'ph-user-minus',
            'colour'       => 'danger',
            'image'        => null,
            'action_url'   => url('/contact-us'),
            'action_label' => 'Contact support',
            'reason'       => $this->reason,
        ];
    }
}
