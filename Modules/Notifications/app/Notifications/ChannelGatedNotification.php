<?php

namespace Modules\Notifications\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\app\Models\NotificationSetting;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Base class for every Jambo notification. Encapsulates the two-layer
 * channel gate:
 *
 *   1. Admin-global switch (NotificationSetting::channelsFor($key))
 *   2. Per-user preference (users.*_notifications_enabled columns)
 *
 * Whichever is stricter wins. Subclasses only need to declare their
 * setting key and supply the database payload; toMail / toWebPush have
 * sensible defaults derived from the same payload, which any subclass
 * can override.
 */
abstract class ChannelGatedNotification extends Notification
{
    use Queueable;

    /**
     * Notification key that points at a NotificationSetting row.
     * Must match a key in NotificationSetting::definitions().
     */
    abstract protected function settingKey(): string;

    /**
     * Shape:
     *   [
     *     'title'      => string,
     *     'message'    => string,
     *     'icon'       => string  (Phosphor class, e.g. 'ph-bell'),
     *     'colour'     => string  ('primary'|'success'|...),
     *     'image'      => string|null,
     *     'action_url' => string|null,
     *     // plus any domain-specific fields
     *   ]
     *
     * @return array<string, mixed>
     */
    abstract public function toDatabase($notifiable): array;

    public function via($notifiable): array
    {
        $global = NotificationSetting::channelsFor($this->settingKey());
        $channels = [];

        if ($global['system'] && ($notifiable->in_app_notifications_enabled ?? true)) {
            $channels[] = 'database';
        }

        if (
            $global['email']
            && ($notifiable->email_notifications_enabled ?? true)
            && !empty($notifiable->email)
        ) {
            $channels[] = 'mail';
        }

        if (
            $global['push']
            && ($notifiable->push_notifications_enabled ?? false)
            && method_exists($notifiable, 'pushSubscriptions')
            && $notifiable->pushSubscriptions()->exists()
            && config('webpush.vapid.public_key')
        ) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $payload = $this->toDatabase($notifiable);

        $mail = (new MailMessage())
            ->subject($payload['title'] ?? 'Notification from Jambo')
            ->greeting('Hi there,')
            ->line($payload['message'] ?? '');

        if (!empty($payload['action_url'])) {
            $mail->action($payload['action_label'] ?? 'Open', $payload['action_url']);
        }

        return $mail->salutation('— The Jambo team');
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $payload = $this->toDatabase($notifiable);

        $msg = (new WebPushMessage())
            ->title($payload['title'] ?? 'Jambo')
            ->icon(url('/favicon.ico'))
            ->body($payload['message'] ?? '');

        if (!empty($payload['image'])) {
            $msg->image($this->absoluteUrl($payload['image']));
        }

        if (!empty($payload['action_url'])) {
            $msg->action('Open', 'open')->data(['url' => $payload['action_url']]);
        }

        return $msg;
    }

    /**
     * Push image / icon URLs sent to the service worker must be absolute.
     * Posters and avatars in payloads can be either, depending on caller —
     * normalise here so subclasses don't have to remember.
     */
    protected function absoluteUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return url(ltrim($path, '/'));
    }
}
