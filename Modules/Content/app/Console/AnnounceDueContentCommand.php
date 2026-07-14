<?php

namespace Modules\Content\app\Console;

use Illuminate\Console\Command;
use Modules\Content\app\Services\ContentAnnouncer;

/**
 * Announces content the moment it actually goes live.
 *
 * An admin can publish a title with a release date in the future. The old
 * code broadcast "New movie added" the instant they hit Save, which meant
 * users tapped a notification for something the public routes wouldn't serve
 * yet and got a 404. Nothing is announced at save time now unless it is
 * already watchable; anything scheduled ahead waits here.
 *
 * Runs every minute from App\Console\Kernel — the same scheduler tick that
 * already drives the queue worker, so it needs no new cron entry.
 */
class AnnounceDueContentCommand extends Command
{
    protected $signature = 'content:announce-due';

    protected $description = 'Broadcast new-content notifications for titles whose release time has arrived';

    public function handle(ContentAnnouncer $announcer): int
    {
        $counts = $announcer->sweepDue();

        if (array_sum($counts) === 0) {
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Announced %d movie(s), %d show(s), %d season(s), %d episode(s).',
            $counts['movies'],
            $counts['shows'],
            $counts['seasons'],
            $counts['episodes'],
        ));

        return self::SUCCESS;
    }
}
