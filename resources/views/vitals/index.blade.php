@extends('layouts.dashboard', [
    'pageTitle' => 'Vitals/Triage',
    'pageSubtitle' => "Today's recorded intake vitals.",
    'context' => 'A daily scan of vitals recorded for today\'s appointments. Open an appointment to review or add its full detail.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Vitals/Triage'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        <form method="GET" action="{{ route('vitals.index') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 md:grid-cols-[1fr_auto]">
            <input name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search patient name or contact number"
                class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">

            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">Filter</button>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Patient</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Doctor</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Vitals</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Recorded By</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Time</th>
                            <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($vitals as $entry)
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-bold text-slate-900">{{ $entry->appointment->patient->full_name }}</p>
                                    <p class="text-xs font-medium text-slate-500">{{ $entry->appointment->patient->contact_number }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-700">{{ $entry->appointment->doctor->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                    {{ $entry->blood_pressure_systolic }}/{{ $entry->blood_pressure_diastolic }} mmHg &middot; {{ $entry->temperature_celsius }}&deg;C &middot; {{ $entry->oxygen_saturation }}% O2
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $entry->recordedBy->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $entry->created_at->format('h:i A') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                    <a href="{{ route('appointments.show', $entry->appointment) }}" class="rounded-lg px-2.5 py-1.5 text-green-700 transition hover:bg-green-50 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-empty-state message="No vitals recorded today yet." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $vitals->links() }}
            </div>
        </div>
    </div>
@endsection
