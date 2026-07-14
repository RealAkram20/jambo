<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-partner content-management rights, granted by the super admin.
 *
 * A partner can always WATCH their titles (they're a normal viewer);
 * editing metadata and deleting titles are opt-in capabilities. Both
 * default OFF — enrollment alone never grants content control.
 * "Their" content = titles carrying their monetization split, the same
 * ownership definition the money uses.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('monetization_partners', function (Blueprint $table) {
            $table->boolean('can_edit_content')->default(false)->after('multiplier');
            $table->boolean('can_delete_content')->default(false)->after('can_edit_content');
        });
    }

    public function down(): void
    {
        Schema::table('monetization_partners', function (Blueprint $table) {
            $table->dropColumn(['can_edit_content', 'can_delete_content']);
        });
    }
};
