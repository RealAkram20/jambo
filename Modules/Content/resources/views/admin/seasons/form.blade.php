{{-- Shared form partial for create + edit --}}
@csrf

<div class="card">
    <div class="card-header"><h6 class="mb-0">Season details</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="number" class="form-label">Number <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('number') is-invalid @enderror" id="number" name="number" value="{{ old('number', $season->number) }}" min="1" required>
                @error('number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-9">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $season->title) }}" placeholder="Optional season title">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label for="synopsis" class="form-label">Synopsis</label>
            <textarea class="form-control @error('synopsis') is-invalid @enderror" id="synopsis" name="synopsis" rows="4">{{ old('synopsis', $season->synopsis) }}</textarea>
            @error('synopsis') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        @include('content::admin.partials.media-picker-field', [
            'key' => 'poster_url', 'label' => 'Poster',
            'value' => old('poster_url', $season->poster_url),
            'accept' => ['jpg','jpeg','png','webp','svg'], 'aspect' => '2/3',
            'placeholder' => 'https://... or /storage/media/posters/...',
        ])

        <div class="mb-3">
            <label for="released_at" class="form-label">Released at</label>
            <input type="date" class="form-control @error('released_at') is-invalid @enderror" id="released_at" name="released_at"
                value="{{ old('released_at', $season->released_at ? \Illuminate\Support\Carbon::parse($season->released_at)->format('Y-m-d') : '') }}">
            @error('released_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="{{ route('admin.series.edit', $show) }}" class="btn btn-ghost">← Back to series</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> Save season
    </button>
</div>

@include('content::admin.partials.media-picker-script')
