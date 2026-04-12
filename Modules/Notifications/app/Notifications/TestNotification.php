<?php

namespace Modules\Notifications\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A pinned "hello world" notification used by the smoke test in
 * tinker and by the future admin settings page's "send test" button.
 * Delivers only via the database channel so tests don't spam inboxes.
 */
class TestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title = 'Hello from Jambo',
        public readonly string $message = 'This is a test notification. If you can see it in the bell dropdown, the system channel is working.',
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'icon' => 'ph-bell-ringing',
            'colour' => 'primary',
            'action_url' => null,
            'source' => 'test',
        ];
    }
}
