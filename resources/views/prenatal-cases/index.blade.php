@php
    $roleLabel = auth()->user()->role?->name ?? 'Staff';
    $statusClasses = [
        'Active' => 'bg-green-100 text-green-700',
        'Completed' => 'bg-blue-100 text-blue-700',
        'Miscarried' => 'bg-slate-100 text-slate-600',
        'Transferred' => 'bg-amber-100 text-amber-700',
    ];
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Prenatal Cases',
    'pageSubtitle' => 'Track maternal care patients across their pregnancy.',
    'context' => 'Search, review, and maintain ongoing prenatal cases.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Prenatal Cases'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row">
                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search by patient name or contact number"
                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700 sm:max-w-sm"
                >
                <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected(($filters['status'] ?? '') === $statusOption)>{{ $statusOption }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                    Filter
                </button>
            </form>

            <a href="{{ route('prenatal-cases.create') }}" class="inline-flex shrink-0 justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                New Case
            </a>
        </div>

        <section class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Patient</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Midwife</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">EDD</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Trimester</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($cases as $case)
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-bold text-slate-900">{{ $case->patient->full_name }}</p>
                                    @if ($case->is_high_risk)
                                        <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-red-700">High Risk</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600">{{ $case->midwife->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $case->estimated_due_date->format('M d, Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $case->trimester }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClasses[$case->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $case->status }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                    <a href="{{ route('prenatal-cases.show', $case) }}" class="text-green-700 hover:text-green-900">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-empty-state message="No prenatal cases recorded yet." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $cases->links() }}
    </div>
@endsection
