<?php

namespace Modules\Notifications\app\Notifications;

class SeasonAddedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly string $showTitle,
        public readonly int $seasonNumber,
        public readonly ?string $showSlug = null,
        public readonly ?string $poster = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'season_added';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'New season added',
            'message'      => "{$this->showTitle} — season {$this->seasonNumber} is out.",
            'icon'         => 'ph-stack',
            'colour'       => 'primary',
            'image'        => $this->poster,
            // Routes use /series/{slug}; /tv-shows (plural) is a
            // 404, /tv-show-detail/{slug} 301s but adds an extra hop.
            'action_url'   => $this->showSlug
                ? url('/series/' . $this->showSlug)
                : url('/series'),
            'action_label' => 'Watch season',
            'show_title'   => $this->showTitle,
            'season_number'=> $this->seasonNumber,
        ];
    }
}
