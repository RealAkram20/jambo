{{-- isSelect2 flag — see movies/create.blade.php for rationale. --}}
@extends('layouts.app', ['module_title' => 'Edit Movie', 'isSelect2' => true])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">{{ $movie->title }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Last updated {{ $movie->updated_at?->diffForHumans() }}
                        @if ($movie->published_at)
                            · published {{ $movie->published_at->diffForHumans() }}
                        @endif
                    </p>
                </div>
                {{-- $listQuery holds the page/filter the admin came from, so
                     this returns to their place in the catalogue rather than
                     dumping them on page 1. --}}
                <a href="{{ route('admin.movies.index', $listQuery ?? []) }}" class="btn btn-ghost">← Back to list</a>
            </div>

            @include('content::admin.partials.movie-breadcrumb', ['movie' => $movie])

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

            {{-- List state rides along on the action URL so the post-save
                 redirect lands back on the right page even if the session
                 fallback has since been overwritten by another tab. --}}
            <form method="POST" action="{{ route('admin.movies.update', ['movie' => $movie] + ($listQuery ?? [])) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('content::admin.movies.form')
            </form>
        </div>
    </div>
</div>
@endsection
