@extends('layouts.app', ['module_title' => 'Add Episode'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Add episode to: {{ $show->title }} — Season {{ $season->number }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">Create a new episode under this season.</p>
                </div>
                <a href="{{ route('admin.series.seasons.edit', [$show, $season]) }}" class="btn btn-ghost">← Back to season</a>
            </div>

            @include('content::admin.partials.series-breadcrumb', ['show' => $show, 'season' => $season, 'episode' => null, 'leaf' => 'Add episode'])

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

            <form method="POST" action="{{ route('admin.series.seasons.episodes.store', [$show, $season]) }}" enctype="multipart/form-data">
                @include('content::admin.episodes.form')
            </form>
        </div>
    </div>
</div>
@endsection
