{{--
    Reviews & ratings block — used on movie detail and series detail.
    All data is computed server-side; the caller just needs to pass:
      $reviews       Collection of published Review models (with user)
      $reviewStats   ['count' => int, 'avg' => float]
      $myReview      ?Review — the auth user's existing review
      $storeRoute    POST endpoint for this content
      $destroyRoute  DELETE endpoint for the auth user's own review
--}}
@php
    $myReview = $myReview ?? null;
    $reviewStats = $reviewStats ?? ['count' => 0, 'avg' => 0];
    $reviews = $reviews ?? collect();
@endphp

{{-- Star rating styles. Markup is rendered 5→1 with row-reverse so
     hover/checked on a higher star fills every star visually to its
     left via the `~` sibling selector. --}}
<style>
.jambo-star-input {
    display: inline-flex;
    flex-direction: row-reverse;
    gap: 4px;
    font-size: 24px;
}
.jambo-star-input input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.jambo-star-input label {
    cursor: pointer;
    color: #4b5160;
    transition: color .15s ease, transform .15s ease;
    margin: 0;
    line-height: 1;
}
.jambo-star-input label:hover { transform: scale(1.1); }
.jambo-star-input label:hover,
.jambo-star-input label:hover ~ label,
.jambo-star-input input[type="radio"]:checked ~ label {
    color: #ffc107;
}
.jambo-star-input input[type="radio"]:focus-visible + label {
    outline: 2px solid #1A98FF;
    outline-offset: 2px;
    border-radius: 4px;
}
</style>

<section class="jambo-reviews-block section-padding">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h4 class="main-title text-capitalize mb-1">Reviews &amp; Ratings</h4>
                @if ($reviewStats['count'] > 0)
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center gap-1">
                            <i class="ph-fill ph-star text-warning"></i>
                            <strong class="fs-5">{{ number_format($reviewStats['avg'], 1) }}</strong>
                            <small class="text-muted">/ 5</small>
                        </span>
                        <span class="text-muted">· {{ $reviewStats['count'] }} {{ Str::plural('review', $reviewStats['count']) }}</span>
                    </div>
                @else
                    <p class="text-muted mb-0 small">No reviews yet — be the first.</p>
                @endif
            </div>
        </div>

        {{-- Auth-gated write / edit form ------------------------------ --}}
        @auth
            <div class="jambo-review-form mb-4 p-3 p-md-4 border border-dark rounded-3">
                @if (session('success'))
                    <div class="alert alert-success py-2 small mb-3">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <h6 class="mb-3">{{ $myReview ? 'Your review' : 'Write a review' }}</h6>

                <form method="POST" action="{{ $storeRoute }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">Your rating</label>
                        @php $currentStars = (int) old('stars', $myReview?->stars ?? 0); @endphp
                        {{-- Rendered 5→1 (right-to-left) with flex-direction: row-reverse
                             so the CSS-only "fill stars to the left of hovered/checked"
                             trick (using `~`) reads visually as 1→5 left-to-right. --}}
                        <div class="jambo-star-input" role="radiogroup" aria-label="Rating">
                            @for ($i = 5; $i >= 1; $i--)
                                <input type="radio" id="jambo-star-{{ $i }}" name="stars" value="{{ $i }}"
                                       {{ $currentStars === $i ? 'checked' : '' }} required>
                                <label for="jambo-star-{{ $i }}" title="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
                                    <i class="ph-fill ph-star"></i>
                                </label>
                            @endfor
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="text" name="title" class="form-control form-control-sm"
                               placeholder="Title (optional)" maxlength="200"
                               value="{{ old('title', $myReview?->title) }}">
                    </div>

                    <div class="mb-3">
                        <textarea name="body" rows="3" class="form-control form-control-sm"
                                  placeholder="Share what you thought of it..." maxlength="4000"
                                  required minlength="3">{{ old('body', $myReview?->body) }}</textarea>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            {{ $myReview ? 'Update review' : 'Post review' }}
                        </button>
                        @if ($myReview)
                            <form method="POST" action="{{ $destroyRoute }}" class="d-inline"
                                  onsubmit="return confirm('Remove your review?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </form>
            </div>
        @else
            <div class="mb-4 p-3 border border-dark rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="text-muted">
                    <i class="ph ph-lock me-1"></i>
                    Sign in to leave a review.
                </span>
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign in</a>
            </div>
        @endauth

        {{-- Existing reviews list ------------------------------------ --}}
        @if ($reviews->count())
            <div class="jambo-reviews-list">
                @foreach ($reviews as $r)
                    <article class="jambo-review d-flex gap-3 p-3 border-bottom border-dark">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold"
                                 style="width:40px; height:40px;">
                                {{ strtoupper(substr($r->user->first_name ?? $r->user->username ?? '?', 0, 1)) }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <strong>{{ $r->user->full_name ?: $r->user->username }}</strong>
                                @if ($r->stars)
                                    <span class="d-inline-flex align-items-center gap-1 small text-warning">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="ph{{ $i <= $r->stars ? '-fill' : '' }} ph-star"></i>
                                        @endfor
                                    </span>
                                @endif
                                <small class="text-muted ms-auto">{{ $r->created_at?->diffForHumans() }}</small>
                            </div>
                            @if ($r->title)
                                <h6 class="mb-1">{{ $r->title }}</h6>
                            @endif
                            <p class="mb-0 text-muted small">{{ $r->body }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
