<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual mobile-money withdrawal workflow:
 *
 *   requested → approved → paid
 *            ↘ rejected (also allowed from approved)
 *
 * The payout number/name/network are SNAPSHOTS of the verified profile
 * at request time — if the partner changes their number afterwards the
 * in-flight request still shows (and pays) what was verified when they
 * asked. hold_entry_id links the negative wallet_entries hold created
 * with the request; on payment the hold IS the permanent debit, on
 * rejection a compensating hold_release credit restores the balance.
 *
 * transaction_reference records the MTN MoMo / Airtel Money receipt id
 * the finance admin got when sending the money off-platform.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')
                ->constrained('monetization_partners')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('requested'); // requested | approved | paid | rejected
            $table->string('payout_msisdn_snapshot', 20);
            $table->string('payout_name_snapshot');
            $table->string('payout_network_snapshot', 10);
            $table->foreignId('hold_entry_id')->nullable()
                ->constrained('wallet_entries')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transaction_reference')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'status']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
