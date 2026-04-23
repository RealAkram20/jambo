# Jambo Gallery

This folder is the root of the admin file manager at `/admin/file-manager`.
Everything uploaded here is served publicly at `/storage/gallery/<path>`.

Keep it separate from:
- `storage/app/public/` directly — that's where spatie/medialibrary writes
  movie posters, avatars, etc. automatically per model.
- `storage/app/source/` — raw source videos awaiting transcode.
- `storage/app/hls/` — transcoded HLS playlists served to the player.

Use this folder for admin-curated assets: banners, hero images, page
backgrounds, marketing uploads, or any asset you want to hand-place into
content without going through the media library.
