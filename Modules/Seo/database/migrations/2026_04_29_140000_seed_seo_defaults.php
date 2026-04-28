<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds empty default rows for the seo.* settings so the admin form
 * has actual records to update on first save (prevents the admin
 * from saving and seeing nothing change because the row didn't exist
 * yet — though setting() does upsert, having the keys present makes
 * the values visible in any DB inspection tool from day one).
 *
 * Idempotent: every row insert checks `where name = ...` and skips
 * if present, so re-running this migration is safe.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $defaults = [
            // Tracking master switch — off until the operator explicitly
            // enables it. A live site shouldn't accidentally start
            // pinging GA4 with placeholder IDs.
            ['name' => 'seo.tracking_enabled',       'val' => '0', 'type' => 'bool'],
            // Recommend on. Admin pageviews skew metrics.
            ['name' => 'seo.exclude_admins',         'val' => '1', 'type' => 'bool'],
            // Sitemap is harmless even with no IDs configured —
            // helps Googlebot find content. On by default.
            ['name' => 'seo.sitemap_enabled',        'val' => '1', 'type' => 'bool'],
            ['name' => 'seo.ga4_id',                 'val' => '',  'type' => 'string'],
            ['name' => 'seo.gtm_id',                 'val' => '',  'type' => 'string'],
            ['name' => 'seo.gsc_verification',       'val' => '',  'type' => 'string'],
            ['name' => 'seo.og_default_image',       'val' => '',  'type' => 'string'],
            ['name' => 'seo.og_default_description', 'val' => '',  'type' => 'string'],
            ['name' => 'seo.twitter_handle',         'val' => '',  'type' => 'string'],
        ];

        $now = now();

        foreach ($defaults as $row) {
            $exists = DB::table('settings')->where('name', $row['name'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('settings')->insert([
                'name'       => $row['name'],
                'val'        => $row['val'],
                'type'       => $row['type'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('name', 'like', 'seo.%')
            ->delete();
    }
};
