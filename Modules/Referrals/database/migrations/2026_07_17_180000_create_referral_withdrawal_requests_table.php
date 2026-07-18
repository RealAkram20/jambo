<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referral-wallet payouts for NON-partner referrers, mirroring the
        // Monetization withdrawal_requests state machine:
        //
        //   requested → approved → paid   (hold entry stays = permanent debit)
        //            ↘ rejected           (hold released back to the wallet)
        //
        // Partners never appear here — their referral rewards are credited
        // straight into their Creator Studio wallet and ride the existing
        // partner withdrawal pipeline.
        Schema::create('referral_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('status', 20)->default('requested');

            // Payout details are entered per request (no stored profile
            // for regular users) and snapshotted here for the clerk.
            $table->string('payee_name', 100);
            $table->string('payee_msisdn', 30);

            $table->foreignId('hold_entry_id')->nullable()
                ->constrained('referral_earnings')->nullOnDelete();

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

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_withdrawal_requests');
    }
};
