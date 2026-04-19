<?php

namespace Modules\Notifications\app\Notifications;

class SubscriptionActivatedNotification extends ChannelGatedNotification
{
    public function __construct(
        public readonly string $planName = 'Jambo premium',
        public readonly ?string $expiresOn = null,
    ) {
    }

    protected function settingKey(): string
    {
        return 'subscription_activated';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'        => 'Subscription activated',
            'message'      => "Your {$this->planName} subscription is active" . ($this->expiresOn ? " until {$this->expiresOn}." : '.') . ' Enjoy Jambo.',
            'icon'         => 'ph-crown-simple',
            'colour'       => 'success',
            'image'        => null,
            'action_url'   => route('profile.membership', ['username' => $notifiable->username]),
            'action_label' => 'View membership',
            'plan'         => $this->planName,
            'expires_on'   => $this->expiresOn,
        ];
    }
}
