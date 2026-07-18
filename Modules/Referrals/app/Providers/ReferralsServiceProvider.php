<?php

namespace Modules\Referrals\app\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ReferralsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Referrals';

    protected string $moduleNameLower = 'referrals';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        // Default a new user's referral code to their username and, when a
        // referral cookie rode along on the signup request, record the
        // pending attribution.
        Event::listen(
            \Illuminate\Auth\Events\Registered::class,
            \Modules\Referrals\app\Listeners\AttributeReferralOnRegistration::class,
        );

        // Same string event + payload shape ([$order, $source]) that the
        // Subscriptions activation listener consumes; dispatched from
        // PaymentController::dispatchActivation().
        Event::listen(
            'payment.completed',
            [\Modules\Referrals\app\Listeners\CreditReferralOnPayment::class, 'handle'],
        );
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
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
