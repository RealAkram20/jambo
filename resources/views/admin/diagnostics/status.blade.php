@extends('layouts.app')

@section('title', 'System status')

@php
    $human = function (?int $bytes): string {
        if ($bytes === null) return '—';
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) { $n /= 1024; $i++; }
        return number_format($n, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    };

    $bool = function (bool $v): string {
        return $v
            ? '<span class="badge bg-success"><i class="ph ph-check"></i> yes</span>'
            : '<span class="badge bg-danger"><i class="ph ph-x"></i> no</span>';
    };

    $diskPctUsed = ($disk['total_bytes'] && $disk['free_bytes'] !== null)
        ? max(0, min(100, 100 - (int) round($disk['free_bytes'] / $disk['total_bytes'] * 100)))
        : null;
@endphp

@section('content')
<div class="row g-3">
    <div class="col-12">
        <h3 class="mb-3"><i class="ph ph-gauge me-1"></i> System status</h3>
    </div>

    {{-- App / runtime --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Application</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="w-50">Name</th><td>{{ $app['name'] }}</td></tr>
                        <tr><th>Version</th><td><code>{{ $app['version'] }}</code></td></tr>
                        <tr><th>Environment</th><td><span class="badge {{ $app['env'] === 'production' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $app['env'] }}</span></td></tr>
                        <tr>
                            <th>Debug mode</th>
                            <td>
                                @if ($app['debug'])
                                    <span class="badge bg-danger">on</span>
                                    <small class="text-danger ms-2">turn off in production</small>
                                @else
                                    <span class="badge bg-success">off</span>
                                @endif
                            </td>
                        </tr>
                        <tr><th>URL</th><td>{{ $app['url'] }}</td></tr>
                        <tr><th>Timezone / locale</th><td>{{ $app['timezone'] }} / {{ $app['locale'] }}</td></tr>
                        <tr><th>PHP</th><td>{{ $app['php'] }}</td></tr>
                        <tr><th>Laravel</th><td>{{ $app['laravel'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Runtime drivers</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="w-50">Cache driver</th><td>{{ $runtime['cache_driver'] }}</td></tr>
                        <tr><th>Queue driver</th><td>{{ $runtime['queue_driver'] }}</td></tr>
                        <tr><th>Session driver</th><td>{{ $runtime['session_driver'] }}</td></tr>
                        <tr><th>Filesystem</th><td>{{ $runtime['filesystem'] }}</td></tr>
                        <tr><th>Mail mailer</th><td>{{ $runtime['mail_mailer'] }}</td></tr>
                        <tr><th>Broadcast</th><td>{{ $runtime['broadcast'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Database --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Database</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="w-50">Driver</th><td>{{ $database['driver'] }}</td></tr>
                        <tr><th>Database</th><td>{{ $database['name'] ?: '—' }}</td></tr>
                        <tr><th>Connection</th><td>{!! $bool($database['connected']) !!}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Disk --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Disk</strong></div>
            <div class="card-body">
                @if ($diskPctUsed !== null)
                    <div class="d-flex justify-content-between mb-1">
                        <span><strong>{{ $human($disk['free_bytes']) }}</strong> free of {{ $human($disk['total_bytes']) }}</span>
                        <span class="text-secondary">{{ $diskPctUsed }}% used</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar {{ $diskPctUsed > 90 ? 'bg-danger' : ($diskPctUsed > 75 ? 'bg-warning' : 'bg-success') }}"
                             role="progressbar"
                             style="width: {{ $diskPctUsed }}%"></div>
                    </div>
                @else
                    <p class="text-secondary mb-0">Could not read free space (PHP open_basedir or restricted host).</p>
                @endif

                <hr>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="w-50">/storage symlink</th><td>{!! $bool($storage['symlink_present']) !!}</td></tr>
                        <tr><th>storage_path()</th><td><code style="word-break:break-all">{{ $storage['storage_path'] }}</code></td></tr>
                        <tr><th>public_path()</th><td><code style="word-break:break-all">{{ $storage['public_path'] }}</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PHP extensions --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>PHP extensions</strong></div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach ($php_extensions as $ext => $loaded)
                        <div class="col-6 col-md-4">
                            @if ($loaded)
                                <span class="badge bg-success-subtle text-success-emphasis w-100 text-start">
                                    <i class="ph ph-check"></i> {{ $ext }}
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger-emphasis w-100 text-start">
                                    <i class="ph ph-x"></i> {{ $ext }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Modules --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Modules</strong></div>
            <div class="card-body">
                @if (empty($modules))
                    <p class="text-secondary mb-0">No <code>modules_statuses.json</code> found.</p>
                @else
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach ($modules as $name => $enabled)
                                <tr>
                                    <th class="w-50">{{ $name }}</th>
                                    <td>{!! $bool((bool) $enabled) !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
