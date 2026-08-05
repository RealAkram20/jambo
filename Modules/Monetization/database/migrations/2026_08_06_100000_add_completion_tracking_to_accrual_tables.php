<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hybrid accrual model: minutes credit continuously as paid viewers
 * watch; crossing the qualify threshold marks the view COMPLETED and
 * tops the credit up to the full runtime.
 *
 * - qualified_views.completed_at    — threshold crossed (the "watched
 *   to the credits" marker; before this the row holds partial minutes)
 * - qualified_views.last_credited_at — when minutes last grew; used by
 *   the conservative daily-cap check
 * - watch_progress_monthly.completed_at — same marker on the progress
 *   row, so completion stats survive even where no payable row exists
 *   (e.g. free viewer)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qualified_views', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('qualified_at');
            $table->timestamp('last_credited_at')->nullable()->after('completed_at');
        });
        Schema::table('watch_progress_monthly', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('qualified');
        });
    }

    public function down(): void
    {
        Schema::table('qualified_views', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'last_credited_at']);
        });
        Schema::table('watch_progress_monthly', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
