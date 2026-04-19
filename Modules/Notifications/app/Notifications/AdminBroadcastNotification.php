<?php

namespace Modules\Notifications\app\Notifications;

class AdminBroadcastNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $linkUrl = null,
        public readonly ?string $linkLabel = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'admin_broadcast';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => $this->subject,
            'message'      => $this->body,
            'icon'         => 'ph-megaphone',
            'colour'       => 'primary',
            'image'        => null,
            'action_url'   => $this->linkUrl,
            'action_label' => $this->linkLabel ?? 'Learn more',
        ];
    }
}
