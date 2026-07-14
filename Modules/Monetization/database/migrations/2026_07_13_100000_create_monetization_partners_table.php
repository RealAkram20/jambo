<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The partner registry: who earns from the monetization program.
 *
 * A partner is NOT the same thing as a Vj row — VJs on content are
 * display credits; only partners explicitly enrolled here accrue
 * earnings. type=vj partners usually point at both a `vjs` row (for
 * catalog identity) and a `users` row (for login); production
 * companies / creators may have only a user link.
 *
 * Payout profile fields live here too (mobile-money number + registered
 * name). Withdrawals are only possible against a `verified` profile,
 * and changing the number drops it back to pending_review + a cooldown
 * freeze (payout_locked_until) — the anti-account-takeover measure.
 *
 * Partners are never hard-deleted (ledger/statements restrict) —
 * suspend instead. user_id/vj_id SET NULL on delete with display_name
 * kept as the audit-safe snapshot, mirroring the financial-record
 * preservation approach used on payment_orders.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('monetization_partners', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30); // vj | production_company | creator
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('vj_id')->nullable()->unique()
                ->constrained('vjs')->nullOnDelete();
            $table->string('display_name');
            $table->string('status', 20)->default('enrolled'); // enrolled | suspended
            $table->decimal('multiplier', 6, 3)->default(1.000);
            $table->timestamp('enrolled_at')->nullable();

            // Payout profile (manual mobile-money KYC)
            $table->string('payout_msisdn', 20)->nullable();
            $table->string('payout_name')->nullable();
            $table->string('payout_network', 10)->nullable(); // mtn | airtel
            $table->string('payout_status', 20)->default('none'); // none | pending_review | verified
            $table->timestamp('payout_verified_at')->nullable();
            $table->foreignId('payout_verified_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('payout_locked_until')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monetization_partners');
    }
};
