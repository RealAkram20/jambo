@extends('layouts.app')

@section('title', 'Error log')

@section('content')
<div class="row">
    <div class="col-12">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="card-title mb-0">
                    <i class="ph ph-file-text me-1"></i> Error log
                </h4>
                <small class="text-secondary">
                    Showing the last
                    {{ number_format($tailLimitBytes / 1024) }} KB of each file.
                </small>
            </div>

            <div class="card-body">
                @if (empty($files))
                    <p class="text-secondary mb-0">No log files found in <code>storage/logs/</code>.</p>
                @else
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="list-group">
                                @foreach ($files as $f)
                                    <a href="{{ route('admin.diagnostics.logs', ['file' => $f['name']]) }}"
                                       class="list-group-item list-group-item-action {{ $selected === $f['name'] ? 'active' : '' }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-truncate" title="{{ $f['name'] }}">{{ $f['name'] }}</strong>
                                            <span class="badge bg-secondary">{{ number_format($f['size'] / 1024, 1) }} KB</span>
                                        </div>
                                        <small class="text-muted">
                                            modified {{ \Carbon\Carbon::createFromTimestamp($f['mtime'])->diffForHumans() }}
                                        </small>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-8">
                            @if ($selected)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong>{{ $selected }}</strong>
                                        <span class="text-secondary ms-2">
                                            ({{ number_format($sizeBytes / 1024, 1) }} KB
                                            @if ($truncated)
                                                <span class="text-warning">— showing tail only</span>
                                            @endif)
                                        </span>
                                    </div>
                                    <form action="{{ route('admin.diagnostics.logs.clear', ['file' => $selected]) }}"
                                          method="POST"
                                          onsubmit="return confirm('Clear {{ $selected }}? This empties the file but keeps it on disk.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="ph ph-trash me-1"></i> Clear file
                                        </button>
                                    </form>
                                </div>

                                <pre style="max-height: 60vh; overflow:auto; background:#0d1117; color:#c9d1d9; padding:1rem; border-radius:6px; font-size:0.85em; line-height:1.45;">{{ $tail !== null && $tail !== '' ? $tail : '(file is empty)' }}</pre>
                            @else
                                <p class="text-secondary">Pick a file on the left to view it.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
