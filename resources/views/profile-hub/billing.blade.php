@include('profile-hub._layout', ['pageTitle' => 'Billing', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Order history</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    Every charge on your account. Click a row to see the invoice.
                </p>
            </div>
            <i class="ph ph-receipt fs-2 text-muted"></i>
        </div>

        @if ($orders->count())
            <div class="table-responsive">
                <table class="table table-borderless table-hover align-middle small mb-0">
                    <thead class="text-muted">
                        <tr>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $tierName = $order->payable?->tier?->name ?? '—';
                                $statusCls = $order->status === 'completed' ? 'bg-success' : 'bg-warning';
                            @endphp
                            <tr>
                                <td>{{ $order->created_at?->format('M j, Y') }}</td>
                                <td>{{ $tierName }}</td>
                                <td>{{ $order->currency ?? 'USD' }} {{ number_format($order->amount, 2) }}</td>
                                <td><span class="badge {{ $statusCls }}">{{ ucfirst($order->status) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('profile.invoice', ['username' => $user->username, 'orderId' => $order->id]) }}"
                                       class="btn btn-link btn-sm p-0 text-primary">
                                        View <i class="ph ph-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $orders->links() }}
            </div>
        @else
            <p class="text-muted mb-2">No orders yet.</p>
            <a href="{{ route('frontend.pricing-page') }}" class="btn btn-primary btn-sm">Browse plans</a>
        @endif
    </div>
@endsection
