<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Promote a user to super-admin.
 *
 * Super-admin is the platform-owner tier. Once assigned, the admin UI
 * blocks regular admins from deleting the user or changing their roles.
 *
 * Run once per environment to bootstrap the FIRST owner account:
 *   php artisan users:make-super-admin you@example.com
 *
 * After that, an existing super-admin can grant or revoke the tier
 * from the Users page (crown button — UserController::grantSuperAdmin /
 * revokeSuperAdmin, gated role:super-admin + password.confirm). This
 * command stays for bootstrap and lockout recovery.
 *
 * Idempotent: running it on an existing super-admin is a no-op.
 */
class MakeSuperAdminCommand extends Command
{
    protected $signature = 'users:make-super-admin
                            {email : Email of the user to promote}';

    protected $description = 'Grant the super-admin role to a user (lookup by email).';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        if ($email === '') {
            $this->error('Email is required.');
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("No user found with email: $email");
            return self::FAILURE;
        }

        if (!Role::where('name', 'super-admin')->exists()) {
            $this->error("The 'super-admin' role doesn't exist. Run `php artisan migrate` first.");
            return self::FAILURE;
        }

        if ($user->hasRole('super-admin')) {
            $this->info("{$user->email} is already a super-admin.");
            return self::SUCCESS;
        }

        // Super-admins always also hold the `admin` role so any
        // role:admin-gated middleware (e.g. /app, /admin/*) still
        // recognises them. Adding without removing existing roles —
        // the user keeps anything else they had.
        $user->assignRole('super-admin');
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        $this->info("✓ {$user->email} is now a super-admin.");
        $this->line('  Roles: ' . $user->roles->pluck('name')->join(', '));

        return self::SUCCESS;
    }
}
