@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('streamButtons.view_all')])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 my-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="main-title text-capitalize mb-0">{{ __('streamMovies.your_personality') }}</h5>
                        <span class="text-muted">{{ $persons->count() }}</span>
                    </div>
                </div>
            </div>
            <div class="card-style-grid">
                <div class="row row-cols-xl-5 row-cols-md-2 row-cols-1 personality-card">
                    @forelse ($persons as $person)
                        <div class="col mb-4">
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
                        <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No personalities yet.' }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    @include('frontend::components.widgets.mobile-footer')
@endsection
