@php
    $sectionPaddingClass = $sectionPaddingClass ?? false;
    $items = $continueWatching ?? collect();
@endphp

{{-- Anonymous users and users with no in-progress content don't see
     this section at all — matches Netflix / Prime. --}}
@if ($items->count())
    <section class="continue-watching-block home-continue-watch {{ $sectionPaddingClass ? 'section-padding-top' : '' }}">
        <div class="d-flex align-items-center justify-content-between px-1 mb-2 pb-1 mb-md-4 pb-md-0">
            <h4 class="main-title text-capitalize mb-0 fw-medium">{{ __('sectionTitle.continue_watching') }}</h4>
        </div>
        {{-- data-loop="false" on purpose: in loop mode Swiper clones and
             re-centers slides so the "most recent" item wouldn't be the
             visually leftmost one. Continue Watching has a hard cap of
             6, so a non-looping row reads naturally. --}}
        <div class="position-relative swiper swiper-card" data-slide="7" data-laptop="3" data-tab="4" data-mobile="3"
            data-mobile-sm="3" data-autoplay="false" data-loop="false" data-navigation="true" data-pagination="false">
            <ul class="p-0 swiper-wrapper m-0 list-inline">
                @foreach ($items as $card)
                    <li class="swiper-slide" data-cw-slide>
                        @include('frontend::components.cards.continue-watch-card', [
                            'imagePath'       => $card->imagePath,
                            'progressValue'   => $card->progressPercent . '%',
                            'dataLeftTime'    => $card->minutesLeft,
                            'watchMovieTitle' => $card->title,
                            'watchMovieDate'  => $card->subtitle,
                            'watchLink'       => $card->watchLink,
                            'removeType'      => $card->removeType,
                            'removeId'        => $card->removeId,
                        ])
                    </li>
                @endforeach
            </ul>
            <div class="d-none d-lg-block">
                <div class="swiper-button swiper-button-next"></div>
                <div class="swiper-button swiper-button-prev"></div>
            </div>
        </div>
    </section>

    {{-- Remove-from-list handler: delegated click so Swiper's clones
         (if loop is ever re-enabled) keep working, and the section
         container survives re-renders without rebinding. --}}
    <script>
    (function(){
        var removeEndpoint = {{ Js::from(url('/api/v1/continue-watching')) }};
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        document.addEventListener('click', async function(e) {
            var btn = e.target.closest('[data-cw-remove]');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();

            var type = btn.dataset.cwType;
            var id = btn.dataset.cwId;
            if (!type || !id) return;

            var slide = btn.closest('[data-cw-slide]');
            if (slide) {
                slide.style.transition = 'opacity 0.25s, transform 0.25s';
                slide.style.opacity = '0.3';
                slide.style.pointerEvents = 'none';
            }

            try {
                var res = await fetch(removeEndpoint + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(id), {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                // Remove slide + check if the row is now empty; if so
                // hide the whole section so the user isn't left staring
                // at an empty carousel.
                if (slide) {
                    var section = slide.closest('.continue-watching-block');
                    slide.remove();
                    if (section && !section.querySelector('[data-cw-slide]')) {
                        section.remove();
                    }
                }
            } catch (err) {
                console.warn('[continue-watching] remove failed', err);
                if (slide) {
                    slide.style.opacity = '1';
                    slide.style.pointerEvents = '';
                }
            }
        });
    })();
    </script>
@endif
