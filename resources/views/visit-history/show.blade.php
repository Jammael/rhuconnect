@php
    $value = fn ($text) => filled($text) ? $text : 'Not recorded';
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Visit Record',
    'pageSubtitle' => 'Documented diagnosis and consultation details.',
    'context' => 'Review the diagnosis, prescription, and notes recorded for this completed appointment.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Patient Visit History', 'link' => route('visit-history.index')],
        ['label' => 'Visit Record'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('visit-history.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Back to Visit History
            </a>
            <a href="{{ route('appointments.show', $visitHistory->appointment) }}" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                View Appointment
            </a>
        </div>

        <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-100 pb-6">
                <p class="text-sm font-semibold uppercase tracking-wide text-green-700">Visit Record</p>
                <h2 class="mt-2 text-2xl font-extrabold text-slate-900">{{ $visitHistory->appointment->patient->full_name }}</h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Consultation on {{ $visitHistory->consultation_date->format('M d, Y') }} with {{ $visitHistory->doctor->name }}</p>
            </div>

            <div class="mt-6 grid gap-8">
                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Diagnosis</h3>
                    <p class="mt-2 text-base text-slate-900">{{ $visitHistory->diagnosis }}</p>
                </section>

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Prescription</h3>
                    <p class="mt-2 text-base text-slate-900">{{ $value($visitHistory->prescription) }}</p>
                </section>

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Notes</h3>
                    <p class="mt-2 text-base text-slate-900">{{ $value($visitHistory->notes) }}</p>
                </section>
            </div>
        </div>
    </div>
@endsection
