<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only ledger for referrer earnings, following the
        // wallet_entries doctrine: rows are never updated or deleted,
        // balance_after is recomputed under lock, and the reference
        // uniqueness makes credits idempotent (callback + IPN both fire
        // payment.completed for the same order).
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20); // reward (withdrawal/adjustment reserved)
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->char('currency', 3);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
            $table->string('memo')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['reference_type', 'reference_id', 'type'], 'referral_earnings_reference_unique');
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_earnings');
    }
};
