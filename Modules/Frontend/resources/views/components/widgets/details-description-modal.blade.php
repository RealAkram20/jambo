@php
    /**
     * "Read more" detail sheet — opened by the #viewMoreDataModal
     * trigger inside components/cards/movie-description.blade.php.
     *
     * Driven by real data. Every section is optional (null/empty
     * collection hides it), so movie, TV show, and episode callers
     * pass only what they have.
     *
     * Props:
     *   $movieName      — title (required)
     *   $description    — long synopsis (required)
     *   $year           — release year (string|int|null)
     *   $views          — formatted views string ("12,345 views")
     *   $movieDuration  — "1hr : 45mins" or "3 seasons"
     *   $ratingCount    — IMDB rating (string|null)
     *   $language       — human language label (null to hide row)
     *   $genres         — array<string> of genre names
     *   $tags           — array<string> of tag names
     *   $cast           — iterable of Person models with pivot for
     *                     character_name (actors)
     *   $crew           — iterable of Person models with pivot.role
     *                     (directors/writers/producers)
     *   $releaseLabel   — formatted upcoming release date (optional)
     */
    $movieName     = $movieName     ?? '';
    $description   = $description   ?? '';
    $year          = $year          ?? null;
    $views         = $views         ?? null;
    $movieDuration = $movieDuration ?? null;
    $ratingCount   = $ratingCount   ?? null;
    $language      = $language      ?? null;
    $genres        = $genres        ?? [];
    $tags          = $tags          ?? [];
    $cast          = $cast          ?? collect();
    $crew          = $crew          ?? collect();
    $releaseLabel  = $releaseLabel  ?? null;
@endphp

<div class="modal fade view-more-data-modal trending-info" id="viewMoreDataModal" tabindex="-1" aria-modal="true"
     aria-labelledby="viewMoreDataModalLabel" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header pb-0">
                <h3 class="text-uppercase m-0 texture-text texture-text-modal fw-bold"
                    id="viewMoreDataModalLabel">
                    {{ $movieName }}
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('streamButtons.close') !== 'streamButtons.close' ? __('streamButtons.close') : 'Close' }}"></button>
            </div>

            <div class="modal-body pt-1">
                {{-- Metadata chips row — year / runtime / views / rating /
                     release date. Every <li> is guarded so a missing
                     field doesn't leave an empty slot. --}}
                <ul class="list-inline d-flex align-items-center flex-wrap gap-3 mt-4">
                    @if ($year)
                        <li><span class="fw-medium">{{ $year }}</span></li>
                    @endif
                    @if ($movieDuration)
                        <li>
                            <span class="d-flex align-items-center gap-1">
                                <i class="ph ph-clock"></i>
                                {{ $movieDuration }}
                            </span>
                        </li>
                    @endif
                    @if ($views)
                        <li>
                            <span class="d-flex align-items-center gap-1">
                                <i class="ph ph-eye"></i>
                                {{ $views }}
                            </span>
                        </li>
                    @endif
                    @if ($ratingCount)
                        <li>
                            <span class="d-flex align-items-center gap-1">
                                <span class="fw-medium">{{ $ratingCount }}</span>
                                <span class="imdb-logo ms-1">
                                    <img src="{{ asset('frontend/images/pages/imdb-logo.svg') }}" loading="lazy"
                                         decoding="async" alt="imdb logo" class="img-fluid imdb-logo1">
                                </span>
                            </span>
                        </li>
                    @endif
                    @if ($releaseLabel)
                        <li>
                            <span class="d-flex align-items-center gap-1 text-primary">
                                <i class="ph ph-calendar-check"></i>
                                {{ $releaseLabel }}
                            </span>
                        </li>
                    @endif
                </ul>

                @if (! empty($genres))
                    <div class="d-flex align-items-baseline flex-wrap gap-2 mt-md-1 mt-2">
                        <h6 class="m-0">{{ __('streamTag.genre') }}:</h6>
                        <ul class="p-0 mb-0 list-inline d-flex flex-wrap movie-tag">
                            @foreach ($genres as $g)
                                <li class="trending-list"><span>{{ $g }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! empty($tags))
                    <div class="d-flex align-items-baseline flex-wrap gap-2 mt-3">
                        <h6 class="m-0">{{ __('frontendheader.tags') }}:</h6>
                        <ul class="iq-blog-meta-cat-tag iq-blogtag mb-0 list-inline d-flex flex-wrap align-items-center gap-1 gap-md-3 mt-2 mt-md-3 tvshow-tags">
                            @foreach ($tags as $t)
                                <li><span class="position-relative">{{ $t }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($language)
                    <div class="mt-4">
                        <div class="d-flex align-items-center gap-1">
                            <i class="ph ph-translate"></i>
                            <ul class="list-inline m-0 d-inline-flex align-items-center gap-2">
                                <li><small>{{ $language }}</small></li>
                            </ul>
                        </div>
                    </div>
                @endif

                <p class="mt-4 mb-0">
                    {{ $description !== '' ? $description : __('streamTag.no_description_available') }}
                </p>

                @if ($cast && count($cast) > 0)
                    <div class="d-flex align-items-baseline row-gap-1 column-gap-2 mt-4">
                        <h6 class="m-0">{{ __('form.cast') }}:</h6>
                        <ul class="list-inline m-0 p-0 d-flex align-items-center flex-wrap row-gap-1 column-gap-2 cast-crew-list">
                            @foreach ($cast as $person)
                                @php
                                    $name = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
                                    $character = $person->pivot->character_name ?? null;
                                @endphp
                                @if ($name !== '')
                                    <li>
                                        <span class="color-inherit">
                                            {{ $name }}@if ($character) <small class="text-muted">({{ $character }})</small>@endif
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($crew && count($crew) > 0)
                    <div class="d-flex align-items-baseline row-gap-1 column-gap-2 mt-4">
                        <h6 class="m-0">{{ __('form.crew') }}:</h6>
                        <ul class="list-inline m-0 p-0 d-flex align-items-center flex-wrap row-gap-1 column-gap-2 cast-crew-list">
                            @foreach ($crew as $person)
                                @php
                                    $name = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
                                    $role = ucfirst($person->pivot->role ?? 'Crew');
                                @endphp
                                @if ($name !== '')
                                    <li>
                                        <span class="color-inherit">
                                            {{ $name }} <small class="text-muted">({{ $role }})</small>
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
