<?php

namespace Modules\Referrals\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Referrals\app\Models\Referral;
use Modules\Referrals\app\Services\ReferralDashboardService;
use Modules\Wallet\app\Models\WithdrawalRequest;

/**
 * The ONE admin-panel Referrals page. Everything referral lives in
 * tabs here so nobody navigates across sidebar items to act:
 *
 *   Refer & Earn — the signed-in admin's OWN participation (everyone
 *                  in the panel; admins take part like any user)
 *   Payouts      — the universal wallet queue (finance | super-admin)
 *   Overview     — the program-wide referral list (super-admin only)
 *
 * Program settings (percentages, cookie window, minimum withdrawal)
 * stay on their own super-admin-only page.
 */
class ReferralAdminController extends Controller
{
    public function index(Request $request, ReferralDashboardService $dashboard)
    {
        $user = $request->user();
        $canClerk = $user->hasAnyRole(['finance', 'super-admin']);
        $isSuper = $user->hasRole('super-admin');

        $data = [
            'activePane' => $request->query('tab', 'refer'),
            'canClerk' => $canClerk,
            'isSuper' => $isSuper,
            'refer' => $dashboard->forUser($user),
        ];

        if ($canClerk) {
            $data['withdrawals'] = WithdrawalRequest::query()
                ->with('owner')
                ->orderByRaw("FIELD(status, 'requested', 'approved', 'paid', 'rejected')")
                ->orderByDesc('requested_at')
                ->paginate(25, ['*'], 'payouts_page')
                ->withQueryString();
            $data['pendingPayouts'] = WithdrawalRequest::where('status', WithdrawalRequest::STATUS_REQUESTED)->count();
        }

        if ($isSuper) {
            $data['stats'] = [
                'total' => Referral::count(),
                'qualified' => Referral::where('status', Referral::STATUS_QUALIFIED)->count(),
                'discounts_given' => (string) (Referral::sum('discount_amount') ?: '0'),
                'rewards_paid' => (string) (Referral::sum('reward_amount') ?: '0'),
            ];

            $query = Referral::with([
                'referrer:id,username,first_name,last_name,email',
                'referredUser:id,username,first_name,last_name,email',
            ])->latest();

            if (in_array($request->query('status'), [Referral::STATUS_PENDING, Referral::STATUS_QUALIFIED], true)) {
                $query->where('status', $request->query('status'));
            }

            if ($search = trim((string) $request->query('search'))) {
                // Escape LIKE wildcards so "%" / "_" in the box match literally.
                $like = '%' . addcslashes($search, '\\%_') . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('code_used', 'like', $like)
                        ->orWhereHas('referrer', fn ($u) => $u->where('email', 'like', $like)->orWhere('username', 'like', $like))
                        ->orWhereHas('referredUser', fn ($u) => $u->where('email', 'like', $like)->orWhere('username', 'like', $like));
                });
            }

            $data['referrals'] = $query->paginate(25, ['*'], 'overview_page')->withQueryString();
        }

        $data['currency'] = config('payments.currency', 'UGX');

        return view('referrals::admin.index', $data);
    }
}
