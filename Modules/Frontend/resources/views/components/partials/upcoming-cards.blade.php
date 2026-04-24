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

        // Display the release date when the admin set one; otherwise
        // signal that a date is TBD so upcoming cards always carry
        // context a user can read.
        $releaseLabel = $item->published_at
            ? $item->published_at->format('M j, Y')
            : __('streamTag.release_tbd') ?? 'Release date TBD';

        // Upcoming detail pages aren't wired yet — the existing
        // movie_detail / series_detail controllers filter by the
        // `published` scope, so linking through would 404. Keep the
        // card non-clicking for now; when detail pages are ready
        // this becomes a simple route() call.
        $cardPath = '#';
    @endphp
    <div class="col">
        @include('frontend::components.cards.card-style', [
            'cardImage' => $item->poster_url ?: $fallbackImg,
            'cardTitle' => $item->title,
            'cardPath' => $cardPath,
            'movietime' => $releaseLabel,
            'cardLang' => $item->year ? (string) $item->year : 'Coming soon',
            'cardGenres' => $item->relationLoaded('genres') ? $item->genres->take(2)->pluck('name')->all() : null,
            'productPremium' => (bool) $item->tier_required,
            'watchableType' => $kind,
            'watchableId' => $item->id,
            'isnotlangCard' => !$item->year,
        ])
    </div>
@endforeach
