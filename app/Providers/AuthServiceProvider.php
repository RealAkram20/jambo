<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super-admins are the platform owners — they implicitly hold every
        // permission and can never be locked out by the Access Control screen,
        // which only edits the *admin* role's grants. This matters especially
        // because super-admins inherit their day-to-day permissions through
        // the admin role they also carry: without this bypass, a super-admin
        // revoking (say) delete_users from the admin role would strip it from
        // themselves too. Returning null (not false) for everyone else lets
        // normal resolution proceed. Canonical Spatie super-admin pattern —
        // covers @can, $user->can(), policies, and the `permission:` route
        // middleware (which calls canAny()).
        Gate::before(function ($user, string $ability) {
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return true;
            }
            return null;
        });
    }
}
