<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which admin created and last edited each piece of content, for
 * the Performance dashboard (per-admin upload counts + earnings) and the
 * activity trail.
 *
 * Both columns are nullable with ON DELETE SET NULL: content must survive
 * the deletion of the admin who made it (losing the credit link is fine;
 * losing the movie is not). The append-only content_activity_log keeps a
 * name snapshot so history stays readable even after the user row is gone.
 */
return new class extends Migration {
    private array $tables = ['movies', 'shows', 'seasons', 'episodes'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('created_by')->nullable()->after('updated_at')
                    ->constrained('users')->nullOnDelete();
                $t->foreignId('updated_by')->nullable()->after('created_by')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('created_by');
                $t->dropConstrainedForeignId('updated_by');
            });
        }
    }
};
