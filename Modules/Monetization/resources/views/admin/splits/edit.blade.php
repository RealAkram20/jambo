@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Splits — {{ $title->title }}</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        {{ ucfirst($type) }}
                        @if ($type === 'show') · episodes of this show all earn against this split set @endif
                        · percentages may total less than 100 (remainder stays with the platform), never more.
                    </p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ $errors->first() }}</div>
                @endif

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.monetization.splits.update', ['type' => $type, 'id' => $title->id]) }}"
                          x-data="splitEditor({{ json_encode(
                              old('splits', $splits->map(fn ($s) => ['partner_id' => $s->partner_id, 'percent' => (string) $s->percent])->values())
                          ) }})">
                        @csrf @method('PUT')

                        <template x-for="(row, index) in rows" :key="index">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-7">
                                    <label class="form-label" x-show="index === 0" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Partner</label>
                                    <select class="form-select" x-model="row.partner_id" :name="`splits[${index}][partner_id]`">
                                        <option value="">— choose partner —</option>
                                        @foreach ($partners as $partner)
                                            <option value="{{ $partner->id }}">
                                                {{ $partner->display_name }}
                                                ({{ str_replace('_', ' ', $partner->type) }}@if($partner->status !== 'enrolled') · SUSPENDED @endif)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-3">
                                    <label class="form-label" x-show="index === 0" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Share %</label>
                                    <input type="number" class="form-control" step="0.01" min="0.01" max="100"
                                           x-model="row.percent" :name="`splits[${index}][percent]`">
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-danger-subtle w-100" @click="rows.splice(index, 1)">
                                        <i class="ph ph-trash-simple"></i>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
                            <button type="button" class="btn btn-sm btn-ghost" @click="rows.push({partner_id: '', percent: ''})">
                                <i class="ph ph-plus me-1"></i> Add partner
                            </button>
                            <div style="font-size:14px;">
                                Total: <code x-text="total().toFixed(2) + '%'"></code>
                                <span class="text-danger ms-2" x-show="total() > 100">exceeds 100%</span>
                                <span class="text-muted ms-2" x-show="total() <= 100" x-text="'(' + (100 - total()).toFixed(2) + '% platform)'"></span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.monetization.splits.index') }}" class="btn btn-ghost">Back</a>
                            <button type="submit" class="btn btn-primary" :disabled="total() > 100">
                                <i class="ph ph-floppy-disk me-1"></i> Save splits
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function splitEditor(initial) {
        return {
            rows: initial.length ? initial : [{partner_id: '', percent: ''}],
            total() {
                return this.rows.reduce((sum, row) => sum + (parseFloat(row.percent) || 0), 0);
            },
        };
    }
</script>
@endsection
