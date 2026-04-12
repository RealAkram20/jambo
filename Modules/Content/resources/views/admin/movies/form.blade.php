{{-- Shared form partial for create + edit --}}
@csrf

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Details</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $movie->title) }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="synopsis" class="form-label">Synopsis</label>
                    <textarea class="form-control @error('synopsis') is-invalid @enderror" id="synopsis" name="synopsis" rows="4">{{ old('synopsis', $movie->synopsis) }}</textarea>
                    @error('synopsis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="year" class="form-label">Year</label>
                        <input type="number" class="form-control @error('year') is-invalid @enderror" id="year" name="year" value="{{ old('year', $movie->year) }}" min="1900" max="2100">
                    </div>
                    <div class="col-md-4">
                        <label for="runtime_minutes" class="form-label">Runtime (min)</label>
                        <input type="number" class="form-control @error('runtime_minutes') is-invalid @enderror" id="runtime_minutes" name="runtime_minutes" value="{{ old('runtime_minutes', $movie->runtime_minutes) }}" min="1" max="1000">
                    </div>
                    <div class="col-md-4">
                        <label for="rating" class="form-label">Rating</label>
                        <select name="rating" id="rating" class="form-select">
                            <option value="">—</option>
                            @foreach (['G', 'PG', 'PG-13', 'R', 'NC-17'] as $r)
                                <option value="{{ $r }}" @selected(old('rating', $movie->rating) === $r)>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Media</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="poster_url" class="form-label">Poster URL</label>
                    <input type="url" class="form-control @error('poster_url') is-invalid @enderror" id="poster_url" name="poster_url" value="{{ old('poster_url', $movie->poster_url) }}" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label for="backdrop_url" class="form-label">Backdrop URL</label>
                    <input type="url" class="form-control @error('backdrop_url') is-invalid @enderror" id="backdrop_url" name="backdrop_url" value="{{ old('backdrop_url', $movie->backdrop_url) }}" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label for="trailer_url" class="form-label">Trailer URL</label>
                    <input type="url" class="form-control @error('trailer_url') is-invalid @enderror" id="trailer_url" name="trailer_url" value="{{ old('trailer_url', $movie->trailer_url) }}" placeholder="https://youtube.com/watch?v=...">
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Streaming</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="dropbox_path" class="form-label">Dropbox path</label>
                    <input type="text" class="form-control @error('dropbox_path') is-invalid @enderror" id="dropbox_path" name="dropbox_path" value="{{ old('dropbox_path', $movie->dropbox_path) }}" placeholder="/Jambo/movies/my-film.mp4">
                    <div class="form-text">Relative path inside the Jambo Dropbox folder. Leave blank for "not streamable yet".</div>
                </div>
                <div class="mb-3">
                    <label for="tier_required" class="form-label">Required tier</label>
                    <select name="tier_required" id="tier_required" class="form-select">
                        <option value="">Free (no subscription required)</option>
                        <option value="basic" @selected(old('tier_required', $movie->tier_required) === 'basic')>Basic</option>
                        <option value="premium" @selected(old('tier_required', $movie->tier_required) === 'premium')>Premium</option>
                    </select>
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
                    <select name="status" id="status" class="form-select">
                        <option value="draft" @selected(old('status', $movie->status ?: 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $movie->status) === 'published')>Published</option>
                    </select>
                </div>
                @if ($movie->exists && $movie->published_at)
                    <div class="text-muted" style="font-size:12px;">Published {{ $movie->published_at->diffForHumans() }}</div>
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
    <a href="{{ route('admin.movies.index') }}" class="btn btn-ghost">← Back to list</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> Save movie
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
})();
</script>
