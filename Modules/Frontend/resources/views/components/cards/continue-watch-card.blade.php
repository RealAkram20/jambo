@php
    // Image path handling — accepts absolute URLs (external CDNs) and
    // relative storage paths (e.g. "media/episode/foo.webp"). Falls
    // back to bundled placeholder art when the content record has
    // nothing set.
    $cwImg = $imagePath ?? 'gameofhero.webp';
    $cwSrc = \Illuminate\Support\Str::startsWith($cwImg, ['http://', 'https://'])
        ? $cwImg
        : asset('frontend/images/' . (\Illuminate\Support\Str::startsWith($cwImg, 'media/') ? '' : 'media/') . $cwImg);
    $cwLink = $watchLink ?? '#';
    $cwProgress = $progressValue ?? '0%';
    $cwLeft = $dataLeftTime ?? 0;
    $cwTitle = $watchMovieTitle ?? '';
    $cwSubtitle = $watchMovieDate ?? '';
@endphp
<div class="iq-watching-block">
    <div class="block-images position-relative">
        <div class="iq-image-box overly-images">
            <a href="{{ $cwLink }}" class="d-block" aria-label="Resume watching {{ $cwTitle }}">
                <img src="{{ $cwSrc }}" alt="{{ $cwTitle }}" class="w-100 d-block border-0 rounded-3 continue-image"
                    loading="lazy">
            </a>
        </div>
        <div class="iq-preogress">
            <span class="px-2 text-white fw-semibold font-size-14 iq-progress-left-data">{{ $cwLeft }} m Left</span>
            <div class="d-flex align-items-center justify-content-between px-2 mb-1">
                <ul class="list-inline m-0 p-0 d-flex row-gap-1 column-gap-3 flex-wrap movie-list-item">
                    <li class="iq-preogress-movie-title position-relative font-size-14">
                        <span class="text-capitalize fw-semibold">{{ $cwTitle }}</span>
                    </li>
                    @if ($cwSubtitle)
                        <li class="flex-shrink-0 fw-semibold font-size-14">
                            <span>{{ $cwSubtitle }}</span>
                        </li>
                    @endif
                </ul>
                <a href="{{ $cwLink }}" class="text-white" aria-label="Resume">
                    <i class="ph-fill ph-play iq-preogress-play-btn fs-6"></i>
                </a>
            </div>
            <div class="progress" role="progressbar" aria-label="Playback progress"
                aria-valuenow="{{ (int) filter_var($cwProgress, FILTER_SANITIZE_NUMBER_INT) }}" aria-valuemin="0"
                aria-valuemax="100" style="height: 2px">
                <div class="progress-bar" style="width: {{ $cwProgress }}"></div>
            </div>
        </div>
        <div class="close-icon-section">
            {{-- Real button (not a <div>) so keyboard / screen-reader
                 users can trigger it. JS handler below the section
                 catches clicks via [data-cw-remove], DELETEs the
                 watch_history row(s), and fades the slide out. --}}
            <button type="button"
                class="position-absolute d-flex align-items-center justify-content-center iq-watching-close-icon border-0"
                data-cw-remove
                data-cw-type="{{ $removeType ?? '' }}"
                data-cw-id="{{ $removeId ?? '' }}"
                data-bs-toggle="tooltip" data-bs-placement="left"
                aria-label="{{ __('sectionTitle.remove_from_list') }}"
                data-bs-original-title="{{ __('sectionTitle.remove_from_list') }}">
                <i class="ph ph-x font-size-14 fw-bold align-middle"></i>
            </button>
        </div>
    </div>
</div>
