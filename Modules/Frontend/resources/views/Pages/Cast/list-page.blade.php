@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.cast')])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="main-title text-capitalize mb-0">{{ __('frontendheader.cast') }}</h4>
                <span class="text-muted">{{ $persons->count() }}</span>
            </div>
            <div class="data-listing row gy-3 row-cols-3 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-5">
                @forelse ($persons as $person)
                    <div class="col">
                        @include('frontend::components.cards.cast', [
                            'castImg' => $person->photo_url ?: 'ava-monroe.webp',
                            'id' => $person->id,
                            'castTime' => $person->movies_count + $person->shows_count,
                            'castTitle' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                            'castSubTitle' => $person->known_for ?: '',
                            'castLink' => route('frontend.cast_details', $person->slug),
                        ])
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No cast yet.' }}</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
