<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    private const SEXES = ['Male', 'Female'];

    private const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function index(Request $request): View
    {
        $canArchive = $this->canArchivePatients($request);
        $showArchived = $canArchive && $request->boolean('archived');

        $patients = Patient::query()
            ->when($showArchived, fn ($query) => $query->onlyTrashed())
            ->when(! $showArchived, fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('sex'), function ($query) use ($request) {
                $query->where('sex', $this->normalizeSex($request->string('sex')->toString()));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('patients.index', [
            'patients' => $patients,
            'filters' => $request->only(['search', 'sex', 'archived']),
            'sexes' => self::SEXES,
            'showArchived' => $showArchived,
            'canArchive' => $canArchive,
        ]);
    }

    public function create(): View
    {
        return view('patients.create', [
            'patient' => new Patient(),
            'sexes' => self::SEXES,
            'bloodTypes' => self::BLOOD_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $patient = Patient::create($this->patientAttributes($request));

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', 'Patient record created successfully.');
    }

    public function show(Patient $patient): View
    {
        return view('patients.show', [
            'patient' => $patient,
            'canArchive' => $this->canArchivePatients(request()),
        ]);
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', [
            'patient' => $patient,
            'sexes' => self::SEXES,
            'bloodTypes' => self::BLOOD_TYPES,
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $patient->update($this->patientAttributes($request));

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', 'Patient record updated successfully.');
    }

    public function archive(Request $request, Patient $patient): RedirectResponse
    {
        abort_unless($this->canArchivePatients($request), 403);

        $patient->delete();

        return back()->with('status', 'Patient record archived successfully.');
    }

    public function restore(Request $request, string $patient): RedirectResponse
    {
        abort_unless($this->canArchivePatients($request), 403);

        $patient = Patient::withTrashed()->findOrFail($patient);
        $patient->restore();

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', 'Patient record restored successfully.');
    }

    private function patientAttributes(Request $request): array
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'sex' => ['required', Rule::in(['MALE', 'FEMALE', 'Male', 'Female'])],
            'civil_status' => ['nullable', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'address' => ['required', 'string', 'max:2000'],
            'barangay' => ['nullable', 'string', 'max:150'],
            'contact_number' => ['required', 'regex:/^\+?[0-9\s-]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'philhealth_id' => ['nullable', 'string', 'max:50'],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'regex:/^\+?[0-9\s-]{7,20}$/'],
            'known_allergies' => ['nullable', 'string'],
            'existing_conditions' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'regex:/^\+?[0-9\s-]{7,20}$/'],
        ]);

        $validated['birthdate'] = $validated['date_of_birth'];
        $validated['sex'] = $this->normalizeSex($validated['sex']);
        $validated['civil_status'] ??= 'Single';
        $validated['barangay'] ??= 'Not specified';

        unset($validated['date_of_birth']);

        return $validated;
    }

    private function normalizeSex(string $sex): string
    {
        return strtoupper($sex) === 'MALE' ? 'Male' : 'Female';
    }

    private function canArchivePatients(Request $request): bool
    {
        return $request->user()?->hasRole(['Administrator', 'Nurse']) ?? false;
    }
}
