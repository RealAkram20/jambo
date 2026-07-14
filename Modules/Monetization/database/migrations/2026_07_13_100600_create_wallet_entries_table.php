<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only wallet ledger. Rows are inserted, never updated or
 * deleted (no updated_at by design; model guards enforce it).
 *
 * Balance doctrine:
 *   authoritative  = SUM(amount) WHERE partner_id
 *   fast read      = balance_after of the newest row
 * Both are written inside a partner-row lockForUpdate in
 * WalletService::append(), and monetization:verify-ledger asserts
 * weekly that they agree.
 *
 * The unique (reference_type, reference_id, type) key is the
 * idempotency backstop: a statement can credit once, a withdrawal can
 * hold once — double-clicked admin buttons and replayed jobs become
 * constraint no-ops instead of double money.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')
                ->constrained('monetization_partners')->restrictOnDelete();
            // statement_credit | withdrawal_hold | hold_release | adjustment
            $table->string('type', 30);
            $table->decimal('amount', 12, 2); // signed: credits +, holds −
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type', 191)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('memo')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(
                ['reference_type', 'reference_id', 'type'],
                'wallet_entries_reference_unique'
            );
            $table->index(['partner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_entries');
    }
};
