<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TerminatesWebSession
{
    public static function logout(Request $request, string $event = 'auth.logout'): void
    {
        $user = $request->user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            AuditLog::record($event, $request, $user);
        }
    }
}
