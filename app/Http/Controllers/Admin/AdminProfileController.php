<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\ReservedUsername;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Admin's own profile page — reachable from the header avatar dropdown.
 *
 * Three focused cards:
 *   - Profile settings (name / username / email / phone)
 *   - Change password (with current-password confirmation)
 *   - Active sessions (list + per-session sign-out + sign-out-all-others)
 *
 * Two-factor auth isn't duplicated here; the Security card surfaces the
 * current 2FA status and links to the existing /{username}/security
 * page which already owns that flow end-to-end (QR code, recovery
 * codes, confirm, disable).
 */
class AdminProfileController extends Controller
{
    public function __construct(private readonly TwoFactorAuthentication $twoFactor)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        // Sessions table only populates when SESSION_DRIVER=database.
        // If the admin is on the file driver for some reason, this
        // returns empty and the view's "No active sessions tracked"
        // empty-state explains why.
        $rows = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get();

        $sessions = $rows->map(function ($s) use ($currentSessionId) {
            return [
                'id' => $s->id,
                'is_current' => hash_equals($currentSessionId, $s->id),
                'ip_address' => $s->ip_address ?: '—',
                'last_activity' => $s->last_activity ? Carbon::createFromTimestamp($s->last_activity) : null,
                'agent' => $this->parseUserAgent($s->user_agent ?? ''),
            ];
        });

        // 2FA state — pending setup, fully enabled, or off. The view
        // renders all three paths inline so admins never need to
        // leave the admin chrome to manage this.
        $is2faEnabled = $user->hasEnabledTwoFactorAuthentication();
        $hasPendingSetup = !is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);

        return view('DashboardPages.admin-profile', [
            'user' => $user,
            'title' => 'My profile',
            'sessions' => $sessions,
            'is2faEnabled' => $is2faEnabled,
            'hasPendingSetup' => $hasPendingSetup,
            'qrSvg' => ($hasPendingSetup || $is2faEnabled) ? $this->twoFactor->qrCodeSvg($user) : null,
            'manualSecret' => ($hasPendingSetup || $is2faEnabled) ? $this->twoFactor->secretForManualEntry($user) : null,
            'recoveryCodes' => $is2faEnabled ? $this->twoFactor->getRecoveryCodes($user) : [],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'username'   => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-zA-Z0-9_.\-]+$/',
                new ReservedUsername(),
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $emailChanged = strcasecmp($data['email'], $user->email) !== 0;

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'username'   => $data['username'],
            'email'      => strtolower($data['email']),
            'phone'      => $data['phone'] !== null ? trim($data['phone']) : null,
        ]);

        if ($emailChanged) {
            // Email change forces re-verification — same rule the user
            // profile hub applies. Locks the admin out of email-verify
            // gated pages until they confirm.
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route('dashboard.profile')
            ->with('status-profile', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()
            ->route('dashboard.profile')
            ->with('status-password', 'Password updated. Sign in again with the new one on other devices.');
    }

    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $count = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return redirect()
            ->route('dashboard.profile')
            ->with('status-sessions', $count > 0
                ? "Signed out of {$count} other " . Str::plural('device', $count) . '.'
                : 'No other devices were signed in.');
    }

    public function logoutSession(Request $request, string $sessionId): RedirectResponse
    {
        // Protection against self-logout from the sessions list — the
        // current session must go through the normal Logout link so
        // CSRF + post-logout redirect run.
        if (hash_equals($request->session()->getId(), $sessionId)) {
            return back()->with('status-sessions', 'Use the Logout link in the header to sign out of this device.');
        }

        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', $sessionId)
            ->delete();

        return back()->with('status-sessions', 'That device has been signed out.');
    }

    /**
     * Minimal UA classifier — hits the common cases so admins can
     * recognise which row is which without adding a composer
     * dependency. Mirrors the parser on the user-side profile hub
     * so both views render identical labels.
     */
    private function parseUserAgent(string $ua): array
    {
        $browser = 'Unknown browser';
        $os = 'Unknown OS';
        $icon = 'ph-globe';

        if (preg_match('/Edg\/([\d.]+)/i', $ua))                    { $browser = 'Microsoft Edge'; }
        elseif (preg_match('/OPR\/([\d.]+)/i', $ua))                { $browser = 'Opera'; }
        elseif (preg_match('/Chrome\/([\d.]+)/i', $ua))             { $browser = 'Chrome'; }
        elseif (preg_match('/Firefox\/([\d.]+)/i', $ua))            { $browser = 'Firefox'; }
        elseif (preg_match('/Version\/[\d.]+.*Safari/i', $ua))      { $browser = 'Safari'; }
        elseif (stripos($ua, 'curl') !== false)                     { $browser = 'curl'; }
        elseif (stripos($ua, 'PostmanRuntime') !== false)           { $browser = 'Postman'; }

        // iPhone / iPad UA strings contain "like Mac OS X", so test
        // iOS first otherwise we mis-label them as macOS.
        if (preg_match('/iPhone|iPad|iPod/i', $ua))                 { $os = 'iOS';     $icon = 'ph-device-mobile'; }
        elseif (preg_match('/Android ([\d.]+)/i', $ua))             { $os = 'Android'; $icon = 'ph-device-mobile'; }
        elseif (preg_match('/Windows NT ([\d.]+)/i', $ua))          { $os = 'Windows'; $icon = 'ph-desktop'; }
        elseif (stripos($ua, 'Mac OS X') !== false)                 { $os = 'macOS';   $icon = 'ph-desktop'; }
        elseif (stripos($ua, 'Linux') !== false)                    { $os = 'Linux';   $icon = 'ph-desktop'; }

        return ['browser' => $browser, 'os' => $os, 'icon' => $icon];
    }
}
