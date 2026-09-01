<?php

namespace App\Http\Controllers;

use App\Models\VisitHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitHistoryController extends Controller
{
    private const ALLOWED_ROLES = ['Administrator', 'Doctor', 'Nurse', 'Data Encoder'];

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->hasRole(self::ALLOWED_ROLES), 403);

        $visitHistories = VisitHistory::query()
            ->with(['appointment.patient', 'doctor'])
            ->when($user->hasRole('Doctor'), fn ($query) => $query->where('doctor_id', $user->id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->whereHas('appointment.patient', function ($query) use ($search) {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->latest('consultation_date')
            ->paginate(10)
            ->withQueryString();

        return view('visit-history.index', [
            'visitHistories' => $visitHistories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Request $request, VisitHistory $visitHistory): View
    {
        $user = $request->user();
        abort_unless($user?->hasRole(self::ALLOWED_ROLES), 403);
        abort_if($user->hasRole('Doctor') && $user->id !== $visitHistory->doctor_id, 403);

        $visitHistory->load(['appointment.patient', 'doctor']);

        return view('visit-history.show', [
            'visitHistory' => $visitHistory,
        ]);
    }
}
