<?php

namespace Modules\Notifications\app\Notifications;

class MovieAddedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly int $movieId,
        public readonly string $movieTitle,
        public readonly ?string $movieSlug = null,
        public readonly ?string $poster = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'movie_added';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'New movie added',
            'message'      => "“{$this->movieTitle}” just arrived in the Jambo catalogue.",
            'icon'         => 'ph-film-strip',
            'colour'       => 'primary',
            'image'        => $this->poster,
            // The listing route is /movie (singular) — /movies is a 404,
            // so the slugless fallback used to strand the user.
            'action_url'   => $this->movieSlug
                ? url('/movie-detail/' . $this->movieSlug)
                : url('/movie'),
            'action_label' => 'Watch now',
            'movie_id'     => $this->movieId,
            'movie_title'  => $this->movieTitle,
        ];
    }
}
