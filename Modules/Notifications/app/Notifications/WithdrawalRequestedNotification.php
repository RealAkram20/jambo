<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Wallet\app\Models\WithdrawalRequest;

/**
 * Admin-facing: someone wants money out — review the payout queue.
 * Covers every wallet owner type (partner profiles and regular users).
 */
class WithdrawalRequestedNotification extends ChannelGatedNotification
{
    public function __construct(protected WithdrawalRequest $withdrawal)
    {
    }

    protected function settingKey(): string
    {
        return 'withdrawal_requested';
    }

    public function toDatabase($notifiable): array
    {
        $amount = number_format((float) $this->withdrawal->amount, 0);

        return [
            'title' => 'Withdrawal requested',
            'message' => "{$this->withdrawal->ownerLabel()} requested a payout of {$this->withdrawal->currency} {$amount}.",
            'icon' => 'ph-hand-coins',
            'colour' => 'warning',
            'image' => null,
            'action_url' => route('admin.wallet.withdrawals.index'),
            'action_label' => 'Review request',
            'withdrawal_id' => $this->withdrawal->id,
        ];
    }
}
