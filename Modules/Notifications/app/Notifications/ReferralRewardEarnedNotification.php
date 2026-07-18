<?php

namespace Modules\Notifications\app\Notifications;

class ReferralRewardEarnedNotification extends ChannelGatedNotification
{
    public function __construct(
        protected string $amount,
        protected string $currency,
    ) {
    }

    protected function settingKey(): string
    {
        return 'referral_reward_earned';
    }

    public function toDatabase($notifiable): array
    {
        $amount = number_format((float) $this->amount, 0);

        return [
            'title' => 'Referral reward earned',
            'message' => "You earned {$this->currency} {$amount} — someone you referred just subscribed.",
            'icon' => 'ph-gift',
            'colour' => 'success',
            'image' => null,
            'action_url' => !empty($notifiable->username)
                ? route('profile.refer', ['username' => $notifiable->username])
                : null,
            'action_label' => 'View earnings',
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
