<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Monetization\app\Models\MonetizationPartner;

class PayoutProfileVerifiedNotification extends ChannelGatedNotification
{
    public function __construct(protected MonetizationPartner $partner)
    {
    }

    protected function settingKey(): string
    {
        return 'payout_profile_verified';
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Payout profile verified',
            'message' => "Your mobile money details ({$this->partner->payout_msisdn}) were verified — you can now request withdrawals.",
            'icon' => 'ph-seal-check',
            'colour' => 'success',
            'image' => null,
            'action_url' => route('partner.withdrawals.index'),
            'action_label' => 'Request withdrawal',
        ];
    }
}
