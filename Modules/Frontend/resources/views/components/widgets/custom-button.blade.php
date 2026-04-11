@if (isset($linkButton) && $linkButton)
    <div class="iq-button link-button">
        <a href="{{ $buttonUrl }}" class="btn text-capitalize position-relative">
            <span class="button-text">{{ $buttonTitle }}</span>
        </a>
    </div>
@else
    <a href="{{ $buttonUrl }}" class="btn btn-primary text-capitalize position-relative rounded-3">
        @if (isset($notBtncaretIcon))
            <span class="button-text">{{ $buttonTitle }}</span>
        @else
            <span class="d-flex align-items-center gap-2">
                <span class="button-text">{{ $buttonTitle }}</span>
                <i class="ph-fill ph-play fs-6"></i>
            </span>
        @endif
    </a>
@endif
