<?php

namespace Modules\Notifications\app\Notifications;

class ContentReportedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly string $contentType, // 'review'|'comment'|'movie'|'show'
        public readonly int $contentId,
        public readonly string $contentTitle,
        public readonly string $reporterUsername,
        public readonly ?string $reasonText = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'content_reported';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'Content reported',
            'message'      => "{$this->reporterUsername} flagged a {$this->contentType}: “{$this->contentTitle}”" . ($this->reasonText ? " — {$this->reasonText}" : '.'),
            'icon'         => 'ph-flag',
            'colour'       => 'danger',
            'image'        => null,
            'action_url'   => url('/admin/moderation'),
            'action_label' => 'Open moderation',
            'content_type' => $this->contentType,
            'content_id'   => $this->contentId,
        ];
    }
}
