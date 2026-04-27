<?php

namespace Modules\Notifications\app\Notifications;

class NewReviewPostedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly int $reviewId,
        public readonly string $reviewerUsername,
        public readonly string $contentTitle,
        public readonly int $rating = 0,
    ) {
    }

    protected function settingKey(): string
    {
        return 'new_review_posted';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'New review posted',
            'message'      => "{$this->reviewerUsername} reviewed “{$this->contentTitle}”" . ($this->rating ? " ({$this->rating}★)." : '.'),
            'icon'         => 'ph-star',
            'colour'       => 'warning',
            'image'        => null,
            // Reviews and ratings now live on the same admin page —
            // dashboard.review was removed in favour of dashboard.rating.
            'action_url'   => route('dashboard.rating'),
            'action_label' => 'Moderate',
            'review_id'    => $this->reviewId,
        ];
    }
}
