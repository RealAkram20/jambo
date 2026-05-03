<?php

namespace Modules\Notifications\app\Notifications;

class ShowAddedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly int $showId,
        public readonly string $showTitle,
        public readonly ?string $showSlug = null,
        public readonly ?string $poster = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'show_added';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'New series added',
            'message'      => "“{$this->showTitle}” is now streaming on Jambo.",
            'icon'         => 'ph-television',
            'colour'       => 'primary',
            'image'        => $this->poster,
            // Routes use /series/{slug} (movie+show URL conventions
            // were normalised to /series/, with /tv-show 301'ing).
            // The fallback now points at /series rather than the
            // non-existent /tv-shows that produced a 404.
            'action_url'   => $this->showSlug
                ? url('/series/' . $this->showSlug)
                : url('/series'),
            'action_label' => 'Open series',
            'show_id'      => $this->showId,
            'show_title'   => $this->showTitle,
        ];
    }
}
