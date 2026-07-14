<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Content\app\Models\Concerns\CleansContentMorphsOnDelete;
use Modules\Content\app\Models\Concerns\HasStreamSource;
use Modules\Content\app\Models\Concerns\TracksContentActivity;
use Modules\Content\database\factories\MovieFactory;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?string $synopsis
 * @property ?int $year
 * @property ?int $runtime_minutes
 * @property ?string $rating
 * @property ?string $poster_url
 * @property ?string $backdrop_url
 * @property ?string $trailer_url
 * @property ?string $dropbox_path
 * @property ?string $video_url
 * @property ?string $tier_required
 * @property string $status
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property int $views_count
 *
 * IMPORTANT — content-cache invalidation contract:
 * When mutating `status` or `published_at`, ALWAYS use Eloquent
 * (`$movie->save()`, `$movie->update([...])`, `$movie->fill().save()`).
 * Do NOT use `Movie::query()->update([...])` or `DB::table('movies')->update([...])`
 * — those bypass model events, which prevents
 * `Modules\Frontend\app\Observers\CatalogCacheObserver` from firing,
 * which leaves the personalised home rails (Top Picks, Smart Shuffle,
 * Fresh Picks, Upcoming) showing stale content until TTL expiry.
 * Full architecture: docs/architecture/content-cache-invalidation.md
 */
class Movie extends Model
{
    use HasFactory;
    use HasStreamSource;
    use CleansContentMorphsOnDelete;
    use TracksContentActivity;

    protected static function booted(): void
    {
        static::deleting(function (Movie $movie) {
            self::cleanContentMorphsFor(self::class, $movie->id);
        });
    }

    public function activityType(): string
    {
        return 'movie';
    }

    protected $table = 'movies';

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'year',
        'runtime_minutes',
        'rating',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'dropbox_path',
        'video_url',
        'video_url_low',
        'tier_required',
        'status',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'announced_at' => 'datetime',
        'year' => 'integer',
        'runtime_minutes' => 'integer',
        'views_count' => 'integer',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_UPCOMING = 'upcoming';

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_movie');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_movie');
    }

    public function vjs(): BelongsToMany
    {
        return $this->belongsToMany(Vj::class, 'movie_vj');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'movie_tag');
    }

    public function cast(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'movie_person')
            ->withPivot(['role', 'character_name', 'display_order']);
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
        // A movie is "publicly published" when it's flagged Published
        // and its release date has passed. Playback is direct passthrough
        // to whatever URL the admin pasted, so there's no encoder state
        // to gate on anymore — uploaders are expected to provide a
        // browser-playable file (mp4/webm/m4v) before hitting Publish.
        // Metadata-only entries (no video at all) still flow through;
        // the player view shows a "not streamable yet" message instead
        // of a broken <video>.
        return $q->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * The PHP mirror of scopePublished — is this title watchable right now?
     *
     * `status = published` alone is NOT enough: an admin may publish a title
     * with a release date in the future. Announcing on status alone is what
     * sent users to a 404, because every public route resolves through
     * scopePublished / scopeDetailVisible, which both require the date to
     * have passed. Notification paths must gate on this, never on status.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lessThanOrEqualTo(now());
    }

    /**
     * Announced / scheduled titles — visible in the Upcoming rail only.
     * `published_at` here is the intended release date (may be null if
     * the admin hasn't picked one yet).
     */
    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_UPCOMING);
    }

    /**
     * Titles whose detail page is publicly reachable: anything that is
     * Published OR flagged Upcoming. The detail page for upcoming
     * titles shows release info / a "Coming soon" state instead of a
     * Watch button, but the page itself must load (users click "Upcoming"
     * cards from the home rail and expect to land *somewhere*).
     *
     * The streaming endpoints (player, stream URL) stay on `published()`
     * — you can't watch what hasn't been released.
     */
    public function scopeDetailVisible(Builder $q): Builder
    {
        // Anything the admin has committed to publishing gets a page —
        // Published (released OR still scheduled) and Upcoming alike. Only
        // Draft is truly invisible.
        //
        // This deliberately does NOT re-check `published_at <= now()`. It
        // used to, which meant a title scheduled for Friday had no page at
        // all until Friday: every link to it — a shared URL, a stale rail
        // card, a notification that fired early — died on a 404. A release
        // date is a reason to show "Coming soon", not a reason to pretend
        // the title doesn't exist. The controller flips the page into its
        // upcoming state via isPubliclyVisible(), and the streaming
        // endpoints still gate on published(), so nothing becomes watchable
        // one second sooner than it should.
        return $q->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_UPCOMING]);
    }

    protected static function newFactory(): MovieFactory
    {
        return MovieFactory::new();
    }
}
