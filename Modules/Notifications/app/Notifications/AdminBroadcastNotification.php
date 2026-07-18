<?php

namespace Modules\Notifications\app\Notifications;

use NotificationChannels\WebPush\WebPushChannel;

class AdminBroadcastNotification extends ChannelGatedNotification
{
    /**
     * @param  list<string>  $channels  Subset of ['system','email','push']
     *   the admin ticked for this one broadcast. It NARROWS the four-layer
     *   gate — it can silence a transport for this send, but can never force
     *   a channel a site switch or a recipient has turned off.
     */
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $linkUrl = null,
        public readonly ?string $linkLabel = null,
        public readonly ?string $imageUrl = null,
        public readonly array $channels = ['system', 'email', 'push'],
    ) {
    }

    protected function settingKey(): string
    {
        return 'admin_broadcast';
    }

    /**
     * Take the channels the four-layer gate allows for this recipient, then
     * keep only the ones this broadcast opted into. Intersection, never
     * union — the picker can only ever remove transports.
     */
    public function via($notifiable): array
    {
        $gated = parent::via($notifiable);

        $selected = [];
        if (in_array('system', $this->channels, true)) {
            $selected[] = 'database';
        }
        if (in_array('email', $this->channels, true)) {
            $selected[] = 'mail';
        }
        if (in_array('push', $this->channels, true)) {
            $selected[] = WebPushChannel::class;
        }

        return array_values(array_intersect($gated, $selected));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => $this->subject,
            'message'      => $this->body,
            'icon'         => 'ph-megaphone',
            'colour'       => 'primary',
            'image'        => $this->imageUrl,
            'action_url'   => $this->linkUrl,
            'action_label' => $this->linkLabel ?? 'Learn more',
        ];
    }
}
