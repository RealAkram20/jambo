<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * Grant the `finance` role to a user.
 *
 *   php artisan users:grant-finance you@example.com
 *
 * Idempotent: running on a user who already has it is a no-op.
 *
 * Auto-assigns `admin` if not already present — every protected
 * payments/pricing route is gated by `role:admin` first, so a
 * finance-only user with no admin role would still be 403'd. The
 * console grant therefore implies "this person can sign into the
 * admin area at all". Revoke with:
 *   php artisan tinker --execute="App\Models\User::where('email','x@y')->first()->removeRole('finance');"
 */
class GrantFinanceRoleCommand extends Command
{
    protected $signature = 'users:grant-finance
                            {email : Email of the user to promote}';

    protected $description = 'Grant the finance role to a user (lookup by email).';

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

        if (!Role::where('name', 'finance')->exists()) {
            $this->error("The 'finance' role doesn't exist. Run `php artisan migrate` first.");
            return self::FAILURE;
        }

        if ($user->hasRole('finance')) {
            $this->info("{$user->email} already has the finance role.");
            return self::SUCCESS;
        }

        $user->assignRole('finance');
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        $this->info("✓ {$user->email} now has the finance role.");
        $this->line('  Roles: ' . $user->roles->pluck('name')->join(', '));

        return self::SUCCESS;
    }
}
