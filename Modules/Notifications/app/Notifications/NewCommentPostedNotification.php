<?php

namespace Modules\Notifications\app\Notifications;

class NewCommentPostedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly int $commentId,
        public readonly string $commenterUsername,
        public readonly string $contentTitle,
        public readonly ?string $excerpt = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'new_comment_posted';
    }

    public function toDatabase($notifiable): array
    {
        $excerpt = $this->excerpt ? ' — “' . \Illuminate\Support\Str::limit($this->excerpt, 80) . '”' : '';

        return [
            'title'        => 'New comment posted',
            'message'      => "{$this->commenterUsername} commented on “{$this->contentTitle}”{$excerpt}",
            'icon'         => 'ph-chat-circle-dots',
            'colour'       => 'warning',
            'image'        => null,
            'action_url'   => route('dashboard.comment'),
            'action_label' => 'Moderate',
            'comment_id'   => $this->commentId,
        ];
    }
}
