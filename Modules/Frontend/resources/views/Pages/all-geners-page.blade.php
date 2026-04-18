@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('streamButtons.view_all')])

@section('content')
    <section class="section-padding">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 my-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="main-title text-capitalize mb-0">{{ __('frontendheader.geners') }}</h5>
                        <span class="text-muted">{{ $genres->count() }} {{ __('streamTag.genre') }}</span>
                    </div>
                </div>
            </div>
            <div class="row row-cols-xl-5 row-cols-md-2 row-cols-1 geners-card geners-style-grid">
                @forelse ($genres as $genre)
                    <div class="col slide-items">
                        @include('frontend::components.cards.genres-card', [
                            'genersTitle' => $genre->name,
                            'genersImage' => $genre->featured_image_url ?: 'media/rabbit.webp',
                            'genersUrl' => route('frontend.genres', $genre->slug),
                        ])
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">{{ __('streamTag.no_results') ?? 'No genres yet.' }}</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
