<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PrenatalCase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrenatalCaseController extends Controller
{
    private const STATUSES = ['Active', 'Completed', 'Miscarried', 'Transferred'];

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->hasRole(['Administrator', 'Midwife']), 403);

        $cases = PrenatalCase::query()
            ->with(['patient', 'midwife'])
            ->when($user->hasRole('Midwife'), fn ($query) => $query->where('midwife_id', $user->id))
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
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('prenatal-cases.index', [
            'cases' => $cases,
            'filters' => $request->only(['search', 'status']),
            'statuses' => self::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->hasRole(['Administrator', 'Midwife']), 403);

        return view('prenatal-cases.create', [
            'case' => new PrenatalCase(),
            'statuses' => self::STATUSES,
            'midwives' => $this->activeMidwives(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['Administrator', 'Midwife']), 403);

        $case = PrenatalCase::create($this->caseAttributes($request));

        return redirect()
            ->route('prenatal-cases.show', $case)
            ->with('status', 'Prenatal case created successfully.');
    }

    public function show(Request $request, PrenatalCase $prenatalCase): View
    {
        $this->authorizeCaseAccess($request, $prenatalCase);

        $prenatalCase->load(['patient', 'midwife', 'appointments' => fn ($query) => $query->orderByDesc('appointment_date')]);

        return view('prenatal-cases.show', [
            'case' => $prenatalCase,
        ]);
    }

    public function edit(Request $request, PrenatalCase $prenatalCase): View
    {
        $this->authorizeCaseAccess($request, $prenatalCase);

        return view('prenatal-cases.edit', [
            'case' => $prenatalCase,
            'statuses' => self::STATUSES,
            'midwives' => $this->activeMidwives(),
        ]);
    }

    public function update(Request $request, PrenatalCase $prenatalCase): RedirectResponse
    {
        $this->authorizeCaseAccess($request, $prenatalCase);

        $prenatalCase->update($this->caseAttributes($request, $prenatalCase));

        return redirect()
            ->route('prenatal-cases.show', $prenatalCase)
            ->with('status', 'Prenatal case updated successfully.');
    }

    /**
     * JSON lookup used by the Appointments booking form: does the selected
     * patient have an active prenatal case, and if so, whose is it (only
     * the owning Midwife or an Administrator may link an appointment to
     * it — a booking Nurse/Data Encoder can see that a case exists but the
     * link itself is still gated the same way case access is).
     */
    public function activeForPatient(Request $request, Patient $patient): JsonResponse
    {
        $case = PrenatalCase::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'Active')
            ->first();

        if (! $case) {
            return response()->json(['case' => null]);
        }

        return response()->json([
            'case' => [
                'id' => $case->id,
                'label' => 'Prenatal case — EDD '.$case->estimated_due_date->format('M d, Y').' ('.$case->trimester.' trimester)',
            ],
        ]);
    }

    private function caseAttributes(Request $request, ?PrenatalCase $case = null): array
    {
        // A Midwife submitting their own case omits midwife_id entirely
        // (it's auto-assigned to themselves below) — only require it in
        // the request when an Administrator is the one filling it in.
        $midwifeIdRule = $request->user()->hasRole('Midwife') ? ['nullable'] : ['required'];

        $validated = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->withoutTrashed()],
            'midwife_id' => [...$midwifeIdRule, Rule::exists('users', 'id')],
            'last_menstrual_period' => ['required', 'date', 'before_or_equal:today'],
            'estimated_due_date' => ['required', 'date'],
            'gravida' => ['required', 'integer', 'min:1', 'max:20'],
            'para' => ['required', 'integer', 'min:0', 'max:20'],
            'is_high_risk' => ['nullable', 'boolean'],
            'risk_notes' => ['nullable', 'string', 'required_if:is_high_risk,1'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['is_high_risk'] = (bool) ($validated['is_high_risk'] ?? false);
        $validated['midwife_id'] = $request->user()->hasRole('Midwife') ? $request->user()->id : $validated['midwife_id'];

        $midwife = User::query()->findOrFail($validated['midwife_id']);
        abort_unless($midwife->hasRole('Midwife'), 422, 'Selected user is not a Midwife.');

        return $validated;
    }

    private function authorizeCaseAccess(Request $request, PrenatalCase $case): void
    {
        $user = $request->user();
        abort_unless($user?->hasRole(['Administrator', 'Midwife']), 403);
        abort_if($user->hasRole('Midwife') && $user->id !== $case->midwife_id, 403);
    }

    private function activeMidwives()
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'Midwife'))
            ->where('account_status', 'ACTIVE')
            ->orderBy('name')
            ->get();
    }
}
