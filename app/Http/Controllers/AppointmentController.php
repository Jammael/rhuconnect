<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\VisitHistory;
use App\Services\DoctorAvailabilityService;
use App\Services\QueueService;
use App\Services\SmsNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    private const STATUSES = ['Pending', 'Approved', 'Cancelled', 'Completed', 'No Show'];

    private const BOOKING_ROLES = ['Administrator', 'Nurse', 'Midwife', 'Data Encoder'];

    private const TERMINAL_STATUSES = ['Cancelled', 'Completed', 'No Show'];

    private const VITALS_ROLES = ['Administrator', 'Nurse'];

    public function __construct(
        protected DoctorAvailabilityService $availabilityService,
        protected QueueService $queueService,
        protected SmsNotificationService $smsService
    ) {}

    public function index(Request $request): View
    {
        $appointments = Appointment::query()
            ->with(['patient', 'doctor'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->whereHas('patient', function ($query) use ($search) {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('appointment_date', $request->string('date')->toString()))
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'filters' => $request->only(['search', 'status', 'doctor_id', 'date']),
            'statuses' => self::STATUSES,
            'doctors' => $this->activeDoctors(),
            'canManage' => $this->canManageAppointments($request),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManageAppointments($request), 403);

        return view('appointments.create', [
            'appointment' => new Appointment(),
            'doctors' => $this->activeDoctors(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManageAppointments($request), 403);

        $validated = $this->appointmentAttributes($request);
        $validated['status'] = 'Pending';

        $appointment = DB::transaction(function () use ($validated): Appointment {
            $appointment = Appointment::create($validated);

            $this->smsService->logBookingConfirmation($appointment);

            return $appointment;
        });

        return redirect()
            ->route('appointments.show', $appointment)
            ->with('status', 'Appointment booked successfully.');
    }

    public function show(Appointment $appointment): View
    {
        $appointment->load(['patient', 'doctor', 'queue', 'visitHistory', 'vitals.recordedBy']);

        $canCheckIn = $this->canManageAppointments(request())
            && ! $appointment->queue
            && in_array($appointment->status, ['Pending', 'Approved'], true)
            && $appointment->appointment_date->isToday();

        $canRecordVitals = request()->user()?->hasRole(self::VITALS_ROLES)
            && ! $appointment->vitals
            && in_array($appointment->status, ['Pending', 'Approved'], true)
            && $appointment->appointment_date->isToday();

        return view('appointments.show', [
            'appointment' => $appointment,
            'statuses' => self::STATUSES,
            'canManage' => $this->canManageAppointments(request()),
            'canComplete' => $this->canTransitionToCompletion(request(), $appointment),
            'canCheckIn' => $canCheckIn,
            'canRecordVitals' => $canRecordVitals,
            'canViewVitals' => $this->canViewVitals(request(), $appointment),
            'suggestedPriorityType' => ($appointment->patient->age ?? 0) >= 60 ? 'Senior Citizen' : 'Regular',
        ]);
    }

    public function edit(Request $request, Appointment $appointment): View
    {
        abort_unless($this->canManageAppointments($request), 403);
        abort_if(in_array($appointment->status, self::TERMINAL_STATUSES, true), 403, 'This appointment can no longer be edited.');

        return view('appointments.edit', [
            'appointment' => $appointment,
            'doctors' => $this->activeDoctors(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($this->canManageAppointments($request), 403);
        abort_if(in_array($appointment->status, self::TERMINAL_STATUSES, true), 403, 'This appointment can no longer be edited.');

        $appointment->update($this->appointmentAttributes($request, $appointment));

        return redirect()
            ->route('appointments.show', $appointment)
            ->with('status', 'Appointment updated successfully.');
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $targetStatus = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ])['status'];

        if (in_array($targetStatus, ['Completed', 'No Show'], true)) {
            abort_unless($this->canTransitionToCompletion($request, $appointment), 403,
                'Only the assigned doctor or an administrator can mark this appointment as '.$targetStatus.'.');
        } else {
            abort_unless($this->canManageAppointments($request), 403);
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
            'diagnosis' => [$targetStatus === 'Completed' ? 'required' : 'sometimes', 'string'],
            'prescription' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($targetStatus === 'Completed' && $appointment->visitHistory()->exists()) {
            throw ValidationException::withMessages([
                'diagnosis' => 'A visit history record already exists for this appointment.',
            ]);
        }

        DB::transaction(function () use ($appointment, $targetStatus, $validated): void {
            $appointment->update([
                'status' => $targetStatus,
                'remarks' => $validated['remarks'] ?? $appointment->remarks,
            ]);

            if ($targetStatus === 'Completed') {
                VisitHistory::create([
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'diagnosis' => $validated['diagnosis'],
                    'prescription' => $validated['prescription'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'consultation_date' => today(),
                ]);
            }

            $this->queueService->syncFromAppointmentStatus($appointment, $targetStatus);
            $this->smsService->logStatusChange($appointment, $targetStatus);
        });

        return back()->with('status', 'Appointment status updated successfully.');
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'date' => ['required', 'date'],
            'exclude_appointment_id' => ['nullable', 'integer'],
        ]);

        $doctor = User::query()->findOrFail($validated['doctor_id']);

        abort_unless($doctor->hasRole('Doctor'), 422, 'Selected user is not a doctor.');

        $slots = $this->availabilityService->getAvailableSlots(
            $doctor,
            Carbon::parse($validated['date']),
            $validated['exclude_appointment_id'] ?? null
        );

        return response()->json(['slots' => $slots]);
    }

    private function appointmentAttributes(Request $request, ?Appointment $appointment = null): array
    {
        $validated = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->withoutTrashed()],
            'doctor_id' => ['required', Rule::exists('users', 'id')],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'purpose_of_visit' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $doctor = User::query()->findOrFail($validated['doctor_id']);

        if (! $doctor->hasRole('Doctor') || ! $doctor->isActive()) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Selected user is not an active doctor.',
            ]);
        }

        $availableSlots = $this->availabilityService->getAvailableSlots(
            $doctor,
            Carbon::parse($validated['appointment_date']),
            $appointment?->id
        );

        if (! in_array($validated['appointment_time'], $availableSlots, true)) {
            throw ValidationException::withMessages([
                'appointment_time' => 'The selected time slot is not available for this doctor on this date.',
            ]);
        }

        $validated['appointment_time'] .= ':00';

        return $validated;
    }

    private function activeDoctors()
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'Doctor'))
            ->where('account_status', 'ACTIVE')
            ->orderBy('name')
            ->get();
    }

    private function canManageAppointments(Request $request): bool
    {
        return $request->user()?->hasRole(self::BOOKING_ROLES) ?? false;
    }

    private function canTransitionToCompletion(Request $request, Appointment $appointment): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrator')
            || ($user->hasRole('Doctor') && $user->id === $appointment->doctor_id);
    }

    /**
     * Vitals are clinical intake data: Administrator and Nurse always see
     * them, but a Doctor only sees them for their own patients — mirroring
     * VisitHistoryController's doctor-self-only read scope. Midwife and
     * Data Encoder never see vitals values, even though they can otherwise
     * view this appointment page.
     */
    private function canViewVitals(Request $request, Appointment $appointment): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole(self::VITALS_ROLES)
            || ($user->hasRole('Doctor') && $user->id === $appointment->doctor_id);
    }
}
