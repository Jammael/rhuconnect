<?php

namespace App\Http\Controllers;

use App\Models\DoctorAvailabilityException;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DoctorAvailabilityController extends Controller
{
    public function __construct(
        protected DoctorAvailabilityService $availabilityService
    ) {}

    /**
     * Admin view: lists all doctors and their schedule summaries.
     * Doctor view: redirects directly to their own schedule edit page.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('Doctor')) {
            return redirect()->route('doctor-availability.edit', $user);
        }

        if (! $user->hasRole('Administrator')) {
            abort(403, 'Unauthorized access to doctor availability.');
        }

        $doctors = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'Doctor'))
            ->where('account_status', 'ACTIVE')
            ->with(['availabilityTemplates', 'availabilityExceptions'])
            ->orderBy('name')
            ->paginate(10);

        // Compute summaries for each doctor
        $doctors->getCollection()->transform(function (User $doctor) {
            $doctor->schedule_summary = $this->availabilityService->getWeeklyScheduleSummary($doctor);
            $doctor->active_exceptions_count = $doctor->availabilityExceptions
                ->where('date', '>=', now()->toDateString())
                ->count();

            return $doctor;
        });

        return view('doctor-availability.index', [
            'doctors' => $doctors,
        ]);
    }

    /**
     * Edit a doctor's weekly recurring availability template (Sun-Sat).
     */
    public function edit(Request $request, User $doctor): View
    {
        $this->authorizeDoctorAccess($request, $doctor);

        $existingTemplates = $doctor->availabilityTemplates()
            ->get()
            ->keyBy('day_of_week');

        $days = [];
        $defaultSlotDuration = 30;

        foreach (DoctorAvailabilityTemplate::DAYS as $dayIndex => $dayName) {
            $existing = $existingTemplates->get($dayIndex);

            if ($existing) {
                $days[$dayIndex] = [
                    'day_of_week' => $dayIndex,
                    'day_name' => $dayName,
                    'is_active' => $existing->is_active,
                    'start_time' => substr($existing->start_time, 0, 5),
                    'end_time' => substr($existing->end_time, 0, 5),
                ];
                $defaultSlotDuration = $existing->slot_duration_minutes;
            } else {
                // Default: Monday-Friday active (08:00 - 17:00), Weekend inactive
                $isDefaultWorkday = in_array($dayIndex, [1, 2, 3, 4, 5], true);
                $days[$dayIndex] = [
                    'day_of_week' => $dayIndex,
                    'day_name' => $dayName,
                    'is_active' => $isDefaultWorkday,
                    'start_time' => '08:00',
                    'end_time' => '17:00',
                ];
            }
        }

        return view('doctor-availability.edit', [
            'doctor' => $doctor,
            'days' => $days,
            'slotDuration' => $defaultSlotDuration,
        ]);
    }

    /**
     * Update the doctor's weekly recurring availability template.
     */
    public function update(Request $request, User $doctor): RedirectResponse
    {
        $this->authorizeDoctorAccess($request, $doctor);

        $validated = $request->validate([
            'slot_duration_minutes' => ['required', 'integer', Rule::in([15, 30, 45, 60])],
            'days' => ['required', 'array', 'size:7'],
            'days.*.is_active' => ['nullable'],
            'days.*.start_time' => ['required', 'date_format:H:i'],
            'days.*.end_time' => ['required', 'date_format:H:i', 'after:days.*.start_time'],
        ]);

        $slotDuration = (int) $validated['slot_duration_minutes'];

        foreach ($validated['days'] as $dayIndex => $dayData) {
            $isActive = ! empty($dayData['is_active']);

            DoctorAvailabilityTemplate::updateOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'day_of_week' => (int) $dayIndex,
                ],
                [
                    'start_time' => $dayData['start_time'].':00',
                    'end_time' => $dayData['end_time'].':00',
                    'slot_duration_minutes' => $slotDuration,
                    'is_active' => $isActive,
                ]
            );
        }

        return redirect()
            ->route('doctor-availability.edit', $doctor)
            ->with('status', 'Weekly recurring schedule saved successfully.');
    }

    /**
     * List date-specific exceptions for a doctor.
     */
    public function exceptions(Request $request, User $doctor): View
    {
        $this->authorizeDoctorAccess($request, $doctor);

        $exceptions = $doctor->availabilityExceptions()
            ->orderBy('date', 'desc')
            ->paginate(15);

        return view('doctor-availability.exceptions', [
            'doctor' => $doctor,
            'exceptions' => $exceptions,
        ]);
    }

    /**
     * Store a date-specific exception (override).
     */
    public function storeException(Request $request, User $doctor): RedirectResponse
    {
        $this->authorizeDoctorAccess($request, $doctor);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'is_available' => ['required', 'boolean'],
            'start_time' => ['nullable', 'required_if:is_available,1', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_if:is_available,1', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DoctorAvailabilityException::updateOrCreate(
            [
                'doctor_id' => $doctor->id,
                'date' => $validated['date'],
            ],
            [
                'is_available' => (bool) $validated['is_available'],
                'start_time' => ! empty($validated['start_time']) ? $validated['start_time'].':00' : null,
                'end_time' => ! empty($validated['end_time']) ? $validated['end_time'].':00' : null,
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return redirect()
            ->route('doctor-availability.exceptions', $doctor)
            ->with('status', 'Date exception saved successfully.');
    }

    /**
     * Delete a date-specific exception.
     */
    public function destroyException(Request $request, User $doctor, DoctorAvailabilityException $exception): RedirectResponse
    {
        $this->authorizeDoctorAccess($request, $doctor);

        if ($exception->doctor_id !== $doctor->id) {
            abort(404, 'Exception not found for this doctor.');
        }

        $exception->delete();

        return redirect()
            ->route('doctor-availability.exceptions', $doctor)
            ->with('status', 'Date exception removed successfully.');
    }

    /**
     * Check if the authenticated user has permission to manage this doctor's availability.
     * - Administrator can manage all doctors.
     * - Doctor can manage only their own availability.
     */
    protected function authorizeDoctorAccess(Request $request, User $doctor): void
    {
        $user = $request->user();

        if (! $doctor->hasRole('Doctor')) {
            abort(404, 'Doctor not found.');
        }

        if ($user->hasRole('Administrator')) {
            return;
        }

        if ($user->hasRole('Doctor') && $user->id === $doctor->id) {
            return;
        }

        abort(403, 'Unauthorized access to doctor availability.');
    }
}

