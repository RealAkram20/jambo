<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Move existing profile photos onto the `profiles` disk.
 *
 * The `profile_image` collection now writes to a disk of its own, rooted
 * through `public/storage/profiles` so a viewer's avatar resolves in every
 * environment — see `config/filesystems.php` for why that path and not
 * `storage/app/public`. Rows written before this change still name the old
 * disk, and would keep resolving to a location that is not served.
 *
 * **Copies rather than moves.** The original file is left where it is, so a
 * rollback is a column update and nothing has to be recovered. The cost is a
 * duplicate per avatar, which is a few hundred kilobytes on a table that has
 * one row per account at most; the alternative is a migration that can lose
 * somebody's photo if it fails halfway.
 *
 * **A file that cannot be read is skipped, not fatal.** On this machine the
 * old location was never served and some rows may point at files that were
 * cleared by hand. A migration that aborts the deploy because one avatar is
 * missing would be a worse outcome than an account falling back to its
 * initial, which is a state every screen already draws correctly.
 */
return new class extends Migration
{
    private const TARGET = 'profiles';

    public function up(): void
    {
        $rows = DB::table('media')
            ->where('collection_name', 'profile_image')
            ->where('disk', '!=', self::TARGET)
            ->get(['id', 'disk', 'file_name']);

        foreach ($rows as $row) {
            // media-library's default path generator: `{media id}/{file name}`.
            $path = $row->id . '/' . $row->file_name;

            try {
                if (! Storage::disk($row->disk)->exists($path)) {
                    continue;
                }

                $contents = Storage::disk($row->disk)->get($path);

                if ($contents === null) {
                    continue;
                }

                Storage::disk(self::TARGET)->put($path, $contents);
            } catch (\Throwable) {
                // The row keeps its old disk and the account falls back to its
                // initial. Better than failing the deploy for one photo.
                continue;
            }

            DB::table('media')->where('id', $row->id)->update(['disk' => self::TARGET]);
        }
    }

    /**
     * Point the rows back at the public disk.
     *
     * The originals were never deleted, so this restores the previous
     * behaviour exactly. The copies under `profiles` are left in place; they
     * are harmless and they are what a re-run would need anyway.
     */
    public function down(): void
    {
        DB::table('media')
            ->where('collection_name', 'profile_image')
            ->where('disk', self::TARGET)
            ->update(['disk' => 'public']);
    }
};
