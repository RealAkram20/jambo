<?php

namespace Modules\Notifications\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A pinned "hello world" notification used by the smoke test in
 * tinker and by the admin settings page's "send test" button. Defaults
 * to database-only to avoid spamming inboxes; pass `channels: ['mail']`
 * (or ['database','mail']) to exercise the mail pipeline end-to-end.
 */
class TestNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $channels
     */
    public function __construct(
        public readonly string $title = 'Hello from Jambo',
        public readonly string $message = 'This is a test notification. If you can see it in the bell dropdown, the system channel is working.',
        public readonly array $channels = ['database'],
    ) {
    }

    public function via($notifiable): array
    {
        return $this->channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->title)
            ->greeting('Hi there')
            ->line($this->message)
            ->line('— The Jambo notifications pipeline');
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
