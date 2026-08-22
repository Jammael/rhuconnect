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
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Edit Patient Record',
    'pageSubtitle' => 'Manage RHUConnect patient information.',
    'context' => 'Keep demographic, contact, guardian, and clinical intake details current.',
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
    <div class="mx-auto max-w-5xl">
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                Please review the highlighted patient fields.
            </div>
        @endif

        <form method="POST" action="{{ route('patients.update', $patient) }}" class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @method('PUT')
            @include('patients._form', ['submitLabel' => 'Save Patient Record'])
        </form>
    </div>
@endsection
