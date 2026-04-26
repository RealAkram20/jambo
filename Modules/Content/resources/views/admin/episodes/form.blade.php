{{-- Shared form partial for create + edit --}}
@csrf

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="number" class="form-label">Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('number') is-invalid @enderror" id="number" name="number" value="{{ old('number', $episode->number) }}" min="1" required>
                        @error('number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-9">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $episode->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="synopsis" class="form-label">Synopsis</label>
                    <textarea class="form-control @error('synopsis') is-invalid @enderror" id="synopsis" name="synopsis" rows="4">{{ old('synopsis', $episode->synopsis) }}</textarea>
                    @error('synopsis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-0">
                    <label for="runtime_minutes" class="form-label">Runtime (min)</label>
                    <input type="number" class="form-control @error('runtime_minutes') is-invalid @enderror" id="runtime_minutes" name="runtime_minutes" value="{{ old('runtime_minutes', $episode->runtime_minutes) }}" min="1" max="1000">
                    @error('runtime_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Media</h6></div>
            <div class="card-body">
                @include('content::admin.partials.media-picker-field', [
                    'key' => 'still_url', 'label' => 'Still',
                    'value' => old('still_url', $episode->still_url),
                    'accept' => ['jpg','jpeg','png','webp','svg'], 'aspect' => '16/9',
                    'placeholder' => 'https://... or /storage/media/stills/...',
                ])
            </div>
        </div>

        @include('content::admin.partials.streaming-tabs', ['model' => $episode])

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Access</h6></div>
            <div class="card-body">
                <label for="tier_required" class="form-label">Required tier</label>
                <select name="tier_required" id="tier_required" class="form-select">
                    <option value="">Free (no subscription required)</option>
                    <option value="basic" @selected(old('tier_required', $episode->tier_required) === 'basic')>Basic</option>
                    <option value="premium" @selected(old('tier_required', $episode->tier_required) === 'premium')>Premium</option>
                </select>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Publishing</h6></div>
            <div class="card-body">
                @php
                    $existingPublishedAt = $episode->published_at;
                    $resolvedStatus = old('status');
                    if (!$resolvedStatus) {
                        if (!$existingPublishedAt) {
                            $resolvedStatus = 'draft';
                        } elseif ($existingPublishedAt->isFuture()) {
                            $resolvedStatus = 'upcoming';
                        } else {
                            $resolvedStatus = 'published';
                        }
                    }
                @endphp

                {{-- Status picker mirrors the movies form. The actual
                     published_at field below is what the controller
                     reads; the status select is a UI helper that
                     toggles whether published_at is shown / cleared
                     and provides clearer admin language. The hidden
                     name="status" means it gets submitted but
                     EpisodeController ignores it (episodes are gated
                     purely on published_at). --}}
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select" data-jambo-status>
                        <option value="draft" @selected($resolvedStatus === 'draft')>Draft</option>
                        <option value="upcoming" @selected($resolvedStatus === 'upcoming')>Upcoming</option>
                        <option value="published" @selected($resolvedStatus === 'published')>Published</option>
                    </select>
                </div>

                <div class="mb-3" data-jambo-release-wrap
                     @if ($resolvedStatus === 'draft') style="display:none;" @endif>
                    <label for="published_at" class="form-label" data-jambo-release-label>
                        {{ $resolvedStatus === 'upcoming' ? 'Release date' : 'Published at' }}
                    </label>
                    <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror"
                           id="published_at" name="published_at"
                           value="{{ old('published_at', $existingPublishedAt ? \Illuminate\Support\Carbon::parse($existingPublishedAt)->format('Y-m-d\TH:i') : '') }}">
                    <div class="form-text" data-jambo-release-hint>
                        @if ($resolvedStatus === 'upcoming')
                            Scheduled air date — episode appears as "Coming soon" until it arrives.
                        @else
                            When this episode went live. Leave blank to auto-stamp on save.
                        @endif
                    </div>
                    @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if ($episode->exists && $existingPublishedAt)
                    <div class="text-muted" style="font-size:12px;">
                        @if ($existingPublishedAt->isFuture())
                            Releases {{ $existingPublishedAt->format('M j, Y') }} ({{ $existingPublishedAt->diffForHumans() }})
                        @else
                            Published {{ $existingPublishedAt->diffForHumans() }}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var statusSel = document.querySelector('[data-jambo-status]');
    var wrap      = document.querySelector('[data-jambo-release-wrap]');
    var label     = document.querySelector('[data-jambo-release-label]');
    var hint      = document.querySelector('[data-jambo-release-hint]');
    var dateInput = document.getElementById('published_at');
    if (!statusSel || !wrap || !label || !hint || !dateInput) return;

    function apply() {
        var s = statusSel.value;
        if (s === 'draft') {
            wrap.style.display = 'none';
            // Don't blast the date — the controller reads it, and an
            // empty value plus status=draft cleanly signals "save as
            // draft" without losing whatever was typed.
            dateInput.value = '';
        } else {
            wrap.style.display = '';
            if (s === 'upcoming') {
                label.textContent = 'Release date';
                hint.textContent = 'Scheduled air date — episode appears as "Coming soon" until it arrives.';
            } else {
                label.textContent = 'Published at';
                hint.textContent = 'When this episode went live. Leave blank to auto-stamp on save.';
            }
        }
    }
    statusSel.addEventListener('change', apply);
})();
</script>

<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="{{ route('admin.series.seasons.edit', [$show, $season]) }}" class="btn btn-ghost">← Back to season</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> Save episode
    </button>
</div>

@include('content::admin.partials.media-picker-script')
@include('content::admin.partials.streaming-tabs-script')
