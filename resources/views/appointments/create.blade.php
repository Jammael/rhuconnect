@extends('layouts.dashboard', [
    'pageTitle' => 'Book Appointment',
    'pageSubtitle' => 'Manage RHUConnect appointment bookings.',
    'context' => 'Schedule a new appointment within the doctor\'s available time slots.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Online Appointments', 'link' => route('appointments.index')],
        ['label' => 'Book Appointment'],
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

        <form method="POST" action="{{ route('appointments.store') }}" class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @include('appointments._form', ['submitLabel' => 'Book Appointment'])
        </form>
    </div>
@endsection
