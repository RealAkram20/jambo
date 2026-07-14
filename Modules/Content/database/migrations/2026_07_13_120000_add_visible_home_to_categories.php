<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories become opt-in homepage rails. Admins flip `visible_home`
 * on the category admin page and the category renders as its own
 * shelf on the homepage (movies + series mixed), with View All
 * pointing at the category's own /categories/{slug} page. Off by
 * default so existing categories don't suddenly flood the homepage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $t) {
            $t->boolean('visible_home')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $t) {
            $t->dropColumn('visible_home');
        });
    }
};
