<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a flexible JSON `meta` column for per-page structured data
 * that doesn't fit a single body field — e.g. the Contact page's
 * 4 contact cards, address block, social URLs, and map embed.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $t) {
            $t->json('meta')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $t) {
            $t->dropColumn('meta');
        });
    }
};
