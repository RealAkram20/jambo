@extends('layouts.app', ['module_title' => 'Notifications'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Notifications</h4>
                        <p class="text-muted mb-0 mt-1" style="font-size:13px;">
                            {{ $unreadCount }} unread · {{ $notifications->total() }} total
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        @if (app()->environment('local'))
                            <form method="POST" action="{{ route('notifications.test-dispatch') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm">
                                    <i class="ph ph-paper-plane-tilt me-1"></i> Send test
                                </button>
                            </form>
                        @endif
                        @if ($unreadCount > 0)
                            <button type="button" class="btn btn-primary btn-sm" id="mark-all-read-btn">
                                <i class="ph ph-check-square me-1"></i> Mark all as read
                            </button>
                        @endif
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif

                <div class="card-body p-0">
                    @if ($notifications->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="ph ph-bell-slash" style="font-size:48px;"></i>
                            <p class="mb-0 mt-3">No notifications yet.</p>
                        </div>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($notifications as $notification)
                                @php($d = (array) $notification->data)
                                <li class="d-flex gap-3 px-4 py-3 border-bottom notification-row" data-id="{{ $notification->id }}"
                                    style="{{ $notification->read_at ? '' : 'background: rgba(26, 152, 255, 0.04);' }}">
                                    <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
                                        style="width:40px;height:40px;background: rgba(26, 152, 255, 0.15); color: var(--bs-primary);">
                                        <i class="ph {{ $d['icon'] ?? 'ph-bell' }} fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-semibold">{{ $d['title'] ?? 'Notification' }}</div>
                                                <div class="text-muted" style="font-size:13px;">{{ $d['message'] ?? '' }}</div>
                                            </div>
                                            <small class="text-muted flex-shrink-0 ms-3">{{ $notification->created_at?->diffForHumans() }}</small>
                                        </div>
                                        @if (!empty($d['action_url']))
                                            <a href="{{ $d['action_url'] }}" class="btn btn-ghost btn-sm mt-2">View details →</a>
                                        @endif
                                    </div>
                                    <div class="flex-shrink-0">
                                        @if (!$notification->read_at)
                                            <span class="badge bg-primary" style="font-size:10px;">NEW</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if ($notifications->hasPages())
                    <div class="card-footer d-flex justify-content-center">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const markAllBtn = document.getElementById('mark-all-read-btn');
    const markAllUrl = @json(route('notifications.mark-all-read'));

    if (markAllBtn) {
        markAllBtn.addEventListener('click', async () => {
            markAllBtn.disabled = true;
            await fetch(markAllUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            location.reload();
        });
    }

    document.querySelectorAll('.notification-row').forEach(row => {
        row.addEventListener('click', async (e) => {
            if (e.target.closest('a')) {
                const id = row.dataset.id;
                if (id) {
                    fetch(`/notifications/${id}/read`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                }
            }
        });
    });
})();
</script>
@endsection
