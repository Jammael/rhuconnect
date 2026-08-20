<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsNotIdle
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return $next($request);
        }

        $lastActivity = $request->session()->get('last_activity');
        $now = now()->timestamp;
        $lifetime = max((int) config('session.lifetime'), 1) * 60;

        if ($lastActivity && ($now - (int) $lastActivity) > $lifetime) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', 'Your session expired due to inactivity. Please sign in again.');
        }

        $isBackgroundPoll = filter_var($request->headers->get('X-Background-Poll'), FILTER_VALIDATE_BOOLEAN);

        if (! $isBackgroundPoll) {
            $request->session()->put('last_activity', $now);
        }

        return $next($request);
    }
}
