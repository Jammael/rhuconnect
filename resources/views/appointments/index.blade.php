@php
    $statusClasses = [
        'Pending' => 'bg-amber-100 text-amber-700',
        'Approved' => 'bg-green-100 text-green-700',
        'Completed' => 'bg-blue-100 text-blue-700',
        'Cancelled' => 'bg-slate-100 text-slate-600',
        'No Show' => 'bg-red-100 text-red-700',
    ];
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Online Appointments',
    'pageSubtitle' => 'Manage RHUConnect appointment bookings.',
    'context' => 'Search, review, and manage scheduled appointments across all doctors.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Online Appointments'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            @if ($canManage)
                <a href="{{ route('appointments.create') }}" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                    Book Appointment
                </a>
            @endif
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

        <form method="GET" action="{{ route('appointments.index') }}" class="card-hover grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 md:grid-cols-[1fr_10rem_10rem_10rem_auto]">
            <input name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search patient name or contact number"
                class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">

            <select name="doctor_id" class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                <option value="">All doctors</option>
                @foreach ($doctors as $doctor)
                    <option value="{{ $doctor->id }}" @selected(($filters['doctor_id'] ?? '') == $doctor->id)>{{ $doctor->name }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>

            <input name="date" type="date" value="{{ $filters['date'] ?? '' }}"
                class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">

            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">Filter</button>
        </form>

        <div class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Patient</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Doctor</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Date &amp; Time</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($appointments as $appointment)
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-bold text-slate-900">{{ $appointment->patient->full_name }}</p>
                                    <p class="text-xs font-medium text-slate-500">{{ $appointment->patient->contact_number }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-700">{{ $appointment->doctor->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">
                                    {{ $appointment->appointment_date->format('M d, Y') }} &middot; {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('h:i A') }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClasses[$appointment->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $appointment->status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                    <a href="{{ route('appointments.show', $appointment) }}" class="rounded-lg px-2.5 py-1.5 text-green-700 transition hover:bg-green-50 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state message="No appointments found." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $appointments->links() }}
            </div>
        </div>
    </div>
@endsection
