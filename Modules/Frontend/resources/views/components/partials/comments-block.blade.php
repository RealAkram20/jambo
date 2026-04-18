{{--
    Comments thread — flat (no nested replies rendered yet) for the
    episode page. Caller passes:
      $comments    Collection of approved top-level Comment models with user
      $storeRoute  POST endpoint for this episode's comments
      $destroyRouteFn  Closure: fn ($comment) => route for deleting it
--}}
@php
    $comments = $comments ?? collect();
@endphp

<section class="jambo-comments-block section-padding">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <h4 class="main-title text-capitalize mb-0">
                Comments
                @if ($comments->count())
                    <span class="text-muted fw-normal ms-2">· {{ $comments->count() }}</span>
                @endif
            </h4>
        </div>

        {{-- Auth-gated form -------------------------------------- --}}
        @auth
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

            <form method="POST" action="{{ $storeRoute }}" class="mb-4">
                @csrf
                <div class="d-flex gap-3">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold"
                             style="width:40px; height:40px;">
                            {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->username ?? '?', 0, 1)) }}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <textarea name="body" rows="2" class="form-control form-control-sm"
                                  placeholder="Leave a comment..." maxlength="2000"
                                  required minlength="2">{{ old('body') }}</textarea>
                        <div class="text-end mt-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ph ph-paper-plane-tilt me-1"></i> Post
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @else
            <div class="mb-4 p-3 border border-dark rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="text-muted">
                    <i class="ph ph-lock me-1"></i> Sign in to join the conversation.
                </span>
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign in</a>
            </div>
        @endauth

        {{-- Existing comments ------------------------------------ --}}
        @if ($comments->count())
            <div class="jambo-comments-list">
                @foreach ($comments as $c)
                    @php
                        $isMine = auth()->check() && auth()->id() === $c->user_id;
                        $isAdmin = auth()->check() && auth()->user()->hasRole('admin');
                    @endphp
                    <article class="jambo-comment d-flex gap-3 p-3 border-bottom border-dark">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white fw-bold"
                                 style="width:40px; height:40px;">
                                {{ strtoupper(substr($c->user->first_name ?? $c->user->username ?? '?', 0, 1)) }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <strong>{{ $c->user->full_name ?: $c->user->username }}</strong>
                                <small class="text-muted">· {{ $c->created_at?->diffForHumans() }}</small>
                                @if ($isMine || $isAdmin)
                                    <form method="POST" action="{{ $destroyRouteFn($c) }}" class="ms-auto"
                                          onsubmit="return confirm('Remove this comment?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-sm text-danger p-0" title="Delete">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <p class="mb-0 small" style="white-space: pre-wrap;">{{ $c->body }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="text-muted">No comments yet. Be the first to say something.</p>
        @endif
    </div>
</section>
