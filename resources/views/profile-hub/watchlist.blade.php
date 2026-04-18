@include('profile-hub._layout', ['pageTitle' => 'Watchlist', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Your watchlist</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    {{ $movies->count() + $shows->count() }}
                    {{ Str::plural('title', $movies->count() + $shows->count()) }} saved for later.
                </p>
            </div>
            <i class="ph ph-bookmarks-simple fs-2 text-muted"></i>
        </div>

        <ul class="nav nav-underline mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#hub-wl-movies"
                        type="button" role="tab">
                    Movies <span class="text-muted small">({{ $movies->count() }})</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#hub-wl-shows"
                        type="button" role="tab">
                    Series <span class="text-muted small">({{ $shows->count() }})</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div id="hub-wl-movies" class="tab-pane fade show active" role="tabpanel">
                @if ($movies->count())
                    <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-4">
                        @foreach ($movies as $m)
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage'      => $m->poster_url ?: 'media/rabbit-portrait.webp',
                                    'cardTitle'      => $m->title,
                                    'movietime'      => $m->runtime_minutes
                                        ? floor($m->runtime_minutes / 60) . 'hr : ' . ($m->runtime_minutes % 60) . 'mins'
                                        : null,
                                    'cardLang'       => 'English',
                                    'cardPath'       => route('frontend.movie_detail', $m->slug),
                                    'cardGenres'     => $m->relationLoaded('genres') ? $m->genres->take(2)->pluck('name')->all() : null,
                                    'productPremium' => (bool) $m->tier_required,
                                    'watchableType'  => 'movie',
                                    'watchableId'    => $m->id,
                                ])
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">
                        No movies saved yet. Tap the <i class="ph ph-plus"></i> on any title to save it.
                    </p>
                @endif
            </div>

            <div id="hub-wl-shows" class="tab-pane fade" role="tabpanel">
                @if ($shows->count())
                    <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-4">
                        @foreach ($shows as $s)
                            <div class="col">
                                @include('frontend::components.cards.card-style', [
                                    'cardImage'      => $s->poster_url ?: 'media/vikings-portrait.webp',
                                    'cardTitle'      => $s->title,
                                    'movietime'      => null,
                                    'cardLang'       => 'English',
                                    'cardPath'       => route('frontend.series_detail', $s->slug),
                                    'cardGenres'     => $s->relationLoaded('genres') ? $s->genres->take(2)->pluck('name')->all() : null,
                                    'productPremium' => (bool) $s->tier_required,
                                    'watchableType'  => 'show',
                                    'watchableId'    => $s->id,
                                ])
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">No series saved yet.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
