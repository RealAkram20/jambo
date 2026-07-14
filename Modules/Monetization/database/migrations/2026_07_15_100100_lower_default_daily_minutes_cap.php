<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drops the daily payable-minutes cap from 1080 (18h) to 480 (8h).
 *
 * 18 hours a day is not a ceiling any human viewer reaches, so it was
 * never really constraining a farm — it only constrained the absurd.
 * 8 hours still leaves ~2x headroom over a genuinely heavy viewer
 * while more than halving what a scripted account can mint per day.
 *
 * Only rewrites the stored value when it is still the OLD DEFAULT.
 * An admin who deliberately tuned this keeps their number.
 */
return new class extends Migration {
    private const OLD_DEFAULT = '1080';
    private const NEW_DEFAULT = '480';

    public function up(): void
    {
        $this->rewrite(self::OLD_DEFAULT, self::NEW_DEFAULT);
    }

    public function down(): void
    {
        $this->rewrite(self::NEW_DEFAULT, self::OLD_DEFAULT);
    }

    private function rewrite(string $from, string $to): void
    {
        $changed = DB::table('settings')
            ->where('name', 'monetization.daily_minutes_cap')
            ->where('val', $from)
            ->update(['val' => $to, 'updated_at' => now()]);

        // Setting::getAllSettings() is a rememberForever cache that only
        // busts on Eloquent events, so a raw update like the one above
        // lands in the DB while the app keeps serving the old value —
        // silently, and forever. Bust it by hand.
        if ($changed) {
            Setting::flushCache();
        }
    }
};
