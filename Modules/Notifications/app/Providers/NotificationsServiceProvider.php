<?php

namespace Modules\Notifications\app\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Notifications';

    protected string $moduleNameLower = 'notifications';

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
        $this->ensureOpensslConfForWebPush();

        // Single subscriber routes every typed event to a notification
        // (Laravel Registered/Verified/PasswordReset + our custom
        // MovieAdded/OrderPlaced/… events). See NotificationEventSubscriber.
        \Illuminate\Support\Facades\Event::subscribe(
            \Modules\Notifications\app\Listeners\NotificationEventSubscriber::class
        );

        // Legacy string-keyed events that pre-date this subscriber.
        // Kept here so the payload shape (an $order + $source tuple)
        // matches what PaymentController dispatches today.
        \Illuminate\Support\Facades\Event::listen(
            'payment.completed',
            [\Modules\Notifications\app\Listeners\SendPaymentReceivedNotification::class, 'handle']
        );

        // ExpireSubscriptionsCommand fires event('subscription.expired', [$user, $plan]).
        \Illuminate\Support\Facades\Event::listen(
            'subscription.expired',
            [\Modules\Notifications\app\Listeners\NotificationEventSubscriber::class, 'handleSubscriptionExpiredStringEvent']
        );
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Must load before any web-push / jwt-library code runs. See the
        // file header for the full context on why PHP's function-
        // namespace fallback is the only clean way to make EC keygen
        // work on Windows/XAMPP.
        require_once __DIR__ . '/../Support/openssl-webpush-shim.php';

        $this->app->register(RouteServiceProvider::class);

        // Bind the dispatcher contract to the default implementation so
        // listeners and controllers can type-hint NotificationDispatcher
        // and get the right thing.
        $this->app->singleton(
            \Modules\Notifications\app\Contracts\NotificationDispatcher::class,
            \Modules\Notifications\app\Services\DefaultNotificationDispatcher::class,
        );
    }

    /**
     * Point OPENSSL_CONF at XAMPP's richer openssl.cnf so the
     * web-push library can create the local EC key it needs to
     * encrypt payloads. The stock PHP/Apache on Windows ships a
     * minimal openssl.cnf that omits EC curve config; without this,
     * every web push dispatch throws "Unable to create the local key."
     *
     * Silently skipped on non-Windows or when the file isn't found,
     * so production Linux deployments with a proper system openssl
     * are unaffected.
     */
    private function ensureOpensslConfForWebPush(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return;
        }

        // Respect an explicit env override if the operator set one.
        if (getenv('OPENSSL_CONF')) {
            return;
        }

        foreach ([
            'C:/xampp/php/extras/openssl/openssl.cnf',
            'C:/xampp/apache/conf/openssl.cnf',
        ] as $candidate) {
            if (is_file($candidate)) {
                putenv('OPENSSL_CONF=' . $candidate);
                $_ENV['OPENSSL_CONF']    = $candidate;
                $_SERVER['OPENSSL_CONF'] = $candidate;
                return;
            }
        }
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Notifications\app\Console\Commands\NotifyExpiringSubscriptions::class,
            \Modules\Notifications\app\Console\Commands\PruneOldNotifications::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

            // Morning reminder so users get the nudge early enough to act.
            $schedule->command('notifications:subscriptions-expiring')
                ->dailyAt('09:00')
                ->withoutOverlapping();

            // Weekly cleanup of read notifications older than 90 days.
            $schedule->command('notifications:prune')
                ->weekly()
                ->sundays()
                ->at('03:00')
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
