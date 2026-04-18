@php
    // Share the canonical URL of whatever page this modal renders on.
    // Callers can override with $shareUrl / $shareTitle when they want
    // a different target (e.g. the movie detail page even when the
    // modal is invoked from /watch). Defaults fall back to the current
    // request URL and page <title>.
    $shareUrl   = $shareUrl   ?? url()->current();
    $shareTitle = $shareTitle ?? (isset($title) ? $title : config('app.name', 'Jambo'));
    $encodedUrl   = rawurlencode($shareUrl);
    $encodedTitle = rawurlencode($shareTitle);
@endphp
<div class="modal fade view-more-data-modal" id="shareModal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered share-modal">
        <div class="modal-content">
            <div class="modal-header pb-0">
                <h5 class="modal-title" id="exampleModalLabelshare">{{__('streamTag.share')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="share-media-box">
                    <div class="media-box">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}" target="_blank" rel="noopener">
                            <span class="image-icon">
                                <i class="icon-facebook-icon"></i>
                            </span>
                            <span class="titles">{{__('streamTag.facebook')}}</span>
                        </a>
                    </div>
                    <div class="media-box">
                        <a href="https://twitter.com/intent/tweet?url={{ $encodedUrl }}&text={{ $encodedTitle }}" target="_blank" rel="noopener">
                            <span class="image-icon">
                                <i class="icon-twitter-icon"></i>
                            </span>
                            <span class="titles text-center">{{__('streamTag.twitter')}}</span>
                        </a>
                    </div>
                    <div class="media-box">
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $encodedUrl }}" target="_blank" rel="noopener">
                            <span class="image-icon">
                                <i class="icon-instagram-icon"></i>
                            </span>
                            <span class="titles">{{__('streamTag.instagram')}}</span>
                        </a>
                    </div>
                    <div class="media-box">
                        <a href="https://api.whatsapp.com/send?text={{ $encodedTitle }}%20{{ $encodedUrl }}" target="_blank" rel="noopener">
                            <span class="image-icon">
                                <i class="icon-whatsapp-icon"></i>
                            </span>
                            <span class="titles">{{__('streamTag.whatsapp')}}</span>
                        </a>
                    </div>
                </div>
                <div class="copy-link">
                    <h6 id="basic-addon1">{{__('streamTag.copy_link')}}</h6>
                    <div class="input-group mb-0">
                        <input type="text" id="copyInput" class="form-control copy-post-url" placeholder="URL"
                            value="{{ $shareUrl }}"
                            aria-label="Page URL" readonly="">
                        <button class="input-group-text copy-url-btn" id="copyButton"><i class="ph ph-copy-simple"
                                id="copyIcon"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
