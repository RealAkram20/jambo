@extends('layouts.app', ['module_title' => 'Order ' . $order->merchant_reference])

@section('content')
@php
    // Resolve subscription-tier context even when there's no
    // linkedSubscription yet (pending / failed orders) — we pull the
    // tier name from metadata if the order recorded it at createOrder
    // time so the page has context either way.
    $tierName = $order->metadata['tier_name'] ?? null;
    $tierSlug = $order->metadata['tier_slug'] ?? null;
    $adminNotes = $order->metadata['admin_notes'] ?? '';
    $statusOverrides = $order->metadata['status_overrides'] ?? [];
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.payments.orders') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ph ph-arrow-left me-1"></i> Back to orders
                </a>
                <h4 class="m-0 ms-2">{{ $order->merchant_reference }}</h4>
                <span class="badge @class([
                    'bg-success' => $order->status === 'completed',
                    'bg-warning' => $order->status === 'pending',
                    'bg-danger' => $order->status === 'failed',
                    'bg-secondary' => $order->status === 'cancelled',
                ])">{{ ucfirst($order->status) }}</span>
            </div>
        </div>

        @if (session('success'))
            <div class="col-12 mb-3"><div class="alert alert-success mb-0">{{ session('success') }}</div></div>
        @endif
        @if (session('error'))
            <div class="col-12 mb-3"><div class="alert alert-danger mb-0">{{ session('error') }}</div></div>
        @endif

        {{-- Left column: the order facts + edit form. --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3" style="font-size:13px;">
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Amount</div>
                            <div class="fs-5 fw-semibold">
                                {{ number_format((float) $order->amount, 0) }}
                                <span class="text-muted fs-6">{{ $order->currency }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Gateway</div>
                            <div>
                                {{ $order->payment_gateway }}
                                @if ($order->payment_method)
                                    <span class="badge bg-info-subtle text-info-emphasis ms-2">{{ $order->payment_method }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Merchant reference</div>
                            <code>{{ $order->merchant_reference }}</code>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Tracking ID</div>
                            <code>{{ $order->order_tracking_id ?: '—' }}</code>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Confirmation code</div>
                            <code>{{ $order->confirmation_code ?: '—' }}</code>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Created</div>
                            {{ $order->created_at?->format('d M Y, H:i:s') }}
                            <span class="text-muted">({{ $order->created_at?->diffForHumans() }})</span>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Last updated</div>
                            {{ $order->updated_at?->format('d M Y, H:i:s') }}
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Payable</div>
                            @if ($tierName)
                                {{ $tierName }}
                                @if ($tierSlug)
                                    <code class="text-muted ms-1" style="font-size:11px;">{{ $tierSlug }}</code>
                                @endif
                            @elseif ($order->payable_type)
                                <code>{{ class_basename($order->payable_type) }}:{{ $order->payable_id }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Edit form. Only mutable fields; see controller comment
                 for why amount / merchant ref / tracking id are locked. --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.payments.orders.update', $order) }}">
                        @csrf
                        @method('PATCH')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="pending" @selected(old('status', $order->status) === 'pending')>Pending</option>
                                    <option value="completed" @selected(old('status', $order->status) === 'completed')>Completed</option>
                                    <option value="failed" @selected(old('status', $order->status) === 'failed')>Failed</option>
                                    <option value="cancelled" @selected(old('status', $order->status) === 'cancelled')>Cancelled</option>
                                </select>
                                <div class="form-text text-warning" style="font-size:11px;">
                                    <i class="ph ph-warning-circle"></i>
                                    Manual override. For the normal catch-up flow, use <strong>Reconcile with gateway</strong> instead — it updates from PesaPal's record of the payment.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment method</label>
                                <input type="text" name="payment_method" class="form-control"
                                    value="{{ old('payment_method', $order->payment_method) }}"
                                    placeholder="card / mpesa / airtel / …" maxlength="50">
                                <div class="form-text" style="font-size:11px;">
                                    Free text. Populated automatically when the gateway reports it.
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Admin notes</label>
                                <textarea name="admin_notes" class="form-control" rows="3" maxlength="2000"
                                    placeholder="e.g. 'Reconciled by hand on 2026-04-26 after PesaPal dashboard showed completed but IPN never arrived.'">{{ old('admin_notes', $adminNotes) }}</textarea>
                                <div class="form-text" style="font-size:11px;">
                                    Saved into <code>metadata.admin_notes</code> alongside who wrote it + timestamp.
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="ph ph-floppy-disk me-1"></i> Save changes
                            </button>
                            <a href="{{ route('admin.payments.orders.show', $order) }}" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Override audit trail: every manual status flip leaves a
                 breadcrumb here so disputes can be reconstructed. --}}
            @if (!empty($statusOverrides))
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Override history</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">
                                    <th>When</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_reverse($statusOverrides) as $entry)
                                    <tr style="font-size:12px;">
                                        <td>{{ \Illuminate\Support\Carbon::parse($entry['at'])->format('d M Y, H:i') }}</td>
                                        <td><span class="badge bg-secondary">{{ $entry['from'] ?? '—' }}</span></td>
                                        <td><span class="badge bg-primary">{{ $entry['to'] ?? '—' }}</span></td>
                                        <td><code>{{ $entry['by'] ?? '—' }}</code></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Raw gateway payload — the source of truth for debugging. --}}
            @if ($order->raw_response)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Gateway response</h5>
                    </div>
                    <div class="card-body p-0">
                        <pre class="mb-0 p-3" style="background:#0b0f17;color:#d3d6dc;font-size:11px;max-height:360px;overflow:auto;">{{ json_encode($order->raw_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right column: user info + action buttons (reconcile/delete). --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer</h5>
                </div>
                <div class="card-body" style="font-size:13px;">
                    @if ($order->user)
                        @php
                            $name = trim(($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? ''));
                        @endphp
                        <div class="fw-semibold">{{ $name ?: $order->user->username }}</div>
                        <div class="text-muted" style="font-size:12px;">
                            <i class="ph ph-envelope-simple"></i> {{ $order->user->email }}
                        </div>
                        @if ($order->user->phone)
                            <div class="text-muted" style="font-size:12px;">
                                <i class="ph ph-phone"></i> {{ $order->user->phone }}
                            </div>
                        @endif
                        <div class="text-muted mt-2" style="font-size:11px;">
                            User ID: {{ $order->user->id }}
                        </div>
                    @else
                        <span class="text-muted">User #{{ $order->user_id }} (deleted)</span>
                    @endif
                </div>
            </div>

            @if ($linkedSubscription)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Activated subscription</h5>
                    </div>
                    <div class="card-body" style="font-size:13px;">
                        <div class="fw-semibold">{{ $linkedSubscription->tier?->name ?: 'Unknown tier' }}</div>
                        <div class="mt-2">
                            <span class="badge @class([
                                'bg-success' => $linkedSubscription->status === 'active',
                                'bg-secondary' => $linkedSubscription->status !== 'active',
                            ])">{{ ucfirst($linkedSubscription->status) }}</span>
                            @if ($linkedSubscription->ends_at)
                                <span class="text-muted ms-2" style="font-size:12px;">
                                    until {{ $linkedSubscription->ends_at->format('d M Y') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    {{-- Reconcile = re-poll PesaPal and sync status. The
                         canonical fix for "stuck pending" orders. --}}
                    <form method="POST" action="{{ route('admin.payments.orders.reconcile', $order) }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-100"
                            @disabled(!$order->order_tracking_id)>
                            <i class="ph ph-arrows-clockwise me-1"></i> Reconcile with gateway
                        </button>
                        <div class="form-text" style="font-size:11px;">
                            Re-polls PesaPal for the current status and syncs this record. Safe to run anytime.
                        </div>
                    </form>

                    {{-- Delete. Guard-railed server-side — completed
                         orders and orders with linked subscriptions are
                         rejected. The confirm() is just belt-and-braces. --}}
                    <form method="POST" action="{{ route('admin.payments.orders.destroy', $order) }}" class="m-0"
                        onsubmit="return confirm('Delete this order permanently? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100 mt-2"
                            @disabled($order->status === 'completed' || $linkedSubscription)>
                            <i class="ph ph-trash me-1"></i> Delete order
                        </button>
                        <div class="form-text" style="font-size:11px;">
                            @if ($linkedSubscription)
                                Can't delete — this order activated a subscription. Cancel the subscription first.
                            @elseif ($order->status === 'completed')
                                Completed orders are locked to preserve the audit trail.
                            @else
                                Permanent. Use only for unused pending / failed / cancelled orders.
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
