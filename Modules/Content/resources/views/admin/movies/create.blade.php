{{-- isSelect2 flag is required for the Streamit layout to load
     dashboard/vendor/select2/dist/js/select2.min.js — without it
     the cast picker's jQuery.fn.select2 is undefined and the
     dropdown can't initialise. --}}
@extends('layouts.app', ['module_title' => 'Add Movie', 'isSelect2' => true])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Add movie</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">Create a new movie and attach genres, categories, tags, and cast.</p>
                </div>
                <a href="{{ route('admin.movies.index') }}" class="btn btn-ghost">← Back to list</a>
            </div>

            @include('content::admin.partials.movie-breadcrumb', ['movie' => null, 'leaf' => 'Add movie'])

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

            <form method="POST" action="{{ route('admin.movies.store') }}" enctype="multipart/form-data">
                @include('content::admin.movies.form', [
                    'currentGenreIds' => [],
                    'currentCategoryIds' => [],
                    'currentTagIds' => [],
                    'currentCast' => [],
                ])
            </form>
        </div>
    </div>
</div>
@endsection
