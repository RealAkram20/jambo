<?php

namespace Modules\Notifications\app\Notifications;

class EmailVerifiedNotification extends ChannelGatedNotification
{
    protected function settingKey(): string
    {
        return 'email_verified';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'Email verified ✓',
            'message'      => 'Your email is now verified. You have full access to Jambo.',
            'icon'         => 'ph-envelope-open',
            'colour'       => 'success',
            'image'        => null,
            'action_url'   => url('/'),
            'action_label' => 'Continue',
        ];
    }
}
