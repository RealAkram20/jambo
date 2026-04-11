<div class="iq-watching-block">
    <div class="block-images position-relative">
        <div class="iq-image-box overly-images">
            <a href="movie-detail" class="d-block">
                <img src="{{asset('frontend/images/media/' .$imagePath)}}" alt="movie-card" class="w-100 d-block border-0 rounded-3 continue-image"
                    loading="lazy">
            </a>
        </div>
        <div class="iq-preogress">
            <span class="px-2 text-white fw-semibold font-size-14 iq-progress-left-data">{{$dataLeftTime}} m
                Left</span>
            <div class="d-flex align-items-center justify-content-between px-2 mb-1">
                <ul class="list-inline m-0 p-0 d-flex row-gap-1 column-gap-3 flex-wrap movie-list-item">
                    <li class="iq-preogress-movie-title position-relative font-size-14"><span
                            class="text-capitalize fw-semibold ">{{$watchMovieTitle}}</span></li>
                    <li class="flex-shrink-0 fw-semibold font-size-14">
                        <span>
                            {{$watchMovieDate}}
                        </span>
                    </li>
                </ul>
                <a>
                    <i class="ph-fill ph-play iq-preogress-play-btn fs-6"></i>
                </a>
            </div>
            <div class="progress" role="progressbar" aria-label="Example 2px high" aria-valuenow="25" aria-valuemin="0"
                aria-valuemax="100" style="height: 2px">
                <div class="progress-bar" style="width: {{$progressValue}}"></div>
            </div>
        </div>
        <div class="close-icon-section">
            <div class="position-absolute d-flex align-items-center justify-content-center iq-watching-close-icon" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="{{ __('sectionTitle.remove_from_list') }}" data-bs-original-title="{{ __('sectionTitle.remove_from_list') }}">
                <i class="ph ph-x font-size-14 fw-bold align-middle"></i>
            </div>
        </div>

    </div>
</div>
