{{-- Shared form partial for create + edit --}}
@csrf

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Details</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $show->title) }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="synopsis" class="form-label">Synopsis</label>
                    <textarea class="form-control @error('synopsis') is-invalid @enderror" id="synopsis" name="synopsis" rows="4">{{ old('synopsis', $show->synopsis) }}</textarea>
                    @error('synopsis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="year" class="form-label">Year</label>
                        <input type="number" class="form-control @error('year') is-invalid @enderror" id="year" name="year" value="{{ old('year', $show->year) }}" min="1900" max="2100">
                    </div>
                    <div class="col-md-6">
                        <label for="rating" class="form-label">Rating</label>
                        <select name="rating" id="rating" class="form-select">
                            <option value="">—</option>
                            @foreach (['G', 'PG', 'PG-13', 'R', 'NC-17'] as $r)
                                <option value="{{ $r }}" @selected(old('rating', $show->rating) === $r)>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Media</h6></div>
            <div class="card-body">
                @include('content::admin.partials.media-picker-field', [
                    'key' => 'poster_url', 'label' => 'Poster',
                    'value' => old('poster_url', $show->poster_url),
                    'accept' => ['jpg','jpeg','png','webp','svg'], 'aspect' => '2/3',
                    'placeholder' => 'https://... or /storage/media/posters/...',
                ])
                @include('content::admin.partials.media-picker-field', [
                    'key' => 'backdrop_url', 'label' => 'Backdrop',
                    'value' => old('backdrop_url', $show->backdrop_url),
                    'accept' => ['jpg','jpeg','png','webp','svg'], 'aspect' => '16/9',
                    'placeholder' => 'https://... or /storage/media/backdrops/...',
                ])
                @include('content::admin.partials.media-picker-field', [
                    'key' => 'trailer_url', 'label' => 'Trailer',
                    'value' => old('trailer_url', $show->trailer_url),
                    'accept' => ['mp4','webm','mov','m4v','mkv'], 'aspect' => '16/9',
                    'placeholder' => 'https://youtube.com/watch?v=... or /storage/media/trailers/...',
                    'hint' => 'YouTube URL or MP4 / WEBM / MOV',
                ])
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Streaming</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="tier_required" class="form-label">Required tier</label>
                    <select name="tier_required" id="tier_required" class="form-select">
                        <option value="">Free (no subscription required)</option>
                        <option value="basic" @selected(old('tier_required', $show->tier_required) === 'basic')>Basic</option>
                        <option value="premium" @selected(old('tier_required', $show->tier_required) === 'premium')>Premium</option>
                    </select>
                    <div class="form-text">Series carry per-episode dropbox paths. Set Dropbox paths on each episode.</div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Cast</h6></div>
            <div class="card-body">
                <div id="cast-rows">
                    @foreach ((old('cast') ?? ($currentCast ?? [])) as $i => $row)
                        @include('content::admin.movies.partials.cast-row', ['i' => $i, 'row' => $row, 'persons' => $persons])
                    @endforeach
                </div>
                <button type="button" class="btn btn-ghost btn-sm mt-2" id="add-cast">
                    <i class="ph ph-plus me-1"></i> Add cast member
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Publishing</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select" data-jambo-status>
                        <option value="draft" @selected(old('status', $show->status ?: 'draft') === 'draft')>Draft</option>
                        <option value="upcoming" @selected(old('status', $show->status) === 'upcoming')>Upcoming</option>
                        <option value="published" @selected(old('status', $show->status) === 'published')>Published</option>
                    </select>
                </div>

                {{-- Release / publish date — same pattern as the movie
                     form. Hidden for drafts; label flips between
                     "Release date" and "Published at" depending on
                     status. Stored on shows.published_at. --}}
                <div class="mb-3" data-jambo-release-wrap
                     @if (old('status', $show->status ?: 'draft') === 'draft') style="display:none;" @endif>
                    <label for="published_at" class="form-label" data-jambo-release-label>
                        {{ old('status', $show->status) === 'upcoming' ? 'Release date' : 'Published at' }}
                    </label>
                    <input type="datetime-local" name="published_at" id="published_at"
                           class="form-control"
                           value="{{ old('published_at', $show->published_at?->format('Y-m-d\TH:i')) }}">
                    <div class="form-text" data-jambo-release-hint>
                        @if (old('status', $show->status) === 'upcoming')
                            Scheduled release date. Surfaced on the detail page and the home "Upcoming" slider.
                        @else
                            When this series went live. Leave blank to auto-stamp on save.
                        @endif
                    </div>
                </div>

                @if ($show->exists && $show->published_at)
                    <div class="text-muted" style="font-size:12px;">
                        @if ($show->status === 'upcoming')
                            Releases {{ $show->published_at->format('M j, Y') }} ({{ $show->published_at->diffForHumans() }})
                        @else
                            Published {{ $show->published_at->diffForHumans() }}
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Genres</h6></div>
            <div class="card-body">
                @foreach ($genres as $genre)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="g{{ $genre->id }}" name="genre_ids[]" value="{{ $genre->id }}"
                            @checked(in_array($genre->id, old('genre_ids', $currentGenreIds ?? [])))>
                        <label class="form-check-label" for="g{{ $genre->id }}">{{ $genre->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Vjs</h6>
                <a href="{{ route('dashboard.vjs') }}" class="text-decoration-none small" title="Manage Vjs">
                    <i class="ph ph-gear"></i>
                </a>
            </div>
            <div class="card-body">
                @forelse ($vjs ?? [] as $vj)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="vj{{ $vj->id }}" name="vj_ids[]" value="{{ $vj->id }}"
                            @checked(in_array($vj->id, old('vj_ids', $currentVjIds ?? [])))>
                        <label class="form-check-label" for="vj{{ $vj->id }}">
                            @if ($vj->colour)
                                <span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:{{ $vj->colour }};vertical-align:middle;"></span>
                            @endif
                            {{ $vj->name }}
                        </label>
                    </div>
                @empty
                    <div class="text-secondary small">
                        No Vjs yet. <a href="{{ route('dashboard.vjs') }}">Add one →</a>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Categories</h6></div>
            <div class="card-body">
                @foreach ($categories as $cat)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="c{{ $cat->id }}" name="category_ids[]" value="{{ $cat->id }}"
                            @checked(in_array($cat->id, old('category_ids', $currentCategoryIds ?? [])))>
                        <label class="form-check-label" for="c{{ $cat->id }}">{{ $cat->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Tags</h6></div>
            <div class="card-body">
                @foreach ($tags as $tag)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="t{{ $tag->id }}" name="tag_ids[]" value="{{ $tag->id }}"
                            @checked(in_array($tag->id, old('tag_ids', $currentTagIds ?? [])))>
                        <label class="form-check-label" for="t{{ $tag->id }}">{{ $tag->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="{{ route('admin.series.index') }}" class="btn btn-ghost">← Back to list</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> Save series
    </button>
</div>

<template id="cast-row-template">
    @include('content::admin.movies.partials.cast-row', ['i' => '__i__', 'row' => [], 'persons' => $persons])
</template>

<script>
(function () {
    const personsJson = @json($persons->map(fn($p) => ['id' => $p->id, 'label' => trim($p->first_name . ' ' . $p->last_name)]));
    let nextIndex = {{ count(old('cast') ?? $currentCast ?? []) }};

    document.getElementById('add-cast').addEventListener('click', function () {
        const tpl = document.getElementById('cast-row-template').innerHTML.replaceAll('__i__', nextIndex++);
        document.getElementById('cast-rows').insertAdjacentHTML('beforeend', tpl);
    });

    document.getElementById('cast-rows').addEventListener('click', function (e) {
        if (e.target.closest('.remove-cast')) {
            e.target.closest('.cast-row').remove();
        }
    });

    // Release-date field visibility + label tracking the status select.
    const statusSel = document.querySelector('[data-jambo-status]');
    const wrap = document.querySelector('[data-jambo-release-wrap]');
    const label = document.querySelector('[data-jambo-release-label]');
    const hint = document.querySelector('[data-jambo-release-hint]');
    if (statusSel && wrap && label && hint) {
        const apply = () => {
            const s = statusSel.value;
            if (s === 'draft') {
                wrap.style.display = 'none';
            } else {
                wrap.style.display = '';
                if (s === 'upcoming') {
                    label.textContent = 'Release date';
                    hint.textContent = 'Scheduled release date. Surfaced on the detail page and the home "Upcoming" slider.';
                } else {
                    label.textContent = 'Published at';
                    hint.textContent = 'When this series went live. Leave blank to auto-stamp on save.';
                }
            }
        };
        statusSel.addEventListener('change', apply);
        apply();
    }
})();
</script>
@include('content::admin.partials.media-picker-script')
