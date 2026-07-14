<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The earning facts: append-only, one row per (user, title, month),
 * written the moment a paid subscriber crosses the completion
 * threshold. Month close aggregates THIS table — never watch_history.
 *
 * Rows are never updated or deleted by application code. The unique
 * key IS the business rule ("a user pays out a title at most once per
 * calendar month") and doubles as replay/concurrency protection via
 * insertOrIgnore.
 *
 * user_id is nullable + SET NULL: these rows are financial evidence
 * backing partner statements, so they must survive account deletion
 * (same doctrine as payment_orders). session_id / ip / subscription id
 * are audit evidence for fraud review.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('qualified_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('watchable_type', 191);
            $table->unsignedBigInteger('watchable_id');
            // Denormalized for episodes so month close can resolve the
            // parent Show's split set without walking season joins.
            $table->unsignedBigInteger('show_id')->nullable();
            $table->date('period_month');
            $table->unsignedSmallInteger('minutes_credited');
            $table->unsignedSmallInteger('runtime_minutes_snapshot');
            $table->unsignedBigInteger('user_subscription_id')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('qualified_at');
            $table->timestamp('created_at')->nullable();

            $table->unique(
                ['user_id', 'watchable_type', 'watchable_id', 'period_month'],
                'qv_user_title_month_unique'
            );
            $table->index('period_month');
            $table->index(['watchable_type', 'watchable_id', 'period_month'], 'qv_title_month_index');
            $table->index(['show_id', 'period_month']);
            $table->index(['user_id', 'qualified_at']); // daily-cap lookups
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualified_views');
    }
};
