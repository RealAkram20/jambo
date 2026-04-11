@if($isUploadImageDefault ?? false)
<div class="streamit-upload form-field form-group position-relative media-attachment-video media-option tvshow_genre_thumbnail_id_field">
    <label class="form-label">{{ $upload_image_name }}</label>
    
    <!-- Hidden file input (per-instance) -->
    <input type="file" class="d-none upload_video_id">

    <!-- Button to trigger file input -->
    <div>
        <a href="#" class="d-flex align-items-center flex-column justify-content-center gap-4 button streamit_upload_video_button tips edit-button">
            <img class="tvshow_genre_thumbnail_previews img-fluid object-cover"  src="{{ asset('dashboard/images/movie-thumb/krishna.webp') }}" alt="image"
                style="width: 150px; height: 150px; object-fit: cover;"></a>
    </div>


    <!-- Hidden file input element (per-instance) -->
    <input type="file" class="file_upload" style="display:none;" accept="image/*">

    <div class="streamit_media_preview-wrap">
        <div class="streamit_media_preview">
            <!-- Remove button (per-instance) -->
            <button class="button remove_tvshow_genre_thumbnail_buttons streamit_remove_video_button tips position-absolute" style="display: block;">
                <span class="remove-media-icon">X</span>
            </button>
        </div>
    </div>
</div>
@else
<div class="streamit-upload form-group position-relative tvshow_genre_thumbnail_id_field">
    <label class="form-label flex-grow-1">{{ $upload_image_name }}</label>

    @if($isRequiredStar ?? false)
        <span> *</span>
    @endif

   <!-- Hidden file input (per-instance) -->
    <input type="file" class="d-none">

    <!-- Button to trigger file input -->
    <div>
        <a href="#" class="d-flex align-items-center flex-column justify-content-center gap-4 button streamit_upload_video_button tips">
            <!-- Default SVG icon (visible before image is selected) -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="img-icon d-block">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            <!-- Default text (visible before image is selected) -->
            <span class="d-block">{{__('form.choose-upload')}}</span>
        </a>
    </div>

    <!-- Hidden file input element (per-instance) -->
    <input type="file" class="file_upload" style="display:none;" accept="image/*">

    <div class="streamit_media_preview-wrap">
        <div class="streamit_media_preview">
            <!-- Image preview (initially hidden) -->
             <img class="tvshow_genre_thumbnail_preview"src="{{ asset('dashboard/images/movie-thumb/gameofheros.webp') }}" alt="image1"
                style="display: none; max-width: 150px; max-height: 150px; object-fit: cover;" />
            <button class="button remove_tvshow_genre_thumbnail_button streamit_remove_video_button position-absolute" style="display: none;">
                <span class="remove-media-icon">X</span>
            </button>
        </div>
    </div>
</div>
@endif
