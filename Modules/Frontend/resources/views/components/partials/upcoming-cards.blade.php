@php
    /**
     * Renders a slice of upcoming items as Bootstrap grid columns.
     * Shared by /upcoming initial render and /upcoming/load-more AJAX
     * so the card markup lives in exactly one place.
     *
     * $items — Collection of Movie|Show each tagged with a `_kind`
     *          attribute ('movie' | 'show') by TopPicksRecommender.
     */
    $items = $items ?? collect();
@endphp

@foreach ($items as $item)
    @php
        $kind = $item->_kind ?? 'movie';
        $fallbackImg = $kind === 'show' ? 'media/vikings-portrait.webp' : 'media/rabbit-portrait.webp';

        // Release date → ribbon label on the card poster. "TBA" when
        // the admin hasn't scheduled one yet, so the card still
        // communicates "upcoming" rather than sitting dateless.
        $releaseLabel = $item->published_at
            ? $item->published_at->format('M j, Y')
            : 'TBA';

        // Detail pages now accept upcoming titles (Movie/Show
        // scopeDetailVisible) and show a "Coming soon" CTA instead of
        // the Watch button, so the card can link through normally.
        $cardPath = $kind === 'show'
            ? route('frontend.series_detail', $item->slug)
            : route('frontend.movie_detail', $item->slug);
    @endphp
    <div class="col">
        @include('frontend::components.cards.card-style', [
            'cardImage' => $item->poster_url ?: $fallbackImg,
            'cardTitle' => $item->title,
            'cardPath' => $cardPath,
            'cardLang' => $item->year ? (string) $item->year : 'Coming soon',
            'cardGenres' => $item->relationLoaded('genres') ? $item->genres->take(2)->pluck('name')->all() : null,
            'productPremium' => (bool) $item->tier_required,
            'upcomingRelease' => $releaseLabel,
            'watchableType' => $kind,
            'watchableId' => $item->id,
            'isnotlangCard' => !$item->year,
        ])
    </div>
@endforeach
