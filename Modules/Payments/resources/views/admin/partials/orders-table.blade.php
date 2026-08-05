{{-- Orders table + pagination. Rendered inside #orders-results on the
     full page, and re-rendered alone for AJAX filter/search requests
     (see PaymentSettingsController::orders). Keep this file free of
     anything that assumes the surrounding page (no @section/@push). --}}
<div class="table-responsive">
    <table class="table custom-table align-middle mb-0">
        <thead>
            <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                <th>Reference</th>
                <th>User</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
                <th>Gateway</th>
                <th>Method</th>
                <th>Created</th>
                <th class="text-end" style="width:88px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                @php
                    $name = trim(($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? ''));
                @endphp
                <tr>
                    <td>
                        <code style="font-size:12px;">{{ $order->merchant_reference }}</code>
                        @if ($order->order_tracking_id)
                            <div class="text-muted" style="font-size:11px;">
                                Tracking: <code>{{ \Illuminate\Support\Str::limit($order->order_tracking_id, 20) }}</code>
                            </div>
                        @endif
                    </td>
                    <td>
                        @if ($order->user)
                            <div class="fw-semibold">{{ $name ?: $order->user->username }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $order->user->email }}</div>
                        @elseif ($order->customer_name || $order->customer_email)
                            {{-- Manual / guest order: no account, but the
                                 snapshot columns still identify the payer. --}}
                            <div class="fw-semibold">{{ $order->customer_name ?: $order->customer_username }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $order->customer_email }}</div>
                        @else
                            <span class="text-muted">User #{{ $order->user_id }} (deleted)</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <strong>{{ number_format((float) $order->amount, 0) }}</strong>
                        <span class="text-muted" style="font-size:11px;">{{ $order->currency }}</span>
                    </td>
                    <td>
                        <span class="badge @class([
                            'bg-success' => $order->status === 'completed',
                            'bg-warning' => $order->status === 'pending',
                            'bg-danger' => $order->status === 'failed',
                            'bg-secondary' => $order->status === 'cancelled',
                        ])">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td><span class="text-muted" style="font-size:12px;">{{ $order->payment_gateway }}</span></td>
                    <td>
                        @if ($order->payment_method)
                            <span class="badge bg-info-subtle text-info-emphasis">{{ $order->payment_method }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--bs-secondary);">
                        {{ $order->created_at?->diffForHumans() }}
                        <div class="text-muted" style="font-size:10px;">{{ $order->created_at?->format('d M Y H:i') }}</div>
                    </td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <a href="{{ route('admin.payments.orders.show', $order) }}"
                                class="btn btn-sm btn-outline-primary"
                                title="Open full order details">
                                <i class="ph ph-eye"></i>
                            </a>
                            {{-- Inline peek — expands raw payload without
                                 navigating away. View button above is
                                 the full admin page. --}}
                            <button class="btn btn-sm btn-outline-secondary"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#order-detail-{{ $order->id }}"
                                    aria-expanded="false"
                                    aria-controls="order-detail-{{ $order->id }}"
                                    title="Quick peek at raw gateway payload">
                                <i class="ph ph-caret-down"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="collapse" id="order-detail-{{ $order->id }}">
                    <td colspan="8" style="background:#0b0f17;">
                        <div class="p-3">
                            <div class="row g-3" style="font-size:12px;">
                                <div class="col-md-4">
                                    <span class="text-muted d-block" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Confirmation</span>
                                    <code>{{ $order->confirmation_code ?: '—' }}</code>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted d-block" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Payable</span>
                                    <code>{{ $order->payable_type ? (class_basename($order->payable_type) . ':' . $order->payable_id) : '—' }}</code>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted d-block" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Updated</span>
                                    {{ $order->updated_at?->format('d M Y H:i:s') }}
                                </div>
                            </div>
                            @if ($order->metadata)
                                <div class="mt-3">
                                    <span class="text-muted d-block" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Metadata</span>
                                    <pre class="mb-0 mt-1 p-2 rounded" style="background:#141923;border:1px solid #1f2738;font-size:11px;color:#d3d6dc;max-height:200px;overflow:auto;">{{ json_encode($order->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endif
                            @if ($order->raw_response)
                                <div class="mt-3">
                                    <span class="text-muted d-block" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Gateway response</span>
                                    <pre class="mb-0 mt-1 p-2 rounded" style="background:#141923;border:1px solid #1f2738;font-size:11px;color:#d3d6dc;max-height:300px;overflow:auto;">{{ json_encode($order->raw_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted" style="font-size:13px;">
                        No orders match these filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($orders->hasPages())
    <div class="d-flex justify-content-center pt-3">
        {{ $orders->links() }}
    </div>
@endif
