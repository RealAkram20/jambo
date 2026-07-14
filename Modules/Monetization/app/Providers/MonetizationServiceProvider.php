<?php

namespace Modules\Monetization\app\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MonetizationServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Monetization';

    protected string $moduleNameLower = 'monetization';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        // Accrue partner watch-time off the Streaming heartbeat. The
        // listener is fully try/catch-guarded — an accrual bug must
        // never break playback.
        Event::listen(
            \Modules\Streaming\app\Events\PlaybackBeat::class,
            \Modules\Monetization\app\Listeners\RecordWatchAccrual::class,
        );
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app['router']->aliasMiddleware(
            'monetization.admin',
            \Modules\Monetization\app\Http\Middleware\EnsureMonetizationAdminAccess::class,
        );
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // Only register command classes that actually exist yet. This
        // module is still being scaffolded; without this guard, a missing
        // command class throws during console bootstrap and breaks EVERY
        // artisan call app-wide (migrate, seed, tinker, schedule).
        $commands = array_filter([
            \Modules\Monetization\app\Console\Commands\ComputeMonthlyDraftCommand::class,
            \Modules\Monetization\app\Console\Commands\VerifyWalletLedgerCommand::class,
        ], 'class_exists');

        if ($commands !== []) {
            $this->commands($commands);
        }
    }

    /**
     * Register command Schedules.
     *
     * The draft statement is computed on the 1st for the month that just
     * ended — nothing is credited to wallets until a super-admin reviews
     * the draft and clicks "Close & Credit". The ledger verify sweep is
     * a cheap integrity assertion (SUM(amount) vs latest balance_after).
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

            $schedule->command('monetization:compute-draft')
                ->monthlyOn(1, '02:30')
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command('monetization:verify-ledger')
                ->weeklyOn(1, '03:30')
                ->withoutOverlapping();
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);

        $componentNamespace = str_replace('/', '\\', config('modules.namespace').'\\'.$this->moduleName.'\\'.config('modules.paths.generator.component-class.path'));
        Blade::componentNamespace($componentNamespace, $this->moduleNameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
