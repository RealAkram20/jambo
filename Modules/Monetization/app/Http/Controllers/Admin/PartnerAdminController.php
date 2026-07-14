<?php

namespace Modules\Monetization\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Content\app\Models\Vj;
use Modules\Monetization\app\Models\MonetizationPartner;
use Modules\Monetization\app\Services\AuditLogger;

/**
 * Partner registry: enrollment, multipliers, payout-profile
 * verification. All writes are super-admin-gated at the route level;
 * index/show are finance-readable.
 */
class PartnerAdminController extends Controller
{
    public function index(Request $request)
    {
        $partners = MonetizationPartner::query()
            ->with(['user:id,username,email', 'vj:id,name'])
            ->withCount('splits')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w
                    ->where('display_name', 'like', $term)
                    ->orWhere('payout_msisdn', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('display_name')
            ->paginate(25)
            ->withQueryString();

        return view('monetization::admin.partners.index', [
            'partners' => $partners,
            'search' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
            'type' => $request->string('type')->toString(),
        ]);
    }

    public function create()
    {
        return view('monetization::admin.partners.form', [
            'partner' => new MonetizationPartner(['multiplier' => '1.000', 'status' => MonetizationPartner::STATUS_ENROLLED]),
            'vjs' => $this->unlinkedVjs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $partner = MonetizationPartner::create($data + [
            'enrolled_at' => now(),
        ]);

        $this->syncPartnerRole(null, $partner->user_id);

        AuditLogger::log('partner.enrolled', $partner, ['after' => $partner->only([
            'type', 'user_id', 'vj_id', 'display_name', 'status', 'multiplier',
        ])]);

        return redirect()
            ->route('admin.monetization.partners.show', $partner)
            ->with('success', "Partner “{$partner->display_name}” enrolled.");
    }

    public function show(MonetizationPartner $partner)
    {
        $partner->load(['user:id,username,email', 'vj:id,name', 'splits.splittable']);

        return view('monetization::admin.partners.show', [
            'partner' => $partner,
            'balance' => $partner->walletBalance(),
            'recentStatements' => $partner->statements()->with('period')->latest()->limit(6)->get(),
            'openWithdrawals' => $partner->withdrawals()->whereIn('status', \Modules\Monetization\app\Models\WithdrawalRequest::OPEN_STATUSES)->get(),
        ]);
    }

    public function edit(MonetizationPartner $partner)
    {
        return view('monetization::admin.partners.form', [
            'partner' => $partner,
            'vjs' => $this->unlinkedVjs($partner->vj_id),
        ]);
    }

    public function update(Request $request, MonetizationPartner $partner): RedirectResponse
    {
        $data = $this->validated($request, $partner);

        $auditFields = ['type', 'user_id', 'vj_id', 'display_name', 'status', 'multiplier', 'can_edit_content', 'can_delete_content'];
        $before = $partner->only($auditFields);
        $previousUserId = $partner->user_id;

        $partner->update($data);

        $this->syncPartnerRole($previousUserId, $partner->user_id);

        AuditLogger::logDiff(
            $data['status'] === MonetizationPartner::STATUS_SUSPENDED && $before['status'] !== $data['status']
                ? 'partner.suspended'
                : 'partner.updated',
            $partner,
            $before,
            $partner->only($auditFields),
        );

        return redirect()
            ->route('admin.monetization.partners.show', $partner)
            ->with('success', 'Partner updated.');
    }

    /**
     * Approve the partner's submitted payout profile. Withdrawals are
     * only possible against a verified profile.
     */
    public function verifyPayout(Request $request, MonetizationPartner $partner): RedirectResponse
    {
        if ($partner->payout_status !== MonetizationPartner::PAYOUT_PENDING_REVIEW) {
            return back()->with('error', 'This payout profile is not awaiting review.');
        }

        $partner->update([
            'payout_status' => MonetizationPartner::PAYOUT_VERIFIED,
            'payout_verified_at' => now(),
            'payout_verified_by' => $request->user()->id,
            'payout_locked_until' => null,
        ]);

        AuditLogger::log('payout_profile.verified', $partner, ['after' => [
            'payout_msisdn' => $partner->payout_msisdn,
            'payout_name' => $partner->payout_name,
            'payout_network' => $partner->payout_network,
        ]]);

        event(new \Modules\Notifications\app\Events\PayoutProfileVerified($partner));

        return back()->with('success', 'Payout profile verified — the partner can now request withdrawals.');
    }

    protected function validated(Request $request, ?MonetizationPartner $partner = null): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(MonetizationPartner::TYPES)],
            'display_name' => 'required|string|max:190',
            'user_id' => [
                'nullable', 'integer', 'exists:users,id',
                Rule::unique('monetization_partners', 'user_id')->ignore($partner?->id),
            ],
            'vj_id' => [
                'nullable', 'integer', 'exists:vjs,id',
                Rule::unique('monetization_partners', 'vj_id')->ignore($partner?->id),
            ],
            'status' => ['required', Rule::in([MonetizationPartner::STATUS_ENROLLED, MonetizationPartner::STATUS_SUSPENDED])],
            'multiplier' => 'required|numeric|min:0.1|max:10',
            'can_edit_content' => 'sometimes|boolean',
            'can_delete_content' => 'sometimes|boolean',
        ]);

        // Unchecked checkboxes don't submit — normalise to explicit
        // false so revoking a right actually persists.
        $data['can_edit_content'] = (bool) ($data['can_edit_content'] ?? false);
        $data['can_delete_content'] = (bool) ($data['can_delete_content'] ?? false);

        return $data;
    }

    /**
     * Keep the spatie `partner` role in lockstep with the user link:
     * linked user gains the role, unlinked user loses it (unless they
     * hold another partner row, which the unique constraint forbids).
     */
    protected function syncPartnerRole(?int $previousUserId, ?int $newUserId): void
    {
        if ($previousUserId && $previousUserId !== $newUserId) {
            User::find($previousUserId)?->removeRole('partner');
        }

        if ($newUserId) {
            User::find($newUserId)?->assignRole('partner');
        }
    }

    /** VJ rows not yet claimed by another partner (plus the current one). */
    protected function unlinkedVjs(?int $keepId = null)
    {
        return Vj::query()
            ->whereDoesntHave('monetizationPartner')
            ->when($keepId, fn ($q) => $q->orWhere('id', $keepId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
