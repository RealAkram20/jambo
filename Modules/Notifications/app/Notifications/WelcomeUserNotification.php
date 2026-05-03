<?php

namespace Modules\Notifications\app\Notifications;

use App\Models\User;

class WelcomeUserNotification extends ChannelGatedNotification
{
    public function __construct(public readonly User $newUser)
    {
    }

    protected function settingKey(): string
    {
        return 'welcome_user';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'Welcome to Jambo 🎬',
            'message'      => 'Thanks for signing up — start exploring movies, series, and add favourites to your watchlist.',
            'icon'         => 'ph-hand-waving',
            'colour'       => 'primary',
            'image'        => null,
            'action_url'   => url('/'),
            'action_label' => 'Browse catalogue',
        ];
    }
}
