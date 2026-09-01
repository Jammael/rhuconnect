<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Vitals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VitalsController extends Controller
{
    private const RECORD_ROLES = ['Administrator', 'Nurse'];

    /**
     * Nurse/Administrator daily overview of vitals recorded for today's
     * appointments. Full per-patient detail (weight, height, chief
     * complaint) lives on the appointment show page — this is a scan list,
     * not a duplicate detail view.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole(self::RECORD_ROLES), 403);

        $vitals = Vitals::query()
            ->with(['appointment.patient', 'appointment.doctor', 'recordedBy'])
            ->whereHas('appointment', fn ($query) => $query->whereDate('appointment_date', today()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->whereHas('appointment.patient', function ($query) use ($search) {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('vitals.index', [
            'vitals' => $vitals,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(self::RECORD_ROLES), 403);

        abort_unless(
            in_array($appointment->status, ['Pending', 'Approved'], true) && $appointment->appointment_date->isToday(),
            403,
            'Vitals can only be recorded for a Pending or Approved appointment scheduled today.'
        );

        if ($appointment->vitals()->exists()) {
            throw ValidationException::withMessages([
                'appointment' => 'Vitals have already been recorded for this appointment.',
            ]);
        }

        $validated = $request->validate([
            'blood_pressure_systolic' => ['required', 'integer', 'between:40,300'],
            'blood_pressure_diastolic' => ['required', 'integer', 'between:20,200'],
            'temperature_celsius' => ['required', 'numeric', 'between:30,45'],
            'pulse_rate' => ['required', 'integer', 'between:20,250'],
            'respiratory_rate' => ['required', 'integer', 'between:5,80'],
            'oxygen_saturation' => ['required', 'integer', 'between:1,100'],
            'weight_kg' => ['required', 'numeric', 'between:0.5,300'],
            'height_cm' => ['required', 'numeric', 'between:20,250'],
            'chief_complaint' => ['nullable', 'string'],
        ]);

        $validated['appointment_id'] = $appointment->id;
        $validated['recorded_by'] = $request->user()->id;

        Vitals::create($validated);

        return back()->with('status', 'Vitals recorded successfully.');
    }
}
