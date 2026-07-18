<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Wallet\app\Models\WithdrawalRequest;

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
        $network = $this->withdrawal->payee_network ? ' (' . strtoupper($this->withdrawal->payee_network) . ')' : '';

        return [
            'title' => 'Withdrawal paid',
            'message' => "{$this->withdrawal->currency} {$amount} was sent to {$this->withdrawal->payee_msisdn}{$network}. Reference: {$this->withdrawal->transaction_reference}.",
            'icon' => 'ph-money',
            'colour' => 'success',
            'image' => null,
            'action_url' => WalletNotificationLinks::forRecipient($this->withdrawal, $notifiable),
            'action_label' => 'View wallet',
            'withdrawal_id' => $this->withdrawal->id,
            'transaction_reference' => $this->withdrawal->transaction_reference,
        ];
    }
}
