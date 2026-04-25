<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Promote a user to super-admin.
 *
 * Super-admin is the platform-owner tier. Once assigned, the admin UI
 * blocks any other admin (including other super-admins) from deleting
 * the user or changing their roles — protection only available from
 * the console, intentionally.
 *
 * Run once per environment for the account that owns that box:
 *   php artisan users:make-super-admin you@example.com
 *
 * Idempotent: running it on an existing super-admin is a no-op.
 *
 * Demotion isn't exposed here on purpose — it's a deliberate two-key
 * action. To demote, drop into tinker:
 *   $u = App\Models\User::where('email', 'x@y')->first();
 *   $u->removeRole('super-admin');
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
