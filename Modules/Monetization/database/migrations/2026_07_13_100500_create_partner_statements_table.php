<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-partner line of a monthly period. Name/type/multiplier are
 * snapshots (the live partner row keeps evolving; the statement must
 * not). `breakdown` archives the per-title composition —
 * [{type, id, title, minutes, split_percent}] — so a partner can see
 * exactly which titles earned what, forever, even if splits change.
 *
 * Amounts are whole shillings produced by the largest-remainder
 * normalization in MonthCloseService: SUM(amount) over a period always
 * equals partner_pool_amount exactly.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')
                ->constrained('monetization_periods')->cascadeOnDelete();
            $table->foreignId('partner_id')
                ->constrained('monetization_partners')->restrictOnDelete();
            $table->string('partner_name');
            $table->string('partner_type', 30);
            $table->decimal('multiplier_used', 6, 3);
            $table->decimal('qualified_minutes', 14, 4)->default(0);
            $table->decimal('weighted_minutes', 18, 6)->default(0);
            $table->decimal('share_ratio', 14, 12)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->unique(['period_id', 'partner_id']);
            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_statements');
    }
};
