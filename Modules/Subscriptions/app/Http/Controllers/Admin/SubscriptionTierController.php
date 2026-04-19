<?php

namespace Modules\Subscriptions\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Subscriptions\app\Models\SubscriptionTier;

/**
 * Admin CRUD for subscription tiers.
 *
 * Routes: /admin/subscription-tiers/*
 * Middleware: web + auth + role:admin (set in Modules/Subscriptions/routes/web.php).
 */
class SubscriptionTierController extends Controller
{
    public function index(Request $request): View
    {
        $query = SubscriptionTier::query()->withCount('userSubscriptions');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('slug', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        if ($period = $request->query('period')) {
            $query->where('billing_period', $period);
        }

        $tiers = $query->ordered()->paginate(20)->withQueryString();

        return view('subscriptions::admin.tiers.index', [
            'tiers' => $tiers,
            'search' => $search,
            'period' => $period ?? '',
            'totalCount' => SubscriptionTier::count(),
        ]);
    }

    public function create(): View
    {
        return view('subscriptions::admin.tiers.create', [
            'tier' => new SubscriptionTier([
                'currency' => 'UGX',
                'billing_period' => SubscriptionTier::PERIOD_MONTHLY,
                'access_level' => SubscriptionTier::ACCESS_BASIC,
                'is_active' => true,
                'sort_order' => 20,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        SubscriptionTier::create($data);

        return redirect()
            ->route('admin.subscription-tiers.index')
            ->with('success', "Tier \"{$data['name']}\" created.");
    }

    public function edit(SubscriptionTier $subscriptionTier): View
    {
        return view('subscriptions::admin.tiers.edit', ['tier' => $subscriptionTier]);
    }

    public function update(Request $request, SubscriptionTier $subscriptionTier): RedirectResponse
    {
        $data = $this->validated($request, $subscriptionTier->id);

        $subscriptionTier->update($data);

        return redirect()
            ->route('admin.subscription-tiers.index')
            ->with('success', "Tier \"{$subscriptionTier->name}\" updated.");
    }

    public function destroy(SubscriptionTier $subscriptionTier): RedirectResponse
    {
        if ($subscriptionTier->userSubscriptions()->exists()) {
            return redirect()
                ->back()
                ->with('error', "Cannot delete \"{$subscriptionTier->name}\" — active user subscriptions reference it. Deactivate it instead.");
        }

        $name = $subscriptionTier->name;
        $subscriptionTier->delete();

        return redirect()
            ->route('admin.subscription-tiers.index')
            ->with('success', "Deleted tier \"{$name}\".");
    }

    /**
     * Normalise input: accept the features textarea as one-per-line, cast
     * is_active from the checkbox, and ensure slug is URL-safe.
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'nullable|string|max:255|unique:subscription_tiers,slug' . ($ignoreId ? ",{$ignoreId}" : '');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'billing_period' => 'required|in:' . implode(',', SubscriptionTier::PERIODS),
            'access_level' => 'required|integer|in:0,1,2,3',
            'max_concurrent_streams' => 'nullable|integer|min:0|max:20',
            'features' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['currency'] = strtoupper($data['currency']);
        $data['is_active'] = $request->boolean('is_active');

        // Features come from a textarea; split by newlines and strip empties.
        $features = array_values(array_filter(array_map(
            'trim',
            preg_split('/\r?\n/', (string) ($data['features'] ?? ''))
        )));
        $data['features'] = $features;

        return $data;
    }
}
