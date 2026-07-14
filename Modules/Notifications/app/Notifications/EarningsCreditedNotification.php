<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Monetization\app\Models\PartnerStatement;

class EarningsCreditedNotification extends ChannelGatedNotification
{
    public function __construct(protected PartnerStatement $statement)
    {
    }

    protected function settingKey(): string
    {
        return 'earnings_credited';
    }

    public function toDatabase($notifiable): array
    {
        $month = $this->statement->period->period_month->format('F Y');
        $amount = number_format((float) $this->statement->amount, 0);

        return [
            'title' => 'Earnings credited',
            'message' => "Your {$month} statement has been released — UGX {$amount} was added to your wallet.",
            'icon' => 'ph-coins',
            'colour' => 'success',
            'image' => null,
            'action_url' => route('partner.wallet'),
            'action_label' => 'View wallet',
            'statement_id' => $this->statement->id,
            'amount' => (string) $this->statement->amount,
        ];
    }
}
