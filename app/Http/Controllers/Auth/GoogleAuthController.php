<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * "Continue with Google" — an additional sign-in method alongside the
 * existing email/password login, not a replacement. It only ever
 * authenticates an *existing*, Administrator-provisioned account by
 * matching Google's verified email address; it never creates a new
 * account or assigns a role. Public self-registration stays disabled
 * exactly as before — this is just a second door into the same house.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(config('services.google.client_id'), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(config('services.google.client_id'), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in failed. Please try again or sign in with your password.',
            ]);
        }

        $user = User::query()->where('email', $googleUser->getEmail())->first();

        if (! $user) {
            AuditLog::record('auth.google_login_no_account', $request, metadata: [
                'email_hash' => hash('sha256', mb_strtolower((string) $googleUser->getEmail())),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'No RHUConnect account found for this Google email. Contact your Administrator.',
            ]);
        }

        if (! $user->isActive()) {
            AuditLog::record('auth.google_login_blocked_inactive', $request, $user);

            return redirect()->route('login')->withErrors([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('auth.google_login_success', $request, $user);

        if ($user->hasRole('Administrator')) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
