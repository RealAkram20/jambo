<?php

namespace Modules\Pages\app\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property ?string $content
 * @property ?array $meta
 * @property ?string $featured_image_url
 * @property ?string $meta_description
 * @property string $status
 * @property bool $is_system
 */
class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta',
        'featured_image_url',
        'meta_description',
        'status',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Convenience accessor for a single meta key with a default.
     * Used heavily by the Contact page view so blade stays tidy.
     */
    public function metaValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta ?? [], $key, $default);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
