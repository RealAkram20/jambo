<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The universal wallet cutover.
 *
 * 1. Creates wallet_ledger_entries + wallet_withdrawal_requests —
 *    ONE ledger and ONE payout queue for every money stream (partner
 *    statement earnings, referral commissions, refunds).
 * 2. Copies the two legacy ledgers in (wallet_entries owned by
 *    MonetizationPartner rows, referral_earnings owned by Users),
 *    remapping withdrawal-request references onto the new queue rows.
 * 3. Drops the legacy tables so no code path can write to them again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 191);
            $table->unsignedBigInteger('owner_id');
            // statement_credit | referral_reward | refund | spend |
            // withdrawal_hold | hold_release | adjustment
            $table->string('type', 30);
            $table->decimal('amount', 12, 2); // signed: credits +, debits −
            $table->decimal('balance_after', 12, 2);
            $table->char('currency', 3);
            $table->string('reference_type', 191)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('memo')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['reference_type', 'reference_id', 'type'], 'wallet_ledger_reference_unique');
            $table->index(['owner_type', 'owner_id', 'currency']);
            $table->index(['owner_type', 'owner_id', 'type']);
        });

        Schema::create('wallet_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 191);
            $table->unsignedBigInteger('owner_id');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('status', 20)->default('requested');
            $table->string('payee_name', 100);
            $table->string('payee_msisdn', 30);
            $table->string('payee_network', 30)->nullable();
            $table->foreignId('hold_entry_id')->nullable()->constrained('wallet_ledger_entries')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transaction_reference')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'status']);
            $table->index('status');
        });

        $this->migrateLegacy();

        Schema::dropIfExists('referral_withdrawal_requests');
        Schema::dropIfExists('referral_earnings');
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('wallet_entries');
    }

    private function migrateLegacy(): void
    {
        $partnerClass = 'Modules\\Monetization\\app\\Models\\MonetizationPartner';
        $userClass = 'App\\Models\\User';
        $newRequestClass = 'Modules\\Wallet\\app\\Models\\WithdrawalRequest';
        $currency = config('payments.currency', 'UGX');

        // Legacy morph strings whose references must be re-pointed at
        // the new queue rows.
        $oldPartnerRequestClass = 'Modules\\Monetization\\app\\Models\\WithdrawalRequest';
        $oldReferralRequestClass = 'Modules\\Referrals\\app\\Models\\ReferralWithdrawalRequest';

        $entryMap = []; // "<table>:<old id>" => new ledger entry id

        // ---- Ledgers ----
        if (Schema::hasTable('wallet_entries')) {
            foreach (DB::table('wallet_entries')->orderBy('id')->cursor() as $row) {
                $newId = DB::table('wallet_ledger_entries')->insertGetId([
                    'owner_type' => $partnerClass,
                    'owner_id' => $row->partner_id,
                    'type' => $row->type,
                    'amount' => $row->amount,
                    'balance_after' => $row->balance_after,
                    'currency' => $currency,
                    'reference_type' => $row->reference_type,
                    'reference_id' => $row->reference_id,
                    'memo' => $row->memo,
                    'meta' => null,
                    'created_by' => $row->created_by,
                    'created_at' => $row->created_at,
                ]);
                $entryMap['wallet_entries:' . $row->id] = $newId;
            }
        }

        if (Schema::hasTable('referral_earnings')) {
            foreach (DB::table('referral_earnings')->orderBy('id')->cursor() as $row) {
                $newId = DB::table('wallet_ledger_entries')->insertGetId([
                    'owner_type' => $userClass,
                    'owner_id' => $row->user_id,
                    // 'reward' becomes the universal 'referral_reward'.
                    'type' => $row->type === 'reward' ? 'referral_reward' : $row->type,
                    'amount' => $row->amount,
                    'balance_after' => $row->balance_after,
                    'currency' => $row->currency,
                    'reference_type' => $row->reference_type,
                    'reference_id' => $row->reference_id,
                    'memo' => $row->memo,
                    'meta' => $row->referral_id ? json_encode(['referral_id' => $row->referral_id]) : null,
                    'created_by' => null,
                    'created_at' => $row->created_at,
                ]);
                $entryMap['referral_earnings:' . $row->id] = $newId;
            }
        }

        // ---- Withdrawal queues ----
        $requestMap = []; // "<old class>:<old id>" => new request id

        if (Schema::hasTable('withdrawal_requests')) {
            foreach (DB::table('withdrawal_requests')->orderBy('id')->cursor() as $row) {
                $requestMap[$oldPartnerRequestClass . ':' . $row->id] = DB::table('wallet_withdrawal_requests')->insertGetId([
                    'owner_type' => $partnerClass,
                    'owner_id' => $row->partner_id,
                    'requested_by' => null,
                    'amount' => $row->amount,
                    'currency' => $currency,
                    'status' => $row->status,
                    'payee_name' => $row->payout_name_snapshot ?? '',
                    'payee_msisdn' => $row->payout_msisdn_snapshot ?? '',
                    'payee_network' => $row->payout_network_snapshot ?? null,
                    'hold_entry_id' => $row->hold_entry_id ? ($entryMap['wallet_entries:' . $row->hold_entry_id] ?? null) : null,
                    'requested_at' => $row->requested_at,
                    'approved_at' => $row->approved_at ?? null,
                    'approved_by' => $row->approved_by ?? null,
                    'paid_at' => $row->paid_at ?? null,
                    'paid_by' => $row->paid_by ?? null,
                    'transaction_reference' => $row->transaction_reference ?? null,
                    'rejected_at' => $row->rejected_at ?? null,
                    'rejected_by' => $row->rejected_by ?? null,
                    'rejection_reason' => $row->rejection_reason ?? null,
                    'created_at' => $row->created_at ?? $row->requested_at,
                    'updated_at' => $row->updated_at ?? $row->requested_at,
                ]);
            }
        }

        if (Schema::hasTable('referral_withdrawal_requests')) {
            foreach (DB::table('referral_withdrawal_requests')->orderBy('id')->cursor() as $row) {
                $requestMap[$oldReferralRequestClass . ':' . $row->id] = DB::table('wallet_withdrawal_requests')->insertGetId([
                    'owner_type' => $userClass,
                    'owner_id' => $row->user_id,
                    'requested_by' => $row->user_id,
                    'amount' => $row->amount,
                    'currency' => $row->currency,
                    'status' => $row->status,
                    'payee_name' => $row->payee_name,
                    'payee_msisdn' => $row->payee_msisdn,
                    'payee_network' => null,
                    'hold_entry_id' => $row->hold_entry_id ? ($entryMap['referral_earnings:' . $row->hold_entry_id] ?? null) : null,
                    'requested_at' => $row->requested_at,
                    'approved_at' => $row->approved_at,
                    'approved_by' => $row->approved_by,
                    'paid_at' => $row->paid_at,
                    'paid_by' => $row->paid_by,
                    'transaction_reference' => $row->transaction_reference,
                    'rejected_at' => $row->rejected_at,
                    'rejected_by' => $row->rejected_by,
                    'rejection_reason' => $row->rejection_reason,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        // Re-point hold/release ledger references from the legacy
        // request classes to the new queue rows so idempotency and
        // traceability survive the merge.
        foreach ($requestMap as $oldKey => $newRequestId) {
            [$oldClass, $oldId] = explode(':', $oldKey);
            DB::table('wallet_ledger_entries')
                ->where('reference_type', $oldClass)
                ->where('reference_id', (int) $oldId)
                ->update([
                    'reference_type' => $newRequestClass,
                    'reference_id' => $newRequestId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_withdrawal_requests');
        Schema::dropIfExists('wallet_ledger_entries');
    }
};
