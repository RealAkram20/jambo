<?php

namespace Modules\Content\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Show;

/**
 * Option C placeholder: populates trailer_url and video_url on every
 * movie / show / episode with a publicly hosted demo MP4 so the player
 * plays something end-to-end while real content is being sourced.
 *
 * URL sources (all verified 200 video/mp4):
 *   - archive.org: Blender open-source films (public domain)
 *   - w3schools.com/html: short test clips
 *
 * Safe to re-run: everything is overwritten deterministically by id.
 */
class DemoStreamUrlsSeeder extends Seeder
{
    /**
     * Short (~10-30s) clips used as trailers. Spread across multiple
     * hosts so one flaky CDN doesn't break every trailer at once.
     */
    private const TRAILERS = [
        'https://www.w3schools.com/html/mov_bbb.mp4',
        'https://www.w3schools.com/tags/movie.mp4',
        'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
        'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/friday.mp4',
    ];

    /**
     * Full-length public-domain features. archive.org is the most
     * reliable host for these sizes (Blender Foundation mirrors); we
     * mix in a lighter-weight MDN video as a third option so not every
     * feature hits the same bucket.
     */
    private const FEATURES = [
        'https://archive.org/download/BigBuckBunny_124/Content/big_buck_bunny_720p_surround.mp4',
        'https://archive.org/download/ElephantsDream/ed_hd.mp4',
        'https://archive.org/download/ElephantsDream/ed_1024_512kb.mp4',
        'https://archive.org/download/Sintel/sintel-2048-stereo.mp4',
        'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
    ];

    /**
     * Low-bitrate companions for the Data Saver quality switch. MDN
     * demos are reliably served from MDN's CloudFront CDN — good
     * fallback when archive.org is throttled.
     */
    private const FEATURES_LOW = [
        'https://www.w3schools.com/html/mov_bbb.mp4',
        'https://www.w3schools.com/tags/movie.mp4',
        'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
        'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/friday.mp4',
    ];

    public function run(): void
    {
        $this->seedMovies();
        $this->seedShows();
        $this->seedEpisodes();
    }

    private function seedMovies(): void
    {
        $movies = Movie::orderBy('id')->get(['id']);
        foreach ($movies as $m) {
            Movie::whereKey($m->id)->update([
                'trailer_url' => self::TRAILERS[$m->id % count(self::TRAILERS)],
                'video_url' => self::FEATURES[$m->id % count(self::FEATURES)],
                'video_url_low' => self::FEATURES_LOW[$m->id % count(self::FEATURES_LOW)],
                // Force the HLS branch off — these aren't transcoded.
                'transcode_status' => null,
                'hls_master_path' => null,
            ]);
        }
        $this->command?->info("Movies updated: {$movies->count()}");
    }

    private function seedShows(): void
    {
        $shows = Show::orderBy('id')->get(['id']);
        foreach ($shows as $s) {
            Show::whereKey($s->id)->update([
                'trailer_url' => self::TRAILERS[$s->id % count(self::TRAILERS)],
            ]);
        }
        $this->command?->info("Shows updated: {$shows->count()}");
    }

    private function seedEpisodes(): void
    {
        $episodes = Episode::orderBy('id')->get(['id']);
        foreach ($episodes as $e) {
            Episode::whereKey($e->id)->update([
                'video_url' => self::FEATURES[$e->id % count(self::FEATURES)],
                'video_url_low' => self::FEATURES_LOW[$e->id % count(self::FEATURES_LOW)],
                'transcode_status' => null,
                'hls_master_path' => null,
            ]);
        }
        $this->command?->info("Episodes updated: {$episodes->count()}");
    }
}
