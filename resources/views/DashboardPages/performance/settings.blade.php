@extends('layouts.app', ['module_title' => 'Performance settings', 'title' => 'Performance settings'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-7 mx-auto">

            <div class="d-flex align-items-center gap-2 mb-4">
                <a href="{{ route('dashboard.performance') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ph ph-arrow-left"></i>
                </a>
                <div>
                    <h4 class="mb-0"><i class="ph ph-sliders-horizontal me-2"></i>Performance rates</h4>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.performance.settings.update') }}">
                        @csrf

                        <p class="text-muted small mb-4">
                            A series is paid its rate once, when the show is first created (season 1).
                            Extra seasons aren't paid on their own — only their episodes are. Seasons
                            themselves are never a paid unit.
                        </p>

                        @php
                            $fields = [
                                'price_per_movie'   => ['label' => 'Per movie',   'icon' => 'ph-film-strip', 'val' => $prices['movie']],
                                'price_per_show'    => ['label' => 'Per series',  'icon' => 'ph-television',  'val' => $prices['show']],
                                'price_per_episode' => ['label' => 'Per episode', 'icon' => 'ph-play-circle', 'val' => $prices['episode']],
                            ];
                        @endphp

                        @foreach ($fields as $name => $f)
                            <div class="mb-3">
                                <label for="{{ $name }}" class="form-label">
                                    <i class="ph {{ $f['icon'] }} me-1"></i> {{ $f['label'] }}
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currency }}</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error($name) is-invalid @enderror"
                                           id="{{ $name }}" name="{{ $name }}"
                                           value="{{ old($name, number_format((float) $f['val'], 2, '.', '')) }}">
                                    @error($name)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="ph ph-floppy-disk me-1"></i> Save rates
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
