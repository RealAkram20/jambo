<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Monetization\app\Models\WithdrawalRequest;

class WithdrawalRejectedNotification extends ChannelGatedNotification
{
    public function __construct(protected WithdrawalRequest $withdrawal)
    {
    }

    protected function settingKey(): string
    {
        return 'withdrawal_rejected';
    }

    public function toDatabase($notifiable): array
    {
        $amount = number_format((float) $this->withdrawal->amount, 0);

        return [
            'title' => 'Withdrawal rejected',
            'message' => "Your withdrawal of UGX {$amount} was rejected and the funds were returned to your wallet. Reason: {$this->withdrawal->rejection_reason}",
            'icon' => 'ph-x-circle',
            'colour' => 'danger',
            'image' => null,
            'action_url' => route('partner.withdrawals.index'),
            'action_label' => 'View withdrawals',
            'withdrawal_id' => $this->withdrawal->id,
        ];
    }
}
