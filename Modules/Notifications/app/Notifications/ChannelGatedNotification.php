<?php

namespace Modules\Notifications\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\app\Models\NotificationAudienceSetting;
use Modules\Notifications\app\Models\NotificationPreference;
use Modules\Notifications\app\Models\NotificationSetting;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Base class for every Jambo notification. Encapsulates the four-layer
 * channel gate — a channel fires only when EVERY layer allows it:
 *
 *   1. Super-admin per-audience switch
 *      (NotificationAudienceSetting::channelsFor($key, $audience)), where
 *      $audience is the recipient's highest role. Falls back to layer 2
 *      when no audience row exists (personal, single-recipient types).
 *   2. Site-wide per-type switch (NotificationSetting::channelsFor($key)).
 *   3. This recipient's per-type opt-out (notification_preferences).
 *   4. This recipient's global per-channel toggle
 *      (users.*_notifications_enabled columns).
 *
 * Strictest layer wins. Subclasses only declare their setting key and the
 * database payload; toMail / toWebPush have sensible defaults derived from
 * the same payload, which any subclass can override.
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
        $key = $this->settingKey();

        // Layer 1+2: the super-admin per-audience switch, falling back to
        // the flat site-wide switch when this type has no audience row
        // (personal, single-recipient types). channelsFor() returns keys
        // in_app/email/push; the flat row uses system/push/email — map it.
        $audience = NotificationAudienceSetting::audienceFor($notifiable);
        $allow = NotificationAudienceSetting::channelsFor($key, $audience);
        if ($allow === null) {
            if (NotificationAudienceSetting::keyIsAudienceControlled($key)) {
                // Role-targeted type, but this audience was deliberately
                // left out of its matrix → deny every channel.
                $allow = ['in_app' => false, 'email' => false, 'push' => false];
            } else {
                // Personal, single-recipient type (no audience concept) →
                // fall back to the flat site-wide switch.
                $flat = NotificationSetting::channelsFor($key);
                $allow = [
                    'in_app' => $flat['system'],
                    'email'  => $flat['email'],
                    'push'   => $flat['push'],
                ];
            }
        }

        $channels = [];

        if (
            $allow['in_app']
            && NotificationPreference::allows($notifiable, $key, 'in_app')
            && ($notifiable->in_app_notifications_enabled ?? true)
        ) {
            $channels[] = 'database';
        }

        if (
            $allow['email']
            && NotificationPreference::allows($notifiable, $key, 'email')
            && ($notifiable->email_notifications_enabled ?? true)
            && !empty($notifiable->email)
        ) {
            $channels[] = 'mail';
        }

        if (
            $allow['push']
            && NotificationPreference::allows($notifiable, $key, 'push')
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
            ->greeting($this->mailGreeting($notifiable))
            ->line($payload['message'] ?? '');

        if (!empty($payload['action_url'])) {
            $mail->action($payload['action_label'] ?? 'Open', $payload['action_url']);
        }

        return $mail->salutation('— The Jambo team');
    }

    /**
     * Address the recipient by their first name when we have one
     * (e.g. "Hi Akram,"), then username, then the generic fallback.
     * Subclasses can override if they need a notification-specific
     * tone.
     */
    protected function mailGreeting($notifiable): string
    {
        $first = trim((string) ($notifiable->first_name ?? ''));
        if ($first !== '') {
            return "Hi {$first},";
        }
        $username = trim((string) ($notifiable->username ?? ''));
        if ($username !== '') {
            return "Hi {$username},";
        }
        return 'Hi there,';
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
