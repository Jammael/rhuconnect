@php
    $value = fn ($text) => filled($text) ? $text : 'Not recorded';
    $statusClasses = [
        'Active' => 'bg-green-100 text-green-700',
        'Completed' => 'bg-blue-100 text-blue-700',
        'Miscarried' => 'bg-slate-100 text-slate-600',
        'Transferred' => 'bg-amber-100 text-amber-700',
    ];
    $appointmentStatusClasses = [
        'Pending' => 'bg-amber-100 text-amber-700',
        'Approved' => 'bg-green-100 text-green-700',
        'Completed' => 'bg-blue-100 text-blue-700',
        'Cancelled' => 'bg-slate-100 text-slate-600',
        'No Show' => 'bg-red-100 text-red-700',
    ];
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Prenatal Case Details',
    'pageSubtitle' => 'Track maternal care patients across their pregnancy.',
    'context' => 'Review pregnancy timeline, risk details, and linked appointments.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Prenatal Cases', 'link' => route('prenatal-cases.index')],
        ['label' => $case->patient->full_name],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('prenatal-cases.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Back to Cases
            </a>
            <a href="{{ route('prenatal-cases.edit', $case) }}" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                Edit Case
            </a>
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
                    <p class="text-sm font-semibold uppercase tracking-wide text-green-700">Prenatal Case</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900">{{ $case->patient->full_name }}</h2>
                    <p class="mt-1 text-sm font-medium text-slate-500">Managed by {{ $case->midwife->name }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($case->is_high_risk)
                        <span class="inline-flex w-fit rounded-full bg-red-100 px-3 py-1 text-xs font-extrabold text-red-700">High Risk</span>
                    @endif
                    <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClasses[$case->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $case->status }}
                    </span>
                </div>
            </div>

            <div class="mt-6 grid gap-8">
                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Pregnancy Timeline</h3>
                    <dl class="mt-4 grid gap-6 md:grid-cols-4">
                        <div><dt class="text-sm font-semibold text-slate-500">Last Menstrual Period</dt><dd class="mt-1 text-base text-slate-900">{{ $case->last_menstrual_period->format('M d, Y') }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Estimated Due Date</dt><dd class="mt-1 text-base text-slate-900">{{ $case->estimated_due_date->format('M d, Y') }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Trimester</dt><dd class="mt-1 text-base text-slate-900">{{ $case->trimester }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Gravida / Para</dt><dd class="mt-1 text-base text-slate-900">G{{ $case->gravida }}P{{ $case->para }}</dd></div>
                    </dl>
                </section>

                @if ($case->is_high_risk)
                    <section>
                        <h3 class="text-lg font-extrabold text-slate-900">Risk Notes</h3>
                        <p class="mt-2 text-base text-slate-900">{{ $value($case->risk_notes) }}</p>
                    </section>
                @endif

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Notes</h3>
                    <p class="mt-2 text-base text-slate-900">{{ $value($case->notes) }}</p>
                </section>
            </div>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Linked Appointments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Doctor</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Purpose</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($case->appointments as $appointment)
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-700">
                                    <a href="{{ route('appointments.show', $appointment) }}" class="hover:text-green-700">
                                        {{ $appointment->appointment_date->format('M d, Y') }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $appointment->doctor->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $appointment->purpose_of_visit }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $appointmentStatusClasses[$appointment->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $appointment->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-empty-state message="No appointments linked to this case yet." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
