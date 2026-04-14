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
                <div class="mb-0">
                    <label for="still_url" class="form-label">Still URL</label>
                    <input type="url" class="form-control @error('still_url') is-invalid @enderror" id="still_url" name="still_url" value="{{ old('still_url', $episode->still_url) }}" placeholder="https://...">
                    @error('still_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Streaming</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="video_url" class="form-label">Video URL</label>
                    <input type="url" class="form-control @error('video_url') is-invalid @enderror" id="video_url" name="video_url" value="{{ old('video_url', $episode->video_url) }}" placeholder="https://www.youtube.com/watch?v=... or https://example.com/ep.mp4">
                    <div class="form-text">YouTube link or direct file URL (.mp4 / .webm / .m3u8). Leave blank if the episode isn't streamable yet.</div>
                    @error('video_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="dropbox_path" class="form-label">Dropbox path <span class="text-muted" style="font-size:11px;">(legacy)</span></label>
                    <input type="text" class="form-control @error('dropbox_path') is-invalid @enderror" id="dropbox_path" name="dropbox_path" value="{{ old('dropbox_path', $episode->dropbox_path) }}" placeholder="/Jambo/shows/my-show/s01e01.mp4">
                    <div class="form-text">Kept for reference only — the player now uses Video URL above.</div>
                    @error('dropbox_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-0">
                    <label for="tier_required" class="form-label">Required tier</label>
                    <select name="tier_required" id="tier_required" class="form-select">
                        <option value="">Free (no subscription required)</option>
                        <option value="basic" @selected(old('tier_required', $episode->tier_required) === 'basic')>Basic</option>
                        <option value="premium" @selected(old('tier_required', $episode->tier_required) === 'premium')>Premium</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Publishing</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="published_at" class="form-label">Published at</label>
                    <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror" id="published_at" name="published_at"
                        value="{{ old('published_at', $episode->published_at ? \Illuminate\Support\Carbon::parse($episode->published_at)->format('Y-m-d\TH:i') : '') }}">
                    <div class="form-text">Leave blank to keep as draft.</div>
                    @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @if ($episode->exists && $episode->published_at)
                    <div class="text-muted" style="font-size:12px;">Currently published {{ $episode->published_at->diffForHumans() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="{{ route('admin.seasons.edit', $season) }}" class="btn btn-ghost">← Back to season</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> Save episode
    </button>
</div>
