<?php

namespace Modules\Installer\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Developer helper: delete the install flag and wizard state file so
 * the installer wizard runs again on the next HTTP request.
 *
 * Use during local development when you want to re-test the wizard.
 * DO NOT run in production — the middleware will immediately start
 * redirecting every request to /install.
 */
class ResetInstallCommand extends Command
{
    protected $signature = 'jambo:reset-install
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Delete the installed flag so the web installer wizard runs again.';

    public function handle(): int
    {
        $flag = storage_path('installed');
        $state = storage_path('app/install-data.json');

        if (app()->environment('production') && !$this->option('force')) {
            $this->error('Refusing to run in production without --force. The installer gate will lock every URL until a user completes the wizard.');
            return self::FAILURE;
        }

        if (!File::exists($flag)) {
            $this->warn("No install flag found at {$flag}. The wizard will run on next request anyway.");
        } else {
            File::delete($flag);
            $this->info("Deleted {$flag}");
        }

        if (File::exists($state)) {
            File::delete($state);
            $this->info("Deleted {$state}");
        }

        $this->newLine();
        $this->info('Install flag cleared. Visit the site in a browser to run the wizard.');
        return self::SUCCESS;
    }
}
