@extends('layouts.app', ['module_title' => 'Edit Pricing Plan'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">{{ $tier->name }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        {{ $tier->currency }} {{ number_format((float) $tier->price, 2) }} · {{ $tier->periodLabel() }}
                        · Last updated {{ $tier->updated_at?->diffForHumans() }}
                    </p>
                </div>
                <a href="{{ route('admin.subscription-tiers.index') }}" class="btn btn-ghost">← Back to list</a>
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

            <form method="POST" action="{{ route('admin.subscription-tiers.update', $tier) }}">
                @method('PUT')
                @include('subscriptions::admin.tiers.form')
            </form>
        </div>
    </div>
</div>
@endsection
