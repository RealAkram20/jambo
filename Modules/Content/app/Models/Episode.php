<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Content\app\Models\Concerns\CleansContentMorphsOnDelete;
use Modules\Content\app\Models\Concerns\HasStreamSource;
use Modules\Content\database\factories\EpisodeFactory;

/**
 * @property int $id
 * @property int $season_id
 * @property int $number
 * @property string $title
 * @property ?string $synopsis
 * @property ?int $runtime_minutes
 * @property ?string $still_url
 * @property ?string $dropbox_path
 * @property ?string $video_url
 * @property ?string $tier_required
 * @property ?\Illuminate\Support\Carbon $published_at
 *
 * IMPORTANT — content-cache invalidation contract:
 * When mutating `published_at`, ALWAYS use Eloquent
 * (`$episode->save()`, `$episode->update([...])`, `$episode->fill().save()`).
 * Do NOT use `Episode::query()->update([...])` or
 * `DB::table('episodes')->update([...])` — those bypass model events,
 * which prevents `Modules\Frontend\app\Observers\CatalogCacheObserver`
 * from firing. The parent show's `Show::scopePublished` requires at
 * least one published episode, so a bypassed publish here ALSO leaves
 * the parent series invisible on cached home rails until TTL expiry.
 * Full architecture: docs/architecture/content-cache-invalidation.md
 */
class Episode extends Model
{
    use HasFactory;
    use HasStreamSource;
    use CleansContentMorphsOnDelete;

    protected static function booted(): void
    {
        static::deleting(function (Episode $episode) {
            self::cleanContentMorphsFor(self::class, $episode->id);
        });
    }

    protected $table = 'episodes';

    protected $fillable = [
        'season_id',
        'number',
        'title',
        'synopsis',
        'runtime_minutes',
        'still_url',
        'dropbox_path',
        'video_url',
        'video_url_low',
        'tier_required',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'number' => 'integer',
        'runtime_minutes' => 'integer',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function show(): HasOneThrough
    {
        return $this->hasOneThrough(
            Show::class,
            Season::class,
            'id',         // Season PK referenced by Episode.season_id
            'id',         // Show PK referenced by Season.show_id
            'season_id',  // Episode column
            'show_id'     // Season column
        );
    }

    /**
     * Public watch URL for this episode:
     * `/episode/<show-slug>/s<season-number>/ep<episode-number>`.
     *
     * Eager-loads `season.show` on the fly if the relationship isn't
     * already in memory so callers in loops don't N+1 themselves. Pass a
     * pre-loaded Show via $showOverride when the episode was fetched from
     * a scope that doesn't hydrate season.show (e.g. watchlist cards).
     */
    public function frontendUrl(?Show $showOverride = null): string
    {
        $show = $showOverride
            ?? $this->season?->show
            ?? $this->loadMissing('season.show')->season?->show;

        $season = $this->season;

        // Defensive fallback for orphaned episodes (season_id points
        // at a deleted/missing season, or NULL). Without this guard the
        // whole page 500s when one bad row makes it into a list.
        if (!$show || !$season) {
            \Log::warning('[episode] frontendUrl called on orphan', [
                'episode_id' => $this->id,
                'season_id' => $this->season_id,
                'has_show' => (bool) $show,
                'has_season' => (bool) $season,
            ]);
            return $show ? route('frontend.series_detail', ['slug' => $show->slug]) : '#';
        }

        return route('frontend.episode', [
            'show' => $show->slug,
            'season' => $season->number,
            'episode' => $this->number,
        ]);
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'ratable');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopePublished(Builder $q): Builder
    {
        // Mirrors Movie::scopePublished — an episode is publicly
        // visible when its release date has passed. Direct-passthrough
        // playback means there's no encoder state to gate on; the
        // uploader is expected to provide a browser-playable file
        // (mp4/webm/m4v) before publishing.
        return $q->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected static function newFactory(): EpisodeFactory
    {
        return EpisodeFactory::new();
    }
}
