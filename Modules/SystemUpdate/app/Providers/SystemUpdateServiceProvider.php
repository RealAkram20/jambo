<?php

namespace Modules\SystemUpdate\app\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SystemUpdateServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'SystemUpdate';

    protected string $moduleNameLower = 'systemupdate';

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
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // ZipExtractor needs the absolute project-root path and the
        // deny-list, neither of which the container can auto-resolve
        // (one's a string, the other's an array from config). Bind it
        // explicitly so UpdateManager can be auto-injected.
        $this->app->singleton(\Modules\SystemUpdate\app\Services\ZipExtractor::class, function ($app) {
            return new \Modules\SystemUpdate\app\Services\ZipExtractor(
                projectRoot: base_path(),
                backupPrefix: config('systemupdate.backup_prefix', 'backup_'),
                denyPatterns: (array) config('systemupdate.deny_patterns', []),
            );
        });

        // DatabaseBackup needs an absolute backup-root path; same
        // reasoning as above. Storage path is the right home for
        // these — keeps gigabyte dumps out of the project tree.
        $this->app->singleton(\Modules\SystemUpdate\app\Services\DatabaseBackup::class, function ($app) {
            $relative = config('systemupdate.db_backup.path', 'app/updates/db-backups');
            return new \Modules\SystemUpdate\app\Services\DatabaseBackup(
                backupRoot: storage_path(ltrim($relative, '/')),
            );
        });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
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
