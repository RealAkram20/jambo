{{-- Shared form partial for create + edit --}}
@csrf

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $person->first_name) }}" required>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $person->last_name) }}" required>
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label for="known_for" class="form-label">Known for</label>
                    <input type="text" class="form-control @error('known_for') is-invalid @enderror" id="known_for" name="known_for" value="{{ old('known_for', $person->known_for) }}" placeholder="actor, director, writer">
                    <div class="form-text">Comma-separated list of roles.</div>
                    @error('known_for') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mt-3">
                    <label for="bio" class="form-label">Biography</label>
                    <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="5">{{ old('bio', $person->bio) }}</textarea>
                    @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Life</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="birth_date" class="form-label">Birth date</label>
                        <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date"
                            value="{{ old('birth_date', optional($person->birth_date)->format('Y-m-d')) }}">
                        @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="death_date" class="form-label">Death date</label>
                        <input type="date" class="form-control @error('death_date') is-invalid @enderror" id="death_date" name="death_date"
                            value="{{ old('death_date', optional($person->death_date)->format('Y-m-d')) }}">
                        <div class="form-text">Leave blank if still alive.</div>
                        @error('death_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Photo</h6></div>
            <div class="card-body">
                @if (!empty(old('photo_url', $person->photo_url)))
                    <div class="mb-3 text-center">
                        <img src="{{ old('photo_url', $person->photo_url) }}" alt="" class="rounded-circle" style="width:120px;height:120px;object-fit:cover;">
                    </div>
                @endif
                <label for="photo_url" class="form-label">Photo URL</label>
                <input type="url" class="form-control @error('photo_url') is-invalid @enderror" id="photo_url" name="photo_url" value="{{ old('photo_url', $person->photo_url) }}" placeholder="https://...">
                @error('photo_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        @if ($person->exists && (($person->movies_count ?? 0) + ($person->shows_count ?? 0)) > 0)
            <div class="card mt-4">
                <div class="card-header"><h6 class="mb-0">Credits</h6></div>
                <div class="card-body">
                    @if ($person->movies_count > 0)
                        <div class="mb-2">
                            <strong style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Movies ({{ $person->movies_count }})</strong>
                            <ul class="list-unstyled mt-1 mb-0" style="font-size:13px;">
                                @foreach ($person->movies->take(10) as $m)
                                    <li>· {{ $m->title }}</li>
                                @endforeach
                                @if ($person->movies_count > 10)
                                    <li class="text-muted">· …and {{ $person->movies_count - 10 }} more</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                    @if ($person->shows_count > 0)
                        <div>
                            <strong style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Shows ({{ $person->shows_count }})</strong>
                            <ul class="list-unstyled mt-1 mb-0" style="font-size:13px;">
                                @foreach ($person->shows->take(10) as $s)
                                    <li>· {{ $s->title }}</li>
                                @endforeach
                                @if ($person->shows_count > 10)
                                    <li class="text-muted">· …and {{ $person->shows_count - 10 }} more</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="{{ route('admin.persons.index') }}" class="btn btn-ghost">← Back to list</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> Save person
    </button>
</div>
