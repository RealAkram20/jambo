<?php

namespace Modules\Notifications\app\Notifications;

use Modules\Wallet\app\Models\WithdrawalRequest;

/**
 * Where a withdrawal notification should send its recipient: partners
 * to the Creator Studio withdrawals page, everyone else to their
 * Refer & Earn earnings tab.
 */
class WalletNotificationLinks
{
    public static function forRecipient(WithdrawalRequest $withdrawal, $notifiable): ?string
    {
        if ($withdrawal->owner_type === 'Modules\\Monetization\\app\\Models\\MonetizationPartner') {
            return \Illuminate\Support\Facades\Route::has('partner.withdrawals.index')
                ? route('partner.withdrawals.index')
                : null;
        }

        return !empty($notifiable->username)
            ? route('profile.refer', ['username' => $notifiable->username, 'tab' => 'earnings'])
            : null;
    }
}
