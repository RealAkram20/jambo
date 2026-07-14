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
            // Deep-link to the episode itself — the alert says "S2E4 is
            // ready to watch", so dropping the user on the series landing
            // page and making them hunt for it is a small betrayal.
            // Falls back to the series page, then the series index.
            // Routes use /series/{slug}; /tv-shows (plural) is a
            // 404, /tv-show-detail/{slug} 301s but adds an extra hop.
            'action_url'   => $this->showSlug
                ? url("/episode/{$this->showSlug}/s{$this->seasonNumber}/ep{$this->episodeNumber}")
                : url('/series'),
            'action_label' => 'Watch now',
            'show_title'   => $this->showTitle,
            'season_number'=> $this->seasonNumber,
            'episode_number' => $this->episodeNumber,
        ];
    }
}
