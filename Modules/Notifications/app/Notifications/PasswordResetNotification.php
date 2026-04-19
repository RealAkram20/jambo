<?php

namespace Modules\Notifications\app\Notifications;

class PasswordResetNotification extends ChannelGatedNotification
{
    public function __construct(public readonly ?string $ipAddress = null)
    {
    }

    protected function settingKey(): string
    {
        return 'password_reset';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'Your password was reset',
            'message'      => 'Your Jambo account password was just changed. If that wasn\'t you, secure your account now.',
            'icon'         => 'ph-key',
            'colour'       => 'warning',
            'image'        => null,
            'action_url'   => route('profile.security', ['username' => $notifiable->username]),
            'action_label' => 'Open security',
            'ip'           => $this->ipAddress,
        ];
    }
}
