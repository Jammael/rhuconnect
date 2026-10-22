@extends('layouts.dashboard', [
    'pageTitle' => 'Edit Appointment',
    'pageSubtitle' => 'Manage RHUConnect appointment bookings.',
    'context' => 'Update the schedule or visit details for this appointment.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Online Appointments', 'link' => route('appointments.index')],
        ['label' => 'Edit Appointment'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="mx-auto max-w-5xl">
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                Please review the highlighted appointment fields.
            </div>
        @endif

        <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @method('PUT')
            @include('appointments._form', ['submitLabel' => 'Save Appointment'])
        </form>
    </div>
@endsection
