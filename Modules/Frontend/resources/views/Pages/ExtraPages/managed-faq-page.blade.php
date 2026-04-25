@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => $page->title])

@php
    // Drop empty rows so an admin who left a slot blank doesn't get an
    // empty accordion bar on the public page.
    $questions = collect($page->metaValue('questions', []))
        ->filter(fn ($r) => trim((string) ($r['q'] ?? '')) !== '' || trim((string) ($r['a'] ?? '')) !== '')
        ->values();
@endphp

@section('content')
    <div class="section-padding">
        <div class="container">
            @if ($questions->isEmpty())
                <p class="text-center text-muted">No questions have been added yet.</p>
            @else
                <div class="iq-accordian iq-accordian-square">
                    @foreach ($questions as $i => $row)
                        <div class="iq-accordian-block {{ $i === 0 ? 'iq-active' : '' }}">
                            <div class="iq-accordian-title text-capitalize d-flex justify-content-between align-items-center">
                                <div class="iq-icon-right">
                                    <i aria-hidden="true" class="ph ph-minus active"></i>
                                    <i aria-hidden="true" class="ph ph-plus inactive"></i>
                                </div>
                                <span class="mb-0 accordian-title">{{ $row['q'] ?? '' }}</span>
                            </div>
                            <div class="iq-accordian-details" style="display: {{ $i === 0 ? 'block' : 'none' }};">
                                <p class="mb-0">{!! nl2br(e($row['a'] ?? '')) !!}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @include('frontend::components.widgets.mobile-footer')
@endsection
