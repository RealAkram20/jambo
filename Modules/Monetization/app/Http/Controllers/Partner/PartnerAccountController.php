<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProfileHubController;
use Illuminate\Http\Request;

/**
 * Partners/VJs have ONE dashboard: the Creator Studio. Their account
 * pages (the profile-hub tabs) are therefore addressed flat inside it
 * (/partner/profile, /partner/security, /partner/devices,
 * /partner/notifications) so the /{username} URL space stays out of
 * their way — ProfileHubController::resolveOwn() redirects a
 * partner's GET on the old /{username} tab URLs here.
 *
 * Each action is a thin delegate to the corresponding hub method with
 * the signed-in user's username, so validation, data shaping, and the
 * views stay single-sourced in ProfileHubController. The hub's shell
 * choice (profile-hub/_layout picks the studio shell for partners)
 * does the rest.
 *
 * Membership / Watchlist / Billing are consumer features with no
 * studio surface — resolveOwn() sends a partner's GETs on those hub
 * URLs to the studio overview instead.
 *
 * Mutating endpoints (profile update, avatar, device logout,
 * notification prefs) intentionally keep their /{username} routes —
 * forms in the hub views post there directly, and the redirect-back
 * lands on a GET that bounces into /partner/* with flash data
 * reflashed.
 */
class PartnerAccountController extends Controller
{
    private function hub(): ProfileHubController
    {
        return app(ProfileHubController::class);
    }

    public function profile(Request $request)
    {
        return $this->hub()->show($request, $request->user()->username);
    }

    public function security(Request $request)
    {
        return $this->hub()->security($request, $request->user()->username);
    }

    public function devices(Request $request)
    {
        return $this->hub()->devices($request, $request->user()->username);
    }

    public function notifications(Request $request)
    {
        return $this->hub()->notifications($request, $request->user()->username);
    }

    public function refer(Request $request)
    {
        return $this->hub()->refer($request, $request->user()->username);
    }
}
