@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Payment settings</h4>
                    <p class="text-muted mb-0 mt-1" style="font-size:13px;">Configure payment gateways and view recent orders.</p>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success mb-3">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.payments.update') }}">
                        @csrf

                        <h6 class="mb-3 mt-2">PesaPal</h6>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="pesapal_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="pesapal_enabled" name="pesapal_enabled" value="1" @checked($values['pesapal_enabled'])>
                            <label class="form-check-label" for="pesapal_enabled">Enable PesaPal</label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="pesapal_environment">Environment</label>
                                <select class="form-select" name="pesapal_environment" id="pesapal_environment">
                                    <option value="sandbox" @selected($values['pesapal_environment'] === 'sandbox')>Sandbox (testing)</option>
                                    <option value="live" @selected($values['pesapal_environment'] === 'live')>Live (production)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="currency">Currency</label>
                                <input type="text" class="form-control" id="currency" name="currency" value="{{ $values['currency'] }}" maxlength="3" placeholder="KES">
                                <div class="form-text">ISO 4217 code. KES, USD, EUR, etc.</div>
                            </div>
                        </div>

                        <div class="form-row mt-3">
                            <label class="form-label" for="pesapal_consumer_key">Consumer key</label>
                            <input type="text" class="form-control" id="pesapal_consumer_key" name="pesapal_consumer_key" value="{{ $values['pesapal_consumer_key'] }}" autocomplete="off">
                        </div>

                        <div class="form-row mt-3">
                            <label class="form-label" for="pesapal_consumer_secret">Consumer secret</label>
                            <input type="password" class="form-control" id="pesapal_consumer_secret" name="pesapal_consumer_secret" placeholder="{{ $values['pesapal_consumer_secret_set'] ? '•••••• (leave blank to keep current)' : 'Enter secret' }}" autocomplete="new-password">
                            <div class="form-text">Stored encrypted. Leave blank to keep the current secret unchanged.</div>
                        </div>

                        <div class="form-row mt-3">
                            <label class="form-label">IPN notification ID</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control" value="{{ $values['pesapal_ipn_id'] ?: 'not registered' }}" readonly style="background:#0b0f17;color:#adafb8;">
                            </div>
                            <div class="form-text">Register your IPN with PesaPal after saving credentials. Uses <code>{{ route('payment.ipn') }}</code> as the notification URL.</div>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">Save settings</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.payments.register-ipn') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" @disabled(!$values['pesapal_enabled'] || !$values['pesapal_consumer_key'])>
                            <i class="ph ph-link me-1"></i> Register IPN with PesaPal
                        </button>
                        <span class="text-muted ms-2" style="font-size:12px;">Only needed after first setup or if you rotate credentials.</span>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Latest orders</h5>
                    <a href="{{ route('admin.payments.orders') }}" class="btn btn-outline-secondary btn-sm">
                        View all <i class="ph ph-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if ($recentOrders->isEmpty())
                        <div class="p-4 text-muted text-center" style="font-size:13px;">No orders yet.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>User</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th>Gateway</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td><code>{{ $order->merchant_reference }}</code></td>
                                            <td>{{ $order->user_id }}</td>
                                            <td class="text-end">{{ number_format($order->amount, 2) }} {{ $order->currency }}</td>
                                            <td>
                                                <span class="badge @class([
                                                    'bg-success' => $order->status === 'completed',
                                                    'bg-warning' => $order->status === 'pending',
                                                    'bg-danger' => $order->status === 'failed',
                                                    'bg-secondary' => $order->status === 'cancelled',
                                                ])">{{ $order->status }}</span>
                                            </td>
                                            <td>{{ $order->payment_gateway }}</td>
                                            <td>{{ $order->created_at?->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
