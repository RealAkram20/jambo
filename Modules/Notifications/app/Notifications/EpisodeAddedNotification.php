<?php

namespace Modules\Notifications\app\Notifications;

class EpisodeAddedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly string $showTitle,
        public readonly int $seasonNumber,
        public readonly int $episodeNumber,
        public readonly ?string $episodeTitle = null,
        public readonly ?string $showSlug = null,
        public readonly ?string $poster = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'episode_added';
    }

    public function toDatabase($notifiable): array
    {
        $label = "S{$this->seasonNumber}E{$this->episodeNumber}";
        if ($this->episodeTitle) $label .= " — {$this->episodeTitle}";

        return [
            'title'        => 'New episode',
            'message'      => "{$this->showTitle}: {$label} is ready to watch.",
            'icon'         => 'ph-play-circle',
            'colour'       => 'primary',
            'image'        => $this->poster,
            'action_url'   => $this->showSlug
                ? url('/tv-show-detail/' . $this->showSlug)
                : url('/tv-shows'),
            'action_label' => 'Watch now',
            'show_title'   => $this->showTitle,
            'season_number'=> $this->seasonNumber,
            'episode_number' => $this->episodeNumber,
        ];
    }
}
