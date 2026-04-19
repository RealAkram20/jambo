<?php

namespace Modules\Notifications\app\Notifications;

use App\Models\User;

class UserSignupNotification extends ChannelGatedNotification
{
    public function __construct(public readonly User $signedUpUser)
    {
    }

    protected function settingKey(): string
    {
        return 'user_signup';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'      => 'New user signup',
            'message'    => $this->displayName() . ' just created an account on Jambo.',
            'icon'       => 'ph-user-plus',
            'colour'     => 'primary',
            'image'      => $this->signedUpUser->avatar_url ?? null,
            'action_url' => route('dashboard.user-list'),
            'action_label' => 'Open user list',
            'user_id'    => $this->signedUpUser->id,
            'username'   => $this->signedUpUser->username,
        ];
    }

    private function displayName(): string
    {
        $full = trim(($this->signedUpUser->first_name ?? '') . ' ' . ($this->signedUpUser->last_name ?? ''));
        return $full !== '' ? $full : ($this->signedUpUser->username ?? $this->signedUpUser->email);
    }
}
