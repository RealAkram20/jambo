<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Wallet\app\Models\WithdrawalRequest;

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
            'message' => "Your payout of {$this->withdrawal->currency} {$amount} was approved — the mobile money transfer is on its way to {$this->withdrawal->payee_msisdn}.",
            'icon' => 'ph-check-circle',
            'colour' => 'primary',
            'image' => null,
            'action_url' => WalletNotificationLinks::forRecipient($this->withdrawal, $notifiable),
            'action_label' => 'View wallet',
            'withdrawal_id' => $this->withdrawal->id,
        ];
    }
}
