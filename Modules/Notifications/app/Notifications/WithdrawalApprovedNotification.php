<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Monetization\app\Models\WithdrawalRequest;

class WithdrawalApprovedNotification extends ChannelGatedNotification
{
    public function __construct(protected WithdrawalRequest $withdrawal)
    {
    }

    protected function settingKey(): string
    {
        return 'withdrawal_approved';
    }

    public function toDatabase($notifiable): array
    {
        $amount = number_format((float) $this->withdrawal->amount, 0);

        return [
            'title' => 'Withdrawal approved',
            'message' => "Your withdrawal of UGX {$amount} was approved — the mobile money transfer is on its way to {$this->withdrawal->payout_msisdn_snapshot}.",
            'icon' => 'ph-check-circle',
            'colour' => 'primary',
            'image' => null,
            'action_url' => route('partner.withdrawals.index'),
            'action_label' => 'View withdrawals',
            'withdrawal_id' => $this->withdrawal->id,
        ];
    }
}
