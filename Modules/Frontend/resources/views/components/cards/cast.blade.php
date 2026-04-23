@php
    $castSrc = media_url($castImg, null, 'frontend/images/cast');
@endphp
<div class="iq-cast position-relative">
    <div class="cast-images position-relative">
        <a href="{{ $castLink }}">
            <img src="{{ $castSrc }}" class="img-fluid" alt="castImg" loading="lazy" />
        </a>
    </div>
    <div class="person-detail">
        <h6 class="cast-title fw-500">
            <a href="{{ $castLink }}">
                {{ $castTitle }}
            </a>
        </h6>
        <ul class="d-flex align-items-center justify-content-center gap-2 list-inline p-0 m-0">
            <li>
                {{-- Role subtitle (Actor / Director / etc.) rendered as
                     plain text. No "all actors" route exists yet, so the
                     earlier javascript:void(0) anchor was a dead click. --}}
                <span class="person-cats d-block">{{ $castSubTitle }}</span>
            </li>
        </ul>
    </div>
</div>
