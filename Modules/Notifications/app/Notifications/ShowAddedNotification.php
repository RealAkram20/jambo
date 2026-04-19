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
            'title'        => 'New show added',
            'message'      => "“{$this->showTitle}” is now streaming on Jambo.",
            'icon'         => 'ph-television',
            'colour'       => 'primary',
            'image'        => $this->poster,
            'action_url'   => $this->showSlug
                ? url('/tv-show-detail/' . $this->showSlug)
                : url('/tv-shows'),
            'action_label' => 'Open show',
            'show_id'      => $this->showId,
            'show_title'   => $this->showTitle,
        ];
    }
}
