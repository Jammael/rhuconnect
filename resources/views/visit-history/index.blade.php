@extends('layouts.dashboard', [
    'pageTitle' => 'Patient Visit History',
    'pageSubtitle' => 'Review documented diagnoses and consultations.',
    'context' => 'Completed appointments are documented here as they are closed out by the treating doctor.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Patient Visit History'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        <form method="GET" action="{{ route('visit-history.index') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 md:grid-cols-[1fr_auto]">
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
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Consultation Date</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Diagnosis</th>
                            <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($visitHistories as $visitHistory)
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-bold text-slate-900">{{ $visitHistory->appointment->patient->full_name }}</p>
                                    <p class="text-xs font-medium text-slate-500">{{ $visitHistory->appointment->patient->contact_number }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-700">{{ $visitHistory->doctor->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $visitHistory->consultation_date->format('M d, Y') }}</td>
                                <td class="max-w-xs truncate px-5 py-4 text-sm font-medium text-slate-500">{{ $visitHistory->diagnosis }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                    <a href="{{ route('visit-history.show', $visitHistory) }}" class="rounded-lg px-2.5 py-1.5 text-green-700 transition hover:bg-green-50 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state message="No visit records found." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $visitHistories->links() }}
            </div>
        </div>
    </div>
@endsection
