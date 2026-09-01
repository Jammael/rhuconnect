<?php

namespace App\Http\Controllers;

use App\Models\SmsNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsNotificationController extends Controller
{
    private const ALLOWED_ROLES = ['Administrator', 'Data Encoder'];

    private const STATUSES = ['Pending', 'Sent', 'Failed'];

    /**
     * Read-only log viewer — nothing is composed or sent from here, this
     * simply lists what the simulated gateway has already logged.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole(self::ALLOWED_ROLES), 403);

        $notifications = SmsNotification::query()
            ->with('patient')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->whereHas('patient', function ($query) use ($search) {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('delivery_status', $request->string('status')->toString()))
            ->latest('sent_at')
            ->paginate(10)
            ->withQueryString();

        return view('sms-notifications.index', [
            'notifications' => $notifications,
            'filters' => $request->only(['search', 'status']),
            'statuses' => self::STATUSES,
        ]);
    }
}
