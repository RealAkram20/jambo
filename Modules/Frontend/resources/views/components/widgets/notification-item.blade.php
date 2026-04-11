<li class="notification-item">
    <div class="d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-md-center flex-md-row flex-column gap-3">
            <div class="notification-image flex-shrink-0">
                <a href="{{ url('movie-detail') }}" class="link-overlay" data-user_id="12" data-notification_id="1">
                    <img src="{{ asset('frontend/images/media/' . $notificationImage) }}" 
                         alt="image"
                         class="img-fluid object-cover result-image">
                </a>
            </div>
            <div class="notification-message">
                <a href="{{ url('movie-detail') }}" class="link-overlay message" data-user_id="12" data-notification_id="1">
                    {{ __('streamEpisode.new_episode_is_released') }} {{ $title }}
                </a>
                <span class="d-block">
                    {{ $days }}
                </span>
            </div>
        </div>
        <div class="notification-actions flex-shrink-0">
            <div class="d-flex justify-content-center align-items-center gap-3">
                <button type="button" 
                        class="btn btn-secondary btn-circle border notification-action-btn"
                        data-user_id="12"
                        data-notification_id="1"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        data-bs-title="{{ __('sectionTitle.mark_as_read_tooltip') }}">
                    <i class="icon-eye-2"></i>
                </button>
            </div>
        </div>
    </div>
</li>