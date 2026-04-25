@extends('layouts.app', ['module_title' => 'Edit Page', 'isQuillEditor' => true])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">{{ $page->title }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Last updated {{ $page->updated_at?->diffForHumans() }}
                        @if ($page->is_system)
                            · <span class="badge bg-info-subtle text-info-emphasis">System page</span>
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    @if ($page->status === 'published' && $page->slug !== 'footer')
                        <a href="{{ url('/' . $page->slug) }}" target="_blank" class="btn btn-ghost">
                            <i class="ph ph-arrow-square-out me-1"></i> View
                        </a>
                    @endif
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost">← Back to list</a>
                </div>
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

            <form method="POST" action="{{ route('admin.pages.update', $page) }}">
                @method('PUT')
                @include('pages::admin.pages.form')
            </form>
        </div>
    </div>
</div>
@endsection
