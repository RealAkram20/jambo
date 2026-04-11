<a href="{{ route('frontend.cast_details') }}">
    <img src="{{ asset('frontend/images/cast/'. $castImage) }}" alt="personality" class="img-fluid object-cover mb-3 rounded-3 personality-img" loading="lazy" />
</a>
<div class="text-center">
    <h6 class="mb-0">
        <a href="{{ route('frontend.cast_details') }}"
            class="font-size-14 text-decoration-none cast-title text-capitalize">{{ $castTitle }}</a>
    </h6>
    <a href="{{ route('frontend.cast_details') }}"
        class="font-size-12 fw-semibold text-decoration-none text-capitalize text-body">{{ $castCategory }}&nbsp;&nbsp;{{ $otherCastCategory ?? '' }}</a>
</div>
