<?php

namespace Modules\Notifications\app\Notifications;

class WatchlistAvailableNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $slug = null,
        public readonly ?string $poster = null,
        public readonly string $kind = 'movie', // 'movie'|'show'
    ) {
    }

    protected function settingKey(): string
    {
        return 'watchlist_available';
    }

    public function toDatabase($notifiable): array
    {
        $prefix = $this->kind === 'show' ? '/tv-show-detail/' : '/movie-detail/';

        return [
            'title'        => 'On your watchlist: now available',
            'message'      => "“{$this->title}” from your watchlist is ready to watch on Jambo.",
            'icon'         => 'ph-bookmark-simple',
            'colour'       => 'info',
            'image'        => $this->poster,
            'action_url'   => $this->slug ? url($prefix . $this->slug) : route('profile.watchlist', ['username' => $notifiable->username]),
            'action_label' => 'Watch now',
            'kind'         => $this->kind,
        ];
    }
}
