<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flip the column-level default for push_notifications_enabled from 0
 * to 1 so that any future direct INSERT (seeders, raw SQL, etc.) gets
 * an opted-in user. Existing rows are deliberately left alone — users
 * who previously toggled push off should stay off.
 *
 * Eloquent's User::$attributes already covers the create() path; this
 * migration is the belt-and-suspenders for the DB layer.
 *
 * MySQL only. `ALTER TABLE ... ALTER COLUMN ... SET DEFAULT` is not valid
 * SQLite, and the raw statement was aborting every migration run on the
 * sqlite test connection — which took the whole test suite down with it.
 * SQLite has no way to alter a column default in place anyway, and the
 * test DB is built fresh from the create migration each run, so skipping
 * is correct rather than merely tolerable.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->setDefault(1);
    }

    public function down(): void
    {
        $this->setDefault(0);
    }

    private function setDefault(int $value): void
    {
        if (! Schema::hasColumn('users', 'push_notifications_enabled')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `users` ALTER COLUMN `push_notifications_enabled` SET DEFAULT {$value}");
    }
};
