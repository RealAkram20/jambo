{{-- Shared form partial for create + edit --}}
@csrf

@php
    // Features are stored as an array but edited as one-per-line text.
    $featuresText = is_array($tier->features)
        ? implode("\n", $tier->features)
        : (string) ($tier->features ?? '');
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Plan Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="name" name="name" value="{{ old('name', $tier->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="sort_order" class="form-label">Sort order <span class="text-danger">*</span></label>
                        <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror"
                            id="sort_order" name="sort_order" value="{{ old('sort_order', $tier->sort_order ?? 20) }}" required>
                        <div class="form-text">Lower numbers show first on the pricing page.</div>
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                        id="slug" name="slug" value="{{ old('slug', $tier->slug) }}">
                    <div class="form-text">Auto-generated from the name if left blank. Used in payment metadata.</div>
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mt-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description" name="description" rows="3">{{ old('description', $tier->description) }}</textarea>
                    <div class="form-text">Appears under the plan name on the public pricing page.</div>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Features</h6></div>
            <div class="card-body">
                <label for="features" class="form-label">Features (one per line)</label>
                <textarea class="form-control @error('features') is-invalid @enderror"
                    id="features" name="features" rows="7"
                    placeholder="Full catalog&#10;Ad-free&#10;HD quality&#10;2 devices">{{ old('features', $featuresText) }}</textarea>
                <div class="form-text">Rendered as a bullet list on the public pricing page.</div>
                @error('features') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Pricing</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-8">
                        <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0"
                            class="form-control @error('price') is-invalid @enderror"
                            id="price" name="price" value="{{ old('price', $tier->price) }}" required>
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-4">
                        <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                        @php
                            $currencies = [
                                'UGX' => 'UGX — Ugandan Shilling',
                                'KES' => 'KES — Kenyan Shilling',
                                'TZS' => 'TZS — Tanzanian Shilling',
                                'RWF' => 'RWF — Rwandan Franc',
                                'USD' => 'USD — US Dollar',
                                'EUR' => 'EUR — Euro',
                                'GBP' => 'GBP — British Pound',
                                'NGN' => 'NGN — Nigerian Naira',
                                'ZAR' => 'ZAR — South African Rand',
                            ];
                            $currentCurrency = strtoupper(old('currency', $tier->currency ?: 'UGX'));
                        @endphp
                        <select name="currency" id="currency"
                            class="form-select @error('currency') is-invalid @enderror" required>
                            @foreach ($currencies as $code => $label)
                                <option value="{{ $code }}" @selected($currentCurrency === $code)>{{ $code }}</option>
                            @endforeach
                            @if (!array_key_exists($currentCurrency, $currencies))
                                <option value="{{ $currentCurrency }}" selected>{{ $currentCurrency }}</option>
                            @endif
                        </select>
                        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label for="billing_period" class="form-label">Billing period <span class="text-danger">*</span></label>
                    <select name="billing_period" id="billing_period" class="form-select @error('billing_period') is-invalid @enderror">
                        @foreach (\Modules\Subscriptions\app\Models\SubscriptionTier::PERIODS as $p)
                            <option value="{{ $p }}" @selected(old('billing_period', $tier->billing_period) === $p)>
                                {{ ucfirst($p) }}
                            </option>
                        @endforeach
                    </select>
                    @error('billing_period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0">Access</h6></div>
            <div class="card-body">
                <label for="access_level" class="form-label">Access level <span class="text-danger">*</span></label>
                <select name="access_level" id="access_level" class="form-select @error('access_level') is-invalid @enderror">
                    <option value="0" @selected(old('access_level', $tier->access_level) == 0)>Free (0)</option>
                    <option value="1" @selected(old('access_level', $tier->access_level) == 1)>Basic (1)</option>
                    <option value="2" @selected(old('access_level', $tier->access_level) == 2)>Premium (2)</option>
                    <option value="3" @selected(old('access_level', $tier->access_level) == 3)>Ultra (3)</option>
                </select>
                <div class="form-text">Higher access levels unlock more content. Movies with a <code>tier_required</code> match against this.</div>
                @error('access_level') <div class="invalid-feedback">{{ $message }}</div> @enderror

                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                        @checked(old('is_active', $tier->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Active — visible on the public pricing page</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="{{ route('admin.subscription-tiers.index') }}" class="btn btn-ghost">← Back to list</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> Save plan
    </button>
</div>
