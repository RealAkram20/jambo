<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Month-scoped watch accumulator — the working memory of the accrual
 * pipeline. watch_history can't serve this purpose: it keeps only the
 * LATEST position per (user, title) forever, so month boundaries,
 * rewatch deltas and real seconds-watched are unrecoverable from it.
 *
 * One row per (user, title, calendar month). seconds_watched grows by
 * wall-clock-bounded deltas on each heartbeat (see WatchAccrualService)
 * and is capped at the title's server-side runtime. `qualified` flips
 * true exactly once per month when the threshold is crossed and the
 * qualified_views fact row is written — after that the accrual path
 * for this row is a cheap no-op update.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('watch_progress_monthly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('watchable_type', 191);
            $table->unsignedBigInteger('watchable_id');
            $table->date('period_month'); // first day of the month
            $table->unsignedInteger('seconds_watched')->default(0);
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->timestamp('last_beat_at')->nullable();
            $table->boolean('qualified')->default(false);
            $table->string('session_id', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'watchable_type', 'watchable_id', 'period_month'],
                'wpm_user_title_month_unique'
            );
            $table->index('period_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_progress_monthly');
    }
};
