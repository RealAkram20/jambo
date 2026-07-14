@extends('monetization::layouts.partner')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-1">Edit — {{ $title->title }}</h4>
                <p class="text-muted mb-0" style="font-size:13px;">
                    {{ ucfirst($type) }} · you can update the presentation of your title. Video files, pricing
                    and runtime are managed by the Jambo team.
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger mx-4 mt-3 mb-0">{{ $errors->first() }}</div>
            @endif

            <div class="card-body">
                <form method="POST" action="{{ route('partner.content.update', ['type' => $type, 'id' => $title->id]) }}">
                    @csrf @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required maxlength="190"
                                   value="{{ old('title', $title->title) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control" min="1900" max="{{ now()->year + 2 }}"
                                   value="{{ old('year', $title->year) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Rating</label>
                            <input type="text" name="rating" class="form-control" maxlength="20"
                                   value="{{ old('rating', $title->rating) }}" placeholder="e.g. PG-13">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Synopsis</label>
                            <textarea name="synopsis" class="form-control" rows="4" maxlength="5000">{{ old('synopsis', $title->synopsis) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Poster URL</label>
                            <input type="text" name="poster_url" class="form-control" maxlength="2048"
                                   value="{{ old('poster_url', $title->poster_url) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Backdrop URL</label>
                            <input type="text" name="backdrop_url" class="form-control" maxlength="2048"
                                   value="{{ old('backdrop_url', $title->backdrop_url) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Trailer URL</label>
                            <input type="text" name="trailer_url" class="form-control" maxlength="2048"
                                   value="{{ old('trailer_url', $title->trailer_url) }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('partner.titles') }}" class="btn btn-ghost">Cancel</a>
                        <button class="btn btn-primary"><i class="ph ph-floppy-disk me-1"></i> Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
