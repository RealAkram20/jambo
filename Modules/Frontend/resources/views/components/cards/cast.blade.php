<div class="iq-cast position-relative">
    <div class="cast-images position-relative">
        <a href="{{ $castLink }}">
            <img src="{{ asset('frontend/images/cast/' . $castImg) }}" class="img-fluid" alt="castImg" loading="lazy" />
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
                <a href="javascript:void(0)" class="person-cats d-block">
                    {{ $castSubTitle }}
                </a>
            </li>
        </ul>
    </div>
</div>
