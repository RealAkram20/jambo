{{-- isSelect2 flag — see movies/create.blade.php for rationale. --}}
@extends('layouts.app', ['module_title' => 'Add Series', 'isSelect2' => true])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Add series</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">Create a new show and attach genres, categories, tags, and cast.</p>
                </div>
                <a href="{{ route('admin.series.index') }}" class="btn btn-ghost">← Back to list</a>
            </div>

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

            <form method="POST" action="{{ route('admin.series.store') }}">
                @include('content::admin.shows.form', [
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
