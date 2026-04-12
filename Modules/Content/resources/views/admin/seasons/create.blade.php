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
                <a href="{{ route('admin.shows.edit', $show) }}" class="btn btn-ghost">← Back to show</a>
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

            <form method="POST" action="{{ route('admin.seasons.store') }}">
                <input type="hidden" name="show_id" value="{{ $show->id }}">
                @include('content::admin.seasons.form')
            </form>
        </div>
    </div>
</div>
@endsection
