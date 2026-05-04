{{-- isSelect2 flag — see movies/create.blade.php for rationale. --}}
@extends('layouts.app', ['module_title' => 'Edit Series', 'isSelect2' => true])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">{{ $show->title }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Last updated {{ $show->updated_at?->diffForHumans() }}
                        @if ($show->published_at)
                            · published {{ $show->published_at->diffForHumans() }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('admin.series.index') }}" class="btn btn-ghost">← Back to list</a>
            </div>

            @include('content::admin.partials.series-breadcrumb', ['show' => $show, 'season' => null, 'episode' => null])

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.series.update', $show) }}">
                @method('PUT')
                @include('content::admin.shows.form')
            </form>

            @include('content::admin.shows.seasons-section')
        </div>
    </div>
</div>
@endsection
