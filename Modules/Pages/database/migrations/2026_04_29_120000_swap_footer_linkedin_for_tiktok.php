<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replace the LinkedIn social entry in the seeded Footer page with
 * TikTok. The original 2026_04_25_000500 footer seeder shipped with
 * Facebook / X / Instagram / LinkedIn — TikTok is the more relevant
 * channel for Jambo's audience, and the operator wanted the swap.
 *
 * This migration is **conservative**: it only touches the LinkedIn
 * row if one is still present in the existing meta. If the admin
 * already removed or replaced LinkedIn (via /admin/pages → Footer →
 * Socials), this migration is a no-op for them — their custom
 * configuration is preserved.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('pages')) {
            return;
        }

        $row = DB::table('pages')->where('slug', 'footer')->first();
        if (!$row || empty($row->meta)) {
            return;
        }

        $meta = is_string($row->meta) ? json_decode($row->meta, true) : (array) $row->meta;
        if (!is_array($meta) || empty($meta['socials']) || !is_array($meta['socials'])) {
            return;
        }

        $changed = false;
        foreach ($meta['socials'] as &$social) {
            if (!is_array($social)) continue;
            $isLinkedIn = (str_contains(strtolower($social['icon'] ?? ''), 'linkedin'))
                || (str_contains(strtolower($social['url']  ?? ''), 'linkedin.com'));
            if ($isLinkedIn) {
                $social['icon'] = 'ph ph-tiktok-logo';
                $social['url']  = 'https://www.tiktok.com/';
                $changed = true;
            }
        }
        unset($social);

        if (!$changed) {
            return;
        }

        DB::table('pages')->where('id', $row->id)->update([
            'meta'       => json_encode($meta),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Reverse the swap. Same conservative shape — only flips
        // entries that look like our TikTok defaults so manual edits
        // by an admin survive a rollback.
        if (!DB::getSchemaBuilder()->hasTable('pages')) {
            return;
        }

        $row = DB::table('pages')->where('slug', 'footer')->first();
        if (!$row || empty($row->meta)) {
            return;
        }

        $meta = is_string($row->meta) ? json_decode($row->meta, true) : (array) $row->meta;
        if (!is_array($meta) || empty($meta['socials']) || !is_array($meta['socials'])) {
            return;
        }

        $changed = false;
        foreach ($meta['socials'] as &$social) {
            if (!is_array($social)) continue;
            $isOurTiktok = ($social['icon'] ?? '') === 'ph ph-tiktok-logo'
                && ($social['url'] ?? '') === 'https://www.tiktok.com/';
            if ($isOurTiktok) {
                $social['icon'] = 'ph-fill ph-linkedin-logo';
                $social['url']  = 'https://www.linkedin.com/';
                $changed = true;
            }
        }
        unset($social);

        if (!$changed) {
            return;
        }

        DB::table('pages')->where('id', $row->id)->update([
            'meta'       => json_encode($meta),
            'updated_at' => now(),
        ]);
    }
};
