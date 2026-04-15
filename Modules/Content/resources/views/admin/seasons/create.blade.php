@extends('layouts.app', ['module_title' => 'Add Season'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Add season to: {{ $show->title }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">Create a new season under this show.</p>
                </div>
                <a href="{{ route('admin.series.edit', $show) }}" class="btn btn-ghost">← Back to series</a>
            </div>

            @include('content::admin.partials.series-breadcrumb', ['show' => $show, 'season' => null, 'episode' => null, 'leaf' => 'Add season'])

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

            <form method="POST" action="{{ route('admin.series.seasons.store', $show) }}">
                @include('content::admin.seasons.form')
            </form>
        </div>
    </div>
</div>
@endsection
