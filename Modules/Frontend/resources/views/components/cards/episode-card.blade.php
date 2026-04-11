<div class="episode-block rounded-3">
    <div class="block-image position-relative z-1">
        <a href="{{ $episodePath }}">
            <img src="{{ asset('frontend/images/'. $showImg) }}" class="img-fluid img-zoom" alt="showImg-{{ $id ?? '' }}" loading="lazy">
        </a>
    </div>

    @if(!empty($episodTitle))
        <h6 class="episode-title fw-medium position-absolute">{{ $episodeNumber }}: {{ $episodTitle }}</h6>
    @endif

    <div class="episode-detail fw-medium position-absolute">
        @if(!empty($episodeTitlesText))
            <h6 class="mt-2 mb-0">{{ $episodeNumber }}: {{ $episodeTitlesText }}</h6>
        @else
            <h6 class="mt-2 mb-0">04 : Island of Secrets</h6>
        @endif

        @if(!empty($episodeDetailText))
            <span class="mb-0 line-count-2 mt-2 small lh-base">{{ $episodeDetailText }}</span>
        @else
            <span class="mb-0 line-count-2 mt-2 small lh-base">
                A flashback episode reveals Captain Roarke’s troubled past as a noble-turned-pirate. His path crossed with the kingdom’s navy captain, who now pursues them with a personal vendetta. Meanwhile, the crew faces an attack from a band of mercenary hunters.
            </span>
        @endif
    </div>
    <div class="position-absolute episode-time font-size-12 fw-bold px-1 rounded-1 d-flex align-items-center z-2 gap-1">
        <i class="ph ph-clock"></i>
        <p class="mb-0">{{ $episodTime }}</p>
    </div>
</div>
