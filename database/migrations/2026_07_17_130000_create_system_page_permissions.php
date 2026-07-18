<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Delegatable access permissions for the System / owner pages.
 *
 * Settings, SEO & Analytics, System Info, and Pages are hidden from
 * regular admins by default: these permissions are created but NOT granted
 * to the admin role, so the sidebar entries stay hidden (@can) and the
 * routes 403 (permission: middleware). A super-admin can grant any of them
 * to the admin role (or a custom role) from the Access Control screen.
 *
 * Super-admins always bypass via the Gate::before in AuthServiceProvider,
 * so they keep full access regardless of what's granted here — and the
 * permissions must EXIST (spatie's Gate::before throws on an unknown
 * ability), which is exactly what this migration guarantees.
 */
return new class extends Migration
{
    private array $permissions = [
        'settings_access',
        'seo_access',
        'system_info_access',
        'pages_access',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['is_fixed' => true],
            );
        }

        // NOT assigned to the admin role on purpose — default hidden.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
