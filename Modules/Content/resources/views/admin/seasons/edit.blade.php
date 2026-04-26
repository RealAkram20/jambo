@extends('layouts.app', ['module_title' => 'Edit Season'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Season {{ $season->number }}@if ($season->title): {{ $season->title }}@endif</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        {{ $show->title }} · last updated {{ $season->updated_at?->diffForHumans() }}
                    </p>
                </div>
                <a href="{{ route('admin.series.edit', $show) }}" class="btn btn-ghost">← Back to series</a>
            </div>

            @include('content::admin.partials.series-breadcrumb', ['show' => $show, 'season' => $season, 'episode' => null])

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

            <form method="POST" action="{{ route('admin.series.seasons.update', [$show, $season]) }}">
                @method('PUT')
                @include('content::admin.seasons.form')
            </form>

            @include('content::admin.seasons.episodes-section')
        </div>
    </div>
</div>

{{-- Same live-refresh behaviour as the movies list: reload on Back
     so a stale browser snapshot can't hide newly-finished
     transcodes, and auto-poll every 30s while any episode in this
     season is still queued or transcoding. --}}
<script>
(function () {
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) location.reload();
    });

    var hasInflight = document.querySelector(
        '.badge[title="Transcode queued"], .badge[title="Transcoding in progress"], .badge[title="Downloading source"]'
    );
    if (hasInflight) {
        setInterval(function () {
            if (!document.hidden) location.reload();
        }, 30000);
    }
})();
</script>
@endsection
