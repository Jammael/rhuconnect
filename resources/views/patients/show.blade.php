@php
    $roleLabel = auth()->user()->role?->name ?? 'Staff';
    $navGroups = match ($roleLabel) {
        'Administrator' => [
            'MAIN MENU' => ['Dashboard', 'Patient Records', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Slot Capacity', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications'],
            'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
        ],
        'Doctor' => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'My Appointments', 'My Availability', 'Patient Visit History', 'Profile']],
        'Nurse' => ['MAIN MENU' => ['Dashboard', 'Patient Queue', 'Patient Records', 'Vitals/Triage', 'Profile']],
        'Midwife' => ['MAIN MENU' => ['Dashboard', 'Maternal Care Appointments', 'Patient Records', 'Visit History', 'Profile']],
        'Data Encoder' => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'Appointment Entry', 'SMS Notifications Log', 'Profile']],
        default => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'Profile']],
    };

    $value = fn ($text) => filled($text) ? $text : 'Not recorded';
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Patient Details',
    'pageSubtitle' => 'Manage RHUConnect patient information.',
    'context' => 'Review demographic, contact, guardian, and clinical intake details.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Patient Records', 'link' => route('patients.index')],
        ['label' => $patient->full_name],
    ],
    'portalLabel' => $roleLabel === 'Administrator' ? 'Admin Portal' : $roleLabel.' Portal',
    'roleLabel' => $roleLabel,
    'navGroups' => $navGroups,
    'user' => auth()->user(),
])

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('patients.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Back to Patients
            </a>
            @unless ($patient->trashed())
                <a href="{{ route('patients.edit', $patient) }}" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                    Edit Patient
                </a>
            @endunless
        </div>

        @if (session('status'))
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: @json(session('status')), type: 'success' },
                    }));
                });
            </script>
        @endif

        <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-green-700">Patient Record</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900">{{ $patient->full_name }}</h2>
                    <p class="mt-1 text-sm font-medium text-slate-500">Record #{{ str_pad((string) $patient->id, 5, '0', STR_PAD_LEFT) }}</p>
                </div>
                <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-extrabold {{ $patient->trashed() ? 'bg-slate-100 text-slate-600' : 'bg-green-100 text-green-700' }}">
                    {{ $patient->trashed() ? 'Archived' : 'Active' }}
                </span>
            </div>

            <div class="mt-6 grid gap-8">
                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Basic Information</h3>
                    <dl class="mt-4 grid gap-6 md:grid-cols-4">
                        <div><dt class="text-sm font-semibold text-slate-500">Date of Birth</dt><dd class="mt-1 text-base text-slate-900">{{ $patient->birthdate?->format('M d, Y') }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Age</dt><dd class="mt-1 text-base text-slate-900">{{ $patient->age !== null ? $patient->age.' years old' : 'Not recorded' }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Sex</dt><dd class="mt-1 text-base text-slate-900">{{ $patient->sex }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Blood Type</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->blood_type) }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Contact & Address</h3>
                    <dl class="mt-4 grid gap-6 md:grid-cols-2">
                        <div><dt class="text-sm font-semibold text-slate-500">Contact Number</dt><dd class="mt-1 text-base text-slate-900">{{ $patient->contact_number }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">PhilHealth ID</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->philhealth_id) }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-sm font-semibold text-slate-500">Address</dt><dd class="mt-1 text-base text-slate-900">{{ $patient->address }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Guardian Information</h3>
                    <dl class="mt-4 grid gap-6 md:grid-cols-2">
                        <div><dt class="text-sm font-semibold text-slate-500">Guardian Name</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->guardian_name) }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Guardian Contact</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->guardian_contact) }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Clinical Information</h3>
                    <dl class="mt-4 grid gap-6 md:grid-cols-3">
                        <div><dt class="text-sm font-semibold text-slate-500">Known Allergies</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->known_allergies) }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Existing Conditions</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->existing_conditions) }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Current Medications</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->current_medications) }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Emergency Contact</h3>
                    <dl class="mt-4 grid gap-6 md:grid-cols-2">
                        <div><dt class="text-sm font-semibold text-slate-500">Name</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->emergency_contact_name) }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Number</dt><dd class="mt-1 text-base text-slate-900">{{ $value($patient->emergency_contact_number) }}</dd></div>
                    </dl>
                </section>
            </div>
        </div>

        @if ($canArchive)
            <div class="flex justify-end">
                @if ($patient->trashed())
                    <form method="POST" action="{{ route('patients.restore', $patient->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                            Restore Patient
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('patients.archive', $patient) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                            Archive Patient
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
@endsection
