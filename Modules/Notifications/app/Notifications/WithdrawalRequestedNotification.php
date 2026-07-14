<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Monetization\app\Models\WithdrawalRequest;

/**
 * Admin-facing: a partner wants money out — review the queue.
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
        $partner = $this->withdrawal->partner->display_name ?? 'A partner';

        return [
            'title' => 'Withdrawal requested',
            'message' => "{$partner} requested a withdrawal of UGX {$amount}.",
            'icon' => 'ph-hand-coins',
            'colour' => 'warning',
            'image' => null,
            'action_url' => route('admin.monetization.withdrawals.show', $this->withdrawal),
            'action_label' => 'Review request',
            'withdrawal_id' => $this->withdrawal->id,
        ];
    }
}
