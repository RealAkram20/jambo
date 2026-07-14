<?php

namespace Modules\Monetization\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Models\WalletEntry;
use Modules\Monetization\app\Models\WithdrawalRequest;
use Modules\Monetization\app\Services\AuditLogger;
use Modules\Monetization\app\Services\WalletService;

/**
 * The payout clerk's queue. Money leaves Jambo OFF-platform (finance
 * sends MTN MoMo / Airtel Money manually); this controller only moves
 * the state machine and the ledger:
 *
 *   requested → approved → paid   (hold stays = permanent debit)
 *            ↘ rejected           (hold released back to wallet)
 *
 * Every transition: transaction + row lock + status assertion, so
 * stale/double-clicked buttons become no-ops instead of double pays.
 */
class WithdrawalAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();

        $withdrawals = WithdrawalRequest::query()
            ->with('partner:id,display_name,type')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'requested', 'approved', 'paid', 'rejected')")
            ->orderByDesc('requested_at')
            ->paginate(25)
            ->withQueryString();

        $counts = WithdrawalRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('monetization::admin.withdrawals.index', [
            'withdrawals' => $withdrawals,
            'counts' => $counts,
            'status' => $status,
        ]);
    }

    public function show(WithdrawalRequest $withdrawal)
    {
        $withdrawal->load(['partner.user:id,username,email', 'approvedBy:id,username', 'paidBy:id,username', 'rejectedBy:id,username']);

        return view('monetization::admin.withdrawals.show', [
            'withdrawal' => $withdrawal,
            'balance' => $withdrawal->partner->walletBalance(),
        ]);
    }

    public function approve(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $withdrawal) {
            $fresh = WithdrawalRequest::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if ($fresh->status !== WithdrawalRequest::STATUS_REQUESTED) {
                return 'This request is no longer awaiting approval.';
            }

            $fresh->update([
                'status' => WithdrawalRequest::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
            ]);

            AuditLogger::log('withdrawal.approved', $fresh);

            return $fresh;
        });

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        event(new \Modules\Notifications\app\Events\WithdrawalApproved($result));

        return back()->with('success', sprintf(
            'Approved. Now send UGX %s via %s to %s (%s), then record the transaction reference below.',
            number_format((float) $result->amount, 0),
            strtoupper($result->payout_network_snapshot),
            $result->payout_msisdn_snapshot,
            $result->payout_name_snapshot,
        ));
    }

    public function markPaid(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
    {
        $data = $request->validate([
            'transaction_reference' => 'required|string|max:190',
        ]);

        $result = DB::transaction(function () use ($request, $withdrawal, $data) {
            $fresh = WithdrawalRequest::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if ($fresh->status !== WithdrawalRequest::STATUS_APPROVED) {
                return 'Only approved requests can be marked paid.';
            }

            // The negative hold entry created at request time IS the
            // permanent debit — nothing more to move in the ledger.
            $fresh->update([
                'status' => WithdrawalRequest::STATUS_PAID,
                'paid_at' => now(),
                'paid_by' => $request->user()->id,
                'transaction_reference' => $data['transaction_reference'],
            ]);

            AuditLogger::log('withdrawal.paid', $fresh, ['after' => [
                'transaction_reference' => $data['transaction_reference'],
            ]]);

            return $fresh;
        });

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        event(new \Modules\Notifications\app\Events\WithdrawalPaid($result));

        return back()->with('success', 'Marked paid — the partner has been notified.');
    }

    public function reject(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string|max:190',
        ]);

        $result = DB::transaction(function () use ($request, $withdrawal, $data) {
            $fresh = WithdrawalRequest::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if (!$fresh->isOpen()) {
                return 'Only requested or approved withdrawals can be rejected.';
            }

            $fresh->update([
                'status' => WithdrawalRequest::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejected_by' => $request->user()->id,
                'rejection_reason' => $data['rejection_reason'],
            ]);

            // Compensating credit returns the held funds to the wallet.
            app(WalletService::class)->append(
                partner: MonetizationPartner::query()->findOrFail($fresh->partner_id),
                type: WalletEntry::TYPE_HOLD_RELEASE,
                amount: (string) $fresh->amount,
                reference: $fresh,
                memo: 'Withdrawal rejected: '.$data['rejection_reason'],
                createdBy: $request->user()->id,
            );

            AuditLogger::log('withdrawal.rejected', $fresh, ['after' => [
                'reason' => $data['rejection_reason'],
            ]]);

            return $fresh;
        });

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        event(new \Modules\Notifications\app\Events\WithdrawalRejected($result));

        return back()->with('success', 'Rejected — funds returned to the partner wallet.');
    }
}
