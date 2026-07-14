<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Monetization\app\Models\WithdrawalRequest;

class WithdrawalPaidNotification extends ChannelGatedNotification
{
    public function __construct(protected WithdrawalRequest $withdrawal)
    {
    }

    protected function settingKey(): string
    {
        return 'withdrawal_paid';
    }

    public function toDatabase($notifiable): array
    {
        $amount = number_format((float) $this->withdrawal->amount, 0);

        return [
            'title' => 'Withdrawal paid',
            'message' => "UGX {$amount} was sent to {$this->withdrawal->payout_msisdn_snapshot} ({$this->withdrawal->payout_network_snapshot}). Reference: {$this->withdrawal->transaction_reference}.",
            'icon' => 'ph-money',
            'colour' => 'success',
            'image' => null,
            'action_url' => route('partner.withdrawals.index'),
            'action_label' => 'View withdrawals',
            'withdrawal_id' => $this->withdrawal->id,
            'transaction_reference' => $this->withdrawal->transaction_reference,
        ];
    }
}
