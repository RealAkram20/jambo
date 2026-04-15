@extends('layouts.app', ['module_title' => 'Edit Episode'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">{{ $show->title }} — S{{ $season->number }}E{{ $episode->number }}: {{ $episode->title }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Last updated {{ $episode->updated_at?->diffForHumans() }}
                        @if ($episode->published_at)
                            · published {{ $episode->published_at->diffForHumans() }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('admin.seasons.edit', $season) }}" class="btn btn-ghost">← Back to season</a>
            </div>

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

            <form method="POST" action="{{ route('admin.episodes.update', $episode) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('content::admin.episodes.form')
            </form>
        </div>
    </div>
</div>
@endsection
