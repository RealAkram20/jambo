<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per accounting month. Drafts are recomputable at will;
 * a closed period is immutable (model saving-guards + no routes) and
 * its statement credits are already in partner wallets.
 *
 * settings_snapshot freezes every input the pool math used (pool %,
 * fee %, infra cost, threshold, each partner's multiplier) so a
 * statement can always be re-derived and audited even after the live
 * settings change.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('monetization_periods', function (Blueprint $table) {
            $table->id();
            $table->date('period_month')->unique();
            $table->string('status', 20)->default('draft'); // draft | closed
            $table->decimal('gross_revenue', 14, 2)->default(0);
            $table->decimal('gateway_fee_amount', 14, 2)->default(0);
            $table->decimal('infra_cost_amount', 14, 2)->default(0);
            $table->decimal('pool_amount', 14, 2)->default(0);
            $table->decimal('partner_pool_amount', 14, 2)->default(0);
            $table->decimal('total_weighted_minutes', 18, 6)->default(0);
            $table->decimal('platform_weighted_minutes', 18, 6)->default(0);
            $table->json('settings_snapshot')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monetization_periods');
    }
};
