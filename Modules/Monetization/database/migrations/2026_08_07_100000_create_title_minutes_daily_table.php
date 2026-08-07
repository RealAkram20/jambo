<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-day credited minutes per title — the data behind the partner
 * dashboard's Daily/Weekly estimated-earnings filter. qualified_views
 * only holds month-cumulative minutes, so day/week windows need their
 * own roll-up. Fed by WatchAccrualService with one atomic increment
 * per credited minute-batch; rows are (day, title) grains, so the
 * table stays tiny (distinct watched titles × days).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('title_minutes_daily', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('watchable_type', 191);
            $table->unsignedBigInteger('watchable_id');
            $table->unsignedBigInteger('show_id')->nullable();
            $table->unsignedInteger('minutes_credited')->default(0);
            $table->timestamps();

            $table->unique(['day', 'watchable_type', 'watchable_id'], 'title_minutes_daily_grain');
            $table->index(['day', 'show_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_minutes_daily');
    }
};
